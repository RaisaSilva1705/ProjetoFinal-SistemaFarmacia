<?php
session_start();

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: servicos.php');
    exit;
}

$id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
$nome_paciente = $_POST['nome_paciente'] ?? '';
$doc_paciente = $_POST['cpf_paciente'] ?? '';
$sexo_paciente = $_POST['sexo_paciente'] ?? '';
$nascimento_paciente = $_POST['nascimento_paciente'] ?? '';
$id_servico = filter_input(INPUT_POST, 'id_servico', FILTER_VALIDATE_INT);
$obs = $_POST['obs'] ?? '';
$id_funcionario = $_SESSION['ID_Funcionario'];

if (empty($id_cliente) && empty($nome_paciente)) {
    $_SESSION['msg'] = ['texto' => 'É necessário identificar o cliente ou informar o nome do paciente.', 'tipo' => 'warning'];
    header('Location: servicos.php');
    exit;
}

if ($id_cliente === 0) 
    $id_cliente = null;

$dados_servico = $_POST['dados_servico'] ?? [];

$conn->begin_transaction();

try {
    $dados_servico_json = json_encode($dados_servico);
    $sql_reg = "INSERT INTO REGISTRO_SERVICOS (ID_Servico, ID_Cliente, Nome_Paciente, Doc_Paciente, Sexo_Paciente, Data_Nascimento_Paciente, ID_Funcionario, Dados_Servico, OBS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_reg = $conn->prepare($sql_reg);
    $stmt_reg->bind_param("iissssiss", $id_servico, $id_cliente, $nome_paciente, $doc_paciente, $sexo_paciente, $nascimento_paciente, $id_funcionario, $dados_servico_json, $obs);
    $stmt_reg->execute();
    $id_registro_servico = $conn->insert_id;
    if ($id_registro_servico == 0) throw new Exception("Falha ao registrar o serviço.");

    $conn->commit();
    
    $servico_nome_stmt = $conn->prepare("SELECT Nome_Servico FROM SERVICOS_FARMACEUTICOS WHERE ID_Servico = ?");
    $servico_nome_stmt->bind_param("i", $id_servico);
    $servico_nome_stmt->execute();
    $servico_nome = $servico_nome_stmt->get_result()->fetch_assoc()['Nome_Servico'];
    $paciente_log = $id_cliente ? "cliente ID {$id_cliente}" : "paciente avulso '{$nome_paciente}'";
    registrar_log($conn, $_SESSION['ID_Usuario'], "Registrou o serviço '{$servico_nome}' para o {$paciente_log} (Registro ID: {$id_registro_servico})");
    
    $_SESSION['msg'] = ['texto' => 'Serviço registrado com sucesso e estoque atualizado!', 'tipo' => 'success'];

} 
catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg'] = ['texto' => 'Erro: ' . $e->getMessage(), 'tipo' => 'danger'];
}

header("Location: servicos.php");
exit;
?>