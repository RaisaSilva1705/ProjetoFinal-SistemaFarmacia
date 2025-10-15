<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = ['texto' => 'Acesso inválido.', 'tipo' => 'danger'];
    header('Location: ../PDV/pdv.php');
    exit;
}

$id_venda_original = filter_input(INPUT_POST, 'id_venda_original', FILTER_VALIDATE_INT);
$id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT) ?: null;
$tipo_resolucao = $_POST['tipo_resolucao'] ?? '';
$id_funcionario = $_SESSION['ID_Funcionario'];
$id_caixa = $_SESSION['ID_Caixa']; 
$itens_para_devolver = $_POST['itens'] ?? [];

$itens_selecionados = array_filter($itens_para_devolver, function($item) {
    return isset($item['devolver']) && $item['devolver'] == '1' && !empty($item['quantidade']);
});

if (!$id_venda_original || empty($itens_selecionados) || empty($tipo_resolucao)) {
    $_SESSION['msg'] = ['texto' => 'Dados da devolução incompletos. Verifique a venda, os itens e a forma de resolução.', 'tipo' => 'warning'];
    header('Location: devolucao_cliente.php');
    exit;
}

if ($tipo_resolucao === 'Credito_Loja' && is_null($id_cliente)) {
    $_SESSION['msg'] = ['texto' => 'Não é possível dar crédito em loja para um "Consumidor Final". O cliente precisa ser identificado na venda original.', 'tipo' => 'danger'];
    header('Location: devolucao_cliente.php');
    exit;
}

$conn->begin_transaction();

try {
    $valor_total_devolvido = 0;
    foreach ($itens_selecionados as $item) {
        $valor_total_devolvido += (float)$item['valor_unitario'] * (int)$item['quantidade'];
    }

    $stmt_devolucao = $conn->prepare("INSERT INTO DEVOLUCOES_CLIENTES (ID_Venda_Original, ID_Cliente, ID_Funcionario, Tipo_Resolucao, Valor_Total_Devolvido, Data_Devolucao) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt_devolucao->bind_param("iiisd", $id_venda_original, $id_cliente, $id_funcionario, $tipo_resolucao, $valor_total_devolvido);
    $stmt_devolucao->execute();
    $id_devolucao = $conn->insert_id;
    if ($id_devolucao == 0) throw new Exception("Falha ao criar o registro principal da devolução.");

    $stmt_item = $conn->prepare("INSERT INTO DEVOLUCOES_CLIENTES_ITENS (ID_Devolucao_Cliente, ID_Produto, Quantidade, Valor_Unitario_Devolvido, Motivo) VALUES (?, ?, ?, ?, ?)");
    $stmt_update_estoque = $conn->prepare("UPDATE ESTOQUE SET Quantidade = Quantidade + ? WHERE ID_Lote = ?");
    $stmt_mov_estoque = $conn->prepare("INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Motivo, Quantidade, OBS) VALUES (?, ?, ?, 'Entrada', 'Devolução de Cliente', ?, ?)");

    foreach ($itens_selecionados as $id_produto => $item) {
        $quantidade = (int)$item['quantidade'];
        $valor_unitario = (float)$item['valor_unitario'];
        $motivo = $item['motivo'];
        $obs_mov = "Ref. Devolução Cliente #{$id_devolucao}";

        $stmt_item->bind_param("iiids", $id_devolucao, $id_produto, $quantidade, $valor_unitario, $motivo);
        $stmt_item->execute();
        
        if (isset($item['retornar_estoque']) && $item['retornar_estoque'] == '1') {
            $stmt_lote = $conn->prepare("SELECT L.ID_Lote, E.ID_Estoque FROM LOTES L JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote WHERE L.ID_Produto = ? ORDER BY L.Data_Validade DESC LIMIT 1");
            $stmt_lote->bind_param("i", $id_produto);
            $stmt_lote->execute();
            $lote_para_retorno = $stmt_lote->get_result()->fetch_assoc();

            if ($lote_para_retorno) {
                $id_lote_retorno = $lote_para_retorno['ID_Lote'];
                $id_estoque_retorno = $lote_para_retorno['ID_Estoque'];
                
                $stmt_update_estoque->bind_param("ii", $quantidade, $id_lote_retorno);
                $stmt_update_estoque->execute();

                $stmt_mov_estoque->bind_param("iiiis", $id_estoque_retorno, $id_produto, $id_funcionario, $quantidade, $obs_mov);
                $stmt_mov_estoque->execute();
            }
        }
    }

    if ($tipo_resolucao === 'Reembolso') {
        $stmt_mov_caixa = $conn->prepare("INSERT INTO MOVIMENTACOES_CAIXA (ID_Caixa, ID_Funcionario, Tipo, Valor, Descricao) VALUES (?, ?, 'Saída', ?, ?)");
        $desc_mov_caixa = "Reembolso - Devolução #{$id_devolucao}";
        $stmt_mov_caixa->bind_param("iids", $id_caixa, $id_funcionario, $valor_total_devolvido, $desc_mov_caixa);
        $stmt_mov_caixa->execute();
    } 
    elseif ($tipo_resolucao === 'Credito_Loja') {
        $stmt_credito = $conn->prepare("UPDATE CLIENTES SET Saldo_Credito = Saldo_Credito + ? WHERE ID_Cliente = ?");
        $stmt_credito->bind_param("di", $valor_total_devolvido, $id_cliente);
        $stmt_credito->execute();
    }
    
    $conn->commit();
    
    registrar_log($conn, $_SESSION['ID_Usuario'], "Registrou devolução de cliente #{$id_devolucao} (Venda Original: #{$id_venda_original}), valor R$ {$valor_total_devolvido}.");
    $_SESSION['msg'] = ['texto' => "Devolução #{$id_devolucao} registrada com sucesso!", 'tipo' => 'success'];

} 
catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg'] = ['texto' => 'Erro ao processar a devolução: ' . $e->getMessage(), 'tipo' => 'danger'];
    header('Location: devolucao_cliente.php');
    exit;
}

header('Location: ../PDV/pdv.php'); 
exit;