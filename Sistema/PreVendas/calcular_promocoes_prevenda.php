<?php
session_start();
header('Content-Type: application/json');

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/busca_promocoes.php"; 

$itens_json = file_get_contents('php://input');
$itens_do_js = json_decode($itens_json, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($itens_do_js)) {
    echo json_encode([]);
    exit;
}

$carrinho_para_calcular = [];
foreach ($itens_do_js as $item) {
    $carrinho_para_calcular[] = [
        'id'         => $item['id'],
        'id_produto' => $item['id'], 
        'tipo'       => $item['tipo'],
        'preco'      => $item['valor'],
        'quantidade' => $item['qtd'],
        'desconto'   => $item['desconto'] ?? 0.00
    ];
}

$itens_com_promocao = calcularPromocoesParaCarrinho($carrinho_para_calcular, $conn);

echo json_encode($itens_com_promocao);
exit;
?>