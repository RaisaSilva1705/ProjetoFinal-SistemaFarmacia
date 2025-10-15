<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['arquivo_xml']) || $_FILES['arquivo_xml']['error'] != 0) {
    $_SESSION['msg'] = ['texto' => 'Nenhum arquivo XML válido foi enviado.', 'tipo' => 'danger'];
    header('Location: entrada_estoque.php');
    exit;
}

$caminho_arquivo_temporario = $_FILES['arquivo_xml']['tmp_name'];

// simplexml_load_file converte o XML em um objeto PHP
$xml = simplexml_load_file($caminho_arquivo_temporario);

if ($xml === false) {
    $_SESSION['msg'] = ['texto' => 'O arquivo enviado não é um XML válido ou está corrompido.', 'tipo' => 'danger'];
    header('Location: entrada_estoque.php');
    exit;
}

// NFes usam um 'namespace'. Sem registrar, não conseguimos acessar os dados.
$ns = $xml->getNamespaces(true);
$namespace_principal = $ns['']; // Geralmente o namespace padrão é o que precisamos
$xml->registerXPathNamespace('nfe', $namespace_principal);

$produtos_encontrados = [];
$produtos_nao_encontrados = [];

// Pega o CNPJ do fornecedor (emitente)
$cnpj_fornecedor_xml = (string)$xml->xpath('//nfe:emit/nfe:CNPJ')[0];

// Pega o número da nota fiscal (nNF)
$numero_nota_xml = (string)$xml->xpath('//nfe:ide/nfe:nNF')[0];

// Pega a lista de todos os produtos ('det' de detalhe) no XML
$itens_xml = $xml->xpath('//nfe:det');

$stmt_produto = $conn->prepare("SELECT ID_Produto, Nome FROM PRODUTOS WHERE EAN_GTIN = ?");

foreach ($itens_xml as $item) {
    $ean = (string)$item->prod->cEAN;

    if (empty($ean) || $ean === 'SEM GTIN') {
        $produtos_nao_encontrados[] = [
            'ean_xml' => 'SEM GTIN',
            'nome_xml' => (string)$item->prod->xProd,
            'qtd_xml' => (float)$item->prod->qCom,
            'custo_xml' => (float)$item->prod->vUnCom
        ];
        continue; 
    }

    $stmt_produto->bind_param("s", $ean);
    $stmt_produto->execute();
    $result = $stmt_produto->get_result();

    if ($result->num_rows > 0) {
        $produto_db = $result->fetch_assoc();
        $produtos_encontrados[] = [
            'id_produto' => $produto_db['ID_Produto'],
            'nome_db' => $produto_db['Nome'],
            'ean_xml' => $ean,
            'nome_xml' => (string)$item->prod->xProd,
            'qtd_xml' => (float)$item->prod->qCom,
            'custo_xml' => (float)$item->prod->vUnCom
        ];
    } 
    else {
        $produtos_nao_encontrados[] = [
            'ean_xml' => $ean,
            'nome_xml' => (string)$item->prod->xProd,
            'qtd_xml' => (float)$item->prod->qCom,
            'custo_xml' => (float)$item->prod->vUnCom
        ];
    }
}

$stmt_produto->close();

$_SESSION['entrada_xml'] = [
    'cnpj_fornecedor' => $cnpj_fornecedor_xml,
    'numero_nota' => $numero_nota_xml,
    'encontrados' => $produtos_encontrados,
    'nao_encontrados' => $produtos_nao_encontrados
];

header('Location: confirmar_entrada_xml.php');
exit;
?>