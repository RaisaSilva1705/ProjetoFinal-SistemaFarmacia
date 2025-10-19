<?php
session_start();

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: servicos.php");
    exit;
}

$id_usuario_logado = $_SESSION['ID_Usuario']; 
$action = $_POST['action'] ?? '';

if ($action === 'change_status') {
    $id_servico = filter_input(INPUT_POST, 'id_servico', FILTER_VALIDATE_INT);
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';

    if (!$id_servico) {
        $_SESSION['msg'] = ['texto' => 'ID do serviço inválido.', 'tipo' => 'danger'];
        header("Location: servicos.php");
        exit;
    }

    if ($novo_status === 'Inativo') {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM REGISTRO_SERVICOS WHERE ID_Servico = ?");
        $stmtCheck->bind_param("i", $id_servico);
        $stmtCheck->execute();
        $total_usos = $stmtCheck->get_result()->fetch_assoc()['total'];

        if ($total_usos > 0) {
            $_SESSION['msg'] = ['texto' => 'Não é possível inativar este serviço, pois ele já foi utilizado em ' . $total_usos . ' atendimento(s).', 'tipo' => 'warning'];
            header("Location: servicos.php");
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE SERVICOS_FARMACEUTICOS SET Status = ? WHERE ID_Servico = ?");
    $stmt->bind_param("si", $novo_status, $id_servico);

    if ($stmt->execute()) {
        registrar_log($conn, $id_usuario_logado, "Alterou o status para '{$novo_status}' do serviço ID: {$id_servico}");
        $_SESSION['msg'] = ['texto' => 'Status alterado com sucesso!', 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status.', 'tipo' => 'danger'];
}
else 
    $_SESSION['msg'] = ['texto' => 'Ação desconhecida.', 'tipo' => 'danger'];

header("Location: servicos.php");
exit;
?>