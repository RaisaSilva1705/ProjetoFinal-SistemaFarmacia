<?php
if (session_status() == PHP_SESSION_NONE || !defined('MODULO_SOLICITADO')) {
    header('Location: ' . SISTEMA_URL . 'dashboard.php');
    exit;
}

$id_cargo_usuario = $_SESSION['ID_Cargo'] ?? 0;

// ----- O CARGO DE 'ADMINISTRADOR' TEM ACESSO IRRESTRITO -----
if ($id_cargo_usuario == 1) 
    return; 

$chave_acesso_requerida = MODULO_SOLICITADO;

$stmt = $conn->prepare(
    "SELECT COUNT(*) 
     FROM CARGOS_MODULOS CM
     JOIN MODULOS M ON CM.ID_Modulo = M.ID_Modulo
     WHERE CM.ID_Cargo = ? AND M.Chave_Acesso = ?"
);
$stmt->bind_param("is", $id_cargo_usuario, $chave_acesso_requerida);
$stmt->execute();
$tem_permissao = $stmt->get_result()->fetch_row()[0] > 0;
$stmt->close();

if (!$tem_permissao) {
    $_SESSION['msg'] = ['texto' => 'Você não tem permissão para acessar esta página.', 'tipo' => 'danger'];
    header('Location: ' . SISTEMA_URL . 'dashboard.php');
    exit;
}
?>