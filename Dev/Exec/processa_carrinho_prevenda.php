<?php
session_start();
header('Content-Type: application/json');

include "config.php";
include "conexao.php";
include "validar_sessao.php";

$codigo_prevenda = $_POST['codigo_prevenda'] ?? '';

if (empty($codigo_prevenda)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Código não fornecido.']);
    exit;
}

$sql = "SELECT 
            pvi.ID_Produto, pvi.ID_Servico, pvi.Quantidade, pvi.Valor_Unitario,
            p.Nome AS Nome_Produto, p.EAN_GTIN,
            sf.Nome_Servico
        FROM PRE_VENDAS pv
        JOIN PRE_VENDAS_ITENS pvi ON pv.ID_PreVenda = pvi.ID_PreVenda
        LEFT JOIN PRODUTOS p ON pvi.ID_Produto = p.ID_Produto
        LEFT JOIN SERVICOS_FARMACEUTICOS sf ON pvi.ID_Servico = sf.ID_Servico
        WHERE pv.Codigo_PreVenda = ? AND pv.Status = 'Pendente'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo_prevenda);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Pré-venda não encontrada ou já utilizada.']);
    exit;
}

if (!isset($_SESSION['carrinho'])) 
    $_SESSION['carrinho'] = [];

while ($item = $result->fetch_assoc()) {
    $novo_item_carrinho = [];
    if ($item['ID_Produto']) {
        $novo_item_carrinho = [
            'codigo' => $item['EAN_GTIN'],
            'nome' => $item['Nome_Produto'],
            'preco' => $item['Valor_Unitario'],
            'foto' => '', // Foto não é essencial para esta operação
            'quantidade' => $item['Quantidade'],
            'id_produto' => $item['ID_Produto'], // Guarda o ID para o processamento final
            'tipo' => 'produto'
        ];
    } 
    else if ($item['ID_Servico']) {
        $novo_item_carrinho = [
            'codigo' => 'SERV' . str_pad($item['ID_Servico'], 6, '0', STR_PAD_LEFT),
            'nome' => $item['Nome_Servico'],
            'preco' => $item['Valor_Unitario'],
            'foto' => '',
            'quantidade' => $item['Quantidade'],
            'id_servico' => $item['ID_Servico'], // Guarda o ID para o processamento final
            'tipo' => 'servico'
        ];
    }
    
    $_SESSION['carrinho'][] = $novo_item_carrinho;
}

$_SESSION['codigo_prevenda_ativa'] = $codigo_prevenda;

echo json_encode(['sucesso' => true, 'mensagem' => 'Itens carregados com sucesso!']);
exit;
?>