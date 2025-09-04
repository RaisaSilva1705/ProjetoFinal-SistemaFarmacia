<?php
session_start();
include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";

$action = $_POST['action'] ?? '';
$id_usuario_logado = $_SESSION['ID_Usuario']; 

if ($action === 'change_status') {
    $id_caixa = filter_input(INPUT_POST, 'id_caixa', FILTER_VALIDATE_INT);
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';

    // VERIFICAÇÃO DE SEGURANÇA: Não pode inativar um caixa que está aberto
    if ($novo_status === 'Inativo') {
        $stmtCheck = $conn->prepare("SELECT Status FROM CAIXAS WHERE ID_Caixa = ?");
        $stmtCheck->bind_param("i", $id_caixa);
        $stmtCheck->execute();
        $status_operacional = $stmtCheck->get_result()->fetch_assoc()['Status'];

        if ($status_operacional === 'Aberto') {
            $_SESSION['msg'] = ['texto' => 'Não é possível inativar um caixa que está atualmente em operação.', 'tipo' => 'warning'];
            header("Location: caixas.php");
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE CAIXAS SET StatusCadastro = ? WHERE ID_Caixa = ?");
    $stmt->bind_param("si", $novo_status, $id_caixa);
    
    if ($stmt->execute()) {
        registrar_log($conn, $id_usuario_logado, "Alterou o status de cadastro para '{$novo_status}' do caixa ID: {$id_caixa}");
        $_SESSION['msg'] = ['texto' => 'Status do caixa alterado com sucesso!', 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status do caixa.', 'tipo' => 'danger'];

} 
else { 
    $id_caixa = filter_input(INPUT_POST, 'id_caixa', FILTER_VALIDATE_INT);
    $caixa_nome = $_POST['caixa'] ?? '';

    if ($id_caixa) { 
        $sql = "UPDATE CAIXAS SET Caixa = ? WHERE ID_Caixa = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $caixa_nome, $id_caixa);
        $acao_log = "Atualizou o nome do caixa para '{$caixa_nome}' (ID: {$id_caixa})";
        $msg_sucesso = "Caixa atualizado com sucesso!";
    } 
    else { 
        $sql = "INSERT INTO CAIXAS (Caixa, Status, StatusCadastro) VALUES (?, 'Fechado', 'Ativo')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $caixa_nome);
        $acao_log = "Cadastrou o novo caixa '{$caixa_nome}'";
        $msg_sucesso = "Caixa cadastrado com sucesso!";
    }

    if ($stmt->execute()) {
        if (!$id_caixa) $id_caixa = $conn->insert_id;
        registrar_log($conn, $id_usuario_logado, $acao_log . " (ID: {$id_caixa})");
        $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao salvar o caixa.', 'tipo' => 'danger'];
}

header("Location: caixas.php");
exit;
?>