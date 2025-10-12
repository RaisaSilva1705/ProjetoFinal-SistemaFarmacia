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
            PVI.ID_Produto, PVI.ID_Servico, PVI.Quantidade, PVI.Valor_Unitario, PVI.Desconto,
            P.Nome AS Nome_Produto, P.EAN_GTIN,
            SF.Nome_Servico,
            C.ID_Cliente, C.Nome AS Nome_Cliente, C.Documento AS Documento_Cliente
        FROM PRE_VENDAS PV
        JOIN PRE_VENDAS_ITENS PVI ON PV.ID_PreVenda = PVI.ID_PreVenda
        LEFT JOIN PRODUTOS P ON PVI.ID_Produto = P.ID_Produto
        LEFT JOIN SERVICOS_FARMACEUTICOS SF ON PVI.ID_Servico = SF.ID_Servico
        LEFT JOIN CLIENTES C ON PV.ID_Cliente = C.ID_Cliente 
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

if (!isset($_SESSION['carrinho'])) 
    $_SESSION['carrinho'] = [];

foreach ($itens_da_prevenda as $item_prevenda) {
    $novo_item = [];
    $id_unico_item = null;
    $tipo_item = null;
    $desconto_item = (float)($item_prevenda['Desconto'] ?? 0.00);
    $preco_final = (float)$item_prevenda['Valor_Unitario'] - $desconto_item;

    if (!empty($item_prevenda['ID_Produto'])) {
        $id_unico_item = $item_prevenda['ID_Produto'];
        $tipo_item = 'produto';
        $novo_item = [
            'codigo' => $item_prevenda['EAN_GTIN'],
            'nome' => $item_prevenda['Nome_Produto'],
            'preco' => $preco_final,
            'quantidade' => $item_prevenda['Quantidade'],
            'id_produto' => $id_unico_item, 
            'tipo' => $tipo_item,
            'origem' => 'prevenda',
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
            'preco' => $preco_final,
            'quantidade' => $item_prevenda['Quantidade'],
            'id_servico' => $id_unico_item, 
            'tipo' => $tipo_item,
            'origem' => 'prevenda',
            'quantidade_verificada' => $item_prevenda['Quantidade'],
            'desconto' => $desconto_item
        ];
    }

    if (!empty($novo_item)) {
        $item_encontrado_no_carrinho = false;
        foreach ($_SESSION['carrinho'] as $index => &$item_carrinho) {
            if ( (isset($item_carrinho['id_produto']) && $item_carrinho['id_produto'] == $id_unico_item && $tipo_item == 'produto') ||
                 (isset($item_carrinho['id_servico']) && $item_carrinho['id_servico'] == $id_unico_item && $tipo_item == 'servico') ) 
            {
                $item_carrinho['quantidade'] += $novo_item['quantidade'];
                $item_encontrado_no_carrinho = true;
                break;
            }
        }
        unset($item_carrinho); 

        if (!$item_encontrado_no_carrinho) 
            $_SESSION['carrinho'][] = $novo_item;
    }
}

$_SESSION['codigo_prevenda_ativa'] = $codigo_prevenda;

$dados_cliente = null;
if (!empty($itens_da_prevenda[0]['ID_Cliente'])) {
    $dados_cliente = [
        'id' => $itens_da_prevenda[0]['ID_Cliente'],
        'nome' => $itens_da_prevenda[0]['Nome_Cliente'],
        'documento' => $itens_da_prevenda[0]['Documento_Cliente']
    ];
}

echo json_encode(['sucesso' => true, 'mensagem' => 'Itens carregados com sucesso!']);
exit;
?>