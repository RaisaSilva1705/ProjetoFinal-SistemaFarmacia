<?php
session_start();

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 
include DEV_PATH . "Exec/validar_sessao.php";

$acao = $_GET['acao'] ?? '';
$id_prevenda = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (empty($acao) || !$id_prevenda) {
    $_SESSION['msg'] = ['texto' => 'Ação ou ID inválido.', 'tipo' => 'danger'];
    header('Location: listagem_prevendas.php');
    exit;
}

if ($acao === 'cancelar') {
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("UPDATE PRE_VENDAS SET Status = 'Cancelada' WHERE ID_PreVenda = ? AND Status = 'Pendente'");
        $stmt->bind_param("i", $id_prevenda);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            registrar_log($conn, $_SESSION['ID_Usuario'], "Cancelou a pré-venda ID: {$id_prevenda}");
            $_SESSION['msg'] = ['texto' => 'Pré-venda cancelada com sucesso.', 'tipo' => 'success'];
            $conn->commit();
        } 
        else {
            $_SESSION['msg'] = ['texto' => 'Pré-venda não pôde ser cancelada (pode já estar finalizada ou não existir).', 'tipo' => 'warning'];
            $conn->rollback();
        }

    } 
    catch (Exception $e) {
        $conn->rollback();
        $_SESSION['msg'] = ['texto' => 'Erro ao cancelar a pré-venda: ' . $e->getMessage(), 'tipo' => 'danger'];
    }
}

header('Location: listagem_prevendas.php');
exit;

?>