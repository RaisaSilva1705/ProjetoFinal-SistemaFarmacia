<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = ['texto' => 'Acesso inválido.', 'tipo' => 'danger'];
    header('Location: ../Estoque/estoque.php');
    exit;
}

$id_fornecedor = filter_input(INPUT_POST, 'id_fornecedor', FILTER_VALIDATE_INT);
$motivo_geral = trim($_POST['motivo_geral'] ?? '');
$id_funcionario = $_SESSION['ID_Funcionario'];
$itens_para_devolver = $_POST['itens'] ?? [];

$itens_selecionados = array_filter($itens_para_devolver, function($item) {
    return isset($item['devolver']) && $item['devolver'] == '1';
});

if (!$id_fornecedor || empty($itens_selecionados)) {
    $_SESSION['msg'] = ['texto' => 'Fornecedor não selecionado ou nenhum item marcado para devolução.', 'tipo' => 'warning'];
    header('Location: devolucao_fornecedor.php');
    exit;
}

$conn->begin_transaction();

try {
    $valor_total_devolucao = 0;
    foreach ($itens_selecionados as $item) {
        $valor_total_devolucao += (float)$item['preco_custo'] * (int)$item['quantidade'];
    }

    $stmt_devolucao = $conn->prepare("INSERT INTO DEVOLUCOES_FORNECEDORES (ID_Fornecedor, ID_Funcionario, Motivo_Geral, Valor_Total_Custo) VALUES (?, ?, ?, ?)");
    $stmt_devolucao->bind_param("iisd", $id_fornecedor, $id_funcionario, $motivo_geral, $valor_total_devolucao);
    $stmt_devolucao->execute();
    $id_devolucao = $conn->insert_id;
    if ($id_devolucao == 0) throw new Exception("Falha ao criar o registro principal da devolução.");

    $stmt_item = $conn->prepare("INSERT INTO DEVOLUCOES_FORNECEDORES_ITENS (ID_Devolucao_Fornecedor, ID_Produto, ID_Lote, Quantidade, Valor_Custo_Unitario) VALUES (?, ?, ?, ?, ?)");
    $stmt_update_estoque = $conn->prepare("UPDATE ESTOQUE SET Quantidade = Quantidade - ? WHERE ID_Lote = ? AND Quantidade >= ?");
    $stmt_mov_estoque = $conn->prepare("INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Motivo, Quantidade, OBS) VALUES (?, ?, ?, 'Saída', 'Devolução a Fornecedor', ?, ?)");

    foreach ($itens_selecionados as $id_lote => $item) {
        $id_produto = (int)$item['id_produto'];
        $quantidade = (int)$item['quantidade'];
        $preco_custo = (float)$item['preco_custo'];
        $obs_mov = "Ref. Devolução #{$id_devolucao}";

        $stmt_item->bind_param("iiiid", $id_devolucao, $id_produto, $id_lote, $quantidade, $preco_custo);
        $stmt_item->execute();

        $stmt_update_estoque->bind_param("iii", $quantidade, $id_lote, $quantidade);
        $stmt_update_estoque->execute();
        if ($stmt_update_estoque->affected_rows == 0) 
            throw new Exception("Estoque insuficiente ou lote inválido para o produto ID {$id_produto}. A devolução foi cancelada.");
        
        $stmt_get_estoque_id = $conn->prepare("SELECT ID_Estoque FROM ESTOQUE WHERE ID_Lote = ?");
        $stmt_get_estoque_id->bind_param("i", $id_lote);
        $stmt_get_estoque_id->execute();
        $id_estoque = $stmt_get_estoque_id->get_result()->fetch_assoc()['ID_Estoque'];
        
        $stmt_mov_estoque->bind_param("iiiis", $id_estoque, $id_produto, $id_funcionario, $quantidade, $obs_mov);
        $stmt_mov_estoque->execute();
    }
    
    $conn->commit();
    
    $total_itens = count($itens_selecionados);
    registrar_log($conn, $_SESSION['ID_Usuario'], "Registrou devolução para fornecedor #{$id_devolucao} com {$total_itens} item(ns), valor total de R$ {$valor_total_devolucao}.");
    $_SESSION['msg'] = ['texto' => "Devolução #{$id_devolucao} registrada com sucesso!", 'tipo' => 'success'];
    
} 
catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg'] = ['texto' => 'Erro ao processar a devolução: ' . $e->getMessage(), 'tipo' => 'danger'];
    header('Location: devolucao_fornecedor.php'); 
    exit;
}

header('Location: ../Estoque/estoque.php'); 
exit;