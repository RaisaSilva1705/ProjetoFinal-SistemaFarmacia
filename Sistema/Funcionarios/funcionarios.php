<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'FUNCIONARIOS_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$busca_nome = $_GET['busca_nome'] ?? '';
$status = (isset($_GET['status']) && $_GET['status'] !== 'Todos') ? $_GET['status'] : '';

$sql = "SELECT 
            F.ID_Funcionario, 
            F.Nome, 
            F.Telefone, 
            F.Email, 
            F.Status,
            c.Cargo 
        FROM FUNCIONARIOS F
        JOIN CARGOS C ON F.ID_Cargo = C.ID_Cargo";

$conditions = [];
$params = [];
$types = '';

if (!empty($busca_nome)) {
    $conditions[] = "f.Nome LIKE ?";
    $types .= 's';
    $params[] = "%" . $busca_nome . "%";
}
if (!empty($status)) {
    $conditions[] = "f.Status = ?";
    $types .= 's';
    $params[] = $status;
}

if (count($conditions) > 0) 
    $sql .= " WHERE " . implode(' AND ', $conditions);

$sql .= " ORDER BY f.Nome ASC";

$stmt = $conn->prepare($sql);
if (!empty($params))
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result()
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Funcionários</title>
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
                    <h3>Funcionários</h3>
                </div>
            
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Gestão de Funcionários</h2>
                        <div>
                            <a href="cadastrar_funcionario.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Novo Funcionário</a>
                            <a href="../Relatorios/relatorio_funcionarios.php" class="btn btn-outline-secondary"><i class="bi bi-bar-chart-line-fill"></i> Ver Relatório</a>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="funcionarios.php">
                            <div class="row align-items-end">
                                <div class="col-md-6">
                                    <label for="busca_nome" class="form-label">Buscar por Nome</label>
                                    <input type="text" name="busca_nome" id="busca_nome" class="form-control" value="<?= htmlspecialchars($busca_nome) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <option value="Ativo" <?= $status == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="Inativo" <?= $status == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nome</th>
                                    <th>Cargo</th>
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
                                            <td><?= htmlspecialchars($row['Cargo']) ?></td>
                                            <td><?= htmlspecialchars($row['Telefone'] ?? 'N/A') ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $row['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $row['Status'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="detalhes_funcionario.php?id=<?= $row['ID_Funcionario'] ?>" class="btn btn-info btn-sm" title="Ver Detalhes"><i class="bi bi-eye-fill"></i></a>
                                                    <a href="editar_funcionario.php?id=<?= $row['ID_Funcionario'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                                    <button type="button" class="btn btn-sm <?= $row['Status'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>"
                                                            title="<?= $row['Status'] == 'Ativo' ? 'Inativar' : 'Ativar' ?>"
                                                            data-bs-toggle="modal" data-bs-target="#modalConfirmStatus"
                                                            data-id="<?= $row['ID_Funcionario'] ?>"
                                                            data-nome="<?= htmlspecialchars($row['Nome']) ?>"
                                                            data-status-atual="<?= $row['Status'] ?>">
                                                        <i class="bi <?= $row['Status'] == 'Ativo' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center">Nenhum funcionário encontrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
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
                        <form action="processa_funcionario.php" method="POST">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="id_funcionario" id="id_status_change">
                            <input type="hidden" name="novo_status" id="novo_status">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="btnConfirmStatus">Confirmar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-person-vcard-fill"></i> Gestão de Funcionários</h4>
            <hr>
            <p>Esta tela é a sua central de gerenciamento de pessoal. Nela, você pode visualizar todos os funcionários cadastrados, buscar por informações específicas e gerenciar o status e os detalhes de cada um.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Busca</h6>
            <p>Utilize os filtros para encontrar um funcionário rapidamente:</p>
            <ul>
                <li><strong>Buscar por Nome:</strong> Digite o nome do funcionário que deseja localizar.</li>
                <li><strong>Status:</strong> Filtre entre funcionários <strong>Ativos</strong> e <strong>Inativos</strong>.</li>
            </ul>

            <h6><i class="bi bi-plus-circle-fill"></i> Novo Funcionário e Relatório</h6>
            <ul>
                <li><strong>Novo Funcionário:</strong> Leva à tela de cadastro para adicionar um novo membro à equipe e criar seu acesso ao sistema.</li>
                <li><strong>Ver Relatório:</strong> Acessa o relatório de desempenho de funcionários, com métricas de vendas e avaliações de clientes.</li>
            </ul>

            <h6><i class="bi bi-pencil-fill"></i> Ações na Lista</h6>
            <p>Para cada funcionário listado, as seguintes ações estão disponíveis:</p>
            <ul>
                <li><i class="bi bi-eye-fill text-success"></i> <strong>Ver Detalhes:</strong> Abre uma tela com o perfil completo do funcionário, incluindo suas atividades recentes no sistema e o resumo de suas avaliações.</li>
                <li><i class="bi bi-pencil-fill text-warning"></i> <strong>Editar:</strong> Permite alterar os dados cadastrais, contratuais e de acesso do funcionário.</li>
                <li><i class="bi bi-pause-circle-fill text-danger"></i> / <i class="bi bi-play-circle-fill text-success"></i> <strong>Inativar/Ativar:</strong> Altera o status do funcionário e do seu usuário de acesso. Um funcionário inativo não pode mais fazer login no sistema.</li>
            </ul>
            <p class="alert alert-danger mt-3"><strong>Regra de Segurança:</strong> Por proteção, o sistema não permite que um usuário inative seu próprio cadastro.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            const modalConfirmStatus = document.getElementById('modalConfirmStatus');
            modalConfirmStatus.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                const id = button.getAttribute('data-id');
                const funcionario = button.getAttribute('data-nome');
                const statusAtual = button.getAttribute('data-status-atual');

                const confirmText = modalConfirmStatus.querySelector('#confirmText');
                const idInput = modalConfirmStatus.querySelector('#id_status_change');
                const novoStatusInput = modalConfirmStatus.querySelector('#novo_status');
                const btnConfirm = modalConfirmStatus.querySelector('#btnConfirmStatus');

                idInput.value = id;

                if (statusAtual === 'Ativo') {
                    confirmText.textContent = `Você tem certeza que deseja INATIVAR o funcionario "${funcionario}"?`;
                    novoStatusInput.value = 'Inativo';
                    btnConfirm.className = 'btn btn-danger';
                    btnConfirm.textContent = 'Sim, Inativar';
                } 
                else {
                    confirmText.textContent = `Você tem certeza que deseja ATIVAR o funcionario "${funcionario}"?`;
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