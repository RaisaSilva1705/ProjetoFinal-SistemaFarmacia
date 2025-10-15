<?php
session_start();
include "config.php"; 
include "conexao.php"; 
include "validar_sessao.php";

header('Content-Type: application/json');

$id_venda = filter_input(INPUT_GET, 'id_venda', FILTER_VALIDATE_INT);

if (!$id_venda) {
    echo json_encode(['sucesso' => false, 'erro' => 'Número da venda inválido ou não fornecido.']);
    exit;
}

$stmt_venda = $conn->prepare("
    SELECT 
        V.ID_Venda, V.DataHora_Venda, V.ID_Cliente,
        COALESCE(C.Nome, 'Consumidor Final') AS Nome_Cliente
    FROM VENDAS V
    LEFT JOIN CLIENTES C ON V.ID_Cliente = C.ID_Cliente
    WHERE V.ID_Venda = ?
");
$stmt_venda->bind_param("i", $id_venda);
$stmt_venda->execute();
$result_venda = $stmt_venda->get_result();

if ($result_venda->num_rows === 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Venda não encontrada.']);
    exit;
}
$dados_venda = $result_venda->fetch_assoc();

$stmt_itens = $conn->prepare("
    SELECT 
        IV.ID_Item_Venda, IV.ID_Produto, IV.Quantidade,
        (IV.Valor_Total / IV.Quantidade) AS Valor_Unitario_Venda,
        P.Nome AS Nome_Produto
    FROM ITENS_VENDA IV
    JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto
    WHERE IV.ID_Venda = ?
");
$stmt_itens->bind_param("i", $id_venda);
$stmt_itens->execute();
$itens_venda = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'sucesso' => true,
    'venda' => $dados_venda,
    'itens' => $itens_venda
]);

$stmt_venda->close();
$stmt_itens->close();
$conn->close();
?>