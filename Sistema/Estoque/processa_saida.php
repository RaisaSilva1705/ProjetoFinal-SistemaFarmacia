<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: estoque.php');
    exit;
}

$id_produto = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);
$id_lote = filter_input(INPUT_POST, 'id_lote', FILTER_VALIDATE_INT);
$quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
$motivo = $_POST['motivo'] ?? '';
$id_funcionario = $_SESSION['ID_Funcionario'];

if (!$id_produto || !$id_lote || !$quantidade || empty($motivo)) {
    $_SESSION['msg'] = ['texto' => 'Todos os campos são obrigatórios.', 'tipo' => 'warning'];
    header('Location: saida_estoque.php');
    exit;
}

$conn->begin_transaction();

try {
    $stmtEstoque = $conn->prepare("SELECT ID_Estoque, Quantidade FROM ESTOQUE WHERE ID_Lote = ? FOR UPDATE");
    $stmtEstoque->bind_param("i", $id_lote);
    $stmtEstoque->execute();
    $estoque_atual = $stmtEstoque->get_result()->fetch_assoc();

    if (!$estoque_atual) 
        throw new Exception("Lote não encontrado no estoque.");

    if ($quantidade > $estoque_atual['Quantidade']) 
        throw new Exception("A quantidade a retirar ({$quantidade}) é maior que o estoque disponível ({$estoque_atual['Quantidade']}).");

    $id_estoque = $estoque_atual['ID_Estoque'];

    $stmtUpdate = $conn->prepare("UPDATE ESTOQUE SET Quantidade = Quantidade - ? WHERE ID_Estoque = ?");
    $stmtUpdate->bind_param("ii", $quantidade, $id_estoque);
    $stmtUpdate->execute();

    $stmtMov = $conn->prepare("INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Quantidade, OBS) VALUES (?, ?, ?, 'Saída', ?, ?)");
    $stmtMov->bind_param("iiiis", $id_estoque, $id_produto, $id_funcionario, $quantidade, $motivo);
    $stmtMov->execute();
    
    $conn->commit();
    
    $_SESSION['msg'] = ['texto' => "Baixa de {$quantidade} unidade(s) do estoque registrada com sucesso!", 'tipo' => 'success'];
    registrar_log($conn, $_SESSION['ID_Usuario'], "Registrou saída manual de {$quantidade} un. do produto ID {$id_produto} (Lote ID: {$id_lote}) pelo motivo: {$motivo}");

} 
catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg'] = ['texto' => 'Erro ao registrar a saída: ' . $e->getMessage(), 'tipo' => 'danger'];
}

header('Location: estoque.php');
exit;
?>