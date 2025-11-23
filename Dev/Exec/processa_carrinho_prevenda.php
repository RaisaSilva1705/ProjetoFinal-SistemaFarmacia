<?php
session_start();
header('Content-Type: application/json');

include "config.php";
include "conexao.php";
include "validar_sessao.php";

$codigo_prevenda = $_POST['codigo_prevenda'] ?? '';

if (isset($_SESSION['codigo_prevenda_ativa']) && $_SESSION['codigo_prevenda_ativa'] == $codigo_prevenda) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Esta pré-venda já foi carregada no carrinho.']);
    exit;
}

if (empty($codigo_prevenda)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Código não fornecido.']);
    exit;
}

$sql = "SELECT 
            PVI.ID_Produto, PVI.ID_Servico, PVI.Quantidade, PVI.Valor_Unitario, PVI.Desconto, PVI.ID_Lote,
            P.Nome AS Nome_Produto, P.EAN_GTIN,
            SF.Nome_Servico,
            C.ID_Cliente, C.Nome AS Nome_Cliente, CD.Numero AS Documento_Cliente
        FROM PRE_VENDAS PV
        JOIN PRE_VENDAS_ITENS PVI ON PV.ID_PreVenda = PVI.ID_PreVenda
        LEFT JOIN PRODUTOS P ON PVI.ID_Produto = P.ID_Produto
        LEFT JOIN SERVICOS_FARMACEUTICOS SF ON PVI.ID_Servico = SF.ID_Servico
        LEFT JOIN CLIENTES C ON PV.ID_Cliente = C.ID_Cliente
        LEFT JOIN CLIENTES_DOCUMENTOS CD ON C.ID_Cliente = CD.ID_Cliente
        WHERE PV.Codigo_PreVenda = ? AND PV.Status = 'Pendente'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo_prevenda);
$stmt->execute();
$result = $stmt->get_result();
$itens_da_prevenda = $result->fetch_all(MYSQLI_ASSOC);

if (count($itens_da_prevenda) === 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Pré-venda não encontrada ou já utilizada.']);
    exit;
}

unset($_SESSION['id_cliente_pdv']);
unset($_SESSION['nome_cliente_pdv']);
$_SESSION['carrinho'] = [];

foreach ($itens_da_prevenda as $item_prevenda) {
    $novo_item = [];
    $id_unico_item = null;
    $tipo_item = null;
    $desconto_item = (float)($item_prevenda['Desconto'] ?? 0.00);

    if (!empty($item_prevenda['ID_Produto'])) {
        $id_unico_item = $item_prevenda['ID_Produto'];
        $tipo_item = 'produto';
        $novo_item = [
            'codigo' => $item_prevenda['EAN_GTIN'],
            'nome' => $item_prevenda['Nome_Produto'],
            'preco' => (float)$item_prevenda['Valor_Unitario'],
            'quantidade' => $item_prevenda['Quantidade'],
            'id_produto' => $id_unico_item, 
            'tipo' => $tipo_item,
            'origem' => 'prevenda',
            'id_lote' => $item_prevenda['ID_Lote'] ?? null,
            'quantidade_verificada' => 0,
            'desconto' => $desconto_item
        ];
    } 
    else if (!empty($item_prevenda['ID_Servico'])) {
        $id_unico_item = $item_prevenda['ID_Servico'];
        $tipo_item = 'servico';
        $novo_item = [
            'codigo' => 'SERV' . str_pad($id_unico_item, 6, '0', STR_PAD_LEFT),
            'nome' => $item_prevenda['Nome_Servico'],
            'preco' => (float)$item_prevenda['Valor_Unitario'],
            'quantidade' => $item_prevenda['Quantidade'],
            'id_servico' => $id_unico_item, 
            'tipo' => $tipo_item,
            'origem' => 'prevenda',
            'quantidade_verificada' => $item_prevenda['Quantidade'],
            'desconto' => $desconto_item
        ];
    }

    if (!empty($novo_item)) 
        $_SESSION['carrinho'][] = $novo_item;
}

$_SESSION['codigo_prevenda_ativa'] = $codigo_prevenda;

$dados_cliente = null;
if (!empty($itens_da_prevenda[0]['ID_Cliente'])) {
    $_SESSION['id_cliente_pdv'] = $itens_da_prevenda[0]['ID_Cliente'];
    $_SESSION['nome_cliente_pdv'] = $itens_da_prevenda[0]['Nome_Cliente'];
    $dados_cliente = [
        'id' => $itens_da_prevenda[0]['ID_Cliente'],
        'nome' => $itens_da_prevenda[0]['Nome_Cliente'],
        'documento' => $itens_da_prevenda[0]['Documento_Cliente']
    ];
}

echo json_encode(['sucesso' => true, 'mensagem' => 'Itens carregados com sucesso!', 'cliente' => $dados_cliente]);
exit;
?>