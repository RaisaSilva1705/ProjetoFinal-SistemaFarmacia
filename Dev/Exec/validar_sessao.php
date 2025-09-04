<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

if($_SESSION['Nome'] != null){
    $restaSessao = $_SESSION['expire'] - strtotime('now');

    if ($restaSessao < 1) {
        registrar_log($conn, $_SESSION['ID_Usuario'], "foi deslogado do sistema devido expiração da sessão.");
        session_destroy();
        session_start();
        $_SESSION["msg"] = ['texto' => 'Sua sessão expirou. Faça login novamente', 'tipo' => 'warning'];
        header('Location:' . SISTEMA_URL . 'index.php');
        exit;
    }
}
else{
    header('Location:' . SISTEMA_URL . 'index.php');
    exit;
}
?>