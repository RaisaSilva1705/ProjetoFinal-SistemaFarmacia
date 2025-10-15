<?php
session_start();

include '../../Dev/Exec/config.php';
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'ESTOQUE_GERENCIAR');
include DEV_PATH . 'Exec/validar_acesso.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = ['texto' => 'Acesso inválido.', 'tipo' => 'danger'];
    header('Location: estoque.php');
    exit;
}

$id_fornecedor = filter_input(INPUT_POST, 'id_fornecedor', FILTER_VALIDATE_INT);
$numero_nota = filter_input(INPUT_POST, 'numero_nota', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$id_funcionario = $_SESSION['ID_Funcionario'];

$produtos = $_POST['produtos'] ?? [];
if (empty($produtos) || !$id_fornecedor) {
    $_SESSION['msg'] = ['texto' => 'Nenhum produto foi adicionado ou o fornecedor não foi selecionado.', 'tipo' => 'warning'];
    header('Location: entrada_estoque.php');
    exit();
}

$conn->begin_transaction();

try {
    $stmtLote = $conn->prepare("INSERT INTO LOTES (ID_Produto, Nome_Lote, Data_Validade, Preco_Custo, Preco_Venda) VALUES (?, ?, ?, ?, ?)");
    $stmtEstoque = $conn->prepare("INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (?, ?, ?)");
    $stmtMovEstoque = $conn->prepare("INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Motivo, Quantidade, OBS) VALUES (?, ?, ?, 'Entrada', 'Compra de Fornecedor', ?, ?)");

    $itens_importados_count = 0;
    foreach ($produtos as $id_produto => $item) {
        if (!isset($item['importar'])) continue;
        
        $nome_lote = $item['lote'];
        $validade = $item['validade'];
        $quantidade = $item['quantidade'];
        $preco_custo = $item['custo'];
        $preco_venda = $item['venda'];

        $stmtLote->bind_param("issdd", $id_produto, $nome_lote, $validade, $preco_custo, $preco_venda);
        $stmtLote->execute();
        $id_lote_novo = $conn->insert_id;
        if ($id_lote_novo == 0) throw new Exception("Falha ao inserir o lote para o produto ID: {$id_produto}");

        $data_entrada = date('Y-m-d');
        $stmtEstoque->bind_param("iis", $id_lote_novo, $quantidade, $data_entrada);
        $stmtEstoque->execute();
        $id_estoque_novo = $conn->insert_id;
        if ($id_estoque_novo == 0) throw new Exception("Falha ao inserir no estoque o lote ID: {$id_lote_novo}");

        $obs_movimentacao = "Entrada via NF: {$numero_nota}";
        $stmtMovEstoque->bind_param("iiiis", $id_estoque_novo, $id_produto, $id_funcionario, $quantidade, $obs_movimentacao);
        $stmtMovEstoque->execute();

        $itens_importados_count++;
    }

    if ($itens_importados_count == 0)
        throw new Exception("Nenhum item foi selecionado para importação.");

    $conn->commit();
    $_SESSION['msg'] = ['texto' => "$itens_importados_count item(ns) importado(s) com sucesso!", 'tipo' => 'success'];
    registrar_log($conn, $_SESSION['ID_Usuario'], "Deu entrada no estouque via XML (NF: {$numero_nota}) com {$itens_importados_count} item(ns).");
} 
catch (Exception $e) {
    $conn->rollback();
    error_log($e->getMessage());
    $_SESSION['msg'] = ['texto' => 'Erro ao registrar a entrada: ' . $e->getMessage() . '. Nenhuma alteração foi salva.', 'tipo' => 'danger'];
    header('Location: entrada_estoque.php');
    exit;
}
finally {
    if (isset($stmtLote)) $stmtLote->close();
    if (isset($stmtEstoque)) $stmtEstoque->close();
    if (isset($stmtMovEstoque)) $stmtMovEstoque->close();
}

header('Location: estoque.php');
exit;
?>