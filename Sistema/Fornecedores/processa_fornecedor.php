<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 
include DEV_PATH . 'Exec/validar_sessao.php';
define('MODULO_SOLICITADO', 'FORNECEDORES_GERENCIAR'); 
include DEV_PATH . 'Exec/validar_acesso.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: fornecedores.php");
    exit;
}

$id_usuario_logado = $_SESSION['ID_Usuario']; 
$action = $_POST['action'] ?? '';

if ($action === 'change_status') {
    $id_fornecedor = filter_input(INPUT_POST, 'id_fornecedor', FILTER_VALIDATE_INT);
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';

    if ($novo_status === 'Inativo') {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM PRODUTOS WHERE ID_Fornecedor = ?");
        $stmtCheck->bind_param("i", $id_fornecedor);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->fetch_assoc()['total'] > 0) {
            $_SESSION['msg'] = ['texto' => 'Não é possível inativar este fornecedor, pois ele está associado a produtos cadastrados.', 'tipo' => 'warning'];
            header("Location: fornecedores.php");
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE FORNECEDORES SET Status = ? WHERE ID_Fornecedor = ?");
    $stmt->bind_param("si", $novo_status, $id_fornecedor);

    if ($stmt->execute()) {
        registrar_log($conn, $id_usuario_logado, "Alterou o status para '{$novo_status}' do fornecedor ID: {$id_fornecedor}");
        $_SESSION['msg'] = ['texto' => 'Status alterado com sucesso!', 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status.', 'tipo' => 'danger'];
    
    header("Location: fornecedores.php");
    exit;
}

$id_fornecedor = filter_input(INPUT_POST, 'id_fornecedor', FILTER_VALIDATE_INT);
$nome_fantasia = $_POST['nome_fantasia']; 
$razao_social = $_POST['razao_social']; 
$cnpj = $_POST['cnpj']; 
$tel = $_POST['tel']; 
$email = $_POST['email']; 
$cep = $_POST['cep']; 
$endereco = $_POST['endereco']; 
$numero = $_POST['numero']; 
$complemento = $_POST['complemento']; 
$bairro = $_POST['bairro']; 
$cidade = $_POST['cidade']; 
$estado = $_POST['estado']; 
$status = $_POST['status']; 
$obs = $_POST['obs'];

if ($id_fornecedor) { // MODO UPDATE
    $sql = "UPDATE FORNECEDORES SET Nome_Fantasia = ?, Nome = ?, CNPJ = ?, Tel = ?, Email = ?, CEP = ?, Endereco = ?, End_Numero = ?, Complemento = ?, Bairro = ?, Cidade = ?, Estado = ?, Status = ?, OBS = ? WHERE ID_Fornecedor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssi", $nome_fantasia, $razao_social, $cnpj, $tel, $email, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $status, $obs, $id_fornecedor);
    $acao_log = "Atualizou o fornecedor '{$nome_fantasia}'";
    $msg_sucesso = "Fornecedor atualizado com sucesso!";
} 
else { // MODO INSERT
    $sql = "INSERT INTO FORNECEDORES (Nome_Fantasia, Nome, CNPJ, Tel, Email, CEP, Endereco, End_Numero, Complemento, Bairro, Cidade, Estado, Status, OBS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssss", $nome_fantasia, $razao_social, $cnpj, $tel, $email, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $status, $obs);
    $acao_log = "Cadastrou o novo fornecedor '{$nome_fantasia}'";
    $msg_sucesso = "Fornecedor cadastrado com sucesso!";
}

if ($stmt->execute()) {
    if (!$id_fornecedor) $id_fornecedor = $conn->insert_id;
    registrar_log($conn, $id_usuario_logado, $acao_log . " (ID: {$id_fornecedor})");
    $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
} 
else 
    $_SESSION['msg'] = ['texto' => 'Erro ao salvar o fornecedor: ' . $stmt->error, 'tipo' => 'danger'];


header("Location: fornecedores.php");
exit;
?>