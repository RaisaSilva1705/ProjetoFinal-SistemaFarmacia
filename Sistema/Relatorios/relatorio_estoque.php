<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$id_categoria = $_GET['id_categoria'] ?? '';
$status_estoque = $_GET['status_estoque'] ?? '';

$sql = "SELECT
            P.Nome,
            C.Categoria,
            P.Quant_Minima,
            SUM(E.Quantidade) AS Quantidade_Total,
            AVG(L.Preco_Custo) AS Preco_Custo_Medio,
            SUM(E.Quantidade * L.Preco_Custo) AS Valor_Custo_Total,
            MAX(L.Preco_Venda) AS Preco_Venda_Atual,
            SUM(E.Quantidade * L.Preco_Venda) AS Valor_Venda_Total
        FROM PRODUTOS P
        LEFT JOIN CATEGORIAS C ON P.ID_Categoria = C.ID_Categoria
        LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto
        LEFT JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote
        WHERE P.Status = 'Ativo'";

$conditions = [];
$params = [];
$types = '';

if (!empty($id_categoria)) {
    $conditions[] = "P.ID_Categoria = ?";
    $types .= 'i';
    $params[] = $id_categoria;
}

if (count($conditions) > 0)
    $sql .= " AND " . implode(' AND ', $conditions);

$sql .= " GROUP BY P.ID_Produto";

if (!empty($status_estoque)) {
    if ($status_estoque === 'Abaixo')
        $sql .= " HAVING Quantidade_Total <= P.Quant_Minima";
    elseif ($status_estoque === 'Normal')
        $sql .= " HAVING Quantidade_Total > P.Quant_Minima";
}

$sql .= " ORDER BY P.Nome ASC";

$stmt = $conn->prepare($sql);
if (!empty($params))
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$produtos_estoque = $result->fetch_all(MYSQLI_ASSOC);

// ----- CARDS DE RESUMO -----
$valor_total_custo = 0;
$valor_total_venda = 0;
foreach ($produtos_estoque as $produto) {
    $valor_total_custo += $produto['Valor_Custo_Total'];
    $valor_total_venda += $produto['Valor_Venda_Total'];
}
$lucro_bruto_potencial = $valor_total_venda - $valor_total_custo;
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Estoque</title>
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
                    <h3>Relatório de Posição de Estoque</h3>
                </div>
                <div class="container p-4">
                    <div class="card card-body mb-4 no-print">
                        <form action="relatorio_estoque.php" method="GET">
                            <div class="row align-items-end">
                                <div class="col-md-5">
                                    <label for="id_categoria" class="form-label">Filtrar por Categoria</label>
                                    <select name="id_categoria" id="id_categoria" class="form-select">
                                        <option value="">Todas as Categorias</option>
                                        <?php
                                        $categorias_result = $conn->query("SELECT ID_Categoria, Categoria FROM CATEGORIAS ORDER BY Categoria");
                                        while ($cat = $categorias_result->fetch_assoc()){
                                            $selected = ($id_categoria == $cat['ID_Categoria']) ? 'selected' : '';
                                            echo "<option value='{$cat['ID_Categoria']}' {$selected}>{$cat['Categoria']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="status_estoque" class="form-label">Filtrar por Status do Estoque</label>
                                    <select name="status_estoque" id="status_estoque" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="Abaixo" <?= $status_estoque == 'Abaixo' ? 'selected' : '' ?>>Abaixo do Mínimo</option>
                                        <option value="Normal" <?= $status_estoque == 'Normal' ? 'selected' : '' ?>>Normal ou Acima</option>
                                    </select>
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
                                    <h5 class="card-title">Valor do Estoque (Custo)</h5>
                                    <p class="card-text fs-4">R$ <?= number_format($valor_total_custo, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Valor do Estoque (Venda)</h5>
                                    <p class="card-text fs-4">R$ <?= number_format($valor_total_venda, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Lucro Bruto Potencial</h5>
                                    <p class="card-text fs-4 text-success fw-bold">R$ <?= number_format($lucro_bruto_potencial, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            Posição de Estoque
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">Imprimir Relatório</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Produtos</th>
                                        <th>Qtd. Atual</th>
                                        <th>Custo Médio</th>
                                        <th>Valor Custo Total</th>
                                        <th>Valor Atual</th>
                                        <th>Valor Venda Total</th>
                                        <th>Margem Média</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($produtos_estoque) > 0): ?>
                                        <?php foreach ($produtos_estoque as $produto):
                                            $custo_total = $produto['Valor_Custo_Total'] ?? 0;
                                            $venda_total = $produto['Valor_Venda_Total'] ?? 0;
                                            $margem = ($custo_total > 0) ? (($venda_total / $custo_total) - 1) * 100 : 0;
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($produto['Nome']) ?></td>
                                                <td><?= intval($produto['Quantidade_Total']) ?></td>
                                                <td>R$ <?= number_format($produto['Preco_Custo_Medio'] ?? 0, 2, ',', '.') ?></td>
                                                <td>R$ <?= number_format($custo_total, 2, ',', '.') ?></td>
                                                <td>R$ <?= number_format($produto['Preco_Venda_Atual'] ?? 0, 2, ',', '.') ?></td>
                                                <td>R$ <?= number_format($venda_total, 2, ',', '.') ?></td>
                                                <td><?= number_format($margem, 2, ',', '.') ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center">Nenhum produto encontrado para os filtros selecionados.</td></tr>
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