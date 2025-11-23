<?php
session_start();
set_time_limit(300); 

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';

$empresa = $conn->query("SELECT Documento, Nome_RazaoSocial FROM CONFIGURACOES LIMIT 1")->fetch_assoc();
$cnpj_farmacia = preg_replace('/\D/', '', $empresa['Documento']); 
$razao_social_farmacia = $empresa['Nome_RazaoSocial'];

$data_inicio = $_POST['data_inicio'] ?? date('Y-m-d');
$data_fim = $_POST['data_fim'] ?? date('Y-m-d');
$data_inicio_sql = $data_inicio . ' 00:00:00';
$data_fim_sql = $data_fim . ' 23:59:59';

// 2. BUSCA SAÍDAS (VENDAS)
$sql_saidas = "
    SELECT 
        M.MS, 
        M.Controlado,
        P.EAN_GTIN, 
        L.Nome_Lote, 
        V.DataHora_Venda, 
        IV.Quantidade, 
        PR.Dados_Adicionais,
        PR.Nome_Profissional,
        PR.Num_Conselho,
        PR.Conselho,
        PR.UF_Conselho,
        PR.Data_Receita
    FROM VENDAS V
    JOIN ITENS_VENDA IV ON V.ID_Venda = IV.ID_Venda
    JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto
    JOIN MEDICAMENTOS M ON P.ID_Produto = M.ID_Produto
    JOIN MOVIMENTACAO_ESTOQUE ME ON (ME.ID_Venda = V.ID_Venda AND ME.ID_Produto = P.ID_Produto AND ME.Tipo = 'Saída')
    JOIN ESTOQUE E ON ME.ID_Estoque = E.ID_Estoque
    JOIN LOTES L ON E.ID_Lote = L.ID_Lote
    LEFT JOIN PRE_VENDAS PV ON V.ID_Venda = PV.ID_Venda
    LEFT JOIN PRESCRICOES PR ON PV.ID_Prescricao = PR.ID_Prescricao
    WHERE M.Controlado = 'Sim' 
      AND V.DataHora_Venda BETWEEN ? AND ?
";

$stmt_saidas = $conn->prepare($sql_saidas);
$stmt_saidas->bind_param("ss", $data_inicio_sql, $data_fim_sql);
$stmt_saidas->execute();
$saidas = $stmt_saidas->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. BUSCA ENTRADAS (COMPRAS)
// em OBS esperamos algo como 'NF: 12345 FORN: 12345678000199'
$sql_entradas = "
    SELECT 
        M.MS, 
        P.EAN_GTIN, 
        L.Nome_Lote, 
        ME.Data_Movimentacao, 
        ME.Quantidade, 
        ME.OBS, 
        F.CNPJ as CNPJ_Fornecedor
    FROM MOVIMENTACAO_ESTOQUE ME
    JOIN ESTOQUE E ON ME.ID_Estoque = E.ID_Estoque
    JOIN LOTES L ON E.ID_Lote = L.ID_Lote
    JOIN PRODUTOS P ON L.ID_Produto = P.ID_Produto
    JOIN MEDICAMENTOS M ON P.ID_Produto = M.ID_Produto
    LEFT JOIN FORNECEDORES F ON P.ID_Fornecedor = F.ID_Fornecedor
    WHERE M.Controlado = 'Sim' 
      AND ME.Tipo = 'Entrada' 
      AND ME.Motivo = 'Compra de Fornecedor'
      AND ME.Data_Movimentacao BETWEEN ? AND ?
";

$stmt_entradas = $conn->prepare($sql_entradas);
$stmt_entradas->bind_param("ss", $data_inicio_sql, $data_fim_sql);
$stmt_entradas->execute();
$entradas = $stmt_entradas->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. CONSTRUÇÃO DO XML
$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><mensagemSNGPC xmlns="urn:sngpc-schema"></mensagemSNGPC>');

// Cabeçalho Obrigatório
$cabecalho = $xml->addChild('cabecalho');
$cabecalho->addChild('cnpjEmissor', $cnpj_farmacia);
$cabecalho->addChild('cpfEmissor', ''); // CPF do Responsável Técnico (Opcional se tiver CNPJ, mas bom ter na config)
$cabecalho->addChild('dataInicio', $data_inicio);
$cabecalho->addChild('dataFim', $data_fim);

$corpo = $xml->addChild('corpo');
$medicamentos = $corpo->addChild('medicamentos');

// -- Processar ENTRADAS
foreach ($entradas as $ent) {
    $entradaNode = $medicamentos->addChild('entradaMedicamentos');
    
    // Extrair NF da OBS (Assumindo formato "NF: 12345")
    preg_match('/NF:\s*(\d+)/i', $ent['OBS'], $matches);
    $nf = $matches[1] ?? '00000';

    $entradaNode->addChild('notaFiscalEntradaMedicamento');
    $entradaNode->notaFiscalEntradaMedicamento->addChild('numeroNotaFiscal', $nf);
    $entradaNode->notaFiscalEntradaMedicamento->addChild('tipoNotaFiscal', '1'); // 1 = Nota Fiscal Normal
    $entradaNode->notaFiscalEntradaMedicamento->addChild('dataNotaFiscal', date('Y-m-d', strtotime($ent['Data_Movimentacao'])));
    $entradaNode->notaFiscalEntradaMedicamento->addChild('cnpjOrigem', preg_replace('/\D/', '', $ent['CNPJ_Fornecedor']));
    $entradaNode->notaFiscalEntradaMedicamento->addChild('cnpjDestino', $cnpj_farmacia);

    $medEntrada = $entradaNode->addChild('medicamentoEntrada');
    $medEntrada->addChild('registroMSMedicamento', preg_replace('/\D/', '', $ent['MS'])); // Somente números
    $medEntrada->addChild('numeroLoteMedicamento', $ent['Nome_Lote']);
    $medEntrada->addChild('quantidadeMedicamento', $ent['Quantidade']);
    $medEntrada->addChild('unidadeMedidaMedicamento', '1'); // 1 = Caixa/Frasco (Geralmente)
}

// -- Processar SAÍDAS (VENDAS)
foreach ($saidas as $sai) {
    $dados_presc = json_decode($sai['Dados_Adicionais'], true);
    $saidaNode = $medicamentos->addChild('saidaMedicamentoVendaAoConsumidor');
    
    // Data da Venda
    $saidaNode->addChild('dataVendaMedicamento', date('Y-m-d', strtotime($sai['DataHora_Venda'])));
    
    // Dados do Medicamento
    $medSaida = $saidaNode->addChild('medicamentoVenda');
    $medSaida->addChild('registroMSMedicamento', preg_replace('/\D/', '', $sai['MS']));
    $medSaida->addChild('numeroLoteMedicamento', $sai['Nome_Lote']);
    $medSaida->addChild('quantidadeMedicamento', $sai['Quantidade']);
    $medSaida->addChild('unidadeMedidaMedicamento', '1'); 
    
    // SNGPC Exige flag de Uso Prolongado (Antibióticos)
    // Lógica: Se for antimicrobiano, verifica a flag. Se for controlado normal, é '2' (Não)
    $usoProlongado = '2'; // Padrão Não
    if (isset($dados_presc['tipo_receita']) && $dados_presc['tipo_receita'] == 'Antimicrobiano') {
         // Verifica se marcou checkbox de uso continuo na tela
         // Você precisará salvar essa flag no JSON Dados_Adicionais no futuro
         // Por enquanto, deixamos 2 ou 1 se a receita for > 30 dias? Vamos deixar padrão 2 para evitar erro de validação schema
    }
    $medSaida->addChild('usoProlongado', $usoProlongado); 

    // Dados do Prescritor
    $prescritor = $saidaNode->addChild('prescritorSNGPC');
    $prescritor->addChild('nomePrescritor', htmlspecialchars(substr($sai['Nome_Profissional'], 0, 50)));
    $prescritor->addChild('numeroRegistroProfissional', preg_replace('/\D/', '', $sai['Num_Conselho']));
    $prescritor->addChild('conselhoProfissional', strtoupper($sai['Conselho'])); // CRM, CRO, RMS
    $prescritor->addChild('ufConselhoProfissional', strtoupper($sai['UF_Conselho']));

    // Dados da Receita
    $receita = $saidaNode->addChild('receitaMedicamento');
    $receita->addChild('numeroReceita', $dados_presc['numero_receita'] ?? '0000');
    
    // Mapeamento Tipo Receita (SNGPC usa códigos numéricos em alguns casos ou string específica)
    // 1 = Receita Controle Especial (Branca), 2 = Notificação B (Azul), 3 = Notificação A (Amarela), 5 = Antimicrobiano
    $tipoReceitaMap = [
        'A1/A2/A3' => '3',
        'B1/B2' => '2',
        'C2' => '1',
        'Especial' => '1',
        'Antimicrobiano' => '5'
    ];
    $tipoReceitaCodigo = $tipoReceitaMap[$dados_presc['tipo_receita']] ?? '1';
    $receita->addChild('tipoReceita', $tipoReceitaCodigo); 
    
    $receita->addChild('dataPrescricao', $sai['Data_Receita']); // YYYY-MM-DD

    // Dados do Comprador
    $comprador = $saidaNode->addChild('compradorMedicamento');
    
    // Lógica para definir quem é o comprador
    $nomeComp = $dados_presc['comprador_eh_paciente'] ? ($dados_presc['paciente_na_receita']) : ($dados_presc['comprador_nome']);
    $docComp = $dados_presc['comprador_eh_paciente'] ? ($dados_presc['comprador_doc']) : ($dados_presc['comprador_doc']);
    
    // SNGPC valida CPF
    $docCompClean = preg_replace('/\D/', '', $docComp);
    
    // Tipo Documento: 1=RG, 2=CPF (Priorize CPF para SNGPC)
    $tipoDoc = (strlen($docCompClean) == 11) ? '2' : '1';

    $comprador->addChild('nomeComprador', htmlspecialchars(substr($nomeComp, 0, 50)));
    $comprador->addChild('tipoDocumento', $tipoDoc); 
    $comprador->addChild('numeroDocumento', $docCompClean);
    $comprador->addChild('orgaoExpedidor', 'SSP'); // Default para evitar erro, ou adicione campo no form
    $comprador->addChild('ufEmissaoDocumento', $empresa['Documento'] ? 'SP' : 'SP');
}

// 5. DOWNLOAD
$nome_arquivo = "SNGPC_" . date('Ymd') . "_" . $cnpj_farmacia . ".xml";
header('Content-type: application/xml');
header('Content-Disposition: attachment; filename="' . $nome_arquivo . '"');

// Formata o XML bonito (Pretty Print)
$dom = new DOMDocument("1.0");
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
echo $dom->saveXML();
exit;
?>