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

$stmt = $conn->prepare("
    SELECT 
        C.ID_Cliente, C.Nome, C.Data_Nascimento, C.Sexo, C.Tel AS Telefone,
        E.CEP, E.Endereco, E.End_Numero, E.Complemento, E.Bairro, E.Cidade, E.Estado
    FROM CLIENTES C
    LEFT JOIN CLI_ENDERECOS E ON C.ID_Cliente = E.ID_Cliente
    WHERE REPLACE(REPLACE(REPLACE(C.Documento, '.', ''), '/', ''), '-', '') = ? 
    AND C.Status = 'Ativo'
    LIMIT 1
");
$stmt->bind_param("s", $documento_limpo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente_e_endereco = $result->fetch_assoc();

    $resposta = [
        'sucesso' => true,
        'id_cliente' => $cliente_e_endereco['ID_Cliente'],
        'nome_cliente' => $cliente_e_endereco['Nome'],
        'data_nascimento' => $cliente_e_endereco['Data_Nascimento'],
        'sexo' => $cliente_e_endereco['Sexo'],
        'telefone' => $cliente_e_endereco['Telefone']
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
} 
else {
    echo json_encode(['sucesso' => false, 'erro' => 'Cliente não encontrado.']);
}

$stmt->close();
$conn->close();
?>