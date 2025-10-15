<?php
session_start();
include "config.php"; 
include "conexao.php"; 
include "validar_sessao.php";

header('Content-Type: application/json');

$id_fornecedor = filter_input(INPUT_GET, 'id_fornecedor', FILTER_VALIDATE_INT);

if (!$id_fornecedor) {
    echo json_encode(['sucesso' => false, 'erro' => 'ID do fornecedor inválido.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT
        P.ID_Produto, P.Nome AS Nome_Produto,
        L.ID_Lote, L.Nome_Lote, L.Data_Validade, L.Preco_Custo,
        E.Quantidade AS Quantidade_Estoque
    FROM PRODUTOS P
    JOIN LOTES L ON P.ID_Produto = L.ID_Produto
    JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote
    WHERE P.ID_Fornecedor = ? AND E.Quantidade > 0
    ORDER BY P.Nome, L.Data_Validade
");
$stmt->bind_param("i", $id_fornecedor);
$stmt->execute();
$result = $stmt->get_result();
$lotes = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode(['sucesso' => true, 'lotes' => $lotes]);

$stmt->close();
$conn->close();
?>