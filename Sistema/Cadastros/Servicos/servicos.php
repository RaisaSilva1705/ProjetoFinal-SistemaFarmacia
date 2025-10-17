<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'SERVICOS_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$busca_nome = $_GET['busca_nome'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT ID_Servico, Nome_Servico, Valor, Status FROM SERVICOS_FARMACEUTICOS";

$conditions = [];
$params = [];
$types = '';

if (!empty($busca_nome)) {
    $conditions[] = "Nome_Servico LIKE ?";
    $types .= 's';
    $params[] = "%" . $busca_nome . "%";
}
if (!empty($status)) {
    $conditions[] = "Status = ?";
    $types .= 's';
    $params[] = $status;
}
if (count($conditions) > 0) 
    $sql .= " WHERE " . implode(' AND ', $conditions);

$sql .= " ORDER BY Nome_Servico ASC";

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
        <title>Gestão de Serviços Farmacêuticos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php';?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Configurações do Sistema</h3>
                </div>
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Serviços Farmacêuticos</h2>
                        <a href="cadastrar_servico.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Novo Serviço</a>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="servicos.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6"><label for="busca_nome" class="form-label">Buscar por Nome</label><input type="text" name="busca_nome" id="busca_nome" class="form-control" value="<?= htmlspecialchars($busca_nome) ?>"></div>
                                <div class="col-md-4"><label for="status" class="form-label">Status</label><select name="status" id="status" class="form-select"><option value="">Todos</option><option value="Ativo" <?= $status == 'Ativo' ? 'selected' : '' ?>>Ativo</option><option value="Inativo" <?= $status == 'Inativo' ? 'selected' : '' ?>>Inativo</option></select></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Serviço</th>
                                    <th class="text-end">Valor</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['Nome_Servico']) ?></td>
                                            <td class="text-end">R$ <?= number_format($row['Valor'], 2, ',', '.') ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $row['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $row['Status'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="editar_servico.php?id=<?= $row['ID_Servico'] ?>" class="btn btn-warning btn-sm" title="Editar Definições"><i class="bi bi-pencil-fill"></i></a>
                                                    <button type="button" class="btn btn-sm <?= $row['Status'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>"
                                                            title="<?= $row['Status'] == 'Ativo' ? 'Inativar' : 'Ativar' ?>"
                                                            data-bs-toggle="modal" data-bs-target="#modalConfirmStatus"
                                                            data-id="<?= $row['ID_Servico'] ?>"
                                                            data-nome="<?= htmlspecialchars($row['Nome_Servico']) ?>"
                                                            data-status-atual="<?= $row['Status'] ?>">
                                                        <i class="bi <?= $row['Status'] == 'Ativo' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">Nenhum serviço cadastrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php include_once DEV_PATH . 'Views/footer.php';?>
        </div>

        <div class="modal fade" id="modalConfirmStatus" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Confirmar Alteração de Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><p id="confirmText"></p></div>
                    <div class="modal-footer">
                        <form action="processa_servico.php" method="POST">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="id_servico" id="id_status_change">
                            <input type="hidden" name="novo_status" id="novo_status">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="btnConfirmStatus">Confirmar</button>
                        </form>
                    </div>
                </div>
            </div>
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
