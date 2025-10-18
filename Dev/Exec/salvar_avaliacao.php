<?php
session_start();
include "config.php";
include "conexao.php";

$id_caixa = filter_input(INPUT_POST, 'id_caixa', FILTER_VALIDATE_INT);
$id_venda = filter_input(INPUT_POST, 'id_venda', FILTER_VALIDATE_INT);
$nota = filter_input(INPUT_POST, 'nota', FILTER_VALIDATE_INT);
$id_funcionario = filter_input(INPUT_POST, 'id_funcionario', FILTER_VALIDATE_INT);

if ($id_caixa && $id_venda && $nota && $id_funcionario) {
    $stmt = $conn->prepare("INSERT INTO AVALIACOES (ID_Venda, ID_Funcionario, Nota) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $id_venda, $id_funcionario, $nota);
    $stmt->execute();

    // Após salvar, a tela pode voltar a aguardar
    $modo = 'Aguardando';
    $stmt_update = $conn->prepare("UPDATE CAIXAS SET Tela_Cliente_Modo = ? WHERE ID_Caixa = ?");
    $stmt_update->bind_param("si", $modo, $id_caixa);
    $stmt_update->execute();
}
?>