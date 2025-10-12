<?php
session_start();
header('Content-Type: application/json');
include "config.php";
include "conexao.php";
include "validar_sessao.php";

$nome = $_GET['nome'] ?? '';
if (strlen($nome) < 3) {
    echo json_encode([]);
    exit;
}
$like_nome = '%' . $nome . '%';

$sql = "SELECT P.ID_Produto, P.Nome, P.EAN_GTIN, M.MS, MAX(L.Preco_Venda) as Preco_Venda
        FROM PRODUTOS P
        JOIN MEDICAMENTOS M ON P.ID_Produto = M.ID_Produto
        LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto
        WHERE M.Controlado = 'Sim' AND P.Status = 'Ativo' AND (P.Nome LIKE ? OR P.EAN_GTIN LIKE ?)
        GROUP BY P.ID_Produto, P.Nome, P.EAN_GTIN, M.MS
        LIMIT 10";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $like_nome, $like_nome);
$stmt->execute();
$result = $stmt->get_result();
$produtos = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($produtos);
exit;
?>