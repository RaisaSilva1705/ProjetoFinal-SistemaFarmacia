<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'ESTOQUE_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$busca_nome = $_GET['busca_nome'] ?? '';
$categoria_id = (isset($_GET['categoria']) && $_GET['categoria'] !== 'Todos') ? $_GET['categoria'] : '';
$quantidade = (isset($_GET['quantidade']) && $_GET['quantidade'] !== 'Todos') ? $_GET['quantidade'] : '';
$status_estoque = (isset($_GET['status']) && $_GET['status'] !== 'Todos') ? $_GET['status'] : '';

$sql = "SELECT
            P.ID_Produto,
            P.Nome,
            P.EAN_GTIN,
            C.Categoria,
            SUM(E.Quantidade) AS Quantidade_Total,
            P.Quant_Minima
        FROM PRODUTOS P 
        LEFT JOIN CATEGORIAS C ON P.ID_Categoria = C.ID_Categoria
        LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto
        LEFT JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote";

$where_conditions = [];
$having_conditions = [];
$params = [];
$types = '';

if (!empty($busca_nome)) {
    $where_conditions[] = "(P.Nome LIKE ? OR P.EAN_GTIN LIKE ?)";
    $types .= 'ss';
    $params[] = "%" . $busca_nome . "%";
    $params[] = "%" . $busca_nome . "%";
}

if (!empty($categoria_id)) {
    $where_conditions[] = "P.ID_Categoria = ?";
    $types .= 'i';
    $params[] = intval($categoria_id);
}
if (!empty($quantidade)) {
    $having_conditions[] = "SUM(E.Quantidade) > ?";
    $types .= 'i';
    $params[] = intval($quantidade);
}
if (!empty($status_estoque)) {
    if ($status_estoque === 'Abaixo') 
        $having_conditions[] = "SUM(E.Quantidade) <= P.Quant_Minima";
    elseif ($status_estoque === 'Acima')
        $having_conditions[] = "SUM(E.Quantidade) > P.Quant_Minima";
}

if (count($where_conditions) > 0) 
    $sql .= " WHERE " . implode(' AND ', $where_conditions);

$sql .= " GROUP BY P.ID_Produto";

if (count($having_conditions) > 0) 
    $sql .= " HAVING " . implode(' AND ', $having_conditions);

$sql .= " ORDER BY P.Nome ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) 
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Estoque</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Gerenciamento de ESTOQUE</h3>
                </div>
            
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Lista de Produtos</h2>
                        <div>
                            <a href="entrada_estoque.php" class="btn btn-primary">Entrada</a>
                            <a href="saida_estoque.php" class="btn btn-danger">Saída</a>
                            <a href="../Relatorios/relatorio_estoque.php" class="btn btn-outline-secondary">Ver Relatório</a>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="estoque.php" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="busca_nome" class="form-label">Nome ou Cód. de Barras</label>
                                    <input type="text" name="busca_nome" id="busca_nome" class="form-control" placeholder="Buscar por nome ou EAN..." value="<?= htmlspecialchars($_GET['busca_nome'] ?? '') ?>">
                                </div>

                                <div class="col-md-2">
                                    <label for="categoria" class="form-label">Categoria</label>
                                    <select name="categoria" id="categoria" class="form-select">
                                        <option value="Todos">Todas</option>
                                        <?php
                                        $categorias_result = $conn->query("SELECT ID_Categoria, Categoria FROM CATEGORIAS ORDER BY Categoria");
                                        while ($cat = $categorias_result->fetch_assoc()) {
                                            $selected = ($_GET['categoria'] ?? '') == $cat['ID_Categoria'] ? 'selected' : '';
                                            echo "<option value='{$cat['ID_Categoria']}' $selected>{$cat['Categoria']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="quantidade" class="form-label">Quantidade</label>
                                    <select name="quantidade" id="quantidade" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <option value="10" <?= ($_GET['quantidade'] ?? '') == '10' ? 'selected' : '' ?>>Acima de 10</option>
                                        <option value="50" <?= ($_GET['quantidade'] ?? '') == '50' ? 'selected' : '' ?>>Acima de 50</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status do Estoque</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <option value="Acima" <?= ($_GET['status'] ?? '') == 'Acima' ? 'selected' : '' ?>>Acima do Estoque Min.</option>
                                        <option value="Abaixo" <?= ($_GET['status'] ?? '') == 'Abaixo' ? 'selected' : '' ?>>Abaixo do Estoque Min.</option>
                                    </select>
                                </div>
    
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Tabela de Estoque -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Produto</th>
                                    <th scope="col">Cód. Barras</th>
                                    <th scope="col">Categoria</th>
                                    <th scope="col">Estoque Atual</th>
                                    <th scope="col">Quant. Mínima</th>
                                    <th scope="col" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0):
                                    while ($row = $result->fetch_assoc()):
                                        $qtd_total = $row["Quantidade_Total"] ?? 0;
                                        $qtd_minima = $row["Quant_Minima"];
                                        $classe_alerta = '';

                                        if ($qtd_total == 0) 
                                            $classe_alerta = 'table-danger text-muted';
                                        elseif ($qtd_total < $qtd_minima) 
                                            $classe_alerta = 'table-danger';
                                        elseif ($qtd_total == $qtd_minima) 
                                            $classe_alerta = 'table-warning';
                                ?>
                                    <tr class="<?= $classe_alerta ?>">
                                        <td><?= htmlspecialchars($row["Nome"]) ?></td>
                                        <td><?= htmlspecialchars($row["EAN_GTIN"]) ?></td>
                                        <td><?= htmlspecialchars($row["Categoria"]) ?></td>
                                        <td class="fw-bold"><?= intval($qtd_total) ?></td>
                                        <td><?= intval($qtd_minima) ?></td>
                                        <td class="text-center">
                                            <a href="lotes_estoque.php?id=<?= $row['ID_Produto'] ?>" class="btn btn-info btn-sm" title="Conferir Lotes">
                                                <i class="bi bi-list-ol"></i>
                                            </a>
                                            <?php if ($qtd_total > 0): ?>
                                                <a href="saida_estoque.php?id_produto=<?= $row['ID_Produto'] ?>" class="btn btn-danger btn-sm" title="Registrar Saída Manual">
                                                    <i class="bi bi-box-arrow-up"></i>
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-danger btn-sm" title="Registrar Saída Manual" disabled>
                                                    <i class="bi bi-box-arrow-up"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <tr><td colspan="6" class="text-center p-4">Nenhum produto encontrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-archive-fill"></i> Gestão de Estoque</h4>
            <hr>
            <p>Esta é a tela de controle central do seu inventário. Ela oferece uma visão geral da quantidade de cada produto em estoque e serve como ponto de partida para todas as operações de gerenciamento.</p>

            <h6><i class="bi bi-arrow-down-up"></i> Ações Principais</h6>
            <ul>
                <li><strong>Entrada:</strong> Leva à tela de registro de entrada de mercadorias, onde você pode lançar notas fiscais de compra (manualmente ou via XML).</li>
                <li><strong>Saída:</strong> Leva à tela de registro de saída manual, utilizada para dar baixa em produtos por motivos que não são vendas (ex: perdas, avarias, uso interno).</li>
                <li><strong>Ver Relatório:</strong> Acessa o relatório de posição de estoque, com uma análise financeira detalhada do seu inventário.</li>
            </ul>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Busca</h6>
            <p>Utilize os filtros para localizar produtos rapidamente e analisar o estado do seu estoque:</p>
            <ul>
                <li><strong>Nome ou Cód. de Barras:</strong> Encontre um produto específico.</li>
                <li><strong>Quantidade:</strong> Filtre produtos com estoque acima de um determinado número.</li>
                <li><strong>Status do Estoque:</strong> Filtre para ver apenas produtos que estão <strong>Abaixo</strong> ou <strong>Acima</strong> do estoque mínimo definido.</li>
            </ul>

            <h6><i class="bi bi-table"></i> Lista de Produtos e Alertas Visuais</h6>
            <p>A tabela é colorida para fornecer alertas rápidos sobre o estado do estoque:</p>
            <ul>
                <li><strong class="text-danger">Linha Vermelha:</strong> Indica que o estoque do produto está <strong>abaixo da quantidade mínima</strong>. A reposição é urgente.</li>
                <li><strong class="text-warning">Linha Amarela:</strong> Indica que o estoque atingiu <strong>exatamente a quantidade mínima</strong>. A reposição é recomendada.</li>
            </ul>
            
            <h6><i class="bi bi-pencil-fill"></i> Ações na Lista</h6>
            <ul>
                <li><i class="bi bi-list-ol text-info"></i> <strong>Conferir Lotes:</strong> Abre uma tela detalhada com todos os lotes deste produto, mostrando suas respectivas validades e quantidades.</li>
                <li><i class="bi bi-box-arrow-up text-danger"></i> <strong>Registrar Saída Manual:</strong> Atalho rápido para a tela de saída, já com o produto pré-selecionado.</li>
            </ul>
        </div>
        
        <?php include_once DEV_PATH . 'Views/toast.php'?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
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