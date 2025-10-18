<?php
session_start();
include "config.php";
include "conexao.php";

$id_caixa = $_SESSION['ID_Caixa'] ?? null;
$carrinho_json = file_get_contents('php://input');

if (!$id_caixa) 
    exit; 

// 1. Decodifica o carrinho recebido do PDV
$carrinho = json_decode($carrinho_json, true);

if (json_last_error() !== JSON_ERROR_NONE) 
    exit;

// 2. Verifica o modo atual da tela do cliente no banco de dados
$stmt_check = $conn->prepare("SELECT Tela_Cliente_Modo FROM CAIXAS WHERE ID_Caixa = ?");
$stmt_check->bind_param("i", $id_caixa);
$stmt_check->execute();
$modo_atual_db = $stmt_check->get_result()->fetch_assoc()['Tela_Cliente_Modo'];

// 3. Se o modo atual for 'Avaliacao', o PDV não pode mais alterá-lo.
if (strtolower($modo_atual_db) === 'avaliacao') 
    exit; 

// 4. Determina o novo modo com base no conteúdo do carrinho
$novo_modo = !empty($carrinho) ? 'Venda' : 'Aguardando';

// 5. Atualiza o banco de dados com o novo modo e o status do carrinho.
$stmt = $conn->prepare("UPDATE CAIXAS SET Tela_Cliente_Modo = ?, Tela_Cliente_Status = ? WHERE ID_Caixa = ?");
$stmt->bind_param("ssi", $novo_modo, $carrinho_json, $id_caixa);
$stmt->execute();
?>