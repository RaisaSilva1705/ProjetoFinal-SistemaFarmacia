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

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$filtro_id_caixa = (isset($_GET['id_caixa']) && $_GET['id_caixa'] !== 'Todos') ? $_GET['id_caixa'] : '';

$sql = "SELECT
            CA.Valor_Vendido,
            C.Caixa AS Nome_Caixa,
            F.Nome AS Nome_Funcionario,
            CA.Data_Abertura,
            CA.Data_Fechamento,
            CA.Saldo_Inicial,
            CA.Saldo_Final
        FROM CAIXAS_ABERTOS AS CA
        JOIN CAIXAS AS C ON CA.ID_Caixa = C.ID_Caixa
        JOIN FUNCIONARIOS AS F ON CA.ID_Funcionario = F.ID_Funcionario
        WHERE CA.Data_Fechamento IS NOT NULL
          AND DATE(CA.Data_Fechamento) BETWEEN ? AND ?";

$params = [$data_inicio, $data_fim];
$types = 'ss';

if (!empty($filtro_id_caixa)) {
    $sql .= " AND C.ID_Caixa = ?";
    $types .= 'i';
    $params[] = $filtro_id_caixa;
}
$sql .= " ORDER BY CA.Data_Fechamento DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$caixas_fechados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_vendido_periodo = array_sum(array_column($caixas_fechados, 'Valor_Vendido'));
$numero_fechamentos = count($caixas_fechados);
$valor_medio_fechamento = ($numero_fechamentos > 0) ? $total_vendido_periodo / $numero_fechamentos : 0;

$caixa_destaque = ['nome' => 'N/A', 'valor' => 0];
if ($numero_fechamentos > 0) {
    $vendas_por_caixa = [];
    foreach ($caixas_fechados as $caixa) {
        $nome = $caixa['Nome_Caixa'];
        $vendas_por_caixa[$nome] = ($vendas_por_caixa[$nome] ?? 0) + $caixa['Valor_Vendido'];
    }
    arsort($vendas_por_caixa);
    $caixa_destaque['nome'] = key($vendas_por_caixa);
    $caixa_destaque['valor'] = current($vendas_por_caixa);
}

$caixas_lista = $conn->query("SELECT ID_Caixa, Caixa FROM CAIXAS WHERE StatusCadastrado = 'Ativo' ORDER BY Caixa")->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Caixas Fechados</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatórios Operacionais</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Relatório de Caixas Fechados</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_caixas.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4"><label for="data_inicio">Período de:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-4"><label for="data_fim">Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-2">
                                    <label for="id_caixa">Caixa:</label>
                                    <select name="id_caixa" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <?php foreach ($caixas_lista as $caixa_opt): ?>
                                            <option value="<?= $caixa_opt['ID_Caixa'] ?>" <?= ($filtro_id_caixa == $caixa_opt['ID_Caixa']) ? 'selected' : '' ?>><?= htmlspecialchars($caixa_opt['Caixa']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Faturamento no Período</div><div class="card-body"><p class="card-text fs-2 fw-bold text-success">R$ <?= number_format($total_vendido_periodo, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Nº de Fechamentos</div><div class="card-body"><p class="card-text fs-2 fw-bold text-primary"><?= $numero_fechamentos ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Valor Médio por Fechamento</div><div class="card-body"><p class="card-text fs-2 fw-bold text-info">R$ <?= number_format($valor_medio_fechamento, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Caixa Destaque</div><div class="card-body"><p class="card-text fs-5"><?= htmlspecialchars($caixa_destaque['nome']) ?><br><span class="badge bg-secondary">R$ <?= number_format($caixa_destaque['valor'], 2, ',', '.') ?></span></p></div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Histórico de Fechamentos</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Caixa</th>
                                        <th>Operador</th>
                                        <th>Abertura</th>
                                        <th>Fechamento</th>
                                        <th class="text-end">Saldo Inicial</th>
                                        <th class="text-end">Valor Vendido</th>
                                        <th class="text-end">Saldo Final</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($caixas_fechados) > 0): ?>
                                        <?php foreach ($caixas_fechados as $caixa): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($caixa['Nome_Caixa']) ?></td>
                                                <td><?= htmlspecialchars($caixa['Nome_Funcionario']) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($caixa['Data_Abertura'])) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($caixa['Data_Fechamento'])) ?></td>
                                                <td class="text-end">R$ <?= number_format($caixa['Saldo_Inicial'], 2, ',', '.') ?></td>
                                                <td class="text-end fw-bold text-success">R$ <?= number_format($caixa['Valor_Vendido'], 2, ',', '.') ?></td>
                                                <td class="text-end">R$ <?= number_format($caixa['Saldo_Final'], 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center p-4">Nenhum fechamento de caixa encontrado para o período e filtros selecionados.</td></tr>
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