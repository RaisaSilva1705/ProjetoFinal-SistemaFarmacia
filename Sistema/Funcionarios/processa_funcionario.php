<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 
include DEV_PATH . 'Exec/validar_sessao.php';
define('MODULO_SOLICITADO', 'FUNCIONARIOS_GERENCIAR'); 
include DEV_PATH . 'Exec/validar_acesso.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: funcionarios.php");
    exit;
}

$id_usuario_logado = $_SESSION['ID_Usuario']; 
$action = $_POST['action'] ?? '';

// --- NOVA LÓGICA PARA MUDANÇA DE STATUS ---
if ($action === 'change_status') {
    $id_funcionario = filter_input(INPUT_POST, 'id_funcionario', FILTER_VALIDATE_INT);
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';

    // Regra de negócio: Impede que o usuário se inactive
    if ($id_funcionario == $_SESSION['ID_Funcionario']) {
        $_SESSION['msg'] = ['texto' => 'Você não pode inativar o seu próprio usuário.', 'tipo' => 'danger'];
        header("Location: funcionarios.php");
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmtFunc = $conn->prepare("UPDATE FUNCIONARIOS SET Status = ? WHERE ID_Funcionario = ?");
        $stmtFunc->bind_param("si", $novo_status, $id_funcionario);
        $stmtFunc->execute();

        $stmtUser = $conn->prepare("UPDATE USUARIOS SET Status = ? WHERE ID_Funcionario = ?");
        $stmtUser->bind_param("si", $novo_status, $id_funcionario);
        $stmtUser->execute();
        
        $conn->commit();
        registrar_log($conn, $id_usuario_logado, "Alterou o status para '{$novo_status}' do funcionário ID: {$id_funcionario}");
        $_SESSION['msg'] = ['texto' => 'Status do funcionário alterado com sucesso!', 'tipo' => 'success'];

    } 
    catch (Exception $e) {
        $conn->rollback();
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status.', 'tipo' => 'danger'];
    }
    header("Location: funcionarios.php");
    exit;
}

// --- LÓGICA UNIFICADA PARA CRIAR E EDITAR ---
$id_funcionario = filter_input(INPUT_POST, 'id_funcionario', FILTER_VALIDATE_INT);

if ($id_funcionario) { // MODO UPDATE
    $nome = $_POST['nome'];
    $documento = $_POST['documento'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $id_cargo = $_POST['id_cargo'];
    $salario = $_POST['salario'];
    $data_admissao = $_POST['data_admissao'];
    $status = $_POST['status'];
    $obs = $_POST['obs'];

    $conn->begin_transaction();
    try {
        $sqlFunc = "UPDATE FUNCIONARIOS SET Nome = ?, Documento = ?, Telefone = ?, Email = ?, ID_Cargo = ?, Salario = ?, Data_Admissao = ?, Status = ?, OBS = ? WHERE ID_Funcionario = ?";
        $stmtFunc = $conn->prepare($sqlFunc);
        $stmtFunc->bind_param("ssssidsssi", $nome, $documento, $telefone, $email, $id_cargo, $salario, $data_admissao, $status, $obs, $id_funcionario);
        $stmtFunc->execute();

        if (!empty($_POST['senha'])) {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $sqlUser = "UPDATE USUARIOS SET Senha = ?, Status = ? WHERE ID_Funcionario = ?";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("ssi", $senha, $status, $id_funcionario);
        } 
        else {
            $sqlUser = "UPDATE USUARIOS SET Status = ? WHERE ID_Funcionario = ?";
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bind_param("si", $status, $id_funcionario);
        }
        $stmtUser->execute();

        $conn->commit();
        registrar_log($conn, $_SESSION['ID_Usuario'], "Editou o funcionário {$nome} (ID: {$id_funcionario})");
        $_SESSION['msg'] = ['texto' => 'Funcionário atualizado com sucesso!', 'tipo' => 'success'];
        header("Location: funcionarios.php");
        exit();
    } 
    catch (Exception $e) {
        $conn->rollback();
        $_SESSION['msg'] = ['texto' => 'Erro ao atualizar funcionário: ' . $e->getMessage(), 'tipo' => 'danger'];
    }
} 
else { // MODO INSERT
    $nome = $_POST['nome'];
    $documento = $_POST['documento'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $id_cargo = $_POST['id_cargo'];
    $salario = $_POST['salario'];
    $data_admissao = $_POST['data_admissao'];
    $status = $_POST['status'];
    $obs = $_POST['obs'];

    $usuario = $_POST['usuario'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $conn->begin_transaction();
    try {
        $sqlFunc = "INSERT INTO FUNCIONARIOS (Nome, Documento, Telefone, Email, ID_Cargo, Salario, Data_Admissao, Status, OBS)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtFunc = $conn->prepare($sqlFunc);
        $stmtFunc->bind_param("ssssidsss", $nome, $documento, $telefone, $email, $id_cargo, $salario, $data_admissao, $status, $obs);
        $stmtFunc->execute();
        $id_funcionario_novo = $conn->insert_id;
        if ($id_funcionario_novo == 0) throw new Exception("Falha ao criar o registro do funcionário.");

        $sqlUser = "INSERT INTO USUARIOS (ID_Funcionario, Usuario, Senha, Status) VALUES (?, ?, ?, ?)";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("isss", $id_funcionario_novo, $usuario, $senha, $status);
        $stmtUser->execute();

        $conn->commit();
        $novo_funcionario = $stmt->insert_id;
        registrar_log($conn, $_SESSION['ID_Usuario'], "Cadastrou o funcionário {$nome} (ID: {$novo_funcionario})");
        $_SESSION['msg'] = ['texto' => 'Funcionário e usuário criados com sucesso!', 'tipo' => 'success'];
        header("Location: funcionarios.php");
        exit();
    } 
    catch (Exception $e) {
        $conn->rollback();
        $_SESSION['msg'] = ['texto' => 'Erro ao cadastrar funcionário: ' . $e->getMessage(), 'tipo' => 'danger'];
    }
}

?>