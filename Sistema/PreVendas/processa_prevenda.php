<?php
session_start();
// Define o cabeçalho da resposta como JSON, essencial para o fetch()
header('Content-Type: application/json');

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
$id_funcionario = $_SESSION['ID_Funcionario']; 
$itens_json = $_POST['itens'] ?? '[]';
$itens = json_decode($itens_json, true);
$id_prevenda_existente = filter_input(INPUT_POST, 'id_prevenda_existente', FILTER_VALIDATE_INT) ?: null;

if (empty($id_cliente))
    $id_cliente = null;

if (json_last_error() !== JSON_ERROR_NONE || empty($itens)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum item válido foi enviado.']);
    exit;
}

$conn->begin_transaction();

try {
    $id_pre_venda_final = $id_prevenda_existente;
    $codigo_gerado = '';
    
    if ($id_prevenda_existente) {
        // --- FLUXO DE ATUALIZAÇÃO ---
        $stmt_delete = $conn->prepare("DELETE FROM PRE_VENDAS_ITENS WHERE ID_PreVenda = ?");
        $stmt_delete->bind_param("i", $id_prevenda_existente);
        $stmt_delete->execute();
        
        $stmt_update = $conn->prepare("UPDATE PRE_VENDAS SET ID_Cliente = ? WHERE ID_PreVenda = ?");
        $stmt_update->bind_param("ii", $id_cliente, $id_prevenda_existente);
        $stmt_update->execute();
        
        $stmt_log = $conn->prepare("SELECT Codigo_PreVenda FROM PRE_VENDAS WHERE ID_PreVenda = ?");
        $stmt_log->bind_param("i", $id_prevenda_existente);
        $stmt_log->execute();
        $codigo_gerado = $stmt_log->get_result()->fetch_assoc()['Codigo_PreVenda'];

        $acao = 'Atualizou';
    } 
    else {
        // --- FLUXO DE CRIAÇÃO ---
        $codigo_gerado = '99' . substr(time(), -5) . mt_rand(1000, 9999);
        $stmt_prevenda = $conn->prepare("INSERT INTO PRE_VENDAS (Codigo_PreVenda, ID_Cliente, ID_Funcionario) VALUES (?, ?, ?)");
        $stmt_prevenda->bind_param("sii", $codigo_gerado, $id_cliente, $id_funcionario);
        $stmt_prevenda->execute();
        $id_pre_venda_final = $conn->insert_id;

        $acao = 'Gerou';
    }

    if (empty($id_pre_venda_final)) throw new Exception("Falha ao processar a pré-venda.");

    $stmt_itens = $conn->prepare("INSERT INTO PRE_VENDAS_ITENS (ID_PreVenda, ID_Produto, ID_Servico, Quantidade, Valor_Unitario, Desconto) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($itens as $item) {
        $id_produto = ($item['tipo'] === 'produto') ? $item['id'] : null;
        $id_servico = ($item['tipo'] === 'servico') ? $item['id'] : null;
        $desconto = $item['desconto'] ?? 0.00;
        $stmt_itens->bind_param("iiiidd", $id_pre_venda_final, $id_produto, $id_servico, $item['qtd'], $item['valor'], $desconto);
        $stmt_itens->execute();
    }

    $conn->commit();
    registrar_log($conn, $_SESSION['ID_Usuario'], $acao . " a pré-venda código {$codigo_gerado}");
    echo json_encode(['sucesso' => true, 'codigo' => $codigo_gerado]);
}
catch (Exception $e) {
    $conn->rollback();
    error_log("Erro ao processar pré-venda: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível gerar a pré-venda. Por favor, tente novamente.']);
}
exit;
?>