<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$id_categoria = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_categoria) {
    $_SESSION['msg'] = ['texto' => 'ID da categoria inválido ou não fornecido.', 'tipo' => 'warning'];
    header('Location: categorias_despesa.php');
    exit();
}

$stmt_cat = $conn->prepare("SELECT Nome_Categoria FROM DESPESAS_CATEGORIAS WHERE ID_Categoria_Despesa = ?");
$stmt_cat->bind_param("i", $id_categoria);
$stmt_cat->execute();
$result_cat = $stmt_cat->get_result();
if ($result_cat->num_rows === 0) {
    $_SESSION['msg'] = ['texto' => 'Categoria não encontrada.', 'tipo' => 'danger'];
    header('Location: categorias_despesa.php');
    exit();
}
$nome_categoria = $result_cat->fetch_assoc()['Nome_Categoria'];
$stmt_cat->close();

$sql_stats = "SELECT
                SUM(Valor) as total_gasto,
                AVG(Valor) as media_gasto,
                MAX(Valor) as maior_gasto,
                COUNT(ID_Despesa) as total_registros
              FROM DESPESAS
              WHERE ID_Categoria_Despesa = ? AND Status = 'Paga' AND Status_Registro = 'Ativo'";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $id_categoria);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc() ?? [];
$stats['total_gasto'] = $stats['total_gasto'] ?? 0;
$stats['media_gasto'] = $stats['media_gasto'] ?? 0;
$stats['maior_gasto'] = $stats['maior_gasto'] ?? 0;
$stats['total_registros'] = $stats['total_registros'] ?? 0;
$stmt_stats->close();

$sql_hist = "SELECT Descricao, Valor, Data_Vencimento, Data_Pagamento
             FROM DESPESAS
             WHERE ID_Categoria_Despesa = ? AND Status = 'Paga' AND Status_Registro = 'Ativo'
             ORDER BY Data_Pagamento DESC";
$stmt_hist = $conn->prepare($sql_hist);
$stmt_hist->bind_param("i", $id_categoria);
$stmt_hist->execute();
$historico = $stmt_hist->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_hist->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Análise de Categoria: <?php echo htmlspecialchars($nome_categoria); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Análise de Despesas</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">
                            Análise da Categoria: <span class="fw-bold"><?php echo htmlspecialchars($nome_categoria); ?></span>
                        </h2>
                        <a href="categorias_despesa.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left-circle"></i> Voltar
                        </a>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-md-3">
                            <div class="card text-center h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Total Gasto</h5>
                                    <p class="card-text fs-2 fw-bold text-danger">R$ <?php echo number_format($stats['total_gasto'], 2, ',', '.'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Média por Pagamento</h5>
                                    <p class="card-text fs-2 fw-bold text-primary">R$ <?php echo number_format($stats['media_gasto'], 2, ',', '.'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Maior Gasto Único</h5>
                                    <p class="card-text fs-2 fw-bold text-warning">R$ <?php echo number_format($stats['maior_gasto'], 2, ',', '.'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-muted">Nº de Pagamentos</h5>
                                    <p class="card-text fs-2 fw-bold text-info"><?php echo $stats['total_registros']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h3 class="mb-3">Histórico de Pagamentos</h3>
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Descrição</th>
                                        <th class="text-end">Valor Pago</th>
                                        <th>Data do Pagamento</th>
                                        <th>Data de Vencimento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($historico) > 0): ?>
                                        <?php foreach ($historico as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['Descricao']); ?></td>
                                                <td class="text-end fw-bold">R$ <?php echo number_format($item['Valor'], 2, ',', '.'); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($item['Data_Pagamento'])); ?></td>
                                                <td><?php echo $item['Data_Vencimento'] ? date('d/m/Y', strtotime($item['Data_Vencimento'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center p-4">Nenhum pagamento registrado para esta categoria.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>