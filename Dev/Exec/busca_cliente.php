<?php
session_start();
include "config.php"; 
include "conexao.php"; 

header('Content-Type: application/json');

$documento = $_GET['documento'] ?? '';

$documento_limpo = preg_replace('/[^0-9]/', '', $documento);

if (empty($documento_limpo)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Documento não informado.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT ID_Cliente, Nome 
    FROM CLIENTES 
    WHERE REPLACE(REPLACE(REPLACE(Documento, '.', ''), '/', ''), '-', '') = ? 
    AND Status = 'Ativo'
");
$stmt->bind_param("s", $documento_limpo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
    echo json_encode([
        'sucesso' => true,
        'id_cliente' => $cliente['ID_Cliente'],
        'nome_cliente' => $cliente['Nome']
    ]);
} 
else 
    echo json_encode(['sucesso' => false, 'erro' => 'Cliente não encontrado.']);

$stmt->close();
$conn->close();
?>