<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';

if (!isset($_SESSION['ID_Caixa'], $_SESSION['ID_CaixaAberto'], $_SESSION['Saldo_Inicial'])) {
    $_SESSION['msg'] = ['texto' => 'Nenhum caixa para conferir', 'tipo' => 'warning'];
    header('Location:' . BASE_URL .'dashboard.php');
    exit;
}

$id_caixa = $_SESSION['ID_Caixa'];
$id_caixaAberto = $_SESSION['ID_CaixaAberto'];
$saldoInicial = $_SESSION['Saldo_Inicial'];

// 1. Busca relatórios
$sqlRelatorio = "SELECT COUNT(*) AS total_vendas, SUM(Valor_Total) AS valor_total FROM VENDAS WHERE ID_CaixaAberto = ?";
$stmtRelatorio = $conn->prepare($sqlRelatorio);
$stmtRelatorio->bind_param("i", $id_caixaAberto);
$stmtRelatorio->execute();
$resultado = $stmtRelatorio->get_result();
$relatorioCaixa = $resultado->fetch_assoc();

$total_vendas = $relatorioCaixa['total_vendas'] ?? 0;
$valor_total = $relatorioCaixa['valor_total'] ?? 0.0;

$total_entradas = 0;
$total_saidas = 0;

$sqlMovimentacoes = "SELECT Tipo, SUM(Valor) as Total_Movimentado 
                     FROM MOVIMENTACOES_CAIXA 
                     WHERE ID_Caixa = ? 
                       AND Data_Movimentacao >= (SELECT Data_Abertura FROM CAIXAS_ABERTOS WHERE ID_CaixaAberto = ?)
                     GROUP BY Tipo";
$stmtMov = $conn->prepare($sqlMovimentacoes);
$stmtMov->bind_param("ii", $id_caixa, $id_caixaAberto);
$stmtMov->execute();
$resultMov = $stmtMov->get_result();

while ($rowMov = $resultMov->fetch_assoc()) {
    if ($rowMov['Tipo'] === 'Entrada') 
        $total_entradas = (float)$rowMov['Total_Movimentado'];
    elseif ($rowMov['Tipo'] === 'Saída') 
        $total_saidas = (float)$rowMov['Total_Movimentado'];
}

$sqlCaixaEDatas = "SELECT C.Caixa,
                            CA.Data_Abertura,
                            CA.Data_Fechamento
                    FROM CAIXAS C LEFT JOIN CAIXAS_ABERTOS CA
                    ON CA.ID_Caixa = C.ID_Caixa
                    WHERE CA.ID_CaixaAberto = ?";
$stmtCaixaEDatas = $conn->prepare($sqlCaixaEDatas);
$stmtCaixaEDatas->bind_param("i", $id_caixaAberto);
$stmtCaixaEDatas->execute();
$resultCaixaEDatas = $stmtCaixaEDatas->get_result();
$relatorioCaixaEDatas = $resultCaixaEDatas->fetch_assoc();

// 2. Busca total vendido por método de pagamento
$sqlMetodos = "SELECT FP.Tipo,
                    SUM(VP.Valor) AS 'Total_Recebido',
                    SUM(VP.Troco) AS 'Troco_Total'
                FROM VENDA_PAGAMENTOS VP INNER JOIN VENDAS V 
                    ON VP.ID_Venda = V.ID_Venda
                INNER JOIN FORMAS_PAGAMENTO FP 
                    ON VP.ID_Forma_Pag = FP.ID_Forma_Pag
                WHERE V.ID_CaixaAberto = ?
                GROUP BY FP.Tipo";
$stmtMetodos = $conn->prepare($sqlMetodos);
$stmtMetodos->bind_param("i", $id_caixaAberto);
$stmtMetodos->execute();
$resultMetodos = $stmtMetodos->get_result();

$valor_dinheiro = $valor_credito = $valor_debito = $valor_pix = $troco = 0;
$quant_dinheiro = $quant_credito = $quant_debito = $quant_pix = 0;

while ($row = $resultMetodos->fetch_assoc()){
    switch ($row['Tipo']){
        case 'Dinheiro': 
            $valor_dinheiro += (float)$row['Total_Recebido'];
            $troco += (float)$row['Troco_Total'];
            $quant_dinheiro++;
            break;
        case 'Cartão de Crédito':
            $valor_credito += (float)$row['Total_Recebido'];
            $quant_credito++;
            break;
        case 'Cartão de Débito':
            $valor_debito += (float)$row['Total_Recebido'];
            $quant_debito++;
            break;
        case 'PIX':
            $valor_pix += (float)$row['Total_Recebido'];
            $quant_pix++;
            break;
    }
}

$dinheiroEmCaixa = ($total_entradas + $valor_dinheiro) - ($total_saidas + $troco);
$saldoFinal = ($total_entradas + $valor_total) - ($total_saidas + $troco);
$dataAtual = date('Y-m-d H:i:s');


if (isset($_GET['acao']) && $_GET['acao'] == 'confirmar_fechamento') {
    if (round($dinheiroEmCaixa, 2) > 0.00) {
        $_SESSION['msg'] = ['texto' => 'Retire o dinheiro em caixa primeiro', 'tipo' => 'warning'];
        header('Location: finalizarcaixa_pdv.php');
        exit();
    }

    // 4. Atualiza status do caixa
    $sqlFechar = "UPDATE CAIXAS SET Status = 'Fechado' WHERE ID_CAIXA = ?";
    $stmtFechar = $conn->prepare($sqlFechar);
    $stmtFechar->bind_param("i", $id_caixa);

    // 5. Fecha o caixa aberto
    $sql = "UPDATE CAIXAS_ABERTOS SET Data_Fechamento = ?, Saldo_Final = ?, Valor_Vendido = ? WHERE ID_CaixaAberto = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sddi", $dataAtual, $saldoFinal, $valor_total, $id_caixaAberto);
    
    if($stmt->execute() && $stmtFechar->execute()){
        $modo_inativo = 'Inativo';
        $stmt_tela = $conn->prepare("UPDATE CAIXAS SET Tela_Cliente_Modo = ?, Tela_Cliente_Status = NULL WHERE ID_Caixa = ?");
        $stmt_tela->bind_param("si", $modo_inativo, $id_caixa);
        $stmt_tela->execute();

        registrar_log($conn, $_SESSION['ID_Usuario'], "Fechou o caixa aberto {$id_caixaAberto} (ID Caixa: {$id_caixa})");
        
        unset(
            $_SESSION['ID_Caixa'],
            $_SESSION['ID_CaixaAberto'],
            $_SESSION['Saldo_Inicial'],
            $_SESSION['Sangria'],
            $_SESSION['Suprimento']
        );

        $_SESSION['msg'] = ['texto' => 'Caixa fechado com sucesso!', 'tipo' => 'success'];

        header('Location: ' . SISTEMA_URL . 'dashboard.php');
        exit();
    }
    else{
        $_SESSION['msg'] = ['texto' => 'Erro ao finalizar o caixa', 'tipo' => 'danger'];
        header('Location: finalizarcaixa_pdv.php');
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Relatório - Caixa</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/resumoCaixa.css">
    </head>
    <body class="bg-light">
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Relatório de Caixa</h3>
                </div>
                
                <div class="container mt-3 p-5">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="col">
                            <div class="resumoCaixa">
                                <div class="text-center small">
                                    <?= $dataAtual ?>
                                </div>
                                <hr>
                                <div class="small">
                                    Caixa: <?= $relatorioCaixaEDatas['Caixa'] ?><br>
                                    Data Abertura: <?= $relatorioCaixaEDatas['Data_Abertura'] ?><br>
                                    Data Fechamento: <?= $relatorioCaixaEDatas['Data_Fechamento'] ? $relatorioCaixaEDatas['Data_Fechamento'] : '' ?>
                                </div>
                                <hr>
                                <div class="small">
                                    Saldo Inicial: R$ <?= number_format($saldoInicial, 2, ',', '.') ?>
                                </div>
                                <hr>
                                <div class="small">
                                    Dinheiro(<?= $quant_dinheiro ?>): R$ <?= number_format($valor_dinheiro, 2, ',', '.') ?><br>
                                    Crédito(<?= $quant_credito ?>): R$ <?= number_format($valor_credito, 2, ',', '.') ?><br>
                                    Débito(<?= $quant_debito ?>): R$ <?= number_format($valor_debito, 2, ',', '.') ?><br>
                                    PIX(<?= $quant_pix ?>): R$ <?= number_format($valor_pix, 2, ',', '.') ?><br>
                                    <br>
                                    Total Vendido: R$ <?= number_format($valor_total, 2, ',', '.') ?><br>
                                    Total de Vendas: <?= $total_vendas ?>
                                </div>
                                <hr>
                                <div class="small">
                                    Dinheiro em Caixa: R$ <?= number_format($dinheiroEmCaixa, 2, ',', '.') ?><br>
                                    (Entradas + Vendas em Dinheiro) - (Saídas + Troco)
                                </div>
                                <hr>
                                <div class="small">
                                    Saldo Final: R$ <?= number_format($saldoFinal, 2, ',', '.') ?><br>
                                    (Entradas + Total Vendido) - (Saídas + Troco)
                                </div>
                                <hr>
                                <div class="small">
                                    Valor Suprimentos (Entradas): R$ <?= number_format($total_entradas, 2, ',', '.') ?><br>
                                    Valor Sangrias (Saídas): R$ <?= number_format($total_saidas, 2, ',', '.') ?>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="mt-1 p-2">
                                <button class="btn btn-success m-2" id="btnSangria" onclick="sangria()">Sangria</button>
                                <a href="?acao=confirmar_fechamento" class="btn btn-danger m-2">Confirmar e Fechar Caixa Definitivamente</a>
                            </div>
                            <div id="camposSangria"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            function sangria() {
                document.getElementById('btnSangria').disabled = true; 
                const sangria = document.createElement('div');
                const valorARetirar = parseFloat(<?= $dinheiroEmCaixa ?>).toFixed(2);

                sangria.innerHTML = `
                    <div class="mb-3 row">
                        <label class="col-sm-4 col-form-label">Sangria (Saída de Dinheiro)</label>
                        <div class="col-sm-8 d-flex align-items-center">
                            <input type="number" step="0.01" min="0" class="form-control" id="valorMovimentacao" value="${valorARetirar}">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-4 col-form-label">Descrição</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="descricaoMovimentacao" value="Sangria - Finalização de caixa" disabled>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <button class="btn btn-primary w-100" onclick="registrarMovimentacao('saida')">Confirmar</button>
                        <div id="erroFuncionalidade" class="text-danger mt-2 w-100 text-center"></div>
                    </div>
                `;
                
                document.getElementById('camposSangria').appendChild(sangria);
            }

            let registroSangria = null;

            function registrarMovimentacao(tipo) {
                registroSangria = tipo;
                let valor = parseFloat(document.getElementById('valorMovimentacao').value);
                let descricao = document.getElementById('descricaoMovimentacao').value.trim();

                if (isNaN(valor) || valor <= 0) {
                    document.getElementById('erroFuncionalidade').textContent = "Informe um valor válido.";
                    return;
                }

                fetch('../../Dev/Exec/registrar_movimentacao.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `tipo=${registroSangria}&valor=${valor}&descricao=${encodeURIComponent(descricao)}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === 'ok') {
                        mostrarToast('Movimentação registrada com sucesso!', 'success', 'Sucesso');
                        location.reload();
                    } else {
                        document.getElementById('erroFuncionalidade').textContent = data;
                    }
                });
            }

            <?php
            if (isset($_SESSION['msg']) && is_array($_SESSION['msg'])) {
                $texto = addslashes($_SESSION['msg']['texto']);
                $tipo = $_SESSION['msg']['tipo'];
                
                echo "mostrarToast('{$texto}', '{$tipo}');";

                unset($_SESSION['msg']);
            }
            ?>
        </script>
    </body>
</html>