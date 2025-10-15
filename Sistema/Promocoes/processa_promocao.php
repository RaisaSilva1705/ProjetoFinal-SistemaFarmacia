<?php
session_start();
include "../../dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: promocoes.php');
    exit;
}

$id_promocao = filter_input(INPUT_POST, 'id_promocao', FILTER_VALIDATE_INT); 
$descricao = trim($_POST['descricao'] ?? '');
$tipo_promocao = $_POST['tipo_promocao'] ?? '';
$data_inicio = $_POST['data_inicio'] ?? '';
$data_fim = !empty($_POST['data_fim']) ? $_POST['data_fim'] : null;
$itens = $_POST['itens'] ?? [];

if (empty($descricao) || empty($tipo_promocao) || empty($data_inicio) || empty($itens)) {
    $_SESSION['msg'] = ['texto' => 'Todos os campos principais e pelo menos um item de regra são obrigatórios.', 'tipo' => 'warning'];
    header('Location: nova_promocao.php');
    exit;
}

$conn->begin_transaction();

try {
    if ($id_promocao) {
        $stmt_promo = $conn->prepare("UPDATE PROMOCOES SET Descricao = ?, Tipo = ?, Data_Inicio = ?, Data_Fim = ? WHERE ID_Promocao = ?");
        $stmt_promo->bind_param("ssssi", $descricao, $tipo_promocao, $data_inicio, $data_fim, $id_promocao);
        $stmt_promo->execute();

        $stmt_delete_itens = $conn->prepare("DELETE FROM PROMOCOES_ITENS WHERE ID_Promocao = ?");
        $stmt_delete_itens->bind_param("i", $id_promocao);
        $stmt_delete_itens->execute();
        
        $acao_log = "Atualizou a promoção '{$descricao}' (ID: {$id_promocao})";
        $msg_sucesso = "Promoção atualizada com sucesso!";

    } 
    else {
        $stmt_promo = $conn->prepare("INSERT INTO PROMOCOES (Descricao, Tipo, Data_Inicio, Data_Fim, Status) VALUES (?, ?, ?, ?, 'Ativo')");
        $stmt_promo->bind_param("ssss", $descricao, $tipo_promocao, $data_inicio, $data_fim);
        $stmt_promo->execute();
        $id_promocao = $conn->insert_id; 

        if ($id_promocao == 0) 
            throw new Exception("Não foi possível criar a promoção principal.");
        
        $acao_log = "Cadastrou a nova promoção '{$descricao}' (ID: {$id_promocao})";
        $msg_sucesso = "Promoção cadastrada com sucesso!";
    }

    $stmt_itens = $conn->prepare(
        "INSERT INTO PROMOCOES_ITENS (ID_Promocao, Tipo_Item, ID_Produto, Quantidade, Valor_Desconto_Percentual, Preco_Fixo_Combo) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    foreach ($itens as $item) {
        $id_produto = filter_var($item['id_produto'], FILTER_VALIDATE_INT);
        $tipo_item = $item['tipo_item'];
        $quantidade = filter_var($item['quantidade'], FILTER_VALIDATE_INT);
        
        $valor_desconto = !empty($item['valor_desconto']) ? filter_var($item['valor_desconto'], FILTER_VALIDATE_FLOAT) : null;
        $preco_fixo = !empty($item['preco_fixo']) ? filter_var($item['preco_fixo'], FILTER_VALIDATE_FLOAT) : null;

        if (!$id_produto || !$quantidade) continue; 

        $stmt_itens->bind_param("isiiid", $id_promocao, $tipo_item, $id_produto, $quantidade, $valor_desconto, $preco_fixo);
        $stmt_itens->execute();
    }

    $conn->commit();
    
    registrar_log($conn, $_SESSION['ID_Usuario'], $acao_log);
    $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];

} 
catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg'] = ['texto' => 'Erro ao salvar a promoção: ' . $e->getMessage(), 'tipo' => 'danger'];
    // error_log("Erro em processa_promocao.php: " . $e->getMessage());
}

header('Location: promocoes.php');
exit;