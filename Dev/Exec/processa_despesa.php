<?php
session_start();
require_once 'config.php';
require_once 'conexao.php';
require_once 'logs.php';
require_once 'validar_sessao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../Sistema/Financeiro/despesas.php');
    exit();
}

$id_despesa = filter_input(INPUT_POST, 'id_despesa', FILTER_VALIDATE_INT); 
$id_categoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
$descricao = trim($_POST['descricao'] ?? '');
$valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$status = $_POST['status'] ?? 'Pendente';
$data_vencimento = !empty($_POST['data_vencimento']) ? $_POST['data_vencimento'] : null;
$data_pagamento = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
$id_funcionario = $_SESSION['ID_Funcionario']; 

$erros = [];
if (empty($descricao)) $erros[] = "A descrição é obrigatória.";
if (empty($id_categoria)) $erros[] = "A categoria é obrigatória.";
if (empty($valor) || $valor <= 0) $erros[] = "O valor deve ser maior que zero.";
if ($status === 'Paga' && empty($data_pagamento)) 
    $erros[] = "A data de pagamento é obrigatória para despesas pagas.";

if (!empty($erros)) {
    $_SESSION['msg'] = ['texto' => implode('<br>', $erros), 'tipo' => 'warning'];
    // Se for edição, volta para editar. Se não, volta para nova.
    $redirect_url = $id_despesa ? "../../Sistema/Financeiro/editar_despesa.php?id=$id_despesa" : "../../Sistema/Financeiro/nova_despesa.php";
    header("Location: $redirect_url");
    exit();
}

if ($status === 'Pendente') 
    $data_pagamento = null;

if ($id_despesa) {
    $sql = "UPDATE DESPESAS SET 
                ID_Categoria_Despesa = ?, 
                Descricao = ?, 
                Valor = ?, 
                Data_Vencimento = ?, 
                Data_Pagamento = ?, 
                Status = ? 
            WHERE ID_Despesa = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdsssi", $id_categoria, $descricao, $valor, $data_vencimento, $data_pagamento, $status, $id_despesa);
    
    $acao_log = "Atualizou a despesa ID: {$id_despesa}";
    $msg_sucesso = "Despesa atualizada com sucesso!";

} 
else {
    $sql = "INSERT INTO DESPESAS 
                (ID_Categoria_Despesa, Descricao, Valor, Data_Vencimento, Data_Pagamento, Status, ID_Funcionario) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdsssi", $id_categoria, $descricao, $valor, $data_vencimento, $data_pagamento, $status, $id_funcionario);

    $acao_log = "Cadastrou a nova despesa '{$descricao}'";
    $msg_sucesso = "Despesa cadastrada com sucesso!";
}

if ($stmt->execute()) {
    if (!$id_despesa) 
        $id_despesa = $conn->insert_id;

    registrar_log($conn, $id_funcionario, $acao_log . " (ID: {$id_despesa}, Valor: R$ {$valor})");
    $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
} 
else 
    $_SESSION['msg'] = ['texto' => 'Ocorreu um erro ao salvar a despesa. Tente novamente.', 'tipo' => 'danger'];

$stmt->close();
$conn->close();

header('Location: ../../Sistema/Financeiro/despesas.php');
exit();
?>