<?php
session_start();
include "../../dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';

$id_promocao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$acao = $_GET['acao'] ?? '';

if (!$id_promocao || ($acao !== 'ativar' && $acao !== 'inativar')) {
    $_SESSION['msg'] = ['texto' => 'Ação ou ID de promoção inválido.', 'tipo' => 'danger'];
    header('Location: promocoes.php');
    exit;
}

$novo_status = ($acao === 'ativar') ? 'Ativo' : 'Inativo';

$stmt = $conn->prepare("UPDATE PROMOCOES SET Status = ? WHERE ID_Promocao = ?");
$stmt->bind_param("si", $novo_status, $id_promocao);

if ($stmt->execute()) {
    $acao_log = ($acao === 'ativar') ? 'Ativou' : 'Inativou';
    $msg_sucesso = ($acao === 'ativar') ? 'Promoção ativada com sucesso!' : 'Promoção inativada com sucesso!';
    
    registrar_log($conn, $_SESSION['ID_Usuario'], "{$acao_log} a promoção ID: {$id_promocao}");
    $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
} 
else 
    $_SESSION['msg'] = ['texto' => 'Ocorreu um erro ao alterar o status da promoção.', 'tipo' => 'danger'];

$stmt->close();

header('Location: promocoes.php');
exit;
?>