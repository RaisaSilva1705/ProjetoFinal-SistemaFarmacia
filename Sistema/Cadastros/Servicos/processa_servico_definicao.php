<?php
session_start();

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: servicos.php");
    exit;
}

$nome_servico = $_POST['nome_servico'] ?? '';
$valor = $_POST['valor'] ?? 0;
$status = $_POST['status'] ?? 'Ativo';
$descricao = $_POST['descricao'] ?? '';
$campos_personalizados = $_POST['campos'] ?? [];
$id_usuario_logado = $_SESSION['ID_Usuario'];

if (empty($nome_servico) || empty($valor)) {
    $_SESSION['msg'] = ['texto' => 'Nome e Valor do serviço são obrigatórios.', 'tipo' => 'warning'];
    header("Location: servicos.php");
    exit;
}

$conn->begin_transaction();

try {
    $stmtServico = $conn->prepare("INSERT INTO SERVICOS_FARMACEUTICOS (Nome_Servico, Valor, Status, Descricao) VALUES (?, ?, ?, ?)");
    $stmtServico->bind_param("sdss", $nome_servico, $valor, $status, $descricao);
    $stmtServico->execute();
    $id_servico_novo = $conn->insert_id;

    if ($id_servico_novo == 0) 
        throw new Exception("Falha ao criar o registro principal do serviço.");

    $stmtCampo = $conn->prepare("INSERT INTO SERVICO_CAMPOS (ID_Servico, Ordem, Label_Campo, Name_Campo, Tipo_Campo, Unidade_Medida) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtRef = $conn->prepare("INSERT INTO SERVICO_CAMPOS_REFERENCIAS (ID_Campo, Descricao_Referencia, Valor_Feminino, Valor_Masculino) VALUES (?, ?, ?, ?)");

    $ordem = 0;
    foreach ($campos_personalizados as $campo) {
        $label = $campo['label'];
        $name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $label)));
        $tipo = $campo['tipo'];
        $unidade = $campo['unidade'];

        if (empty($label)) continue; 

        $stmtCampo->bind_param("iissss", $id_servico_novo, $ordem, $label, $name, $tipo, $unidade);
        $stmtCampo->execute();
        $id_campo_novo = $conn->insert_id;

        $ordem++;

        if ($id_campo_novo == 0) 
            throw new Exception("Falha ao salvar o campo personalizado '{$label}'.");

        if (isset($campo['referencias']) && is_array($campo['referencias'])) {
            foreach ($campo['referencias'] as $ref) {
                $ref_descricao = $ref['descricao'];
                $ref_fem = $ref['fem'];
                $ref_masc = $ref['masc'];

                if (!empty($ref_descricao)) {
                    $stmtRef->bind_param("isss", $id_campo_novo, $ref_descricao, $ref_fem, $ref_masc);
                    $stmtRef->execute();
                }
            }
        }
    }

    $conn->commit();
    
    registrar_log($conn, $id_usuario_logado, "Cadastrou e definiu os campos do serviço '{$nome_servico}' (ID: {$id_servico_novo})");
    $_SESSION['msg'] = ['texto' => 'Definições do serviço salvas com sucesso!', 'tipo' => 'success'];
    header("Location: servicos.php");
    exit();

} 
catch (Exception $e) {
    $conn->rollback();
    error_log($e->getMessage()); 
    $_SESSION['msg'] = ['texto' => 'Erro ao salvar as definições do serviço. Nenhuma alteração foi feita.', 'tipo' => 'danger'];
    header("Location: servicos.php");
    exit();
}
?>