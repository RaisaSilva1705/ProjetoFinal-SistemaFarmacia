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

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$busca_nome = $_GET['busca_nome'] ?? '';

$sql = "SELECT
            F.Nome_Fantasia,
            COUNT(DISTINCT P.ID_Produto) AS Total_Produtos_Vendidos,
            SUM(IV.Quantidade) AS Total_Unidades_Vendidas,
            SUM(IV.Valor_Total) AS Faturamento_Gerado
        FROM ITENS_VENDA IV
        JOIN VENDAS V ON IV.ID_Venda = V.ID_Venda
        JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto
        JOIN FORNECEDORES F ON P.ID_Fornecedor = F.ID_Fornecedor
        WHERE DATE(V.DataHora_Venda) BETWEEN ? AND ?";

$params = [$data_inicio, $data_fim];
$types = 'ss';

if (!empty($busca_nome)) {
    $sql .= " AND F.Nome_Fantasia LIKE ?";
    $types .= 's';
    $params[] = "%" . $busca_nome . "%";
}
$sql .= " GROUP BY F.ID_Fornecedor, F.Nome_Fantasia ORDER BY Faturamento_Gerado DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$fornecedores_ranking = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$fornecedor_destaque = ['Nome_Fantasia' => 'N/A', 'Faturamento_Gerado' => 0];
$faturamento_total_fornecedores = 0;
$total_produtos_unicos_vendidos = 0;
$total_unidades_vendidas = 0;

if (count($fornecedores_ranking) > 0) {
    $fornecedor_destaque = $fornecedores_ranking[0]; 
    
    foreach ($fornecedores_ranking as $fornecedor) {
        $faturamento_total_fornecedores += $fornecedor['Faturamento_Gerado'];
        $total_produtos_unicos_vendidos += $fornecedor['Total_Produtos_Vendidos'];
        $total_unidades_vendidas += $fornecedor['Total_Unidades_Vendidas'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Análise de Fornecedores</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatórios Estratégicos</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Análise de Fornecedores</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_fornecedores.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3"><label for="data_inicio">Período de:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-3"><label for="data_fim">Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-4"><label for="busca_nome">Buscar Fornecedor:</label><input type="text" name="busca_nome" class="form-control" placeholder="Digite o nome fantasia..." value="<?= htmlspecialchars($busca_nome) ?>"></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Faturamento Total (Fornec.)</div><div class="card-body"><p class="card-text fs-2 fw-bold text-success">R$ <?= number_format($faturamento_total_fornecedores, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Fornecedor Destaque</div><div class="card-body"><p class="card-text fs-5"><?= htmlspecialchars($fornecedor_destaque['Nome_Fantasia']) ?><br><span class="badge bg-primary">R$ <?= number_format($fornecedor_destaque['Faturamento_Gerado'], 2, ',', '.') ?></span></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Produtos Únicos Vendidos</div><div class="card-body"><p class="card-text fs-2 fw-bold text-info"><?= $total_produtos_unicos_vendidos ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Unidades Totais Vendidas</div><div class="card-body"><p class="card-text fs-2 fw-bold text-secondary"><?= $total_unidades_vendidas ?></p></div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Ranking de Fornecedores por Faturamento Gerado</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Fornecedor</th>
                                        <th class="text-center">Produtos Únicos</th>
                                        <th class="text-center">Unidades Vendidas</th>
                                        <th class="text-end">Faturamento Gerado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($fornecedores_ranking) > 0): 
                                        $rank = 1;
                                        foreach ($fornecedores_ranking as $fornecedor): ?>
                                            <tr>
                                                <td><?= $rank++ ?></td>
                                                <td><?= htmlspecialchars($fornecedor['Nome_Fantasia']) ?></td>
                                                <td class="text-center"><?= $fornecedor['Total_Produtos_Vendidos'] ?></td>
                                                <td class="text-center"><?= $fornecedor['Total_Unidades_Vendidas'] ?></td>
                                                <td class="text-end fw-bold text-success">R$ <?= number_format($fornecedor['Faturamento_Gerado'], 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center p-4">Nenhum fornecedor teve produtos vendidos no período selecionado.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>