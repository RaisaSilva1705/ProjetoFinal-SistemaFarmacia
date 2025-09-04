<?php
session_start();

include '../../../Dev/Exec/config.php';
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 
include DEV_PATH . 'Exec/validar_sessao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = ['texto' => 'Cargo não informada', 'tipo' => 'danger'];
    header("Location: cargos.php");
    exit;
}

$id_usuario_logado = $_SESSION['ID_Usuario']; 
$action = $_POST['action'] ?? '';

if ($action === 'change_status') {
    $id_cargo = filter_input(INPUT_POST, 'id_cargo', FILTER_VALIDATE_INT);
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';

    if ($novo_status === 'Inativo') {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM FUNCIONARIOS WHERE ID_Cargo = ? AND Status = 'Ativo'");
        $stmtCheck->bind_param("i", $id_cargo);
        $stmtCheck->execute();
        $total_usos = $stmtCheck->get_result()->fetch_assoc()['total'];

        if ($total_usos > 0) {
            $_SESSION['msg'] = ['texto' => 'Não é possível inativar este cargo, pois ele está sendo utilizado por ' . $total_usos . ' funcionário(s) ativo(s).', 'tipo' => 'warning'];
            header("Location: cargos.php");
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE CARGOS SET Status = ? WHERE ID_Cargo = ?");
    $stmt->bind_param("si", $novo_status, $id_cargo);

    if ($stmt->execute()) {
        registrar_log($conn, $id_usuario_logado, "Alterou o status para '{$novo_status}' do cargo ID: {$id_cargo}");
        $_SESSION['msg'] = ['texto' => 'Status alterado com sucesso!', 'tipo' => 'success'];
    } 
    else
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status.', 'tipo' => 'danger'];
}
else {
    $id_cargo = filter_input(INPUT_POST, 'id_cargo', FILTER_VALIDATE_INT);
    $cargo = $_POST['cargo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    
    if (empty($cargo)) {
        $_SESSION['msg'] = ['texto' => 'O nome do cargo é obrigatório.', 'tipo' => 'warning'];
        header("Location: cargos.php");
        exit;
    }
    
    if ($id_cargo) {
        $sql = "UPDATE CARGOS SET Cargo = ?, Descricao = ? WHERE ID_Cargo = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $cargo, $descricao, $id_cargo);
        $acao_log = "Atualizou o cargo '{$cargo}'";
        $msg_sucesso = "Cargo atualizado com sucesso!";
    } 
    else {
        $sql = "INSERT INTO CARGOS (Cargo, Descricao) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $cargo, $descricao);
        $acao_log = "Cadastrou o nova cargo '{$cargo}'";
        $msg_sucesso = "Cargo cadastrado com sucesso!";
    }
    
    if ($stmt->execute()) {
        if (!$id_cargo) {
            $id_cargo = $conn->insert_id;
            $acao_log .= " (ID: {$id_cargo})";
        }
    
        registrar_log($conn, $id_usuario_logado, $acao_log);
        $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao salvar o cargo: ' . $stmt->error, 'tipo' => 'danger'];
}

header("Location: cargos.php");
exit;

?>