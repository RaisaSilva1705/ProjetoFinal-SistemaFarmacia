<?php
header('Content-Type: application/json');
include "config.php";
include "conexao.php";

$ean = $_GET['ean'] ?? '';

if (empty($ean)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'EAN não fornecido.']);
    exit;
}

$sql = "SELECT 
            P.ID_Produto, P.Nome, P.Descricao, P.Foto,
            L.Preco_Venda,
            PROMO.Descricao AS Promocao_Descricao,
            PROMO.Tipo AS Promocao_Tipo
        FROM PRODUTOS P
        JOIN LOTES L ON P.ID_Produto = L.ID_Produto
        LEFT JOIN PROMOCOES_ITENS PI ON P.ID_Produto = PI.ID_Produto
        LEFT JOIN PROMOCOES PROMO ON PI.ID_Promocao = PROMO.ID_Promocao 
            AND PROMO.Status = 'Ativo' 
            AND PROMO.Data_Inicio <= CURDATE() 
            AND (PROMO.Data_Fim IS NULL OR PROMO.Data_Fim >= CURDATE())
        WHERE P.EAN_GTIN = ?
        ORDER BY L.Data_Validade DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $ean);
$stmt->execute();
$result = $stmt->get_result();
$produto = $result->fetch_assoc();

if ($produto) {
    $produto['Foto'] = $produto['Foto'] ? DEV_URL . 'Imagens/imgProdutos/' . htmlspecialchars($produto['Foto']) : DEV_URL . 'Imagens/ImgSistema/sem-imagem.jpg';
    echo json_encode(['sucesso' => true, 'produto' => $produto]);
} 
else 
    echo json_encode(['sucesso' => false, 'mensagem' => 'Produto não encontrado.']);

exit;
?>