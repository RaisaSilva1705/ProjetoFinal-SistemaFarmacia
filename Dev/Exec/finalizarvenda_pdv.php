<?php
session_start();
include "config.php";
include 'conexao.php';
include 'logs.php';

header('Content-Type: application/json');

// Recebe os dados em JSON bruto e Decodifica para array
$dadosJSON = file_get_contents('php://input');
$dados = json_decode($dadosJSON, true);

if (!$dados) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados inválidos ou malformados']);
    exit;
}

$valor_total = isset($dados['valor_total']) ? number_format($dados['valor_total'], 2, '.', '') : null;
$total_pago = isset($dados['total_pago']) ? number_format($dados['total_pago'], 2, '.', ',') : null;
$total_itens = isset($dados['total_itens']) ? intval($dados['total_itens']) : null;
$id_cliente = isset($dados['id_cliente']) ? intval($dados['id_cliente']) : null;
$id_funcionario = isset($dados['id_funcionario']) ? intval($dados['id_funcionario']) : null;
$desconto = isset($dados['desconto']) ? number_format($dados['desconto'], 2, '.', '') : 0.00;
$formas_pagamento = isset($dados['formas_pagamento']) ? $dados['formas_pagamento'] : [];

if (empty($formas_pagamento)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Nenhuma forma de pagamento informada']);
    exit;
}

if (empty($_SESSION['carrinho'])) {
    $_SESSION['msg'] = ['texto' => 'Carrinho vazio. Nenhuma venda registrada.', 'tipo' => 'warning'];
    header("Location: pdv.php");
    exit;
}

$conn->begin_transaction();

try {
    if (!isset($_SESSION['ID_CaixaAberto'])) {
        http_response_code(400);
        echo json_encode(['erro' => 'Caixa não está aberto.']);
        exit;
    }
    $id_caixaAberto = $_SESSION['ID_CaixaAberto'];

    // Prepara a inserção da da venda
    $stmt = $conn->prepare("INSERT INTO VENDAS 
                                (ID_Funcionario, ID_CaixaAberto, 
                                ID_Cliente, DataHora_Venda, 
                                Valor_Total, Desconto) 
                            VALUES (?, ?, ?, NOW(), ?, ?)");
    $stmt->bind_param("iiidd", $id_funcionario, $id_caixaAberto, $id_cliente, $valor_total, $desconto);
    $stmt->execute();

    $idVenda = $stmt->insert_id;
    $id_caixa = $_SESSION['ID_Caixa'];
    
    $stmtMov = $conn->prepare("INSERT INTO MOVIMENTACOES_CAIXA (ID_Caixa, ID_Funcionario, Tipo, Valor, Descricao)
                               VALUES (?, ?, 'Entrada', ?, ?)");
    // Descrição  da movimentação
    $descricaoMov = "Venda #$idVenda";
    $stmtMov->bind_param("iids", $id_caixa, $id_funcionario, $valor_total, $descricaoMov);
    $stmtMov->execute();

    $stmtItem = $conn->prepare("INSERT INTO ITENS_VENDA (ID_Venda, ID_Produto, Quantidade, Valor_Total) 
                                VALUES (?, ?, ?, ?)");

    $sqlLotes = "SELECT L.ID_Lote, E.ID_Estoque, E.Quantidade
                  FROM LOTES L
                  JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote
                  WHERE L.ID_Produto = ? AND E.Quantidade > 0
                  ORDER BY L.Data_Validade ASC";

    $stmtLotes = $conn->prepare($sqlLotes);
 
    $stmtUpdateEstoque = $conn->prepare("UPDATE ESTOQUE SET Quantidade = Quantidade - ? WHERE ID_Estoque = ?");

    $stmtPag = $conn->prepare("INSERT INTO VENDA_PAGAMENTOS (ID_Venda, ID_Forma_Pag, Valor, Troco, Quant_Vezes)
                               VALUES (?, ?, ?, ?, ?)");
    
    // realiza as inserções
    foreach($formas_pagamento as $pagamento) {
        $id_forma_pag = intval($pagamento['id_forma_pag']);
        $valor = number_format((float)$pagamento['valor'], 2, '.', '');
        $trocoCalculado = max(0, $total_pago - $valor_total);
        $trocoReal = ($id_forma_pag == 1 ? $trocoCalculado : 0.00); 
        $quant_vezes = intval($pagamento['quant_vezes']);
        
        $stmtPag->bind_param("iiddi", $idVenda, $id_forma_pag, $valor, $trocoReal, $quant_vezes);
        $stmtPag->execute();
    }

    foreach ($_SESSION['carrinho'] as $item) {
        $codigo = $item['codigo'];

        $stmtProduto = $conn->prepare("SELECT ID_Produto FROM PRODUTOS WHERE EAN_GTIN = ?");
        $stmtProduto->bind_param("s", $codigo);
        $stmtProduto->execute();
        $resultProduto = $stmtProduto->get_result();
        $id_produto = $resultProduto->fetch_assoc()['ID_Produto'];

        $quantidade = $item['quantidade'];
        $preco = $item['preco'];
        $valor_total_item = $preco * $quantidade;

        $stmtItem->bind_param("iiid", $idVenda, $id_produto, $quantidade, $valor_total_item);
        $stmtItem->execute();

        $stmtLotes->bind_param("i", $id_produto);
        $stmtLotes->execute();
        $lotes = $stmtLotes->get_result();

        $stmtMov = $conn->prepare("INSERT INTO MOVIMENTACAO_ESTOQUE 
                                      (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Quantidade, ID_Venda) 
                                   VALUES (?, ?, ?, 'SAIDA', ?, ?)");

        $qtdRestante = $quantidade;

        while ($qtdRestante > 0 && ($lote = $lotes->fetch_assoc())) {
            $id_estoque = $lote['ID_Estoque'];
            $qtd_disponivel = $lote['Quantidade'];

            if ($qtd_disponivel >= $qtdRestante) {
                $qtd_a_retirar = $qtdRestante;
                $qtdRestante = 0;
            } 
            else {
                $qtd_a_retirar = $qtd_disponivel;
                $qtdRestante -= $qtd_disponivel;
            }

            $stmtUpdateEstoque->bind_param("ii", $qtd_a_retirar, $id_estoque);
            $stmtUpdateEstoque->execute();

            $stmtMov->bind_param("iiiii", $id_estoque, $id_produto, $id_funcionario, $qtd_a_retirar, $idVenda);
            $stmtMov->execute();

        }

        if ($qtdRestante > 0) 
            throw new Exception("Estoque insuficiente por lote para o produto ID: {$id_produto}.");
    }

    $conn->commit();
    registrar_log($conn, $_SESSION['ID_Usuario'], "Realizou a venda {$idVenda} (ID Caixa Aberto: {$id_caixaAberto})");
    echo json_encode(['sucesso' => true, 'id_venda' => $idVenda]);
    unset($_SESSION['carrinho']);
} 
catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao processar venda', 'detalhe' => $e->getMessage()]);
}
?>
