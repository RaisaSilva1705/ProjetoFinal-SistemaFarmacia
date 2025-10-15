<?php
session_start();

include "../../dev/Exec/config.php"; 
include DEV_PATH . 'Exec/conexao.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) 
    $_SESSION['msg'] = ['texto' => 'ID da categoria inválido.', 'tipo' => 'danger'];
else {
    // Verifica se a categoria está em uso antes de excluir
    $stmt_check = $conn->prepare("SELECT ID_Despesa FROM DESPESAS WHERE ID_Categoria_Despesa = ?");
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $_SESSION['msg'] = ['texto' => 'Ação bloqueada! Esta categoria está vinculada a uma ou mais despesas.', 'tipo' => 'warning'];
    } 
    else {
        $stmt_delete = $conn->prepare("DELETE FROM DESPESAS_CATEGORIAS WHERE ID_Categoria_Despesa = ?");
        $stmt_delete->bind_param("i", $id);
        if ($stmt_delete->execute()) 
            $_SESSION['msg'] = ['texto' => 'Categoria excluída com sucesso!', 'tipo' => 'success'];
        else 
            $_SESSION['msg'] = ['texto' => 'Erro ao excluir a categoria.', 'tipo' => 'danger'];
        
        $stmt_delete->close();
    }
    $stmt_check->close();
}

header('Location: categorias_despesa.php');
exit();
?>