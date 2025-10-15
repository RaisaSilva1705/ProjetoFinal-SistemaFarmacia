<?php
session_start();
include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 
include DEV_PATH . 'Exec/validar_sessao.php';
define('MODULO_SOLICITADO', 'CONFIGURACOES_GERENCIAR');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cargos.php");
    exit;
}

$id_cargo = filter_input(INPUT_POST, 'id_cargo', FILTER_VALIDATE_INT);
$permissoes = $_POST['permissoes'] ?? []; 
$id_usuario_logado = $_SESSION['ID_Usuario']; 

if (!$id_cargo) {
    $_SESSION['msg'] = ['texto' => 'Cargo inválido ou não especificado.', 'tipo' => 'danger'];
    header("Location: cargos.php");
    exit;
}

$conn->begin_transaction();

try {
    $stmt_delete = $conn->prepare("DELETE FROM CARGOS_MODULOS WHERE ID_Cargo = ?");
    $stmt_delete->bind_param("i", $id_cargo);
    $stmt_delete->execute();
    $stmt_delete->close();

    if (!empty($permissoes)) {
        $stmt_insert = $conn->prepare("INSERT INTO CARGOS_MODULOS (ID_Cargo, ID_Modulo) VALUES (?, ?)");
        
        foreach ($permissoes as $id_modulo) {
            $id_modulo_int = (int)$id_modulo; // Garante que é um inteiro
            $stmt_insert->bind_param("ii", $id_cargo, $id_modulo_int);
            $stmt_insert->execute();
        }
        $stmt_insert->close();
    }

    $conn->commit();
    
    $total_permissoes = count($permissoes);
    registrar_log($conn, $id_usuario_logado, "Atualizou permissões do cargo ID {$id_cargo}. Total de {$total_permissoes} permissões concedidas.");
    $_SESSION['msg'] = ['texto' => 'Permissões atualizadas com sucesso!', 'tipo' => 'success'];

} 
catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg'] = ['texto' => 'Ocorreu um erro ao salvar as permissões: ' . $e->getMessage(), 'tipo' => 'danger'];
    error_log("Erro em processa_permissoes.php: " . $e->getMessage());
}

header("Location: permissoes_cargo.php?cargo_id=" . $id_cargo);
exit;
?>