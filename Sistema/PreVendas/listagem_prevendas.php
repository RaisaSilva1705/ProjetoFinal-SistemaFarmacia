<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'PDV_ACESSAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$sql = "SELECT 
            pv.ID_PreVenda,
            pv.Codigo_PreVenda,
            pv.Status,
            pv.Data_Criacao,
            c.Nome AS Nome_Cliente,
            f.Nome AS Nome_Funcionario,
            (SELECT SUM((pvi.Valor_Unitario - pvi.Desconto) * pvi.Quantidade) 
             FROM PRE_VENDAS_ITENS pvi 
             WHERE pvi.ID_PreVenda = pv.ID_PreVenda) AS Valor_Total
        FROM PRE_VENDAS pv
        LEFT JOIN CLIENTES c ON pv.ID_Cliente = c.ID_Cliente
        JOIN FUNCIONARIOS f ON pv.ID_Funcionario = f.ID_Funcionario
        ORDER BY pv.Data_Criacao DESC";

$resultado = $conn->query($sql);
$todas_prevendas = $resultado->fetch_all(MYSQLI_ASSOC);

$pendentes = array_filter($todas_prevendas, fn($pv) => $pv['Status'] == 'Pendente');
$finalizadas = array_filter($todas_prevendas, fn($pv) => $pv['Status'] == 'Finalizada');
$canceladas = array_filter($todas_prevendas, fn($pv) => $pv['Status'] == 'Cancelada');

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Listagem de Pré-Vendas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>
        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Listagem de Pré-Vendas</h3>
                </div>
                <div class="container p-5">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Gerenciar Pré-Vendas</h2>
                        <a href="prevendas.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nova Pré-Venda
                        </a>
                    </div>

                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pendentes-tab" data-bs-toggle="tab" data-bs-target="#pendentes-pane" type="button">
                                Pendentes <span class="badge bg-warning text-dark"><?= count($pendentes) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="finalizadas-tab" data-bs-toggle="tab" data-bs-target="#finalizadas-pane" type="button">
                                Finalizadas <span class="badge bg-success"><?= count($finalizadas) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="canceladas-tab" data-bs-toggle="tab" data-bs-target="#canceladas-pane" type="button">
                                Canceladas <span class="badge bg-danger"><?= count($canceladas) ?></span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content card card-body">
                        <div class="tab-pane fade show active" id="pendentes-pane">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Funcionário</th>
                                        <th>Data</th>
                                        <th>Valor Total</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pendentes)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">Nenhuma pré-venda pendente encontrada.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($pendentes as $pv): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($pv['Codigo_PreVenda']) ?></td>
                                                <td><?= htmlspecialchars($pv['Nome_Cliente'] ?? 'Consumidor Final') ?></td>
                                                <td><?= htmlspecialchars($pv['Nome_Funcionario']) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($pv['Data_Criacao'])) ?></td>
                                                <td>R$ <?= number_format($pv['Valor_Total'] ?? 0, 2, ',', '.') ?></td>
                                                <td class="text-center">
                                                    <a href="cupom_prevenda.php?codigo=<?= $pv['Codigo_PreVenda'] ?>" target="_blank" class="btn btn-info btn-sm" title="Reimprimir Cupom"><i class="bi bi-printer-fill"></i></a>
                                                    <a href="prevendas.php?id_prevenda=<?= $pv['ID_PreVenda'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                                    <button onclick="abrirModalCancelamento(<?= $pv['ID_PreVenda'] ?>, '<?= htmlspecialchars($pv['Codigo_PreVenda']) ?>')" class="btn btn-danger btn-sm" title="Cancelar"><i class="bi bi-x-circle-fill"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="finalizadas-pane">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Funcionário</th>
                                        <th>Data</th>
                                        <th>Valor Total</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($finalizadas)): ?>
                                        <tr><td colspan="6" class="text-center text-muted">Nenhuma pré-venda finalizada encontrada.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($finalizadas as $pv): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($pv['Codigo_PreVenda']) ?></td>
                                                <td><?= htmlspecialchars($pv['Nome_Cliente'] ?? 'Consumidor Final') ?></td>
                                                <td><?= htmlspecialchars($pv['Nome_Funcionario']) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($pv['Data_Criacao'])) ?></td>
                                                <td>R$ <?= number_format($pv['Valor_Total'] ?? 0, 2, ',', '.') ?></td>
                                                <td class="text-center">
                                                    <a href="cupom_prevenda.php?codigo=<?= $pv['Codigo_PreVenda'] ?>" target="_blank" class="btn btn-info btn-sm" title="Ver Cupom"><i class="bi bi-receipt"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane fade" id="canceladas-pane">
                             <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Funcionário</th>
                                        <th>Data</th>
                                        <th>Valor Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($canceladas)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">Nenhuma pré-venda cancelada encontrada.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($canceladas as $pv): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($pv['Codigo_PreVenda']) ?></td>
                                                <td><?= htmlspecialchars($pv['Nome_Cliente'] ?? 'Consumidor Final') ?></td>
                                                <td><?= htmlspecialchars($pv['Nome_Funcionario']) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($pv['Data_Criacao'])) ?></td>
                                                <td>R$ <?= number_format($pv['Valor_Total'] ?? 0, 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <div class="modal fade" id="modalConfirmarCancelamento" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirmar Cancelamento</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja cancelar a pré-venda código <strong id="codigoPreVendaModal"></strong>?</p>
                        <p class="text-danger fw-bold">Esta ação não pode ser desfeita.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <a href="#" id="btn-confirmar-cancelamento" class="btn btn-danger">Sim, Cancelar Pré-Venda</a>
                    </div>
                </div>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-list-task"></i> Gerenciamento de Pré-Vendas</h4>
            <hr>
            <p>Esta tela funciona como um painel de controle para monitorar o status de todas as pré-vendas geradas no sistema. Ela é dividida em abas para facilitar a organização do fluxo de trabalho.</p>

            <h6><i class="bi bi-clock-history"></i> Abas de Status</h6>
            <ul>
                <li><strong>Pendentes:</strong> Lista todas as pré-vendas que foram geradas e estão aguardando o cliente passar no caixa para finalizar o pagamento. Esta é a sua principal aba de trabalho.</li>
                <li><strong>Finalizadas:</strong> Mostra um histórico de todas as pré-vendas que já foram processadas e concluídas no PDV.</li>
                <li><strong>Canceladas:</strong> Exibe as pré-vendas que foram canceladas antes da finalização.</li>
            </ul>

            <h6><i class="bi bi-pencil-fill"></i> Ações Disponíveis (na Aba "Pendentes")</h6>
            <p>Para cada pré-venda pendente, você pode realizar as seguintes ações:</p>
            <ul>
                <li><i class="bi bi-printer-fill text-info"></i> <strong>Reimprimir Cupom:</strong> Gera novamente o cupom com o código de barras, caso o cliente o tenha perdido.</li>
                <li><i class="bi bi-pencil-fill text-warning"></i> <strong>Editar:</strong> Permite reabrir a pré-venda para adicionar ou remover itens, alterar o cliente ou reaplicar descontos.</li>
                <li><i class="bi bi-x-circle-fill text-danger"></i> <strong>Cancelar:</strong> Invalida a pré-venda, impedindo que ela seja finalizada no caixa. Esta ação é útil caso o cliente desista da compra.</li>
            </ul>
        </div>
        
        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            const modalCancelamento = new bootstrap.Modal(document.getElementById('modalConfirmarCancelamento'));

            function abrirModalCancelamento(id, codigo) {
                document.getElementById('codigoPreVendaModal').textContent = codigo;

                const btnConfirmar = document.getElementById('btn-confirmar-cancelamento');
                btnConfirmar.href = 'processa_prevenda_acoes.php?acao=cancelar&id=' + id;

                modalCancelamento.show();
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