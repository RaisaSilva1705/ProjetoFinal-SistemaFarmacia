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
$ordenar_por = $_GET['ordenar_por'] ?? 'maior_faturamento'; 

$order_by_sql = '';
switch ($ordenar_por) {
    case 'melhor_avaliacao':
        $order_by_sql = "ORDER BY Media_Avaliacoes DESC";
        break;
    case 'pior_avaliacao':
        $order_by_sql = "ORDER BY Media_Avaliacoes ASC";
        break;
    case 'menor_faturamento':
        $order_by_sql = "ORDER BY Faturamento_Total ASC";
        break;
    case 'maior_faturamento':
    default:
        $order_by_sql = "ORDER BY Faturamento_Total DESC";
        break;
}

$sql = "SELECT
            F.Nome, C.Cargo,
            COUNT(DISTINCT V.ID_Venda) AS Total_Vendas,
            COALESCE(SUM(V.Valor_Total), 0) AS Faturamento_Total,
            COALESCE(AVG(A.Nota), 0) AS Media_Avaliacoes
        FROM FUNCIONARIOS F
        LEFT JOIN VENDAS V ON F.ID_Funcionario = V.ID_Funcionario AND DATE(V.DataHora_Venda) BETWEEN ? AND ?
        LEFT JOIN AVALIACOES A ON V.ID_Venda = A.ID_Venda
        LEFT JOIN CARGOS C ON F.ID_Cargo = C.ID_Cargo";

$params = [$data_inicio, $data_fim];
$types = 'ss';

if (!empty($busca_nome)) {
    $sql .= " WHERE F.Nome LIKE ?";
    $types .= 's';
    $params[] = "%" . $busca_nome . "%";
}
$sql .= " GROUP BY F.ID_Funcionario, F.Nome, C.Cargo " . $order_by_sql; 

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$funcionarios_ranking = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// O restante do seu PHP de cálculo de KPIs continua igual e correto
$faturamento_total_periodo = array_sum(array_column($funcionarios_ranking, 'Faturamento_Total'));
$total_vendas_periodo = array_sum(array_column($funcionarios_ranking, 'Total_Vendas'));
$ticket_medio_venda = ($total_vendas_periodo > 0) ? $faturamento_total_periodo / $total_vendas_periodo : 0;
// Pega o primeiro do ranking (que agora pode ser por faturamento ou avaliação)
$funcionario_destaque = count($funcionarios_ranking) > 0 ? $funcionarios_ranking[0] : ['Nome' => 'N/A', 'Faturamento_Total' => 0];
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Desempenho de Funcionários</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatórios de Gestão de Pessoas</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Desempenho de Funcionários</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_funcionarios.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2"><label for="data_inicio">Período de:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-2"><label for="data_fim">Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-3"><label for="busca_nome">Buscar Funcionário:</label><input type="text" name="busca_nome" class="form-control" placeholder="Digite o nome..." value="<?= htmlspecialchars($busca_nome) ?>"></div>
                                <div class="col-md-3">
                                    <label for="ordenar_por">Ordenar por:</label>
                                    <select name="ordenar_por" id="ordenar_por" class="form-select">
                                        <option value="maior_faturamento" <?= $ordenar_por == 'maior_faturamento' ? 'selected' : '' ?>>Maior Faturamento</option>
                                        <option value="menor_faturamento" <?= $ordenar_por == 'menor_faturamento' ? 'selected' : '' ?>>Menor Faturamento</option>
                                        <option value="melhor_avaliacao" <?= $ordenar_por == 'melhor_avaliacao' ? 'selected' : '' ?>>Melhor Avaliação</option>
                                        <option value="pior_avaliacao" <?= $ordenar_por == 'pior_avaliacao' ? 'selected' : '' ?>>Pior Avaliação</option>
                                    </select>
                                </div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Faturamento Total</div><div class="card-body"><p class="card-text fs-2 fw-bold text-success">R$ <?= number_format($faturamento_total_periodo, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Funcionário Destaque</div><div class="card-body"><p class="card-text fs-5"><?= htmlspecialchars($funcionario_destaque['Nome']) ?><br><span class="badge bg-primary">R$ <?= number_format($funcionario_destaque['Faturamento_Total'], 2, ',', '.') ?></span></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Total de Vendas</div><div class="card-body"><p class="card-text fs-2 fw-bold text-secondary"><?= $total_vendas_periodo ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Ticket Médio por Venda</div><div class="card-body"><p class="card-text fs-2 fw-bold text-info">R$ <?= number_format($ticket_medio_venda, 2, ',', '.') ?></p></div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Ranking de Funcionários por Faturamento</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Funcionário</th>
                                        <th>Cargo</th>
                                        <th class="text-center">Nº de Vendas</th>
                                        <th class="text-end">Faturamento Total</th>
                                        <th class="text-center">Avaliação Média</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($funcionarios_ranking) > 0): 
                                        $rank = 1;
                                        foreach ($funcionarios_ranking as $funcionario): ?>
                                            <tr>
                                                <td><?= $rank++ ?></td>
                                                <td><?= htmlspecialchars($funcionario['Nome']) ?></td>
                                                <td><?= htmlspecialchars($funcionario['Cargo']) ?></td>
                                                <td class="text-center"><?= $funcionario['Total_Vendas'] ?></td>
                                                <td class="text-end fw-bold text-success">R$ <?= number_format($funcionario['Faturamento_Total'], 2, ',', '.') ?></td>
                                                <td class="text-center">
                                                    <?php if($funcionario['Media_Avaliacoes']): ?>
                                                        <span class="badge bg-primary fs-6">
                                                            <i class="bi bi-star-fill"></i>
                                                            <?= number_format($funcionario['Media_Avaliacoes'], 2, ',', '.') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center p-4">Nenhum funcionário realizou vendas no período selecionado.</td></tr>
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
            <h4><i class="bi bi-person-check-fill"></i> Relatório de Desempenho de Funcionários</h4>
            <hr>
            <p>Este relatório permite analisar e comparar a performance da sua equipe de vendas. Utilize esta ferramenta para identificar funcionários destaque, reconhecer bons desempenhos e encontrar oportunidades de treinamento.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Análise</h6>
            <ul>
                <li><strong>Período (De/Até):</strong> Defina o intervalo de datas para a análise.</li>
                <li><strong>Buscar Funcionário:</strong> Filtre o relatório para ver o desempenho de um único funcionário.</li>
                <li><strong>Ordenar por:</strong> Permite classificar o ranking de funcionários por diferentes critérios. Use esta opção para obter diferentes perspectivas sobre o desempenho, como identificar quem mais vende (faturamento) ou quem melhor atende (avaliação).</li>
            </ul>

            <h6><i class="bi bi-bar-chart-line-fill"></i> Indicadores Gerais da Equipe</h6>
            <p>Os cards no topo da página fornecem um resumo do desempenho de toda a equipe no período filtrado:</p>
            <ul>
                <li><strong>Faturamento Total:</strong> A soma de todas as vendas realizadas pela equipe.</li>
                <li><strong>Funcionário Destaque:</strong> O vendedor que gerou o maior faturamento no período.</li>
                <li><strong>Total de Vendas:</strong> O número total de transações concluídas pela equipe.</li>
                <li><strong>Ticket Médio por Venda:</strong> O valor médio de cada venda realizada, um indicador da qualidade do atendimento e da capacidade de agregar valor.</li>
            </ul>

            <h6><i class="bi bi-award-fill"></i> Ranking de Funcionários</h6>
            <p>A tabela principal classifica os funcionários com base no <strong>faturamento total</strong>, do maior para o menor. Para cada um, são apresentadas as seguintes métricas:</p>
            <ul>
                <li><strong>Nº de Vendas:</strong> Quantas transações o funcionário realizou.</li>
                <li><strong>Faturamento Total:</strong> O valor total gerado por suas vendas.</li>
                <li><strong>Avaliação Média:</strong> A nota média recebida dos clientes nos atendimentos, variando de 1 (Péssimo) a 5 (Excelente). Esta é uma métrica crucial da qualidade do serviço prestado.</li>
            </ul>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
    </body>
</html>