<?php
session_start();
include "config.php"; 
include "conexao.php"; 

header('Content-Type: application/json');

$id_cliente = filter_input(INPUT_GET, 'id_cliente', FILTER_VALIDATE_INT);

if (!$id_cliente) {
    echo json_encode([]); 
    exit;
}

$stmt = $conn->prepare("
    SELECT
        P.Nome,
        P.EAN_GTIN,
        COUNT(V.ID_Venda) AS Frequencia
    FROM VENDAS V
    JOIN ITENS_VENDA IV ON V.ID_Venda = IV.ID_Venda
    JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto
    WHERE V.ID_Cliente = ?
    GROUP BY P.ID_Produto, P.Nome, P.EAN_GTIN
    ORDER BY Frequencia DESC
    LIMIT 3
");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();
$sugestoes = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($sugestoes);

$stmt->close();
$conn->close();
?>