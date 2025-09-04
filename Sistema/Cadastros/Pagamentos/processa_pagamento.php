<?php
session_start();

include '../../../Dev/Exec/config.php';
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 
include DEV_PATH . 'Exec/validar_sessao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = ['texto' => 'Forma de pagamento não informada', 'tipo' => 'danger'];
    header("Location: formas_pagamentos.php");
    exit;
}

$id_usuario_logado = $_SESSION['ID_Usuario']; 
$action = $_POST['action'] ?? '';

if ($action === 'change_status') {
    $id_forma_pag = filter_input(INPUT_POST, 'id_forma_pag', FILTER_VALIDATE_INT);
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';

    if ($novo_status === 'Inativo') {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM VENDA_PAGAMENTOS WHERE ID_Forma_Pag = ?");
        $stmtCheck->bind_param("i", $id_forma_pag);
        $stmtCheck->execute();
        $total_usos = $stmtCheck->get_result()->fetch_assoc()['total'];

        if ($total_usos > 0) {
            $_SESSION['msg'] = ['texto' => 'Não é possível inativar: forma de pagamento já utilizada em vendas.', 'tipo' => 'warning'];
            header("Location: formas_pagamentos.php");
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE FORMAS_PAGAMENTO SET Status = ? WHERE ID_Forma_Pag = ?");
    $stmt->bind_param("si", $novo_status, $id_forma_pag);

    if ($stmt->execute()) {
        registrar_log($conn, $id_usuario_logado, "Alterou o status para '{$novo_status}' da forma de pagamento ID: {$id_forma_pag}");
        $_SESSION['msg'] = ['texto' => 'Status alterado com sucesso!', 'tipo' => 'success'];
    } 
    else
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status.', 'tipo' => 'danger'];
}
else {
    $id_forma_pag = filter_input(INPUT_POST, 'id_forma_pag', FILTER_VALIDATE_INT);
    $tipo = $_POST['tipo'] ?? '';
    
    if (empty($tipo)) {
        $_SESSION['msg'] = ['texto' => 'O nome da forma é obrigatório.', 'tipo' => 'warning'];
        header("Location: formas_pagamentos.php");
        exit;
    }
    
    if ($id_forma_pag) {
        $sql = "UPDATE FORMAS_PAGAMENTO SET Tipo = ? WHERE ID_Forma_Pag = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $tipo, $id_forma_pag);
        $acao_log = "Atualizou a forma de pagamento '{$tipo}'";
        $msg_sucesso = "Forma de Pagamento atualizada com sucesso!";
    } 
    else {
        $sql = "INSERT INTO FORMAS_PAGAMENTO (Tipo) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $tipo);
        $acao_log = "Cadastrou a nova forma de pagamento '{$tipo}'";
        $msg_sucesso = "Forma de Pagamento cadastrada com sucesso!";
    }
    
    if ($stmt->execute()) {
        if (!$id_forma_pag) {
            $id_forma_pag = $conn->insert_id;
            $acao_log .= " (ID: {$id_forma_pag})";
        }
    
        registrar_log($conn, $id_usuario_logado, $acao_log);
        $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao salvar a forma de pagamento: ' . $stmt->error, 'tipo' => 'danger'];
}

header("Location: formas_pagamentos.php");
exit;

?>