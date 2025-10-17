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

$filtro_id_categoria = (isset($_GET['id_categoria']) && $_GET['id_categoria'] !== 'Todos') ? $_GET['id_categoria'] : '';
$status_estoque = (isset($_GET['status_estoque']) && $_GET['status_estoque'] !== 'Todos') ? $_GET['status_estoque'] : '';

$sql = "SELECT
            P.Nome,
            P.Quant_Minima,
            SUM(E.Quantidade) AS Quantidade_Total,
            SUM(E.Quantidade * L.Preco_Custo) AS Valor_Custo_Total,
            SUM(E.Quantidade * L.Preco_Venda) AS Valor_Venda_Total
        FROM PRODUTOS P
        LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto
        LEFT JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote
        WHERE P.Status = 'Ativo'";

if (!empty($filtro_id_categoria)) 
    $sql .= " AND P.ID_Categoria = ?";
$sql .= " GROUP BY P.ID_Produto, P.Nome, P.Quant_Minima"; 

if (!empty($status_estoque)) {
    if ($status_estoque === 'Abaixo')
        $sql .= " HAVING Quantidade_Total < P.Quant_Minima";
    elseif ($status_estoque === 'Normal')
        $sql .= " HAVING Quantidade_Total >= P.Quant_Minima";
}
$sql .= " ORDER BY P.Nome ASC";

$stmt = $conn->prepare($sql);
if (!empty($filtro_id_categoria)) 
    $stmt->bind_param('i', $filtro_id_categoria);
$stmt->execute();
$produtos_estoque = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$valor_total_custo = 0;
$lucro_bruto_potencial = 0;
$itens_abaixo_minimo = 0;
$itens_em_estoque = count($produtos_estoque);

foreach ($produtos_estoque as $produto) {
    $custo_total_item = $produto['Valor_Custo_Total'] ?? 0;
    $venda_total_item = $produto['Valor_Venda_Total'] ?? 0;
    
    $valor_total_custo += $custo_total_item;
    $lucro_bruto_potencial += ($venda_total_item - $custo_total_item);

    if (($produto['Quantidade_Total'] ?? 0) < $produto['Quant_Minima']) 
        $itens_abaixo_minimo++;
}

$categorias_lista = $conn->query("SELECT ID_Categoria, Categoria FROM CATEGORIAS ORDER BY Categoria")->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Posição de Estoque</title>
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
                        <h2 class="m-0">Posição de Estoque</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form action="relatorio_estoque.php" method="GET">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label for="id_categoria">Filtrar por Categoria:</label>
                                    <select name="id_categoria" class="form-select">
                                        <option value="Todos">Todas</option>
                                        <?php foreach ($categorias_lista as $cat): ?>
                                            <option value="<?= $cat['ID_Categoria'] ?>" <?= ($filtro_id_categoria == $cat['ID_Categoria']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['Categoria']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label for="status_estoque">Filtrar por Status:</label>
                                    <select name="status_estoque" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <option value="Abaixo" <?= $status_estoque == 'Abaixo' ? 'selected' : '' ?>>Abaixo do Mínimo</option>
                                        <option value="Normal" <?= $status_estoque == 'Normal' ? 'selected' : '' ?>>Estoque Normal</option>
                                    </select>
                                </div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Valor do Estoque (Custo)</div><div class="card-body"><p class="card-text fs-2 fw-bold text-primary">R$ <?= number_format($valor_total_custo, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Lucro Bruto Potencial</div><div class="card-body"><p class="card-text fs-2 fw-bold text-success">R$ <?= number_format($lucro_bruto_potencial, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Itens em Estoque</div><div class="card-body"><p class="card-text fs-2 fw-bold text-info"><?= $itens_em_estoque ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Itens Abaixo do Mínimo</div><div class="card-body"><p class="card-text fs-2 fw-bold text-danger"><?= $itens_abaixo_minimo ?></p></div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Detalhes do Estoque</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-center">Qtd. Atual</th>
                                        <th class="text-center">Qtd. Mínima</th>
                                        <th class="text-end">Valor Custo Total</th>
                                        <th class="text-end">Valor Venda Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($produtos_estoque) > 0): ?>
                                        <?php foreach ($produtos_estoque as $produto):
                                            $custo_total = $produto['Valor_Custo_Total'] ?? 0;
                                            $venda_total = $produto['Valor_Venda_Total'] ?? 0;
                                            $quantidade_atual = $produto['Quantidade_Total'] ?? 0;
                                            $classe_alerta = ($quantidade_atual < $produto['Quant_Minima']) ? 'table-danger' : '';
                                        ?>
                                            <tr class="<?= $classe_alerta ?>">
                                                <td><?= htmlspecialchars($produto['Nome']) ?></td>
                                                <td class="text-center fw-bold"><?= intval($quantidade_atual) ?></td>
                                                <td class="text-center text-muted"><?= intval($produto['Quant_Minima']) ?></td>
                                                <td class="text-end">R$ <?= number_format($custo_total, 2, ',', '.') ?></td>
                                                <td class="text-end">R$ <?= number_format($venda_total, 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center p-4">Nenhum produto encontrado para os filtros selecionados.</td></tr>
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