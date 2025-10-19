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

$busca_nome = $_GET['busca_nome'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$sql = "SELECT
            C.Nome,
            CD.Numero,
            COUNT(V.ID_Venda) AS Total_Compras,
            SUM(V.Valor_Total) AS Total_Gasto
        FROM CLIENTES C
        JOIN VENDAS V ON C.ID_Cliente = V.ID_Cliente
        LEFT JOIN CLIENTES_DOCUMENTOS CD ON C.ID_Cliente = CD.ID_Cliente
        WHERE DATE(V.DataHora_Venda) BETWEEN ? AND ?";

$params = [$data_inicio, $data_fim];
$types = 'ss';

if (!empty($busca_nome)) {
    $sql .= " AND C.Nome LIKE ?";
    $types .= 's';
    $params[] = "%" . $busca_nome . "%";
}
$sql .= " GROUP BY C.ID_Cliente, C.Nome, CD.Numero ORDER BY Total_Gasto DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$clientes_ranking = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$cliente_destaque = ['Nome' => 'N/A', 'Total_Gasto' => 0];
$total_clientes_unicos = count($clientes_ranking);
$faturamento_clientes = 0;
$total_compras_clientes = 0;

if ($total_clientes_unicos > 0) {
    $cliente_destaque = $clientes_ranking[0]; 
    $faturamento_clientes = array_sum(array_column($clientes_ranking, 'Total_Gasto'));
    $total_compras_clientes = array_sum(array_column($clientes_ranking, 'Total_Compras'));
}

$ticket_medio_cliente = ($total_compras_clientes > 0) ? $faturamento_clientes / $total_compras_clientes : 0;

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Desempenho de Clientes</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatórios de CRM</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Desempenho de Clientes</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_clientes.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3"><label for="data_inicio">Período de:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-3"><label for="data_fim">Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-4"><label for="busca_nome">Buscar Cliente:</label><input type="text" name="busca_nome" class="form-control" placeholder="Digite o nome..." value="<?= htmlspecialchars($busca_nome) ?>"></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Faturamento (Clientes Cad.)</div><div class="card-body"><p class="card-text fs-2 fw-bold text-success">R$ <?= number_format($faturamento_clientes, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Cliente Destaque</div><div class="card-body"><p class="card-text fs-5"><?= htmlspecialchars($cliente_destaque['Nome']) ?><br><span class="badge bg-primary">R$ <?= number_format($cliente_destaque['Total_Gasto'], 2, ',', '.') ?></span></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Ticket Médio por Cliente</div><div class="card-body"><p class="card-text fs-2 fw-bold text-info">R$ <?= number_format($ticket_medio_cliente, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Clientes Únicos</div><div class="card-body"><p class="card-text fs-2 fw-bold text-secondary"><?= $total_clientes_unicos ?></p></div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Ranking de Clientes por Valor Gasto</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Cliente</th>
                                        <th>Documento</th>
                                        <th class="text-center">Nº de Compras</th>
                                        <th class="text-end">Valor Total Gasto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($clientes_ranking) > 0): 
                                        $rank = 1;
                                        foreach ($clientes_ranking as $cliente): ?>
                                            <tr>
                                                <td><?= $rank++ ?></td>
                                                <td><?= htmlspecialchars($cliente['Nome']) ?></td>
                                                <td><?= htmlspecialchars($cliente['Documento']) ?></td>
                                                <td class="text-center"><?= $cliente['Total_Compras'] ?></td>
                                                <td class="text-end fw-bold text-success">R$ <?= number_format($cliente['Total_Gasto'], 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center p-4">Nenhuma venda para clientes cadastrados no período selecionado.</td></tr>
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
            <h4><i class="bi bi-person-heart"></i> Relatório de Desempenho de Clientes</h4>
            <hr>
            <p>Este relatório, focado em <strong>clientes cadastrados</strong>, é uma ferramenta de CRM (Customer Relationship Management) que ajuda a identificar seus clientes mais valiosos e a entender seus padrões de compra.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Análise</h6>
            <ul>
                <li><strong>Período (De/Até):</strong> Defina o intervalo de datas para analisar o comportamento de compra dos clientes.</li>
                <li><strong>Buscar Cliente:</strong> Filtre para ver os dados de um cliente específico.</li>
            </ul>

            <h6><i class="bi bi-graph-up-arrow"></i> Indicadores de Clientes</h6>
            <p>Os cards no topo da página resumem o comportamento de compra dos seus clientes cadastrados no período:</p>
            <ul>
                <li><strong>Faturamento (Clientes Cad.):</strong> Mostra o quanto do seu faturamento total veio de clientes identificados.</li>
                <li><strong>Cliente Destaque:</strong> O cliente que mais gastou na sua farmácia no período.</li>
                <li><strong>Ticket Médio por Cliente:</strong> O valor médio gasto por cada cliente em cada compra.</li>
                <li><strong>Clientes Únicos:</strong> A quantidade de clientes cadastrados diferentes que realizaram compras no período.</li>
            </ul>

            <h6><i class="bi bi-trophy"></i> Ranking de Clientes</h6>
            <p>A tabela principal classifica seus clientes com base no <strong>valor total gasto</strong>, permitindo identificar quem são seus clientes "curva A". Use esta informação para criar ações de fidelização, promoções direcionadas e fortalecer o relacionamento com seus melhores compradores.</p>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
    </body>
</html>