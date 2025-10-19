<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';

$empresa = $conn->query("SELECT Documento, Nome_RazaoSocial FROM CONFIGURACOES LIMIT 1")->fetch_assoc();
$cnpj_farmacia = preg_replace('/\D/', '', $empresa['Documento']); 
$razao_social_farmacia = $empresa['Nome_RazaoSocial'];

$data_inicio = $_POST['data_inicio'] ?? date('Y-m-d');
$data_fim = $_POST['data_fim'] ?? date('Y-m-d');
$data_fim_query = $data_fim . ' 23:59:59';

// 2. Busca as SAÍDAS (VENDAS) de controlados no período
$sql_saidas = "SELECT M.MS, P.EAN_GTIN, L.Nome_Lote, V.DataHora_Venda, IV.Quantidade, PR.*
               FROM VENDAS V
               JOIN ITENS_VENDA IV ON V.ID_Venda = IV.ID_Venda
               JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto
               JOIN MEDICAMENTOS M ON P.ID_Produto = M.ID_Produto
               JOIN LOTES L ON (SELECT ID_Lote FROM ESTOQUE WHERE ID_Estoque = (SELECT ID_Estoque FROM MOVIMENTACAO_ESTOQUE WHERE ID_Venda = V.ID_Venda AND ID_Produto = P.ID_Produto LIMIT 1)) = L.ID_Lote
               LEFT JOIN PRE_VENDAS PV ON V.ID_Venda = PV.ID_Venda
               LEFT JOIN PRESCRICOES PR ON PV.ID_Prescricao = PR.ID_Prescricao
               WHERE M.Controlado = 'Sim' AND V.DataHora_Venda BETWEEN ? AND ?";
$stmt_saidas = $conn->prepare($sql_saidas);
$stmt_saidas->bind_param("ss", $data_inicio, $data_fim_query);
$stmt_saidas->execute();
$saidas = $stmt_saidas->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Busca as ENTRADAS (COMPRAS) de controlados no período
$sql_entradas = "SELECT M.MS, P.EAN_GTIN, L.Nome_Lote, ME.Data_Movimentacao, ME.Quantidade, ME.OBS
                 FROM MOVIMENTACAO_ESTOQUE ME
                 JOIN LOTES L ON ME.ID_Estoque = (SELECT ID_Estoque FROM ESTOQUE WHERE ID_Lote = L.ID_Lote)
                 JOIN PRODUTOS P ON L.ID_Produto = P.ID_Produto
                 JOIN MEDICAMENTOS M ON P.ID_Produto = M.ID_Produto
                 WHERE M.Controlado = 'Sim' 
                   AND ME.Tipo = 'Entrada' 
                   AND ME.Motivo = 'Compra de Fornecedor'
                   AND ME.Data_Movimentacao BETWEEN ? AND ?";
$stmt_entradas = $conn->prepare($sql_entradas);
$stmt_entradas->bind_param("ss", $data_inicio, $data_fim_query);
$stmt_entradas->execute();
$entradas = $stmt_entradas->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. AGRUPA TODAS AS MOVIMENTAÇÕES POR MEDICAMENTO
$medicamentos_movimentados = [];
foreach ($entradas as $mov) { $medicamentos_movimentados[$mov['MS']]['entradas'][] = $mov; }
foreach ($saidas as $mov) { $medicamentos_movimentados[$mov['MS']]['saidas'][] = $mov; }
ksort($medicamentos_movimentados); // Ordena pelo registro MS

// 5. GERAÇÃO DO XML
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><movimentacoes></movimentacoes>');
$cabecalho = $xml->addChild('cabecalho');
$cabecalho->addChild('cnpj', $cnpj_farmacia);
$cabecalho->addChild('razaoSocial', $razao_social_farmacia);
$cabecalho->addChild('dataInicio', $data_inicio);
$cabecalho->addChild('dataFim', $data_fim);

foreach ($medicamentos_movimentados as $ms => $movs) {
    $medicamento_node = $xml->addChild('medicamento');
    $medicamento_node->addAttribute('registroMS', $ms);

    if (!empty($movs['entradas'])) {
        foreach ($movs['entradas'] as $entrada) {
            preg_match('/NF: (\S+)/', $entrada['OBS'], $matches);
            $numero_nf = $matches[1] ?? 'N/A';

            $entrada_node = $medicamento_node->addChild('entrada');
            $entrada_node->addChild('data', date('Y-m-d', strtotime($entrada['Data_Movimentacao'])));
            $entrada_node->addChild('numeroNotaFiscal', $numero_nf);
            $entrada_node->addChild('lote', $entrada['Nome_Lote']);
            $entrada_node->addChild('quantidade', $entrada['Quantidade']);
        }
    }
    if (!empty($movs['saidas'])) {
        foreach ($movs['saidas'] as $saida) {
            $dados_prescricao = json_decode($saida['Dados_Adicionais'], true);
            $saida_node = $medicamento_node->addChild('saidaVenda');
            $saida_node->addChild('data', date('Y-m-d', strtotime($saida['DataHora_Venda'])));
            $saida_node->addChild('lote', $saida['Nome_Lote']);
            $saida_node->addChild('quantidade', $saida['Quantidade']);
            // O SNGPC exige dados que estão no JSON da prescrição
            $saida_node->addChild('nomeComprador', $dados_prescricao['comprador_nome'] ?? $dados_prescricao['paciente_na_receita']);
            $saida_node->addChild('docComprador', $dados_prescricao['comprador_doc'] ?? 'N/A');
        }
    }
}

// 6. SERVE O ARQUIVO PARA DOWNLOAD
$nome_arquivo = "SNGPC_MOV_{$cnpj_farmacia}_" . date('Ymd_His') . ".xml";
header('Content-type: text/xml; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');

$dom = new DOMDocument("1.0");
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
echo $dom->saveXML();
exit;
?>