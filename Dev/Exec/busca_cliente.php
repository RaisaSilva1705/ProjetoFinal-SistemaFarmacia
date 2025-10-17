<?php
session_start();
include "config.php"; 
include "conexao.php"; 

header('Content-Type: application/json');

$documento = $_GET['documento'] ?? '';
$documento_limpo = preg_replace('/[^0-9]/', '', $documento);

if (empty($documento_limpo)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Documento não informado.']);
    exit;
}

// 1. Encontra o ID do cliente na tabela de documentos
$stmt_doc = $conn->prepare("SELECT ID_Cliente FROM CLIENTES_DOCUMENTOS WHERE REPLACE(REPLACE(REPLACE(Numero, '.', ''), '/', ''), '-', '') = ? LIMIT 1");
$stmt_doc->bind_param("s", $documento_limpo);
$stmt_doc->execute();
$result_doc = $stmt_doc->get_result();

if ($result_doc->num_rows === 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Cliente não encontrado com este documento.']);
    exit;
}
$id_cliente = $result_doc->fetch_assoc()['ID_Cliente'];
$stmt_doc->close();


// 2. Com o ID, busca os dados completos do cliente e seu endereço
$stmt_cli = $conn->prepare("
    SELECT 
        C.ID_Cliente, C.Nome, C.Data_Nascimento, C.Sexo, C.Tel AS Telefone, C.Saldo_Credito,
        E.CEP, E.Endereco, E.End_Numero, E.Complemento, E.Bairro, E.Cidade, E.Estado
    FROM CLIENTES C
    LEFT JOIN CLI_ENDERECOS E ON C.ID_Cliente = E.ID_Cliente
    WHERE C.ID_Cliente = ? AND C.Status = 'Ativo'
    LIMIT 1
");
$stmt_cli->bind_param("i", $id_cliente);
$stmt_cli->execute();
$result_cli = $stmt_cli->get_result();
$cliente_e_endereco = $result_cli->fetch_assoc();

// Monta a resposta (lógica inalterada, para não quebrar o PDV)
$resposta = [
    'sucesso' => true,
    'id_cliente' => $cliente_e_endereco['ID_Cliente'],
    'nome_cliente' => $cliente_e_endereco['Nome'],
    'data_nascimento' => $cliente_e_endereco['Data_Nascimento'],
    'sexo' => $cliente_e_endereco['Sexo'],
    'telefone' => $cliente_e_endereco['Telefone'],
    'saldo_credito' => $cliente_e_endereco['Saldo_Credito'] 
];

if ($cliente_e_endereco['CEP'] !== null) {
    $resposta['endereco'] = [
        'CEP' => $cliente_e_endereco['CEP'],
        'Endereco' => $cliente_e_endereco['Endereco'],
        'End_Numero' => $cliente_e_endereco['End_Numero'],
        'Complemento' => $cliente_e_endereco['Complemento'],
        'Bairro' => $cliente_e_endereco['Bairro'],
        'Cidade' => $cliente_e_endereco['Cidade'],
        'Estado' => $cliente_e_endereco['Estado']
    ];
}

echo json_encode($resposta);

$stmt_cli->close();
$conn->close();
?>