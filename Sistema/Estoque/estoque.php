<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$busca_nome = $_GET['busca_nome'] ?? '';
$quantidade = $_GET['quantidade'] ?? '';
$status_estoque = $_GET['status'] ?? '';

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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            
                <div class="container p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Lista de Produtos</h2>
                        <div>
                            <a href="entrada_estoque.php" class="btn btn-primary">Entrada</a>
                            <a href="saida_estoque.php" class="btn btn-danger">Saída</a>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="estoque.php" class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="busca_nome" class="form-label">Nome ou Cód. de Barras</label>
                                    <input type="text" name="busca_nome" id="busca_nome" class="form-control" placeholder="Buscar por nome ou EAN..." value="<?= htmlspecialchars($_GET['busca_nome'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="quantidade" class="form-label">Quantidade</label>
                                    <select name="quantidade" id="quantidade" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="10" <?= ($_GET['quantidade'] ?? '') == '10' ? 'selected' : '' ?>>Acima de 10</option>
                                        <option value="50" <?= ($_GET['quantidade'] ?? '') == '50' ? 'selected' : '' ?>>Acima de 50</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status do Estoque</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">Todos</option>
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
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) { // quebra de página após 20 resultados
                                        if($row["Quantidade_Total"] <= $row["Quant_Minima"]){
                                            $class = ($row["Quantidade_Total"] == $row["Quant_Minima"]) ? "table-warning" : '';
                                            $classe_estoque = ($row["Quantidade_Total"] <= $row["Quant_Minima"]) ? "table-danger" : '' ;
                                        }
                                        else {
                                            $class = "table-success";
                                            $classe_estoque = '';
                                        }
        
                                        echo "<tr class='{$classe_estoque}'>";
                                            echo '<td>' . $row["Nome"] . '</td>';
                                            echo '<td>' . $row["EAN_GTIN"] . '</td>';
                                            echo '<td>' . $row["Categoria"] . '</td>';
                                            echo '<td class="' . $class . '">' . $row["Quantidade_Total"] . '</td>';
                                            echo '<td>' . $row["Quant_Minima"] . '</td>';
                                            echo '<td>
                                                    <a href="lotes_estoque.php?codigo=' . $row['ID_Produto'] . '" class="btn btn-warning btn-sm">Conferir Lotes</a>
                                                    <a href="saida_estoque.php?codigo=' . $row['ID_Produto'] . '" class="btn btn-danger btn-sm">Saída</a>
                                                </td>';
                                        echo '</tr>';
                                    }
                                } 
                                else 
                                    echo '<tr><td colspan="10" class="text-center">Nenhum produto cadastrado.</td></tr>';
                                ?>
                            </tbody>
                        </table>
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