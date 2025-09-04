<?php
session_start();

include '../../../Dev/Exec/config.php';
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 
include DEV_PATH . 'Exec/validar_sessao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = ['texto' => 'Unidade não informada', 'tipo' => 'danger'];
    header("Location: unidades.php");
    exit;
}

$id_usuario_logado = $_SESSION['ID_Usuario']; 
$action = $_POST['action'] ?? '';

if ($action === 'change_status') {
    $id_unidade = filter_input(INPUT_POST, 'id_unidade', FILTER_VALIDATE_INT);
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';

    if ($novo_status === 'Inativo') {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM PRODUTOS WHERE ID_Unidade = ?");
        $stmtCheck->bind_param("i", $id_unidade);
        $stmtCheck->execute();
        $total_usos = $stmtCheck->get_result()->fetch_assoc()['total'];

        if ($total_usos > 0) {
            $_SESSION['msg'] = ['texto' => 'Não é possível inativar: unidade já utilizada em produtos.', 'tipo' => 'warning'];
            header("Location: unidades.php");
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE UNIDADES SET Status = ? WHERE ID_Unidade = ?");
    $stmt->bind_param("si", $novo_status, $id_unidade);

    if ($stmt->execute()) {
        registrar_log($conn, $id_usuario_logado, "Alterou o status para '{$novo_status}' da unidade ID: {$id_unidade}");
        $_SESSION['msg'] = ['texto' => 'Status alterado com sucesso!', 'tipo' => 'success'];
    } 
    else
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status.', 'tipo' => 'danger'];
}
else {
    $id_unidade = filter_input(INPUT_POST, 'id_unidade', FILTER_VALIDATE_INT);
    $unidade = $_POST['unidade'] ?? '';
    $abreviacao = $_POST['abreviacao'] ?? '';
    
    if (empty($unidade)) {
        $_SESSION['msg'] = ['texto' => 'O nome da unidade é obrigatório.', 'tipo' => 'warning'];
        header("Location: unidades.php");
        exit;
    }
    
    if ($id_unidade) {
        $sql = "UPDATE UNIDADES SET Unidade = ?, Abreviacao = ? WHERE ID_Unidade = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $unidade, $abreviacao, $id_unidade);
        $acao_log = "Atualizou a unidade '{$unidade}'";
        $msg_sucesso = "Unidade atualizada com sucesso!";
    } 
    else {
        $sql = "INSERT INTO UNIDADES (Tipo, Abreviacao) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $unidade, $abreviacao);
        $acao_log = "Cadastrou a nova unidade '{$unidade}'";
        $msg_sucesso = "Unidade cadastrada com sucesso!";
    }
    
    if ($stmt->execute()) {
        if (!$id_unidade) {
            $id_unidade = $conn->insert_id;
            $acao_log .= " (ID: {$id_unidade})";
        }
    
        registrar_log($conn, $id_usuario_logado, $acao_log);
        $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao salvar a unidade: ' . $stmt->error, 'tipo' => 'danger'];
}

header("Location: unidades.php");
exit;

?>