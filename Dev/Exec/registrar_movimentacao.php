<?php
session_start();
include "config.php";
include 'conexao.php';

$tipo = $_POST['tipo'];
$valor = floatval($_POST['valor']);
$descricao = trim($_POST['descricao']);

if ($tipo === 'entrada'){
    $suprimento = $_SESSION['Suprimento'] ?? 0;
    $suprimento += $valor;
    $_SESSION['Suprimento'] = $suprimento;
}
else {
    $saida = $_SESSION['Sangria'] ?? 0;
    $saida += $valor;
    $_SESSION['Sangria'] = $saida;
}

if (!in_array($tipo, ['entrada', 'saida'])) {
    echo "Tipo inválido.";
    exit;
}

$tipoSQL = $tipo === 'entrada' ? 'Entrada' : 'Saída';
$idCaixa = $_SESSION['ID_Caixa'];
$idFuncionario = $_SESSION['ID_Funcionario'];

$stmt = $conn->prepare("INSERT INTO MOVIMENTACOES_CAIXA 
                        (ID_Caixa, ID_Funcionario, Tipo, Valor, Descricao)
                        VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisds", $idCaixa, $idFuncionario, $tipoSQL, $valor, $descricao);

if ($stmt->execute())
    echo "ok";
else 
    echo "Erro ao registrar movimentação.";

?>
