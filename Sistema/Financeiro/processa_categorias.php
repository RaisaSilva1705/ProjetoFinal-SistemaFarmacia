<?php
session_start();
include "../../dev/Exec/config.php"; 
include DEV_PATH . 'Exec/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categorias_despesa.php');
    exit();
}

$id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
$nome_categoria = trim($_POST['nome_categoria'] ?? '');
$id_usuario_logado = $_SESSION['ID_Usuario']; 

if (empty($nome_categoria)) {
    $_SESSION['msg'] = ['texto' => 'O nome da categoria não pode estar em branco.', 'tipo' => 'warning'];
    header('Location: categorias_despesa.php');
    exit();
}

if ($id_categoria) {
    $stmt = $conn->prepare("SELECT ID_Categoria_Despesa FROM DESPESAS_CATEGORIAS WHERE Nome_Categoria = ? AND ID_Categoria_Despesa != ?");
    $stmt->bind_param("si", $nome_categoria, $id_categoria);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) 
        $_SESSION['msg'] = ['texto' => 'Erro: Já existe outra categoria com este nome.', 'tipo' => 'danger'];
    else {
        $stmt_update = $conn->prepare("UPDATE DESPESAS_CATEGORIAS SET Nome_Categoria = ? WHERE ID_Categoria_Despesa = ?");
        $stmt_update->bind_param("si", $nome_categoria, $id_categoria);
        if ($stmt_update->execute()) {
            registrar_log($conn, $id_usuario_logado, "Atualizou a categoria de despesa ID: {$id_categoria} para '{$nome_categoria}'");
            $_SESSION['msg'] = ['texto' => 'Categoria atualizada com sucesso!', 'tipo' => 'success'];
        } 
        else 
            $_SESSION['msg'] = ['texto' => 'Erro ao atualizar a categoria.', 'tipo' => 'danger'];
        
        $stmt_update->close();
    }
    $stmt->close();
} 
else {
    $stmt = $conn->prepare("SELECT ID_Categoria_Despesa FROM DESPESAS_CATEGORIAS WHERE Nome_Categoria = ?");
    $stmt->bind_param("s", $nome_categoria);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) 
        $_SESSION['msg'] = ['texto' => 'Erro: Esta categoria já existe.', 'tipo' => 'danger']; 
    else {
        $stmt_insert = $conn->prepare("INSERT INTO DESPESAS_CATEGORIAS (Nome_Categoria) VALUES (?)");
        $stmt_insert->bind_param("s", $nome_categoria);
        if ($stmt_insert->execute()) {
            $novo_id = $conn->insert_id;
            registrar_log($conn, $id_usuario_logado, "Cadastrou a nova categoria de despesa '{$nome_categoria}' (ID: {$novo_id})");
            $_SESSION['msg'] = ['texto' => 'Categoria cadastrada com sucesso!', 'tipo' => 'success'];
        } 
        else 
            $_SESSION['msg'] = ['texto' => 'Erro ao cadastrar a categoria.', 'tipo' => 'danger'];

        $stmt_insert->close();
    }
    $stmt->close();
}

header('Location: categorias_despesa.php');
exit();
?>