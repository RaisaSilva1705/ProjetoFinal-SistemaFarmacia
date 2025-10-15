<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CONFIGURACOES_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$busca_texto = $_GET['busca_texto'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT ID_Cargo, Cargo, Descricao, Status FROM CARGOS";

$conditions = [];
$params = [];
$types = '';

if (!empty($busca_texto)) {
    $conditions[] = "(Cargo LIKE ?)";
    $types .= 's';
    $params[] = "%" . $busca_texto . "%";
}
if (!empty($status)) {
    $conditions[] = "Status = ?";
    $types .= 's';
    $params[] = $status;
}

if (count($conditions) > 0)
    $sql .= " WHERE " . implode(' AND ', $conditions);

$sql .= " ORDER BY Cargo ASC";

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
        <title>Cargos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Configurações do Sistema</h3>
                </div>
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Gestão de Cargos</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCargo">
                            <i class="bi bi-plus-circle"></i> Novo Cargo
                        </button>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="cargos.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-6"><label for="busca_texto" class="form-label">Buscar por Nome</label><input type="text" name="busca_texto" id="busca_texto" class="form-control" value="<?= htmlspecialchars($busca_texto) ?>"></div>
                                <div class="col-md-4"><label for="status" class="form-label">Status</label><select name="status" id="status" class="form-select"><option value="">Todos</option><option value="Ativo" <?= $status == 'Ativo' ? 'selected' : '' ?>>Ativo</option><option value="Inativo" <?= $status == 'Inativo' ? 'selected' : '' ?>>Inativo</option></select></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Cargo</th>
                                    <th>Descrição</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['Cargo']) ?></td>
                                            <td><?= htmlspecialchars($row['Descricao']) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $row['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $row['Status'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="permissoes_cargo.php?cargo_id=<?= $row['ID_Cargo'] ?>" class="btn btn-secondary btn-sm" title="Gerenciar Permissões">
                                                        <i class="bi bi-shield-check"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-warning btn-sm" title="Editar Cargo"
                                                            data-bs-toggle="modal" data-bs-target="#modalCargo"
                                                            data-id="<?= $row['ID_Cargo'] ?>"
                                                            data-cargo="<?= htmlspecialchars($row['Cargo']) ?>"
                                                            data-descricao="<?= htmlspecialchars($row['Descricao']) ?>">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm <?= $row['Status'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>" 
                                                            title="<?= $row['Status'] == 'Ativo' ? 'Inativar' : 'Ativar' ?>"
                                                            data-bs-toggle="modal" data-bs-target="#modalConfirmStatus"
                                                            data-id="<?= $row['ID_Cargo'] ?>"
                                                            data-cargo="<?= htmlspecialchars($row['Cargo']) ?>"
                                                            data-status-atual="<?= $row['Status'] ?>">
                                                        <i class="bi <?= $row['Status'] == 'Ativo' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">Nenhum cargo cadastrado.</td></tr>
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
        <div class="modal fade" id="modalCargo" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCargoLabel">Adicionar Cargo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form action="processa_cargo.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id_cargo" id="id_cargo">
                            <div class="mb-3">
                                <label for="cargo" class="form-label">Nome do Cargo</label>
                                <input type="text" name="cargo" id="cargo" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <textarea name="descricao" id="descricao" class="form-control" rows="3"></textarea>
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
                        <form action="processa_cargo.php" method="POST">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="id_cargo" id="id_status_change">
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
            const modalCargo = document.getElementById('modalCargo');
            modalCargo.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; 
                const modalTitle = modalCargo.querySelector('.modal-title');
                
                const idInput = modalCargo.querySelector('#id_cargo');
                const cargoInput = modalCargo.querySelector('#cargo');
                const descricaoInput = modalCargo.querySelector('#descricao');

                const id = button.getAttribute('data-id');
                if (id) {
                    modalTitle.textContent = 'Editar Cargo';
                    idInput.value = id;
                    cargoInput.value = button.getAttribute('data-cargo');
                    descricaoInput.value = button.getAttribute('data-descricao');
                } 
                else {
                    modalTitle.textContent = 'Adicionar Novo Cargo';
                    idInput.value = '';
                    cargoInput.value = '';
                    descricaoInput.value = '';
                }
            });

            const modalConfirmStatus = document.getElementById('modalConfirmStatus');
            modalConfirmStatus.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                const id = button.getAttribute('data-id');
                const cargo = button.getAttribute('data-cargo');
                const statusAtual = button.getAttribute('data-status-atual');

                const confirmText = modalConfirmStatus.querySelector('#confirmText');
                const idInput = modalConfirmStatus.querySelector('#id_status_change');
                const novoStatusInput = modalConfirmStatus.querySelector('#novo_status');
                const btnConfirm = modalConfirmStatus.querySelector('#btnConfirmStatus');

                idInput.value = id;

                if (statusAtual === 'Ativo') {
                    confirmText.textContent = `Você tem certeza que deseja INATIVAR o cargo "${cargo}"?`;
                    novoStatusInput.value = 'Inativo';
                    btnConfirm.className = 'btn btn-danger';
                    btnConfirm.textContent = 'Sim, Inativar';
                } 
                else {
                    confirmText.textContent = `Você tem certeza que deseja ATIVAR o cargo "${cargo}"?`;
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
