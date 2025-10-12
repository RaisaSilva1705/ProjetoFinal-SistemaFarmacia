<?php
session_start();
header('Content-Type: application/json');
include "config.php";
include "conexao.php";
include "validar_sessao.php";

$id_produto = filter_input(INPUT_GET, 'id_produto', FILTER_VALIDATE_INT);

if (!$id_produto) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID do produto não fornecido.']);
    exit;
}

$sql = "SELECT 
            L.ID_Lote, 
            L.Nome_Lote, 
            L.Data_Validade,
            E.Quantidade 
        FROM LOTES L
        JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote
        WHERE L.ID_Produto = ? AND E.Quantidade > 0
        ORDER BY L.Data_Validade ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_produto);
$stmt->execute();
$result = $stmt->get_result();
$lotes = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['sucesso' => true, 'lotes' => $lotes]);
exit;
?>