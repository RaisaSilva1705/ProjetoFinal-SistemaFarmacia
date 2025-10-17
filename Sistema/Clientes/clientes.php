<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CLIENTES_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$busca_texto = $_GET['busca_texto'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT 
            C.ID_Cliente, C.Nome, C.Tel, C.Email, C.Status,
            (SELECT CD.Numero FROM CLIENTES_DOCUMENTOS CD WHERE CD.ID_Cliente = C.ID_Cliente AND (CD.Tipo = 'CPF' OR CD.Tipo = 'CNPJ') LIMIT 1) AS DocumentoPrincipal
        FROM CLIENTES C";

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
        <title>Gestão de Clientes</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php';?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Clientes</h3>
                </div>
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Gestão de Clientes</h2>
                        <div>
                            <a href="cadastrar_cliente.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Novo Cliente</a>
                            <a href="../Relatorios/relatorio_clientes.php" class="btn btn-outline-secondary"><i class="bi bi-bar-chart-line-fill"></i> Ver Relatório</a>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="clientes.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6"><label for="busca_texto">Buscar por Nome ou Documento</label><input type="text" name="busca_texto" id="busca_texto" class="form-control" value="<?= htmlspecialchars($busca_texto) ?>"></div>
                                <div class="col-md-4"><label for="status">Status</label><select name="status" id="status" class="form-select"><option value="">Todos</option><option value="Ativo" <?= $status == 'Ativo' ? 'selected' : '' ?>>Ativo</option><option value="Inativo" <?= $status == 'Inativo' ? 'selected' : '' ?>>Inativo</option></select></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nome</th>
                                    <th>Documento Principal</th>
                                    <th>Telefone</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['Nome']) ?></td>
                                            <td><?= htmlspecialchars($row['DocumentoPrincipal'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($row['Tel']) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $row['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $row['Status'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="detalhes_cliente.php?id=<?= $row['ID_Cliente'] ?>" class="btn btn-info btn-sm" title="Ver Detalhes"><i class="bi bi-eye-fill"></i></a>
                                                    <a href="editar_cliente.php?id=<?= $row['ID_Cliente'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center">Nenhum cliente encontrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php';?>
        </div>
        
        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
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