<?php
header('Content-Type: application/json');
include "config.php";
include "conexao.php";

$id_caixa = filter_input(INPUT_GET, 'id_caixa', FILTER_VALIDATE_INT);
if (!$id_caixa) {
    echo json_encode(['modo' => 'Inativo']);
    exit;
}

$stmt = $conn->prepare("SELECT Tela_Cliente_Modo, Tela_Cliente_Status FROM CAIXAS WHERE ID_Caixa = ?");
$stmt->bind_param("i", $id_caixa);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    $status = json_decode($result['Tela_Cliente_Status'], true) ?? [];
    echo json_encode(['modo' => $result['Tela_Cliente_Modo'], 'status' => $status]);
} 
else 
    echo json_encode(['modo' => 'Inativo']);
?>