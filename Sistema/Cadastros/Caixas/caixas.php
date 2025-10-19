<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CONFIGURACOES_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$busca_texto = $_GET['busca_texto'] ?? '';
$status = (isset($_GET['status']) && $_GET['status'] !== "Todos") ? $_GET['status'] : '';
$statusCadastrado = (isset($_GET['status_cadastrado']) && $_GET['status_cadastrado'] !== "Todos") ? $_GET['status_cadastrado'] : '';

$sql = "SELECT ID_Caixa, Caixa, Status, StatusCadastrado FROM CAIXAS";

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
if (!empty($statusCadastrado)) {
    $conditions[] = "StatusCadastrado = ?";
    $types .= 's';
    $params[] = $statusCadastrado;
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
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Configurações do Sistema</h3>
                </div>
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Gestão de Caixas</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCaixa">
                            <i class="bi bi-plus-circle"></i> Novo Caixa
                        </button>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="caixas.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4"><label for="busca_texto" class="form-label">Buscar por Nome</label><input type="text" name="busca_texto" id="busca_texto" class="form-control" value="<?= htmlspecialchars($busca_texto) ?>"></div>
                                <div class="col-md-3"><label for="status" class="form-label">Status Operacional</label><select name="status" id="status" class="form-select"><option value="Todos">Todos</option><option value="Aberto" <?= $status == 'Aberto' ? 'selected' : '' ?>>Aberto</option><option value="Fechado" <?= $status == 'Fechado' ? 'selected' : '' ?>>Fechado</option></select></div>
                                <div class="col-md-3"><label for="status_cadastrado" class="form-label">Status do Cadastro</label><select name="status_cadastrado" id="status_cadastrado" class="form-select"><option value="Todos">Todos</option><option value="Ativo" <?= $statusCadastrado == 'Ativo' ? 'selected' : '' ?>>Ativo</option><option value="Inativo" <?= $statusCadastrado == 'Inativo' ? 'selected' : '' ?>>Inativo</option></select></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Caixa</th>
                                    <th class="text-center">Status Operacional</th>
                                    <th class="text-center">Status no Cadastro</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['Caixa']) ?></td>
                                            <td class="text-center">
                                                <span class="badge <?= $row['Status'] == 'Aberto' ? 'bg-info text-dark' : 'bg-secondary' ?>"><?= $row['Status'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $row['StatusCadastrado'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $row['StatusCadastrado'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-warning btn-sm" title="Editar"
                                                            data-bs-toggle="modal" data-bs-target="#modalCaixa"
                                                            data-id="<?= $row['ID_Caixa'] ?>"
                                                            data-caixa="<?= htmlspecialchars($row['Caixa']) ?>">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm <?= $row['StatusCadastrado'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>"
                                                            title="<?= $row['StatusCadastrado'] == 'Ativo' ? 'Inativar' : 'Ativar' ?>"
                                                            data-bs-toggle="modal" data-bs-target="#modalConfirmStatus"
                                                            data-id="<?= $row['ID_Caixa'] ?>"
                                                            data-caixa="<?= htmlspecialchars($row['Caixa']) ?>"
                                                            data-status-atual="<?= $row['StatusCadastrado'] ?>">
                                                        <i class="bi <?= $row['StatusCadastrado'] == 'Ativo' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">Nenhum caixa encontrado com os filtros aplicados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>
        
        <!-- Modal Cadastro/Edição -->
        <div class="modal fade" id="modalCaixa" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
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

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-cash-register"></i> Gestão de Caixas</h4>
            <hr>
            <p>Esta tela é utilizada para configurar os pontos de venda (caixas) da sua farmácia. Aqui você pode cadastrar, editar e gerenciar todos os terminais que serão utilizados para registrar as vendas.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Busca</h6>
            <p>Utilize os campos no topo da página para localizar caixas específicos:</p>
            <ul>
                <li><strong>Buscar por Nome:</strong> Digite o nome ou parte do nome do caixa (ex: "Caixa 01").</li>
                <li><strong>Status Operacional:</strong> Filtra os caixas que estão atualmente <strong>Abertos</strong> (em uso por um operador) ou <strong>Fechados</strong>.</li>
                <li><strong>Status do Cadastro:</strong> Filtra os caixas que estão <strong>Ativos</strong> (disponíveis para serem abertos no PDV) ou <strong>Inativos</strong> (desativados do sistema).</li>
            </ul>
            <p>Após definir seus filtros, clique em <strong>"Filtrar"</strong> para atualizar a lista.</p>

            <h6><i class="bi bi-plus-circle-fill"></i> Cadastrar um Novo Caixa</h6>
            <ol>
                <li>Clique no botão <strong>"Novo Caixa"</strong> no canto superior direito.</li>
                <li>Na janela que se abre, informe um nome claro e identificável para o novo caixa (ex: "Caixa 02", "PDV Perfumaria").</li>
                <li>Clique em <strong>"Salvar"</strong>. O novo caixa será criado com o status "Ativo" por padrão e estará disponível para uso.</li>
            </ol>

            <h6><i class="bi bi-pencil-fill"></i> Ações na Lista</h6>
            <p>Para cada caixa listado na tabela, você pode realizar as seguintes ações:</p>
            <ul>
                <li><i class="bi bi-pencil-fill text-warning"></i> <strong>Editar:</strong> Permite alterar o nome de um caixa já cadastrado.</li>
                <li><i class="bi bi-pause-circle-fill text-danger"></i> <strong>Inativar:</strong> Se um caixa está "Ativo", esta opção o tornará "Inativo". Um caixa inativo não pode ser aberto por um operador no PDV, mas seu histórico de vendas é mantido.</li>
                <li><i class="bi bi-play-circle-fill text-success"></i> <strong>Ativar:</strong> Se um caixa está "Inativo", esta opção o tornará "Ativo" novamente, permitindo que ele seja usado no PDV.</li>
            </ul>
            <p class="alert alert-info"><strong>Importante:</strong> A inativação de um caixa é uma ação administrativa e não afeta um caixa que já está em operação. O "Status Operacional" só muda para "Fechado" quando o próprio operador encerra o seu turno no PDV.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
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
