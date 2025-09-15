<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$sql = "SELECT
            P.ID_Produto,
            P.Nome,
            SUM(IV.Quantidade) AS Quantidade_Vendida,
            SUM(IV.Valor_Total) AS Faturamento_Total,
            -- Estimativa de custo total baseado no custo médio do lote
            (SUM(IV.Quantidade) * AVG(L.Preco_Custo)) AS Custo_Total_Estimado
        FROM ITENS_VENDA IV
        JOIN VENDAS V ON IV.ID_Venda = V.ID_Venda
        JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto
        LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto
        WHERE DATE(V.DataHora_Venda) BETWEEN ? AND ?
        GROUP BY P.ID_Produto, P.Nome
        ORDER BY Faturamento_Total DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $data_inicio, $data_fim);
$stmt->execute();
$result = $stmt->get_result();
$produtos_vendidos = $result->fetch_all(MYSQLI_ASSOC);

// ----- CARDS DE RESUMO -----
$produto_mais_vendido_qtd = ['nome' => 'N/A', 'valor' => 0];
$produto_mais_rentavel = ['nome' => 'N/A', 'valor' => 0];
$total_lucro_bruto = 0;

if (count($produtos_vendidos) > 0) {
    $mais_vendido_temp = $produtos_vendidos[0];

    foreach ($produtos_vendidos as $produto) {
        if ($produto['Quantidade_Vendida'] > $mais_vendido_temp['Quantidade_Vendida'])
            $mais_vendido_temp = $produto;
        $total_lucro_bruto += ($produto['Faturamento_Total'] - $produto['Custo_Total_Estimado']);
    }

    $produto_mais_vendido_qtd = [
        'nome' => $mais_vendido_temp['Nome'],
        'valor' => $mais_vendido_temp['Quantidade_Vendida']
    ];
    
    $produto_mais_rentavel = [
        'nome' => $produtos_vendidos[0]['Nome'],
        'valor' => $produtos_vendidos[0]['Faturamento_Total']
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Produtos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatório de Desempenho de Produtos</h3>
                </div>
                <div class="container p-5">
                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_produtos.php">
                            <div class="row align-items-end">
                                <div class="col-md-5">
                                    <label for="data_inicio" class="form-label">Data Início:</label>
                                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>" required>
                                </div>
                                <div class="col-md-5">
                                    <label for="data_fim" class="form-label">Data Fim:</label>
                                    <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Produto Mais Vendido</h5>
                                    <p class="card-text fs-5">
                                        <?= htmlspecialchars($produto_mais_vendido_qtd['nome'])?>
                                        <br>
                                        <span class="badge bg-primary">
                                            <?= intval($produto_mais_vendido_qtd['valor']) ?> un.
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Produto Mais Rentável</h5>
                                    <p class="card-text fs-5">
                                        <?= htmlspecialchars($produto_mais_rentavel['nome'])?>
                                        <br>
                                        <span class="badge bg-success">
                                            R$ <?= number_format($produto_mais_rentavel['valor'], 2, ',', '.') ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Lucro Bruto no Período</h5>
                                    <p class="card-text fs-5 text-success fw-bold">
                                        <br>
                                        R$ <?= number_format($total_lucro_bruto, 2, ',', '.') ?> 
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            Desempenho dos Produtos no Período
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">Imprimir Relatório</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-center">Qtd. Vendida</th>
                                        <th class="text-end">Faturamento Total</th>
                                        <th class="text-end">Custo Estimado</th>
                                        <th class="text-end">Lucro Bruto Estimado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($produtos_vendidos) > 0): ?>
                                        <?php foreach ($produtos_vendidos as $produto): 
                                            $lucro_produto = $produto['Faturamento_Total'] - $produto['Custo_Total_Estimado'];
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($produto['Nome']) ?></td>
                                                <td class="text-center"><?= intval($produto['Quantidade_Vendida']) ?></td>
                                                <td class="text-end">R$ <?= number_format($produto['Faturamento_Total'], 2, ',', '.') ?></td>
                                                <td class="text-end text-muted">R$ <?= number_format($produto['Custo_Total_Estimado'], 2, ',', '.') ?></td>
                                                <td class="text-end fw-bold <?= $lucro_produto > 0 ? 'text-success' : 'text-danger' ?>">
                                                    R$ <?= number_format($lucro_produto, 2, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center">Nenhum produto vendido no período selecionado.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
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