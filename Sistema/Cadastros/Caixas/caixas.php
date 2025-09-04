<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$busca_texto = $_GET['busca_texto'] ?? '';
$status = $_GET['status'] ?? '';
$statusCadastro = $_GET['status_cadastro'] ?? '';

$sql = "SELECT ID_Caixa, Caixa, Status, StatusCadastro FROM CAIXAS";

$conditions = [];
$params = [];
$types = '';

if (!empty($busca_texto)) {
    $conditions[] = "(Caixa LIKE ?)";
    $types .= 's';
    $params[] = "%" . $busca_texto . "%";
}
if (!empty($status)) {
    $conditions[] = "Status = ?";
    $types .= 's';
    $params[] = $status;
}
if (!empty($statusCadastro)) {
    $conditions[] = "StatusCadastro = ?";
    $types .= 's';
    $params[] = $statusCadastro;
}

if (count($conditions) > 0)
    $sql .= " WHERE " . implode(' AND ', $conditions);

$sql .= " ORDER BY Caixa ASC";

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
        <title>Caixas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Gestão de CAIXAS</h3>
                </div>
                <div class="container p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Lista de Caixas</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCaixa">
                            Adicionar Novo Caixa
                        </button>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="caixas.php">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label for="busca_texto" class="form-label">Buscar por Forma de Pagamento</label>
                                    <input type="text" name="busca_texto" id="busca_texto" class="form-control" value="<?= htmlspecialchars($busca_texto) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="Aberto" <?= $status == 'Aberto' ? 'selected' : '' ?>>Aberto</option>
                                        <option value="Fechado" <?= $status == 'Fechado' ? 'selected' : '' ?>>Fechado</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="status_cadastro" class="form-label">Status Cadastrado</label>
                                    <select name="status_cadastro" id="status_cadastro" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="Ativo" <?= $statusCadastro == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="Inativo" <?= $statusCadastro == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
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
                                    <th>Caixa</th>
                                    <th>Status Operacional</th>
                                    <th>Status no Cadastro</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['Caixa']) ?></td>
                                            <td <?php $class = $row['Status'] == 'Aberto' ? 'table-info' : 'table-warning'; echo "class='{$class}'"?>> 
                                                <?= htmlspecialchars($row['Status']) ?>
                                            </td>
                                            <td <?php $class = $row['StatusCadastro'] == 'Ativo' ? 'table-success' : 'table-danger'; echo "class='{$class}'"?>> 
                                                <?= htmlspecialchars($row['StatusCadastro']) ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-warning btn-sm btn-edit"
                                                        data-bs-toggle="modal" data-bs-target="#modalCaixa"
                                                        data-id="<?= $row['ID_Caixa'] ?>"
                                                        data-caixa="<?= htmlspecialchars($row['Caixa']) ?>">
                                                    Editar
                                                </button>
                                                <button type="button" class="btn btn-sm <?= $row['StatusCadastro'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>"
                                                        data-bs-toggle="modal" data-bs-target="#modalConfirmStatus"
                                                        data-id="<?= $row['ID_Caixa'] ?>"
                                                        data-caixa="<?= htmlspecialchars($row['Caixa']) ?>"
                                                        data-status-atual="<?= $row['StatusCadastro'] ?>"
                                                        data-status-op="<?= $row['Status'] ?>">
                                                    <?= $row['StatusCadastro'] == 'Ativo' ? 'Inativar' : 'Ativar' ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">Nenhum caixa cadastrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>
        
        <!-- Modal Cadastro/Edição -->
        <div class="modal fade" id="modalCaixa" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCaixaLabel">Adicionar Caixa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="processa_caixa.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id_caixa" id="id_caixa">
                            <div class="mb-3">
                                <label for="caixa" class="form-label">Nome do Caixa (Ex: Caixa 01, PDV-Farma)</label>
                                <input type="text" name="caixa" id="caixa" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal de confirmação -->
        <div class="modal fade" id="modalConfirmStatus" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Alteração de Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="confirmText"></p>
                    </div>
                    <div class="modal-footer">
                        <form action="processa_caixa.php" method="POST">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="id_caixa" id="id_status_change">
                            <input type="hidden" name="caixa" id="caixa">
                            <input type="hidden" name="novo_status" id="novo_status">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="btnConfirmStatus">Confirmar</button>
                        </form>
                    </div>
                </div>
            </div>
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
            const modalCaixa = document.getElementById('modalCaixa');
            modalCaixa.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; 
                const modalTitle = modalCaixa.querySelector('.modal-title');
                
                const idInput = modalCaixa.querySelector('#id_caixa');
                const caixaInput = modalCaixa.querySelector('#caixa');
                const descricaoInput = modalCaixa.querySelector('#descricao');

                const id = button.getAttribute('data-id');
                if (id) {
                    modalTitle.textContent = 'Editar Caixa';
                    idInput.value = id;
                    caixaInput.value = button.getAttribute('data-caixa');
                    descricaoInput.value = button.getAttribute('data-descricao');
                } 
                else {
                    modalTitle.textContent = 'Adicionar Novo Caixa';
                    idInput.value = '';
                    caixaInput.value = '';
                    descricaoInput.value = '';
                }
            });

            const modalConfirmStatus = document.getElementById('modalConfirmStatus');
            modalConfirmStatus.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                const id = button.getAttribute('data-id');
                const nomeCaixa = button.getAttribute('data-caixa'); 
                const statusAtual = button.getAttribute('data-status-atual');

                const confirmText = modalConfirmStatus.querySelector('#confirmText');
                const idInput = modalConfirmStatus.querySelector('#id_status_change');
                const novoStatusInput = modalConfirmStatus.querySelector('#novo_status');
                const btnConfirm = modalConfirmStatus.querySelector('#btnConfirmStatus');

                idInput.value = id;

                if (statusAtual === 'Ativo') {
                    confirmText.textContent = `Você tem certeza que deseja INATIVAR o caixa "${nomeCaixa}"?`;
                    novoStatusInput.value = 'Inativo';
                    btnConfirm.className = 'btn btn-danger';
                    btnConfirm.textContent = 'Sim, Inativar';
                } 
                else {
                    confirmText.textContent = `Você tem certeza que deseja ATIVAR o caixa "${nomeCaixa}"?`;
                    novoStatusInput.value = 'Ativo';
                    btnConfirm.className = 'btn btn-success';
                    btnConfirm.textContent = 'Sim, Ativar';
                }
            });

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
