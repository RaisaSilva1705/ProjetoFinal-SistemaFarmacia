<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'FINANCEIRO_VER');
include DEV_PATH . "Exec/validar_acesso.php";

// Lógica de Filtros (inicial)
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$status_despesa = (isset($_GET['status']) && $_GET['status'] !== 'Todos') ? $_GET['status'] : '';

// Query para buscar as despesas
$sql = "SELECT 
            D.*, 
            DC.Nome_Categoria,
            F.Nome as Nome_Funcionario
        FROM DESPESAS D
        JOIN DESPESAS_CATEGORIAS DC ON D.ID_Categoria_Despesa = DC.ID_Categoria_Despesa
        JOIN FUNCIONARIOS F ON D.ID_Funcionario = F.ID_Funcionario
        WHERE DATE(D.Data_Registro) BETWEEN ? AND ?
        AND D.Status_Registro = 'Ativo'";

$params = [$data_inicio, $data_fim];
$types = 'ss';

if (!empty($status_despesa)) {
    $sql .= " AND D.Status = ?";
    $params[] = $status_despesa;
    $types .= 's';
}
$sql .= " ORDER BY D.Data_Vencimento DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$despesas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Gestão de Despesas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Gestão de Despesas</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Contas a Pagar e Pagas</h2>
                        <div>
                            <a href="../Relatorios/relatorio_financeiro.php" class="btn btn-outline-secondary">
                                <i class="bi bi-pie-chart-fill"></i> Ver Relatório
                            </a>
                            <a href="nova_despesa.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nova Despesa</a>
                            <a href="categorias_despesa.php" class="btn btn-outline-secondary">Gerenciar Categorias</a>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="despesas.php">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label for="data_inicio">Período de:</label>
                                    <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="data_fim">Até:</label>
                                    <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="status">Status:</label>
                                    <select name="status" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <option value="Pendente" <?= $status_despesa == 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                                        <option value="Paga" <?= $status_despesa == 'Paga' ? 'selected' : '' ?>>Paga</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Categoria</th>
                                        <th class="text-end">Valor</th>
                                        <th>Data Venc.</th>
                                        <th>Data Pag.</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($despesas) > 0): ?>
                                        <?php foreach ($despesas as $despesa): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($despesa['Descricao']) ?></td>
                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($despesa['Nome_Categoria']) ?></span></td>
                                                <td class="text-end fw-bold">R$ <?= number_format($despesa['Valor'], 2, ',', '.') ?></td>
                                                <td><?= $despesa['Data_Vencimento'] ? date('d/m/Y', strtotime($despesa['Data_Vencimento'])) : 'N/A' ?></td>
                                                <td><?= $despesa['Data_Pagamento'] ? date('d/m/Y', strtotime($despesa['Data_Pagamento'])) : '---' ?></td>
                                                <td>
                                                    <span class="badge <?= $despesa['Status'] == 'Paga' ? 'bg-success' : 'bg-warning' ?>">
                                                        <?= $despesa['Status'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a title="Editar Lançamento" href="editar_despesa.php?id=<?= $despesa['ID_Despesa'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#confirmCancelModal"
                                                        data-url="../../dev/Exec/cancela_despesa.php?id=<?= $despesa['ID_Despesa'] ?>"
                                                        title="Cancelar Lançamento">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center">Nenhuma despesa encontrada para os filtros selecionados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <!-- Modal para cancelar despesa -->
        <div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-labelledby="modalLabelCancel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-dark">
                        <h5 class="modal-title text-white" id="modalLabelCancel">Confirmação de Cancelamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Tem certeza que deseja cancelar este lançamento?
                        <br><br>
                        Esta ação não poderá ser desfeita.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <a href="#" id="btnConfirmCancel" class="btn btn-danger">Sim, Cancelar Lançamento</a>
                    </div>
                </div>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-wallet2"></i> Gestão de Despesas (Contas a Pagar e Pagas)</h4>
            <hr>
            <p>Esta é a sua central de controle de contas a pagar. Nela, você pode registrar, gerenciar e acompanhar todas as despesas operacionais da farmácia, garantindo que suas obrigações financeiras estejam sempre em dia.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Busca</h6>
            <p>Utilize os filtros para encontrar lançamentos específicos:</p>
            <ul>
                <li><strong>Período de: / Até:</strong> Defina o intervalo de datas com base na <strong>data de registro</strong> da despesa no sistema.</li>
                <li><strong>Status:</strong> Filtre entre despesas que estão <strong>Pagas</strong> ou <strong>Pendentes</strong>.</li>
            </ul>

            <h6><i class="bi bi-plus-circle-fill"></i> Ações Principais</h6>
            <ul>
                <li><strong>Ver Relatório Financeiro:</strong> Atalho para o Relatório Financeiro, onde o total das despesas pagas impacta o cálculo do seu lucro.</li>
                <li><strong>Nova Despesa:</strong> Abre o formulário para registrar uma nova conta a pagar.</li>
                <li><strong>Gerenciar Categorias:</strong> Leva à tela de cadastro e edição das categorias de despesas (ex: Aluguel, Salários, Marketing).</li>
            </ul>

            <h6><i class="bi bi-pencil-fill"></i> Ações na Lista</h6>
            <p>Para cada despesa listada, as seguintes ações estão disponíveis:</p>
            <ul>
                <li><i class="bi bi-pencil-fill text-warning"></i> <strong>Editar:</strong> Permite alterar qualquer informação do lançamento, como descrição, valor, datas ou status.</li>
                <li><i class="bi bi-x-circle text-danger"></i> <strong>Cancelar Lançamento:</strong> Remove o registro da despesa do sistema. Esta ação é útil para corrigir lançamentos feitos por engano.</li>
            </ul>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            const confirmCancelModal = document.getElementById('confirmCancelModal');
            if (confirmCancelModal) {
                confirmCancelModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const url = button.getAttribute('data-url');
                    const confirmBtn = confirmCancelModal.querySelector('#btnConfirmCancel');
                    confirmBtn.setAttribute('href', url);
                });
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