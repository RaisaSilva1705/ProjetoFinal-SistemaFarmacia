<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$busca_nome = $_GET['busca_nome'] ?? ''; 

$sql = "SELECT
            F.ID_Funcionario, F.Nome, C.Cargo,
            COUNT(V.ID_Venda) AS Total_Vendas,
            SUM(V.Valor_Total) AS Faturamento_Total
        FROM FUNCIONARIOS F
        JOIN VENDAS V ON F.ID_Funcionario = V.ID_Funcionario
        JOIN CARGOS C ON F.ID_Cargo = C.ID_Cargo";

$conditions = [];
$params = [];
$types = '';

$conditions[] = "DATE(V.DataHora_Venda) BETWEEN ? AND ?";
$types .= 'ss';
$params[] = $data_inicio;
$params[] = $data_fim;

if (!empty($busca_nome)) {
    $conditions[] = "F.Nome LIKE ?";
    $types .= 's';
    $params[] = "%" . $busca_nome . "%";
}

$sql .= " WHERE " . implode(' AND ', $conditions);
$sql .= " GROUP BY F.ID_Funcionario, F.Nome, C.Cargo ORDER BY Faturamento_Total DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$funcionarios_ranking = $result->fetch_all(MYSQLI_ASSOC);

// ----- CARDS DE RESUMO -----
$funcionario_destaque = $funcionarios_ranking[0] ?? ['Nome_Funcionario' => 'N/A', 'Faturamento_Total' => 0];
$total_vendedores_ativos = count($funcionarios_ranking); 
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Funcionários</title>
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
                    <h3>Relatório de Desempenho de Funcionários</h3>
                </div>
                <div class="container p-4">
                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_funcionarios.php">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="data_inicio">Data Início:</label>
                                    <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="data_fim">Data Fim:</label>
                                    <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="busca_nome">Buscar Funcionário Específico:</label>
                                    <input type="text" id="busca_nome" name="busca_nome" class="form-control" placeholder="Digite o nome do funcionário..." value="<?= htmlspecialchars($busca_nome) ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card text-center bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Funcionário Destaque do Período</h5>
                                    <p class="card-text fs-4"><?= htmlspecialchars($funcionario_destaque['Nome']) ?></p>
                                    <span class="badge bg-success fs-6">Total Faturado: R$ <?= number_format($funcionario_destaque['Faturamento_Total'], 2, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card text-center h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Vendedores com Vendas no Período</h5>
                                    <br>
                                    <p class="card-text fs-4"><?= $total_vendedores_ativos ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            Ranking de Funcionários por Faturamento
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">Imprimir Relatório</button>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($funcionarios_ranking) > 0): 
                                        $rank = 1;
                                    ?>
                                        <?php foreach ($funcionarios_ranking as $funcionario): ?>
                                            <tr>
                                                <td><?= $rank++ ?></td>
                                                <td><?= htmlspecialchars($funcionario['Nome']) ?></td>
                                                <td><?= htmlspecialchars($funcionario['Cargo']) ?></td>
                                                <td class="text-center"><?= $funcionario['Total_Vendas'] ?></td>
                                                <td class="text-end fw-bold">R$ <?= number_format($funcionario['Faturamento_Total'], 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center">Nenhum funcionário realizou vendas no período selecionado.</td></tr>
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