<?php
session_start();
header('Content-Type: application/json'); 

include "config.php";
include "conexao.php";
include "validar_sessao.php";

$id_servico = filter_input(INPUT_GET, 'id_servico', FILTER_VALIDATE_INT);

if (!$id_servico) {
    echo json_encode(['erro' => 'ID do serviço não fornecido.']);
    exit;
}

$campos = [];
$sql = "SELECT Label_Campo, Name_Campo, Tipo_Campo, Unidade_Medida 
        FROM SERVICO_CAMPOS 
        WHERE ID_Servico = ? 
        ORDER BY Ordem, ID_Campo";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_servico);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc())
    $campos[] = $row;

echo json_encode($campos);
exit;