<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$busca_texto = $_GET['busca_texto'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT ID_Cliente, Nome, Documento, Tel, Email, Status FROM CLIENTES";

$conditions = [];
$params = [];
$types = '';

if (!empty($busca_texto)) {
    $conditions[] = "(Nome LIKE ? OR Documento LIKE ?)";
    $types .= 'ss';
    $params[] = "%" . $busca_texto . "%";
    $params[] = "%" . $busca_texto . "%";
}
if (!empty($status)) {
    $conditions[] = "Status = ?";
    $types .= 's';
    $params[] = $status;
}

if (count($conditions) > 0) 
    $sql .= " WHERE " . implode(' AND ', $conditions);

$sql .= " ORDER BY Nome ASC";

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
        <title>Clientes</title>
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
                    <h3>Gerenciamento de CLIENTES</h3>
                </div>
            
                <div class="container p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Lista de Clientes</h2>
                        <div>
                            <a href="cadastrar_cliente.php" class="btn btn-primary">Cadastrar Novo Cliente</a>
                            <a href="../Relatorios/relatorio_clientes.php" class="btn btn-outline-secondary">Ver Relatório</a>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="clientes.php">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label for="busca_texto" class="form-label">Buscar por Nome ou Documento (CPF/CNPJ)</label>
                                    <input type="text" name="busca_texto" id="busca_texto" class="form-control" value="<?= htmlspecialchars($busca_texto) ?>">
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

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nome</th>
                                    <th>Documento</th>
                                    <th>Telefone</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['Nome']) ?></td>
                                            <td><?= htmlspecialchars($row['Documento']) ?></td>
                                            <td><?= htmlspecialchars($row['Tel']) ?></td>
                                            <td><?= htmlspecialchars($row['Email']) ?></td>
                                            <td <?php $badge_class = $row['Status'] == 'Ativo' ? 'table-success' : 'table-danger'; echo "class='{$badge_class}'"?>>
                                                <?= htmlspecialchars($row['Status']) ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="detalhes_cliente.php?id=<?= $row['ID_Cliente'] ?>" class="btn btn-success btn-sm">Ver Detalhes</a>
                                                <a href="editar_cliente.php?id=<?= $row['ID_Cliente'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Nenhum cliente encontrado.</td>
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