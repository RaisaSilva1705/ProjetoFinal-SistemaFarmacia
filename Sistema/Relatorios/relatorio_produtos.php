<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'RELATORIOS_VER');
include DEV_PATH . "Exec/validar_acesso.php";

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$sql = "SELECT
            P.Nome,
            SUM(IV.Quantidade) AS Quantidade_Vendida,
            SUM(IV.Valor_Total) AS Faturamento_Total,
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
$produtos_vendidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_faturamento = 0;
$total_lucro_bruto = 0;
$total_unidades_vendidas = 0;
$produto_mais_vendido_qtd = ['nome' => 'N/A', 'valor' => 0];
$produto_mais_rentavel = ['nome' => 'N/A', 'valor' => 0];

if (count($produtos_vendidos) > 0) {
    $produto_mais_rentavel = [
        'nome' => $produtos_vendidos[0]['Nome'],
        'valor' => $produtos_vendidos[0]['Faturamento_Total']
    ];
    
    $mais_vendido_temp = $produtos_vendidos[0];

    foreach ($produtos_vendidos as $produto) {
        $total_faturamento += $produto['Faturamento_Total'];
        $total_lucro_bruto += ($produto['Faturamento_Total'] - $produto['Custo_Total_Estimado']);
        $total_unidades_vendidas += $produto['Quantidade_Vendida'];

        if ($produto['Quantidade_Vendida'] > $mais_vendido_temp['Quantidade_Vendida']) {
            $mais_vendido_temp = $produto;
        }
    }

    $produto_mais_vendido_qtd = [
        'nome' => $mais_vendido_temp['Nome'],
        'valor' => $mais_vendido_temp['Quantidade_Vendida']
    ];
}

$margem_lucro_bruta = ($total_faturamento > 0) ? ($total_lucro_bruto / $total_faturamento) * 100 : 0;

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Desempenho de Produtos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatórios Gerenciais</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Desempenho de Produtos</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_produtos.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5"><label for="data_inicio">Período de:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-5"><label for="data_fim">Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Produto Mais Vendido (Qtd)</div><div class="card-body"><p class="card-text fs-5"><?= htmlspecialchars($produto_mais_vendido_qtd['nome']) ?><br><span class="badge bg-primary"><?= intval($produto_mais_vendido_qtd['valor']) ?> un.</span></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Produto Mais Rentável (R$)</div><div class="card-body"><p class="card-text fs-5"><?= htmlspecialchars($produto_mais_rentavel['nome']) ?><br><span class="badge bg-success">R$ <?= number_format($produto_mais_rentavel['valor'], 2, ',', '.') ?></span></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Total de Unidades Vendidas</div><div class="card-body"><p class="card-text fs-2 fw-bold text-info"><?= $total_unidades_vendidas ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Margem de Lucro Bruta</div><div class="card-body"><p class="card-text fs-2 fw-bold text-warning"><?= number_format($margem_lucro_bruta, 1, ',', '.') ?>%</p></div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Desempenho por Produto</h4>
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
                                                <td class="text-end fw-bold <?= $lucro_produto >= 0 ? 'text-success' : 'text-danger' ?>">R$ <?= number_format($lucro_produto, 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center p-4">Nenhum produto vendido no período selecionado.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php include_once DEV_PATH . 'Views/footer.php'; ?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-graph-up"></i> Relatório de Desempenho de Produtos</h4>
            <hr>
            <p>Este relatório é uma ferramenta estratégica essencial para entender quais produtos são os campeões de venda e quais geram mais lucro para o seu negócio. Use esta análise para otimizar seu mix de produtos, planejar compras e criar promoções mais eficazes.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtro por Período</h6>
            <p>Utilize os campos de data no topo para definir o período que você deseja analisar e descobrir o desempenho dos produtos em diferentes épocas.</p>

            <h6><i class="bi bi-trophy-fill"></i> Indicadores de Destaque</h6>
            <p>Os cards no topo da página destacam os produtos com melhor performance no período selecionado:</p>
            <ul>
                <li><strong>Produto Mais Vendido (Qtd):</strong> Mostra o item que teve o maior número de unidades vendidas, independentemente do preço. Ideal para identificar produtos populares.</li>
                <li><strong>Produto Mais Rentável (R$):</strong> Mostra o item que gerou o maior faturamento total (preço x quantidade). É o seu "carro-chefe" em termos de receita.</li>
                <li><strong>Total de Unidades Vendidas:</strong> A soma de todas as unidades de todos os produtos vendidos no período.</li>
                <li><strong>Margem de Lucro Bruta:</strong> A porcentagem média de lucro obtida sobre o custo dos produtos vendidos. Um indicador chave da saúde financeira das suas vendas.</li>
            </ul>

            <h6><i class="bi bi-table"></i> Desempenho por Produto</h6>
            <p>A tabela principal ranqueia todos os produtos vendidos no período, ordenados pelo maior faturamento. Ela detalha para cada item:</p>
            <ul>
                <li><strong>Qtd. Vendida:</strong> Total de unidades vendidas.</li>
                <li><strong>Faturamento Total:</strong> A receita gerada por aquele produto.</li>
                <li><strong>Custo Estimado:</strong> Uma estimativa do custo total das unidades vendidas.</li>
                <li><strong>Lucro Bruto Estimado:</strong> A diferença entre o faturamento e o custo, mostrando o lucro gerado pelo produto antes das despesas operacionais.</li>
            </ul>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
    </body>
</html>