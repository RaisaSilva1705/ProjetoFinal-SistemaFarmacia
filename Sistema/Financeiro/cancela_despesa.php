<?php
session_start();
include "../../dev/Exec/config.php"; 
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";

$id_despesa = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$id_usuario = $_SESSION['ID_Usuario'];

if (!$id_despesa) {
    $_SESSION['msg'] = ['texto' => 'ID da despesa inválido.', 'tipo' => 'danger'];
    header('Location: despesas.php');
    exit();
}

$stmt = $conn->prepare("UPDATE DESPESAS SET Status_Registro = 'Cancelado' WHERE ID_Despesa = ?");
$stmt->bind_param("i", $id_despesa);

if ($stmt->execute()) {
    registrar_log($conn, $id_usuario, "Cancelou o registro da despesa ID: {$id_despesa}");
    $_SESSION['msg'] = ['texto' => 'Despesa cancelada com sucesso! Ela não será mais exibida nos relatórios.', 'tipo' => 'success'];
} 
else 
    $_SESSION['msg'] = ['texto' => 'Erro ao cancelar a despesa.', 'tipo' => 'danger'];

$stmt->close();
header('Location: despesas.php');
exit();
?>