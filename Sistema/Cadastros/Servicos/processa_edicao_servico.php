<?php
session_start();

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: servicos.php");
    exit;
}

$id_servico = filter_input(INPUT_POST, 'id_servico', FILTER_VALIDATE_INT);
$nome_servico = $_POST['nome_servico'] ?? '';
$valor = $_POST['valor'] ?? 0;
$status = $_POST['status'] ?? 'Ativo';
$descricao = $_POST['descricao'] ?? '';

$campos = $_POST['campos'] ?? [];
$delete_campos = $_POST['delete_campos'] ?? [];
$delete_referencias = $_POST['delete_referencias'] ?? [];

if (!$id_servico || empty($nome_servico) || empty($valor)) {
    $_SESSION['msg'] = ['texto' => 'Dados essenciais do serviço não foram fornecidos.', 'tipo' => 'warning'];
    header("Location: editar_servico.php?id=" . $id_servico);
    exit;
}

$conn->begin_transaction();

try {
    if (!empty($delete_referencias)) {
        $stmtDelRef = $conn->prepare("DELETE FROM SERVICO_CAMPOS_REFERENCIAS WHERE ID_Referencia = ?");
        foreach ($delete_referencias as $id_ref) {
            $id_ref_int = (int)$id_ref;
            $stmtDelRef->bind_param("i", $id_ref_int);
            $stmtDelRef->execute();
        }
    }

    if (!empty($delete_campos)) {
        $stmtDelCampo = $conn->prepare("DELETE FROM SERVICO_CAMPOS WHERE ID_Campo = ? AND ID_Servico = ?");
        foreach ($delete_campos as $id_campo) {
            $id_campo_int = (int)$id_campo;
            $stmtDelCampo->bind_param("ii", $id_campo_int, $id_servico);
            $stmtDelCampo->execute();
        }
    }

    $stmtServico = $conn->prepare("UPDATE SERVICOS_FARMACEUTICOS SET Nome_Servico = ?, Valor = ?, Status = ?, Descricao = ? WHERE ID_Servico = ?");
    $stmtServico->bind_param("sdssi", $nome_servico, $valor, $status, $descricao, $id_servico);
    $stmtServico->execute();

    $stmtCampoUpdate = $conn->prepare("UPDATE SERVICO_CAMPOS SET Ordem = ?, Label_Campo = ?, Tipo_Campo = ?, Unidade_Medida = ? WHERE ID_Campo = ? AND ID_Servico = ?");
    $stmtCampoInsert = $conn->prepare("INSERT INTO SERVICO_CAMPOS (ID_Servico, Ordem, Label_Campo, Name_Campo, Tipo_Campo, Unidade_Medida) VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmtRefUpdate = $conn->prepare("UPDATE SERVICO_CAMPOS_REFERENCIAS SET Descricao_Referencia = ?, Valor_Feminino = ?, Valor_Masculino = ? WHERE ID_Referencia = ? AND ID_Campo = ?");
    $stmtRefInsert = $conn->prepare("INSERT INTO SERVICO_CAMPOS_REFERENCIAS (ID_Campo, Descricao_Referencia, Valor_Feminino, Valor_Masculino) VALUES (?, ?, ?, ?)");

    $ordem = 0;
    foreach ($campos as $campo_dados) {
        $id_campo_atual = $campo_dados['id_campo'] ? (int)$campo_dados['id_campo'] : null;
        $label = $campo_dados['label'];
        $tipo = $campo_dados['tipo'];
        $unidade = $campo_dados['unidade'];

        if (empty($label)) continue; 

        if ($id_campo_atual) {
            $stmtCampoUpdate->bind_param("isssii", $ordem, $label, $tipo, $unidade, $id_campo_atual, $id_servico);
            $stmtCampoUpdate->execute();
        } 
        else {
            $name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $label))); 
            $stmtCampoInsert->bind_param("iissss", $id_servico, $ordem, $label, $name, $tipo, $unidade);
            $stmtCampoInsert->execute();
            $id_campo_atual = $conn->insert_id; 
        }

        $ordem++;

        if (isset($campo_dados['referencias']) && is_array($campo_dados['referencias'])) {
            foreach ($campo_dados['referencias'] as $ref_dados) {
                $id_ref_atual = $ref_dados['id_referencia'] ? (int)$ref_dados['id_referencia'] : null;
                $ref_desc = $ref_dados['descricao'];
                $ref_fem = $ref_dados['fem'];
                $ref_masc = $ref_dados['masc'];
                
                if (empty($ref_desc)) continue; 

                if ($id_ref_atual) {
                    $stmtRefUpdate->bind_param("sssii", $ref_desc, $ref_fem, $ref_masc, $id_ref_atual, $id_campo_atual);
                    $stmtRefUpdate->execute();
                } 
                else {
                    $stmtRefInsert->bind_param("isss", $id_campo_atual, $ref_desc, $ref_fem, $ref_masc);
                    $stmtRefInsert->execute();
                }
            }
        }
    }
    
    $conn->commit();
    
    registrar_log($conn, $_SESSION['ID_Usuario'], "Atualizou as definições do serviço '{$nome_servico}' (ID: {$id_servico})");
    $_SESSION['msg'] = ['texto' => 'Serviço atualizado com sucesso!', 'tipo' => 'success'];
    header("Location: servicos.php");
    exit();

} 
catch (Exception $e) {
    $conn->rollback();
    error_log("Erro ao atualizar serviço (ID: {$id_servico}): " . $e->getMessage()); 
    $_SESSION['msg'] = ['texto' => 'Ocorreu um erro ao salvar as alterações. Nenhuma modificação foi feita no banco de dados.', 'tipo' => 'danger'];
    header("Location: editar_servico.php?id=" . $id_servico);
    exit();
}