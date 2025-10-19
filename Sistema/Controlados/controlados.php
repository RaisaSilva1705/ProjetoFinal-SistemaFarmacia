<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CONTROLADOS_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');
$filtro_medicamento_id = (isset($_GET['medicamento_id']) && $_GET['medicamento_id'] !== 'Todos') ? $_GET['medicamento_id'] : '';
$filtro_prescritor = (isset($_GET['prescritor']) && $_GET['prescritor'] !== 'Todos') ? $_GET['prescritor'] : '';

$controlados_lista = $conn->query("SELECT DISTINCT P.ID_Produto, P.Nome FROM ITENS_VENDA IV JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto JOIN MEDICAMENTOS M ON P.ID_Produto = M.ID_Produto WHERE M.Controlado = 'Sim' ORDER BY P.Nome")->fetch_all(MYSQLI_ASSOC);
$prescritores_lista = $conn->query("SELECT DISTINCT Nome_Profissional FROM PRESCRICOES ORDER BY Nome_Profissional")->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT 
            V.DataHora_Venda,
            P.Nome AS Nome_Medicamento,
            IV.Quantidade,
            COALESCE(C.Nome, 'Paciente não cadastrado') AS Nome_Paciente,
            PR.Nome_Profissional,
            CONCAT(PR.Conselho, ' ', PR.Num_Conselho, '/', PR.UF_Conselho) AS Conselho_Profissional,
            PR.ID_Prescricao,
            V.ID_Venda
        FROM ITENS_VENDA IV
        JOIN VENDAS V ON IV.ID_Venda = V.ID_Venda
        JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto
        JOIN MEDICAMENTOS M ON P.ID_Produto = M.ID_Produto
        LEFT JOIN PRE_VENDAS PV ON V.ID_Venda = PV.ID_Venda
        LEFT JOIN PRESCRICOES PR ON PV.ID_Prescricao = PR.ID_Prescricao
        LEFT JOIN CLIENTES C ON PR.ID_Cliente = C.ID_Cliente
        WHERE DATE(V.DataHora_Venda) BETWEEN ? AND ?
          AND M.Controlado = 'Sim'
          AND PR.ID_Prescricao IS NOT NULL"; 

$params = [$data_inicio, $data_fim];
$types = 'ss';

if ($filtro_medicamento_id) {
    $sql .= " AND P.ID_Produto = ?";
    $params[] = $filtro_medicamento_id;
    $types .= 'i';
}
if (!empty($filtro_prescritor)) {
    $sql .= " AND PR.Nome_Profissional = ?";
    $params[] = $filtro_prescritor;
    $types .= 's';
}
$sql .= " ORDER BY V.DataHora_Venda DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Gerenciamento e Rastreabilidade de Controlados</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Gerenciamento e Rastreabilidade de Controlados</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Dispensação de Controlados</h2>
                        <div>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalSNGPC">
                                <i class="bi bi-file-earmark-zip-fill"></i> Gerar Arquivo SNGPC
                            </button>
                            <a href="dispensacao_controlados.php" class="btn btn-primary"><i class="bi bi-shield-lock-fill"></i> Dispensar Medicamento</a>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="controlados.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3"><label for="data_inicio">Período de:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-3"><label for="data_fim">Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-2">
                                    <label for="medicamento_id">Medicamento:</label>
                                    <select name="medicamento_id" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <?php foreach ($controlados_lista as $med): ?>
                                            <option value="<?= $med['ID_Produto'] ?>" <?= ($filtro_medicamento_id == $med['ID_Produto']) ? 'selected' : '' ?>><?= htmlspecialchars($med['Nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="prescritor">Prescritor:</label>
                                    <select name="prescritor" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <?php foreach ($prescritores_lista as $presc): ?>
                                            <option value="<?= htmlspecialchars($presc['Nome_Profissional']) ?>" <?= ($filtro_prescritor == $presc['Nome_Profissional']) ? 'selected' : '' ?>><?= htmlspecialchars($presc['Nome_Profissional']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Registros Detalhados de Dispensação</h4>
                            <span class="badge bg-secondary"><?= count($registros) ?> registro(s)</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Data/Hora Venda</th>
                                        <th>Medicamento</th>
                                        <th class="text-center">Qtd.</th>
                                        <th>Paciente</th>
                                        <th>Prescritor</th>
                                        <th>Conselho</th>
                                        <th class="text-center">Termo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($registros) > 0): ?>
                                        <?php foreach ($registros as $reg): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($reg['DataHora_Venda'])) ?></td>
                                                <td><?= htmlspecialchars($reg['Nome_Medicamento']) ?></td>
                                                <td class="text-center"><?= $reg['Quantidade'] ?></td>
                                                <td><?= htmlspecialchars($reg['Nome_Paciente']) ?></td>
                                                <td><?= htmlspecialchars($reg['Nome_Profissional']) ?></td>
                                                <td><?= htmlspecialchars($reg['Conselho_Profissional']) ?></td>
                                                <td class="text-center">
                                                    <a href="termo_dispensacao.php?id=<?= $reg['ID_Prescricao'] ?>" class="btn btn-info btn-sm" title="Imprimir Termo" target="_blank">
                                                        <i class="bi bi-printer-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center p-4">Nenhuma dispensação de controlados encontrada para os filtros selecionados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <div class="modal fade" id="modalSNGPC" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Gerar Arquivo de Movimentação (SNGPC)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="gerar_sngpc.php" method="POST" target="_blank">
                        <div class="modal-body">
                            <p>Selecione o período para gerar o arquivo XML de movimentação de medicamentos controlados.</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="sngpc_data_inicio" class="form-label">Data de Início</label>
                                    <input type="date" name="data_inicio" id="sngpc_data_inicio" class="form-control" required value="<?= date('Y-m-d', strtotime('-7 days')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="sngpc_data_fim" class="form-label">Data Final</label>
                                    <input type="date" name="data_fim" id="sngpc_data_fim" class="form-control" required value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-download"></i> Gerar e Baixar XML</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-file-earmark-lock2-fill"></i> Gerenciamento e Rastreabilidade de Controlados</h4>
            <hr>
            <p>Esta é uma das telas mais importantes para a <strong>conformidade regulatória</strong> da farmácia. Ela funciona como o livro de registro eletrônico, fornecendo um histórico detalhado e auditável de todas as dispensações (vendas) de medicamentos de controle especial.</p>

            <h6><i class="bi bi-shield-lock-fill"></i> Ações Principais</h6>
            <ul>
                <li><strong>Dispensar Medicamento:</strong> Abre o formulário completo para registrar uma nova receita de controlado, processo que culmina na geração de uma pré-venda.</li>
                <li><strong>Gerar Arquivo SNGPC:</strong> Abre uma janela para que você selecione um período e gere o arquivo XML de movimentação, pronto para ser transmitido ao Sistema Nacional de Gerenciamento de Produtos Controlados.</li>
            </ul>

            <h6><i class="bi bi-funnel-fill"></i> Ferramentas de Auditoria (Filtros)</h6>
            <p>Utilize os filtros para realizar auditorias e rastrear informações específicas:</p>
            <ul>
                <li><strong>Período (De/Até):</strong> Defina o intervalo de datas para a investigação.</li>
                <li><strong>Medicamento:</strong> Filtre para ver todo o histórico de saída de um medicamento controlado específico.</li>
                <li><strong>Prescritor:</strong> Filtre para rastrear todas as receitas de um médico ou profissional de saúde que foram atendidas na sua farmácia.</li>
            </ul>

            <h6><i class="bi bi-bar-chart-fill"></i> Indicadores de Dispensação</h6>
            <p>Os cards no topo da página fornecem um resumo rápido do período, ideal para análises gerenciais.</p>

            <h6><i class="bi bi-list-check"></i> Registros Detalhados de Dispensação</h6>
            <p>A tabela principal é o seu registro de auditoria. Ela detalha cada venda, informando a data, o medicamento, a quantidade, o paciente e o profissional prescritor. Use esta tabela para conferências internas e para a geração de relatórios para órgãos fiscalizadores como a ANVISA.</p>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
    </body>
</html>