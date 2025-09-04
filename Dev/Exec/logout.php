<?php
    session_start();
    include 'config.php';
    include 'conexao.php';
    include 'logs.php';
    registrar_log($conn, $_SESSION['ID_Usuario'], "Deslogou do Sistema.");
    session_destroy();
    header('Location:' . SISTEMA_URL . 'index.php');
    exit;
?>