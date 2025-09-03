<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";

include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$busca_nome = $_GET['busca_nome'] ?? '';
$categoria_id = $_GET['categoria'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT
            P.ID_Produto,
            P.Nome,
            P.Status,
            P.EAN_GTIN,
            P.Quant_Minima,
            F.Nome_Fantasia AS Nome_Fornecedor,
            C.Categoria,
            SUM(E.Quantidade) AS Quantidade_Total,
            MAX(L.Preco_Venda) AS Preco_Atual
        FROM PRODUTOS P 
        LEFT JOIN CATEGORIAS C ON C.ID_Categoria = P.ID_Categoria
        LEFT JOIN FORNECEDORES F ON F.ID_Fornecedor = P.ID_Fornecedor
        LEFT JOIN LOTES L ON L.ID_Produto = P.ID_Produto
        LEFT JOIN ESTOQUE E ON E.ID_Lote = L.ID_Lote";

$conditions = [];
$params = [];
$types = '';

if (!empty($busca_nome)) {
    $conditions[] = "(P.Nome LIKE ? OR P.EAN_GTIN LIKE ?)";
    $types .= 'ss';
    $params[] = "%" . $busca_nome . "%";
    $params[] = "%" . $busca_nome . "%";
}

if (!empty($categoria_id)) {
    $conditions[] = "P.ID_Categoria = ?";
    $types .= 'i';
    $params[] = $categoria_id;
}

if (!empty($status)) {
    $conditions[] = "P.Status = ?";
    $types .= 's';
    $params[] = $status;
}

if (count($conditions) > 0)
    $sql .= " WHERE " . implode(' AND ', $conditions);

$sql .= " GROUP BY P.ID_Produto ORDER BY P.Nome ASC";

$stmt = $conn->prepare($sql);
if (!empty($params))
    $stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Listagem de Produtos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Sidebar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Listagem de Produtos</h3>
                </div>
    
                <div class="container mt-3 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Lista de Produto</h2>
                        <a href="cadastrar_produto.php" class="btn btn-primary">Cadastrar</a>
                    </div>
    
                    <div class="card card-body mb-4">
                        <form method="GET" action="produtos.php" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="busca_nome" class="form-label">Nome ou Cód. de Barras</label>
                                    <input type="text" name="busca_nome" id="busca_nome" class="form-control" placeholder="Buscar por nome ou EAN..." value="<?= htmlspecialchars($_GET['busca_nome'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="categoria" class="form-label">Categoria</label>
                                    <select name="categoria" id="categoria" class="form-select">
                                        <option value="">Todas</option>
                                        <?php
                                        $categorias_result = $conn->query("SELECT ID_Categoria, Categoria FROM CATEGORIAS ORDER BY Categoria");
                                        while ($cat = $categorias_result->fetch_assoc()) {
                                            $selected = ($_GET['categoria'] ?? '') == $cat['ID_Categoria'] ? 'selected' : '';
                                            echo "<option value='{$cat['ID_Categoria']}' $selected>{$cat['Categoria']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
    
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="Ativo" <?= ($_GET['status'] ?? '') == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="Inativo" <?= ($_GET['status'] ?? '') == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
    
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Produto</th>
                                    <th scope="col">Cód. Barras</th>
                                    <th scope="col">Categoria</th>
                                    <th scope="col">Estoque</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Preço</th>
                                    <th scope="col" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) { // quebra de página após 20 resultados
                                            $classe_estoque = ($row['Quantidade_Total'] <= $row['Quant_Minima']) ? 'table-danger' : '';
                                            $badge_class = $row['Status'] === 'Ativo' ? 'table-success' : 'table-danger';
    
                                            echo "<tr class='{$classe_estoque}'>";
                                                echo '<td>' . htmlspecialchars($row["Nome"]) . '</td>';
                                                echo '<td>' . htmlspecialchars($row["EAN_GTIN"]) . '</td>';
                                                echo '<td>' . htmlspecialchars($row["Categoria"]) . '</td>';
                                                echo '<td>' . $row["Quantidade_Total"] . '</td>';
                                                echo "<td class='{$badge_class}'>" . htmlspecialchars($row["Status"]) . "</td>";
                                                echo '<td>R$ ' . number_format($row['Preco_Atual'] ?? 0.00, 2, ',', '.') . '</td>';
                                                echo '<td>
                                                        <a href="editar_produto.php?codigo=' . $row["ID_Produto"] . '" class="btn btn-warning btn-sm">Editar</a>
                                                        <a href="inativar_produto.php?codigo=' . $row["ID_Produto"] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Tem certeza que deseja inativar este produto?\')">Inativar</a>
                                                    </td>';
                                            echo '</tr>';
                                        }
                                    } 
                                    else {
                                        echo '<tr><td colspan="7" class="text-center">Nenhum produto cadastrado.</td></tr>';
                                    }
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

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function mostrarToast(texto, tipo = 'success', titulo = 'Notificação') {
                const toastLiveExample = document.getElementById('liveToast');
                const toastHeader = toastLiveExample.querySelector('.toast-header');
                
                // Define o título padrão baseado no tipo, se não for fornecido
                if (titulo === 'Notificação') 
                    titulo = ucfirst(tipo === 'danger' ? 'Erro' : (tipo === 'warning' ? 'Atenção' : 'Sucesso'));
                
                const headerClass = `text-bg-${tipo}`;

                document.getElementById('toastTitulo').innerText = titulo;
                document.getElementById('toastCorpo').innerText = texto;
                
                // Remove classes de cor antigas e adiciona a nova
                toastHeader.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning', 'text-bg-info');
                toastHeader.classList.add(headerClass);

                const toast = new bootstrap.Toast(toastLiveExample);
                toast.show();
            }

            // Função auxiliar para deixar a primeira letra maiúscula (o PHP faz isso, o JS não)
            function ucfirst(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }

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
