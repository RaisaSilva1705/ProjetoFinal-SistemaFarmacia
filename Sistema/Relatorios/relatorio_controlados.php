<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');
$filtro_medicamento_id = filter_input(INPUT_GET, 'medicamento_id', FILTER_VALIDATE_INT);
$filtro_prescritor = $_GET['prescritor'] ?? '';

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

$total_dispensacoes = 0;
$total_unidades = 0;
$medicamento_mais_dispensado = ['nome' => 'N/A', 'qtd' => 0];
$prescritor_destaque = ['nome' => 'N/A', 'qtd' => 0];

if (count($registros) > 0) {
    $total_dispensacoes = count(array_unique(array_column($registros, 'ID_Prescricao')));
    $total_unidades = array_sum(array_column($registros, 'Quantidade'));

    $contagem_medicamentos = array_count_values(array_column($registros, 'Nome_Medicamento'));
    arsort($contagem_medicamentos);
    $nome_medicamento = key($contagem_medicamentos);
    $medicamento_mais_dispensado = ['nome' => $nome_medicamento, 'qtd' => $contagem_medicamentos[$nome_medicamento]];

    $contagem_prescritores = array_count_values(array_column($registros, 'Nome_Profissional'));
    arsort($contagem_prescritores);
    $nome_prescritor = key($contagem_prescritores);
    $prescritor_destaque = ['nome' => $nome_prescritor, 'qtd' => $contagem_prescritores[$nome_prescritor]];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Dispensação de Controlados</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatórios de Conformidade</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Dispensação de Controlados</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_controlados.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3"><label for="data_inicio">Período de:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-3"><label for="data_fim">Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-2">
                                    <label for="medicamento_id">Medicamento:</label>
                                    <select name="medicamento_id" class="form-select">
                                        <option value="">Todos</option>
                                        <?php foreach ($controlados_lista as $med): ?>
                                            <option value="<?= $med['ID_Produto'] ?>" <?= ($filtro_medicamento_id == $med['ID_Produto']) ? 'selected' : '' ?>><?= htmlspecialchars($med['Nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="prescritor">Prescritor:</label>
                                    <select name="prescritor" class="form-select">
                                        <option value="">Todos</option>
                                        <?php foreach ($prescritores_lista as $presc): ?>
                                            <option value="<?= htmlspecialchars($presc['Nome_Profissional']) ?>" <?= ($filtro_prescritor == $presc['Nome_Profissional']) ? 'selected' : '' ?>><?= htmlspecialchars($presc['Nome_Profissional']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Total de Dispensações</div><div class="card-body"><p class="card-text fs-2 fw-bold text-primary"><?= $total_dispensacoes ?></p><small>(Receitas únicas)</small></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Total de Unidades</div><div class="card-body"><p class="card-text fs-2 fw-bold text-info"><?= $total_unidades ?></p><small>(Caixas/Frascos)</small></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Medicamento Mais Dispensado</div><div class="card-body"><p class="card-text fs-5"><?= htmlspecialchars($medicamento_mais_dispensado['nome']) ?><br><span class="badge bg-danger"><?= $medicamento_mais_dispensado['qtd'] ?>x</span></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Prescritor Destaque</div><div class="card-body"><p class="card-text fs-5"><?= htmlspecialchars($prescritor_destaque['nome']) ?><br><span class="badge bg-secondary"><?= $prescritor_destaque['qtd'] ?> disp.</span></p></div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Registros Detalhados de Dispensação</h4>
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
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center p-4">Nenhuma dispensação de controlados encontrada para os filtros selecionados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>