<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 
include DEV_PATH . 'Exec/validar_sessao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = ['texto' => 'Acesso inválido.', 'tipo' => 'danger'];
    header("Location: clientes.php");
    exit;
}

$id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
$nome = trim($_POST['nome'] ?? '');
$tipo_pessoa = $_POST['tipo_pessoa'] ?? 'PF';
$data_nascimento = !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
$sexo = $_POST['sexo'] ?? '';
$genero = $_POST['genero'] ?? '';
$tel = $_POST['tel'] ?? '';
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$status = $_POST['status'] ?? 'Ativo';
$obs = trim($_POST['obs'] ?? '');
$senha = $_POST['senha'] ?? '';

$documentos = $_POST['documentos'] ?? [];
$delete_documentos = $_POST['delete_documentos'] ?? [];


$action = $_POST['action'] ?? '';

if ($action === 'change_status') {
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';

    if (!$id_cliente) {
        $_SESSION['msg'] = ['texto' => 'ID do cliente inválido.', 'tipo' => 'danger'];
        header("Location: clientes.php");
        exit;
    }

    if ($novo_status === 'Inativo') {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM VENDAS WHERE ID_Cliente = ?");
        $stmtCheck->bind_param("i", $id_cliente);
        $stmtCheck->execute();
        $total_usos = $stmtCheck->get_result()->fetch_assoc()['total'];

        if ($total_usos > 0) {
            $_SESSION['msg'] = ['texto' => 'Não é possível inativar este cliente, pois ele já foi registrado em ' . $total_usos . ' vendas(s).', 'tipo' => 'warning'];
            header("Location: clientes.php");
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE CLIENTES SET Status = ? WHERE ID_Cliente = ?");
    $stmt->bind_param("si", $novo_status, $id_cliente);

    if ($stmt->execute()) {
        registrar_log($conn, $_SESSION['ID_Usuario'], "Alterou o status para '{$novo_status}' do cliente ID: {$id_cliente}");
        $_SESSION['msg'] = ['texto' => 'Status alterado com sucesso!', 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status.', 'tipo' => 'danger'];

    header("Location: clientes.php");
    exit;
}

if (empty($nome) || empty($email) || empty($documentos)) {
    $_SESSION['msg'] = ['texto' => 'Nome, Email e pelo menos um Documento são obrigatórios.', 'tipo' => 'warning'];
    header("Location: " . ($id_cliente ? "editar_cliente.php?id=$id_cliente" : "cadastrar_cliente.php"));
    exit;
}

$conn->begin_transaction();

try {
    if ($id_cliente) {
        if (!empty($senha)) {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $stmt_cliente = $conn->prepare("UPDATE CLIENTES SET Nome = ?, Tipo = ?, Sexo = ?, Genero = ?, Data_Nascimento = ?, Tel = ?, Email = ?, Status = ?, OBS = ?, Senha = ? WHERE ID_Cliente = ?");
            $stmt_cliente->bind_param("ssssssssssi", $nome, $tipo_pessoa, $sexo, $genero, $data_nascimento, $tel, $email, $status, $obs, $senha_hash, $id_cliente);
        } 
        else {
            $stmt_cliente = $conn->prepare("UPDATE CLIENTES SET Nome = ?, Tipo = ?, Sexo = ?, Genero = ?, Data_Nascimento = ?, Tel = ?, Email = ?, Status = ?, OBS = ? WHERE ID_Cliente = ?");
            $stmt_cliente->bind_param("sssssssssi", $nome, $tipo_pessoa, $sexo, $genero, $data_nascimento, $tel, $email, $status, $obs, $id_cliente);
        }
        $acao_log = "Atualizou o cliente '{$nome}'";
        $msg_sucesso = "Cliente atualizado com sucesso!";
    } 
    else {
        if (empty($senha)) throw new Exception("A senha é obrigatória para novos clientes.");
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt_cliente = $conn->prepare("INSERT INTO CLIENTES (Nome, Tipo, Sexo, Genero, Data_Nascimento, Tel, Email, Senha, Status, OBS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_cliente->bind_param("ssssssssss", $nome, $tipo_pessoa, $sexo, $genero, $data_nascimento, $tel, $email, $senha_hash, $status, $obs);
        $acao_log = "Cadastrou o novo cliente '{$nome}'";
        $msg_sucesso = "Cliente cadastrado com sucesso!";
    }

    if (!$stmt_cliente->execute()) 
        throw new Exception("Falha ao salvar os dados principais do cliente: " . $stmt_cliente->error);
    
    
    if (!$id_cliente) 
        $id_cliente = $conn->insert_id;
    
    $stmt_cliente->close();

    if (!empty($delete_documentos)) {
        $stmt_del_doc = $conn->prepare("DELETE FROM CLIENTES_DOCUMENTOS WHERE ID_Documento = ? AND ID_Cliente = ?");
        foreach ($delete_documentos as $id_doc_del) {
            $stmt_del_doc->bind_param("ii", $id_doc_del, $id_cliente);
            $stmt_del_doc->execute();
        }
        $stmt_del_doc->close();
    }

    $stmt_update_doc = $conn->prepare("UPDATE CLIENTES_DOCUMENTOS SET Tipo = ?, Numero = ? WHERE ID_Documento = ? AND ID_Cliente = ?");
    $stmt_insert_doc = $conn->prepare("INSERT INTO CLIENTES_DOCUMENTOS (ID_Cliente, Tipo, Numero) VALUES (?, ?, ?)");
    foreach ($documentos as $doc) {
        $id_doc = $doc['id'] ? (int)$doc['id'] : null;
        $tipo_doc = $doc['tipo'];
        $numero_doc = $doc['numero'];

        if (empty($numero_doc)) continue; 

        if ($id_doc) { 
            $stmt_update_doc->bind_param("ssii", $tipo_doc, $numero_doc, $id_doc, $id_cliente);
            $stmt_update_doc->execute();
        } 
        else { 
            $stmt_insert_doc->bind_param("iss", $id_cliente, $tipo_doc, $numero_doc);
            $stmt_insert_doc->execute();
        }
    }
    $stmt_update_doc->close();
    $stmt_insert_doc->close();

    $conn->commit();
    
    registrar_log($conn, $_SESSION['ID_Usuario'], $acao_log . " (ID: {$id_cliente})");
    $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
    
} 
catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg'] = ['texto' => 'Ocorreu um erro ao salvar o cliente: ' . $e->getMessage(), 'tipo' => 'danger'];
    header("Location: " . ($id_cliente ? "editar_cliente.php?id=$id_cliente" : "cadastrar_cliente.php"));
    exit;
}

header("Location: clientes.php");
exit;
?>