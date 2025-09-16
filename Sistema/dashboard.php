<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include "../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . 'Exec/validar_sessao.php';

// Card: Vendas Hoje
$sqlVendasHoje = "SELECT SUM(Valor_Total) AS total_hoje FROM VENDAS WHERE DATE(DataHora_Venda) = CURDATE()";
$resultVendasHoje = $conn->query($sqlVendasHoje);
$dadosVendasHoje = $resultVendasHoje->fetch_assoc();
$totalVendasHoje = $dadosVendasHoje['total_hoje'] ?? 0.00; 

// Card: Caixa Atual
$saldoTotalCaixas = 0.00;

$sqlCaixasAbertos = "SELECT ID_CaixaAberto, ID_Caixa, Saldo_Inicial, Data_Abertura
                   FROM CAIXAS_ABERTOS
                   WHERE Data_Fechamento IS NULL";
$resultCaixasAbertos = $conn->query($sqlCaixasAbertos);

if ($resultCaixasAbertos->num_rows > 0) {
    while ($caixa = $resultCaixasAbertos->fetch_assoc()) {
        $id_caixa_aberto = $caixa['ID_CaixaAberto'];
        $id_caixa_fisico = $caixa['ID_Caixa'];
        $saldoInicial = (float)$caixa['Saldo_Inicial'];
        $data_abertura = $caixa['Data_Abertura'];

        $sqlVendas = "SELECT SUM(Valor) AS total_dinheiro, SUM(Troco) AS total_troco
                      FROM VENDA_PAGAMENTOS VP
                      JOIN VENDAS V ON VP.ID_Venda = V.ID_Venda
                      WHERE V.ID_CaixaAberto = ? AND VP.ID_Forma_Pag = 1"; // ID 1 = Dinheiro
        $stmtVendas = $conn->prepare($sqlVendas);
        $stmtVendas->bind_param("i", $id_caixa_aberto);
        $stmtVendas->execute();
        $resultVendas = $stmtVendas->get_result()->fetch_assoc();
        $totalVendasDinheiro = (float)($resultVendas['total_dinheiro'] ?? 0.00);
        $totalTroco = (float)($resultVendas['total_troco'] ?? 0.00);

        $sqlMov = "SELECT Tipo, SUM(Valor) as total_mov
                   FROM MOVIMENTACOES_CAIXA
                   WHERE ID_Caixa = ? AND Data_Movimentacao >= ?
                   GROUP BY Tipo";
        $stmtMov = $conn->prepare($sqlMov);
        $stmtMov->bind_param("is", $id_caixa_fisico, $data_abertura);
        $stmtMov->execute();
        $resultMov = $stmtMov->get_result();
        
        $totalEntradas = 0.00;
        $totalSaidas = 0.00;
        while ($mov = $resultMov->fetch_assoc()) {
            if ($mov['Tipo'] == 'Entrada') $totalEntradas = (float)$mov['total_mov'];
            else $totalSaidas = (float)$mov['total_mov'];
        }
        
        $saldoCaixaIndividual = $saldoInicial + $totalVendasDinheiro - $totalTroco + $totalEntradas - $totalSaidas;
        $saldoTotalCaixas += $saldoCaixaIndividual;
    }
}

// Card: Clientes Ativos
$sqlClientes = "SELECT COUNT(ID_Cliente) AS total_clientes FROM CLIENTES WHERE Status = 'Ativo'";
$resultClientes = $conn->query($sqlClientes);
$dadosClientes = $resultClientes->fetch_assoc();
$totalClientesAtivos = $dadosClientes['total_clientes'];

// Card: Estoque Baixo
$sqlEstoqueBaixo = "SELECT COUNT(*) AS total_baixo 
                    FROM (
                        SELECT 
                            P.Quant_Minima, 
                            SUM(E.Quantidade) AS Quantidade_Total
                        FROM PRODUTOS P
                        LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto
                        LEFT JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote
                        WHERE P.Status = 'Ativo'
                        GROUP BY P.ID_Produto, P.Quant_Minima
                    ) AS subquery
                    WHERE Quantidade_Total <= Quant_Minima";
$resultEstoqueBaixo = $conn->query($sqlEstoqueBaixo);
$dadosEstoqueBaixo = $resultEstoqueBaixo->fetch_assoc();
$totalEstoqueBaixo = $dadosEstoqueBaixo['total_baixo'];

// Box: Últimas Movimentações
$sqlMovimentacoes = "SELECT Tipo, Valor, Descricao 
                     FROM MOVIMENTACOES_CAIXA 
                     ORDER BY Data_Movimentacao DESC 
                     LIMIT 5";
$resultMovimentacoes = $conn->query($sqlMovimentacoes);
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Painel Administrativo</h3>
                </div>
    
                <!-- Dashboard Cards -->
                <div class="container mt-2 p-4">
                    <div class="row justify-content-center">
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-success shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Vendas Hoje</h5>
                                    <p class="card-text fs-4">R$ <?= number_format($totalVendasHoje, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-warning shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Caixas Atuais</h5>
                                    <p class="card-text fs-4">R$ <?= number_format($saldoTotalCaixas, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-primary shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Clientes Ativos</h5>
                                    <p class="card-text fs-4"><?= $totalClientesAtivos ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-danger shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Estoque Baixo</h5>
                                    <p class="card-text fs-4"><?= $totalEstoqueBaixo ?> itens</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
    
                <!-- Área para gráficos ou relatórios -->
                <div class="row m-4">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header bg-light">
                                Vendas da Semana
                            </div>
                            <div class="card-body">
                                <p>Gráfico aqui (ex: Chart.js)</p>
                            </div>
                        </div>
                    </div>
    
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header bg-light">
                                Últimas Movimentações
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <?php if ($resultMovimentacoes->num_rows > 0): ?>
                                        <?php while($mov = $resultMovimentacoes->fetch_assoc()): 
                                            $valorFormatado = number_format($mov['Valor'], 2, ',', '.');
                                            $classe_texto = $mov['Tipo'] == 'Entrada' ? 'text-success' : 'text-danger';
                                            $sinal = $mov['Tipo'] == 'Entrada' ? '+' : '-';
                                        ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <?= htmlspecialchars($mov['Descricao']) ?>
                                                <span class="fw-bold <?= $classe_texto ?>">
                                                    <?= $sinal ?> R$ <?= $valorFormatado ?>
                                                </span>
                                            </li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item">Nenhuma movimentação registrada.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <!-- Toast -->
         <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                <strong class="me-auto" id="toastTitulo">Notificação</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="toastCorpo">
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo DEV_URL ?>JS/toast.js"></script>
        <script>
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