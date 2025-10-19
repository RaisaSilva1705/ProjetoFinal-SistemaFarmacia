<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
    exit;
}

$action = $_POST['action'] ?? '';
$id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
$id_endereco_cli = filter_input(INPUT_POST, 'id_endereco_cli', FILTER_VALIDATE_INT);

if ($action === 'remover') {
    if (!$id_cliente || !$id_endereco_cli) {
        echo json_encode(['sucesso' => false, 'erro' => 'IDs inválidos para remoção.']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM CLI_ENDERECOS WHERE ID_Endereco_Cli = ? AND ID_Cliente = ?");
    $stmt->bind_param("ii", $id_endereco_cli, $id_cliente);

    if ($stmt->execute()) {
        registrar_log($conn, $_SESSION['ID_Usuario'], "Removeu um endereço (ID: {$id_endereco_cli}) do cliente ID: {$id_cliente}");
        echo json_encode(['sucesso' => true]);
    } 
    else 
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao remover o endereço.']);

    exit;
}

$cep = $_POST['cep'] ?? '';
$endereco = $_POST['endereco'] ?? '';
$numero = $_POST['numero'] ?? '';
$complemento = $_POST['complemento'] ?? '';
$bairro = $_POST['bairro'] ?? '';
$cidade = $_POST['cidade'] ?? '';
$estado = $_POST['estado'] ?? '';
$obs = $_POST['obs'] ?? '';

if (!$id_cliente || empty($cep) || empty($endereco) || empty($numero) || empty($bairro) || empty($cidade) || empty($estado)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Todos os campos obrigatórios devem ser preenchidos.']);
    exit;
}

if ($id_endereco_cli) {
    $acao = "Editou";
    $sql = "UPDATE CLI_ENDERECOS SET CEP = ?, Endereco = ?, End_Numero = ?, Complemento = ?, Bairro = ?, Cidade = ?, Estado = ?, OBS = ? WHERE ID_Endereco_Cli = ? AND ID_Cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssii", $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $obs, $id_endereco_cli, $id_cliente);
} 
else {
    $acao = "Cadastrou";
    $sql = "INSERT INTO CLI_ENDERECOS (ID_Cliente, CEP, Endereco, End_Numero, Complemento, Bairro, Cidade, Estado, OBS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssss", $id_cliente, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $obs);
}

if ($stmt->execute()) {
    registrar_log($conn, $_SESSION['ID_Usuario'], "{$acao} o endereço do cliente ID: {$id_cliente})");
    echo json_encode(['sucesso' => true]);
}
else 
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar o endereço no banco de dados: ' . $stmt->error]);

$stmt->close();
$conn->close();
?>