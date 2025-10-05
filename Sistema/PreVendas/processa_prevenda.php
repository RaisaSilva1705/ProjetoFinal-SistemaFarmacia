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

if (empty($id_cliente))
    $id_cliente = null;

if (json_last_error() !== JSON_ERROR_NONE || empty($itens)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum item válido foi enviado.']);
    exit;
}

$conn->begin_transaction();

try {
    $prefixo = '99';
    $timestamp_part = substr(time(), -5); // Pega os últimos 5 dígitos do tempo atual (relativamente único)
    $random_part = mt_rand(1000, 9999);  // Um número aleatório de 4 dígitos para garantir unicidade
    $codigo_gerado = $prefixo . $timestamp_part . $random_part;
    // Exemplo de código gerado: 99123456789

    $sql_prevenda = "INSERT INTO PRE_VENDAS (Codigo_PreVenda, ID_Cliente, ID_Funcionario) VALUES (?, ?, ?)";
    $stmt_prevenda = $conn->prepare($sql_prevenda);
    $stmt_prevenda->bind_param("sii", $codigo_gerado, $id_cliente, $id_funcionario);
    $stmt_prevenda->execute();

    $id_pre_venda_nova = $conn->insert_id;
    if ($id_pre_venda_nova == 0) 
        throw new Exception("Falha ao criar o registro principal da pré-venda.");
    
    $sql_itens = "INSERT INTO PRE_VENDAS_ITENS (ID_PreVenda, ID_Produto, ID_Servico, Quantidade, Valor_Unitario) VALUES (?, ?, ?, ?, ?)";
    $stmt_itens = $conn->prepare($sql_itens);

    foreach ($itens as $item) {
        $id_produto = null;
        $id_servico = null;

        if ($item['tipo'] === 'produto') 
            $id_produto = $item['id'];
        else if ($item['tipo'] === 'servico') 
            $id_servico = $item['id'];

        $quantidade = $item['qtd'];
        $valor = $item['valor'];

        if ($id_produto || $id_servico) {
            $stmt_itens->bind_param("iiiid", $id_pre_venda_nova, $id_produto, $id_servico, $quantidade, $valor);
            $stmt_itens->execute();
        }
    }

    $conn->commit();
    registrar_log($conn, $_SESSION['ID_Usuario'], "Gerou a pré-venda código {$codigo_gerado} (ID: {$id_pre_venda_nova})");
    echo json_encode(['sucesso' => true, 'codigo' => $codigo_gerado]);

} 
catch (Exception $e) {
    $conn->rollback();
    error_log("Erro ao processar pré-venda: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível gerar a pré-venda. Por favor, tente novamente.']);
}

exit;
?>