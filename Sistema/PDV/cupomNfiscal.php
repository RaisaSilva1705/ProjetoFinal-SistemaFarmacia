<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";

// Incluir o arquivo de conexão
include DEV_PATH . 'Exec/conexao.php';

if (!isset($_GET['ID_Venda'])){
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
    die('Venda não encontrada.');
}

// Itens da venda
$sqlTabelaItens = "SELECT IV.Quantidade,
                          IV.Valor_Total,
                          P.Nome AS 'Nome_Produto',
                          P.EAN_GTIN AS 'CodBarras',
                          U.Abreviacao,
                          L.Preco_Unitario
                FROM ITENS_VENDA IV LEFT JOIN PRODUTOS P 
                    ON IV.ID_Produto = P.ID_Produto
                LEFT JOIN UNIDADES U
                    ON P.ID_Unidade = U.ID_Unidade
                LEFT JOIN (
                    SELECT L1.ID_Produto,
                        L1.Preco_Unitario
                    FROM LOTES L1
                    INNER JOIN (
                        SELECT ID_Produto, 
                                MIN(Data_Validade) AS 'Data_Validade'
                        FROM LOTES
                        GROUP BY ID_Produto
                    ) L2 
                    ON L1.ID_Produto = L2.ID_Produto AND L1.Data_Validade = L2.Data_Validade
                ) L ON P.ID_Produto = L.ID_Produto
                WHERE IV.ID_Venda = ?";
$stmtTabItens = $conn->prepare($sqlTabelaItens);
$stmtTabItens->bind_param("i", $id_venda);
$stmtTabItens->execute();
$resultTabItens = $stmtTabItens->get_result();
$tabItens = $resultTabItens->fetch_all(MYSQLI_ASSOC);

$dataHora = date('d/m/Y H:i:s', strtotime($dadosVenda['DataHora_Venda']));

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cupom Fiscal #<?= $id_venda ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: monospace;
        }
        .cupom {
            background: white;
            background-image: url('../../Dev/Imagens/imgSistema/mascaraCNF.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            max-width: 365px;
            margin: 30px auto;
            padding: 20px;
            border: 1px dashed #000;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        hr {
            border: none;
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .text-center {
            text-align: center;
        }
        .small {
            font-size: 0.8em;
        }
    </style>
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
                    $preco_un = number_format($item['Preco_Unitario'], 2, ',', '.');
                    $vl_total = number_format($item['Valor_Total'], 2, ',', '.');

                    $nomeProduto = strlen($item['Nome_Produto']) > 20 ? substr($item['Nome_Produto'], 0, 20) . '...' : $item['Nome_Produto'];
            ?>
            <div class="small">
                <?= $cont ?> | <?= $item['CodBarras'] ?> | <?= $nomeProduto ?><br>
                <div style="text-align: right;"><?= $item['Quantidade'] ?> | <?= $item['Abreviacao'] ?> | <?= $preco_un ?> | <?= $vl_total ?></div>
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
