<?php
session_start();
header('Content-Type: application/json');

include "config.php";
include 'conexao.php';
include 'logs.php';
include "validar_sessao.php";

$dadosJSON = file_get_contents('php://input');
$dados = json_decode($dadosJSON, true);

if (!$dados || empty($_SESSION['carrinho']) || !isset($dados['formas_pagamento'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados da venda, carrinho ou pagamento inválidos.']);
    exit;
}

$id_cliente = isset($dados['id_cliente']) ? (intval($dados['id_cliente']) ?: null) : null;
$id_funcionario = $_SESSION['ID_Funcionario'];
$id_caixaAberto = $_SESSION['ID_CaixaAberto'];
$id_caixa = $_SESSION['ID_Caixa'];
$valor_total = (float)($dados['valor_total'] ?? 0);
$total_pago = (float)($dados['total_pago'] ?? 0);
$desconto = (float)($dados['desconto'] ?? 0);
$formas_pagamento = $dados['formas_pagamento'];

$conn->begin_transaction();

try {
    $stmtVenda = $conn->prepare("INSERT INTO VENDAS (ID_Funcionario, ID_CaixaAberto, ID_Cliente, DataHora_Venda, Valor_Total, Desconto) VALUES (?, ?, ?, NOW(), ?, ?)");
    $stmtVenda->bind_param("iiidd", $id_funcionario, $id_caixaAberto, $id_cliente, $valor_total, $desconto);
    $stmtVenda->execute();
    $idVenda = $stmtVenda->insert_id;
    if ($idVenda == 0) throw new Exception("Falha ao criar a venda.");

    $stmtPag = $conn->prepare("INSERT INTO VENDA_PAGAMENTOS (ID_Venda, ID_Forma_Pag, Valor, Troco, Quant_Vezes) VALUES (?, ?, ?, ?, ?)");
    $trocoCalculado = max(0, $total_pago - $valor_total);
    $primeiroPagamento = true;
    
    foreach($formas_pagamento as $pagamento) {
        if (isset($item['tipo']) && $item['tipo'] === 'servico') {
            continue; 
        }
        
        $id_forma_pag = intval($pagamento['id_forma_pag']);
        $valor = (float)$pagamento['valor'];
        $trocoReal = ($primeiroPagamento && $trocoCalculado > 0) ? $trocoCalculado : 0.00;
        $valor_liquido = $valor - $trocoReal;
        $quant_vezes = intval($pagamento['quant_vezes']);
        $stmtPag->bind_param("iiddi", $idVenda, $id_forma_pag, $valor_liquido, $trocoReal, $quant_vezes);
        $stmtPag->execute();
        if ($trocoReal > 0) $primeiroPagamento = false;
    }

    $stmtItem = $conn->prepare("INSERT INTO ITENS_VENDA (ID_Venda, ID_Produto, Quantidade, Valor_Total, Desconto) VALUES (?, ?, ?, ?, ?)");

    // QUERY 1: Busca lote específico (Para CONTROLADOS)
    $stmtLoteEspecifico = $conn->prepare("SELECT ID_Estoque, Quantidade FROM ESTOQUE WHERE ID_Lote = ?");
    // QUERY 2: Busca FIFO - Mais antigo com saldo (Para COMUNS)
    $stmtLotesFIFO = $conn->prepare("SELECT E.ID_Estoque, E.Quantidade, L.Nome_Lote FROM ESTOQUE E JOIN LOTES L ON E.ID_Lote = L.ID_Lote WHERE L.ID_Produto = ? AND E.Quantidade > 0 ORDER BY L.Data_Validade ASC");
    
    $stmtUpdateEstoque = $conn->prepare("UPDATE ESTOQUE SET Quantidade = Quantidade - ? WHERE ID_Estoque = ?");
    $stmtMovEstoque = $conn->prepare("INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Motivo, Quantidade, ID_Venda, OBS) VALUES (?, ?, ?, 'Saída', 'Venda', ?, ?, ?)");
    
    foreach ($_SESSION['carrinho'] as $item) {
        $id_produto = $item['id_produto']; 
        $quantidade = $item['quantidade'];
        $desconto_item = $item['desconto'] ?? 0.00;
        $valor_total_item = $item['preco'] * $quantidade;
        $id_lote_especifico = $item['id_lote'] ?? null;

        $stmtItem->bind_param("iiidd", $idVenda, $id_produto, $quantidade, $valor_total_item, $desconto_item);
        $stmtItem->execute();

        $qtd_restante_para_baixar = $quantidade;

        // CENÁRIO A: Medicamento Controlado (Lote Específico Obrigatório)
        if ($id_lote_especifico) {
            $stmtLoteEspecifico->bind_param("i", $id_lote_especifico);
            $stmtLoteEspecifico->execute();
            $resultadoLote = $stmtLoteEspecifico->get_result()->fetch_assoc();

            if (!$resultadoLote || $resultadoLote['Quantidade'] < $qtd_restante_para_baixar) 
                throw new Exception("Estoque insuficiente no Lote selecionado para o produto: " . $item['nome']);

            $stmtUpdateEstoque->bind_param("ii", $qtd_restante_para_baixar, $resultadoLote['ID_Estoque']);
            $stmtUpdateEstoque->execute();

            $obs_mov = 'Venda Controlado PDV #' . $idVenda;
            $stmtMovEstoque->bind_param("iiiiis", $resultadoLote['ID_Estoque'], $id_produto, $id_funcionario, $qtd_restante_para_baixar, $idVenda, $obs_mov);
            $stmtMovEstoque->execute();
        
        } 
        // CENÁRIO B: Produto Comum 
        else {
            $stmtLotesFIFO->bind_param("i", $id_produto);
            $stmtLotesFIFO->execute();
            $lotes_disponiveis = $stmtLotesFIFO->get_result()->fetch_all(MYSQLI_ASSOC);

            if (empty($lotes_disponiveis)) 
                throw new Exception("Estoque insuficiente para o produto: " . $item['nome']);

            foreach ($lotes_disponiveis as $lote) {
                if ($qtd_restante_para_baixar <= 0) break;

                $qtd_no_lote = $lote['Quantidade'];
                $qtd_a_retirar_deste_lote = 0;

                if ($qtd_no_lote >= $qtd_restante_para_baixar) 
                    $qtd_a_retirar_deste_lote = $qtd_restante_para_baixar;
                else 
                    $qtd_a_retirar_deste_lote = $qtd_no_lote;

                $stmtUpdateEstoque->bind_param("ii", $qtd_a_retirar_deste_lote, $lote['ID_Estoque']);
                $stmtUpdateEstoque->execute();

                $obs_mov = 'Venda PDV #' . $idVenda . ' (Lote: ' . $lote['Nome_Lote'] . ')';
                $stmtMovEstoque->bind_param("iiiiis", $lote['ID_Estoque'], $id_produto, $id_funcionario, $qtd_a_retirar_deste_lote, $idVenda, $obs_mov);
                $stmtMovEstoque->execute();

                $qtd_restante_para_baixar -= $qtd_a_retirar_deste_lote;
            }

            if ($qtd_restante_para_baixar > 0) 
                 throw new Exception("Estoque total insuficiente para o produto: " . $item['nome']);
        }
    }

    if (isset($_SESSION['codigo_prevenda_ativa']) && !empty($_SESSION['codigo_prevenda_ativa'])) {
        $codigo_prevenda = $_SESSION['codigo_prevenda_ativa'];
        $stmtUpdatePreVenda = $conn->prepare("UPDATE PRE_VENDAS SET Status = 'Finalizada', ID_Venda = ?, Data_Finalizacao = NOW() WHERE Codigo_PreVenda = ?");
        $stmtUpdatePreVenda->bind_param("is", $idVenda, $codigo_prevenda);
        $stmtUpdatePreVenda->execute();
    }

    $conn->commit();

    // Salva os dados da venda na tela do cliente para a avaliação
    $modo_avaliacao = 'Avaliacao';
    $status_avaliacao = json_encode(['id_venda' => $idVenda, 'id_funcionario' => $id_funcionario]);
    $stmt_tela = $conn->prepare("UPDATE CAIXAS SET Tela_Cliente_Modo = ?, Tela_Cliente_Status = ? WHERE ID_Caixa = ?");
    $stmt_tela->bind_param("ssi", $modo_avaliacao, $status_avaliacao, $id_caixa);
    $stmt_tela->execute();
    
    unset($_SESSION['carrinho']);
    unset($_SESSION['codigo_prevenda_ativa']);

    registrar_log($conn, $_SESSION['ID_Usuario'], "Finalizou a venda #{$idVenda}");
    echo json_encode(['sucesso' => true, 'id_venda' => $idVenda]);

} 
catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    error_log("Erro ao finalizar venda: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao processar venda', 'detalhe' => $e->getMessage()]);
}

exit;
?>