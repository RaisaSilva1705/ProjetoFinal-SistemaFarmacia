<?php
session_start();
header('Content-Type: application/json');

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido.']);
    exit;
}

$id_cliente = filter_input(INPUT_POST, 'id_cliente_paciente', FILTER_VALIDATE_INT) ?: null;
$id_funcionario = $_SESSION['ID_Funcionario'];
$nome_profissional = $_POST['nome_profissional'] ?? '';
$conselho = $_POST['conselho'] ?? '';
$num_conselho = $_POST['num_conselho'] ?? '';
$uf_conselho = $_POST['uf_conselho'] ?? '';
$data_receita = $_POST['data_receita'] ?? '';
$comprador_eh_paciente = isset($_POST['comprador_eh_paciente']);

if ($comprador_eh_paciente) {
    $comprador_nome = $_POST['nome_paciente'] ?? '';
    $comprador_doc = $_POST['busca_cliente_cpf'] ?? ''; // Pega o CPF do campo do paciente
    $comprador_tel = $_POST['tel_paciente'] ?? '';
} else {
    $comprador_nome = $_POST['nome_comprador'] ?? '';
    $comprador_doc = $_POST['busca_comprador_cpf'] ?? ''; // Pega o CPF do campo do comprador
    $comprador_tel = $_POST['tel_comprador'] ?? '';
}

$dados_adicionais = [
    'paciente_na_receita' => $_POST['paciente_nome_receita'] ?? '',
    'paciente_dn_receita' => $_POST['paciente_dn_receita'] ?? '',
    'paciente_sexo_receita' => $_POST['paciente_sexo_receita'] ?? '',
    'numero_receita' => $_POST['num_receita'] ?? '',
    'tipo_receita' => $_POST['tipo_receita'] ?? '',
    'receita_digital' => isset($_POST['receita_digital_check']),
    'dispensador_digital' => $_POST['dispensador_digital'] ?? '',
    'comprador_eh_paciente' => $comprador_eh_paciente,
    'comprador_nome' => $comprador_nome,
    'comprador_doc' => $comprador_doc,
    'comprador_tel' => $comprador_tel
];

$itens_json = $_POST['itens_dispensacao'] ?? '[]';
$itens = json_decode($itens_json, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($itens)) {
    $_SESSION['msg'] = ['texto' => 'Nenhum medicamento válido foi enviado.', 'tipo' => 'warning'];
    header("Location: dispensacao_controlados.php");
    exit();
}

$conn->begin_transaction();

try {
    // 1. INSERE NA TABELA PRESCRICOES
    $stmt_prescricao = $conn->prepare(
        "INSERT INTO PRESCRICOES (ID_Cliente, ID_Funcionario, Nome_Profissional, Conselho, Num_Conselho, UF_Conselho, Data_Receita, Dados_Adicionais) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $dados_adicionais_json = json_encode($dados_adicionais);
    $stmt_prescricao->bind_param("iissssss", $id_cliente, $id_funcionario, $nome_profissional, $conselho, $num_conselho, $uf_conselho, $data_receita, $dados_adicionais_json);
    $stmt_prescricao->execute();
    $id_prescricao_nova = $conn->insert_id;

    if ($id_prescricao_nova == 0) throw new Exception("Falha ao salvar a prescrição.");

    // 2. GERA O CÓDIGO E CRIA A PRÉ-VENDA, JÁ COM O LINK PARA A PRESCRIÇÃO
    $codigo_gerado = '99' . substr(time(), -5). mt_rand(1000, 9999);
    $stmt_prevenda = $conn->prepare("INSERT INTO PRE_VENDAS (Codigo_PreVenda, ID_Cliente, ID_Funcionario, ID_Prescricao) VALUES (?, ?, ?, ?)");
    $stmt_prevenda->bind_param("siii", $codigo_gerado, $id_cliente, $id_funcionario, $id_prescricao_nova);
    $stmt_prevenda->execute();
    $id_pre_venda_nova = $conn->insert_id;
    
    if ($id_pre_venda_nova == 0) throw new Exception("Falha ao criar a pré-venda.");

    // 3. INSERE OS ITENS NA PRÉ-VENDA
    $stmt_itens = $conn->prepare("INSERT INTO PRE_VENDAS_ITENS (ID_PreVenda, ID_Produto, Quantidade, Valor_Unitario) VALUES (?, ?, ?, ?)");
    foreach ($itens as $item) {
        $stmt_itens->bind_param("iiid", $id_pre_venda_nova, $item['id_produto'], $item['quantidade'], $item['preco']);
        $stmt_itens->execute();
    }

    $conn->commit();
    
    $redirect_url = BASE_URL . 'Sistema/PreVendas/prevendas.php?id_prevenda=' . $id_pre_venda_nova;
    echo json_encode(['sucesso' => true, 'redirectUrl' => $redirect_url, 'id_prescricao' => $id_prescricao_nova]);
    exit;
} 
catch (Exception $e) {
    $conn->rollback();
    error_log("Erro ao processar dispensação: " . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não foi possível processar a dispensação. Erro: ' . $e->getMessage()]);
    exit;
}

?>