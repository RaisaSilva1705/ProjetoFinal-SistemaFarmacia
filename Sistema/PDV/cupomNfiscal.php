<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';

if (!isset($_GET['ID_Venda'])){
    $_SESSION['msg'] = ['texto' => 'ID da venda não fornecido.', 'tipo' => 'warning'];
    header("Location: pdv.php");
    exit();
}

$id_venda = $_GET['ID_Venda'];

// Busca dados da empresa
$sqlDadosEmpresa =  "SELECT * FROM CONFIGURACOES";
$stmtDadosEmpresa = $conn->prepare($sqlDadosEmpresa);
$stmtDadosEmpresa->execute();
$resultDadosEmpresa = $stmtDadosEmpresa->get_result();
$dadosEmpresa = $resultDadosEmpresa->fetch_assoc();

// Busca dados da venda
$sqlDadosVenda =  "SELECT V.DataHora_Venda,
                         V.Valor_Total,
                         V.Desconto,
                         Cli.Nome AS 'Nome_Cliente',
                         F.Nome AS 'Nome_Funcionario'
                  FROM VENDAS V LEFT JOIN FUNCIONARIOS F 
                      ON V.ID_Funcionario = F.ID_Funcionario
                  LEFT JOIN CLIENTES Cli
                      ON V.ID_Cliente = Cli.ID_Cliente
                  WHERE V.ID_Venda = ?";
$stmtDadosVenda = $conn->prepare($sqlDadosVenda);
$stmtDadosVenda->bind_param("i", $id_venda);
$stmtDadosVenda->execute();
$resultDadosVenda = $stmtDadosVenda->get_result();
$dadosVenda = $resultDadosVenda->fetch_assoc();

// Busca dados dos pagamentos da venda
$sqlDadosPagamento =  "SELECT FP.Tipo,
                            VP.Valor AS 'Valor_Pago',
                            VP.Troco,
                            VP.Quant_Vezes,
                            Cai.Caixa
                  FROM FORMAS_PAGAMENTO FP LEFT JOIN VENDA_PAGAMENTOS VP
                      ON VP.ID_Forma_Pag = FP.ID_Forma_Pag
                  LEFT JOIN VENDAS V
                      ON V.ID_Venda = VP.ID_Venda
                  LEFT JOIN CAIXAS_ABERTOS CA
                      ON V.ID_CaixaAberto = CA.ID_CaixaAberto
                  LEFT JOIN CAIXAS Cai
                      ON CA.ID_Caixa = Cai.ID_Caixa
                  WHERE V.ID_Venda = ?
                  ORDER BY VP.ID_VendaPagamento ASC";
$stmtDadosPagamento = $conn->prepare($sqlDadosPagamento);
$stmtDadosPagamento->bind_param("i", $id_venda);
$stmtDadosPagamento->execute();
$resultDadosPagamento = $stmtDadosPagamento->get_result();
$dadosPagamento = $resultDadosPagamento->fetch_all(MYSQLI_ASSOC);

if (!$dadosVenda) {
    $_SESSION['msg'] = ['texto' => 'Venda não encontrada', 'tipo' => 'danger'];
    header("Location: pdv.php");
    exit();
}

// Itens da venda
$sqlTabelaItens = "
    -- Seleciona os PRODUTOS da venda
    (SELECT 
        'produto' AS TipoItem,
        P.Nome AS Nome,
        P.EAN_GTIN AS Codigo,
        IV.Quantidade AS Quantidade,
        (IV.Valor_Total / IV.Quantidade) AS Valor_Unitario,
        IV.Valor_Total AS Valor_Total
    FROM ITENS_VENDA IV
    JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto
    WHERE IV.ID_Venda = ?)

    UNION ALL

    -- Seleciona os SERVIÇOS da venda (via Pré-Venda)
    (SELECT 
        'servico' AS TipoItem,
        SF.Nome_Servico AS Nome,
        CONCAT('SERV', SF.ID_Servico) AS Codigo,
        PVI.Quantidade AS Quantidade,
        PVI.Valor_Unitario AS Valor_Unitario,
        (PVI.Valor_Unitario * PVI.Quantidade) AS Valor_Total
    FROM PRE_VENDAS PV
    JOIN PRE_VENDAS_ITENS PVI ON PV.ID_PreVenda = PVI.ID_PreVenda
    JOIN SERVICOS_FARMACEUTICOS SF ON PVI.ID_Servico = SF.ID_Servico
    WHERE PV.ID_Venda = ? AND PVI.ID_Servico IS NOT NULL)
";

$stmtTabItens = $conn->prepare($sqlTabelaItens);
// Precisamos passar o ID da venda duas vezes, uma para cada parte da UNION
$stmtTabItens->bind_param("ii", $id_venda, $id_venda);
$stmtTabItens->execute();
$tabItens = $stmtTabItens->get_result()->fetch_all(MYSQLI_ASSOC);

$dataHora = date('d/m/Y H:i:s', strtotime($dadosVenda['DataHora_Venda']));

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cupom Fiscal #<?= $id_venda ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/cupomNfiscal.css">
</head>
<body>

    <div class="cupom">
        <div class="text-center small">
            <strong><?= $dadosEmpresa['Nome_RazaoSocial'] ?></strong><br>
            CNPJ: <?= $dadosEmpresa['Documento'] ?><br>
            <?= $dadosEmpresa['Endereco'] ?>, <?= $dadosEmpresa['End_Numero'] ?> - Loja <?= $dadosEmpresa['Loja'] ?><br>
            <?= $dadosEmpresa['Bairro'] ?>, <?= $dadosEmpresa['Cidade'] ?>/<?= $dadosEmpresa['Estado'] ?>, CEP: <?= $dadosEmpresa['CEP'] ?>
        </div>
        <hr>
        <div class="text-center small">
            <strong>Extrato No. <?= $id_venda ?></strong><br>
            <strong>CUPOM NÃO FISCAL ELETRÔNICO</strong>
        </div>
        <hr>
        <div class="text-center small">
            <?= $dadosVenda['Nome_Cliente'] ? 'Cliente: ' . $dadosVenda['Nome_Cliente'] : 'CONSUMIDOR NÃO IDENTIFICADO' ?>
        </div>
        <hr>
        <div> <!-- LISTAGEM DOS PRODUTOS -->
            <div class="text-center" style="font-size: 12px;">
                # | COD | DESC | QTD | UN | VL UN R$ | VL ITEM R$
            </div>
            <hr>
            <?php 
                $cont = 1;
                foreach($tabItens as $item):
                    $preco_un = number_format($item['Valor_Unitario'], 2, ',', '.');
                    $vl_total = number_format($item['Valor_Total'], 2, ',', '.');
                    $nomeItem = strlen($item['Nome']) > 20 ? substr($item['Nome'], 0, 20) . '...' : $item['Nome'];
                    $unidade = $item['TipoItem'] === 'produto' ? 'UN' : 'SERV';
            ?>
            <div class="small">
                <?= $cont ?> | <?= $item['Codigo'] ?> | <?= $nomeItem ?><br>
                <div style="text-align: right;"><?= $item['Quantidade'] ?> | <?= $unidade ?> | <?= $preco_un ?> | <?= $vl_total ?></div>
            </div>
            <?php $cont++; endforeach; ?>
        </div>
        <br>
        <div> <!-- LISTAGEM DOS PAGAMENTOS -->
            <div style="font-family: monospace; font-size: 12px;">
                <?php foreach($dadosPagamento as $pag): ?>
                    <div><?= $pag['Tipo'] ?>: <?= number_format($pag['Valor_Pago'], 2, ',', '.') ?></div>
                <?php endforeach; ?>
                <?php if ($pag['Troco'] > 0.00)
                    echo "<div>Troco: " . number_format($pag['Troco'], 2, ',', '.') . "</div>";
                ?>
                <?php if ($pag['Quant_Vezes'] > 1) 
                    echo "<div>Parcelado " . $pag['Quant_Vezes'] . " vezes.</div>";
                ?>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between;">
                    
                        <span><strong>TOTAL: </strong></span>
                        <span><strong>R$ <?= number_format($dadosVenda['Valor_Total'], 2, ',', '.') ?></strong></span>
                    
                </div> 
            </div>
        </div>
        <hr>
        <div class="text-center small"> <!-- "RODAPÉ" -->
            SAT No. XXXXXX<br>
            <?= $dataHora ?>
        </div>
        <hr>
        <div class="small">
            <div style="display: flex; justify-content: space-between; font-family: monospace; font-size: 12px;">
                <span>Valor do CF-e:</span>
                <span>R$ <?= number_format($dadosVenda['Valor_Total'], 2, ',', '.') ?></span>
            </div>
            <?php foreach($dadosPagamento as $pag): ?>
                <div style="display: flex; justify-content: space-between;">
                    <span><?= $pag['Tipo'] ?>:</span> 
                    <span><?= number_format($pag['Valor_Pago'], 2, ',', '.') ?></span>
                </div>
            <?php endforeach; ?>
            <?php if ($pag['Troco'] > 0.00): ?>
                <div style="display: flex; justify-content: space-between;">
                    <span>Troco:</span>
                    <span> <?= number_format($pag['Troco'], 2, ',', '.') ?></span>
                </div>
            <?php endif; ?>
            Loja <?= $dadosEmpresa['Loja'] ?><br>
            Operador: <?= $dadosVenda['Nome_Funcionario'] ?><br>
            <div class="text-center">
                <strong>** <?= $dadosEmpresa['Nome_Fantasia'] ?> **</strong><br>
                "<?= $dadosEmpresa['Slogan'] ?>"<br>
                AGRADECEMOS A PREFERÊNCIA!<br>
                <?= $dataHora ?> - Caixa: <?= $dadosPagamento[0]['Caixa'] ?>
            </div>
        </div>
    </div>

    <script>
        //window.print();
    </script>

</body>
</html>
