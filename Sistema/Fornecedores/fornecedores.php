<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$order_by = "ID_Funcionario";
$order_dir = "ASC";  
$status_filter = ""; 

$busca_nome = $_GET['busca_nome'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT ID_Fornecedor, Nome_Fantasia, CNPJ, Tel, Email, Status FROM FORNECEDORES";

$conditions = [];
$params = [];
$types = '';

if (!empty($busca_nome)) {
    $conditions[] = "(Nome_Fantasia LIKE ? OR CNPJ LIKE ?)";
    $types .= 'ss';
    $params[] = "%" . $busca_nome . "%";
    $params[] = "%" . $busca_nome . "%";
}
if (!empty($status)) {
    $conditions[] = "Status = ?";
    $types .= 's';
    $params[] = $status;
}

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

$sql .= " ORDER BY Nome_Fantasia ASC";

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
        <title>Fornecedores</title>
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
                    <h3>Gerenciamento de FORNECEDORES</h3>
                </div>
            
                <div class="container p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Lista de Fornecedores</h2>
                        <div>
                            <a href="cadastrar_fornecedor.php" class="btn btn-primary">Cadastrar</a>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="fornecedores.php">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label for="busca_nome" class="form-label">Buscar por Nome Fantasia ou CNPJ</label>
                                    <input type="text" name="busca_nome" id="busca_nome" class="form-control" value="<?= htmlspecialchars($busca_nome) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="Ativo" <?= $status == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="Inativo" <?= $status == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Tabela de Fornecedor -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Nome Fantasia</th>
                                    <th scope="col">CNPJ</th>
                                    <th scope="col">Telefone</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['Nome_Fantasia']) ?></td>
                                            <td><?= htmlspecialchars($row['CNPJ']) ?></td>
                                            <td><?= htmlspecialchars($row['Tel']) ?></td>
                                            <td><?= htmlspecialchars($row['Email']) ?></td>
                                            <td <?php $badge_class = $row['Status'] == 'Ativo' ? 'table-success' : 'table-danger'; echo "class='{$badge_class}'" ?>>
                                                <?= htmlspecialchars($row['Status']) ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="detalhes_fornecedor.php?id=<?= $row['ID_Fornecedor'] ?>" class="btn btn-success btn-sm">Detalhes</a>
                                                <a href="editar_fornecedor.php?id=<?= $row['ID_Fornecedor'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Nenhum fornecedor encontrado.</td>
                                    </tr>
                                <?php endif; ?>
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