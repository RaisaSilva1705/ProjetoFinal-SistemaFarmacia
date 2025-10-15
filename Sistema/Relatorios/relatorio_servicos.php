<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'RELATORIOS_VER');
include DEV_PATH . "Exec/validar_acesso.php";

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');
$filtro_servico_id = filter_input(INPUT_GET, 'servico_id', FILTER_VALIDATE_INT);
$filtro_funcionario_id = filter_input(INPUT_GET, 'funcionario_id', FILTER_VALIDATE_INT);

$servicos = $conn->query("SELECT ID_Servico, Nome_Servico FROM SERVICOS_FARMACEUTICOS WHERE Status = 'Ativo' ORDER BY Nome_Servico")->fetch_all(MYSQLI_ASSOC);
$funcionarios = $conn->query("SELECT ID_Funcionario, Nome FROM FUNCIONARIOS WHERE Status = 'Ativo' ORDER BY Nome")->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT 
            RS.ID_Registro_Servico,
            RS.DataHora,
            RS.Nome_Paciente,
            SF.Nome_Servico,
            SF.Valor,
            F.Nome AS Nome_Funcionario
        FROM REGISTRO_SERVICOS RS
        JOIN SERVICOS_FARMACEUTICOS SF ON RS.ID_Servico = SF.ID_Servico
        JOIN FUNCIONARIOS F ON RS.ID_Funcionario = F.ID_Funcionario
        WHERE DATE(RS.DataHora) BETWEEN ? AND ?";

$params = [$data_inicio, $data_fim];
$types = 'ss';

if ($filtro_servico_id) {
    $sql .= " AND RS.ID_Servico = ?";
    $params[] = $filtro_servico_id;
    $types .= 'i';
}
if ($filtro_funcionario_id) {
    $sql .= " AND RS.ID_Funcionario = ?";
    $params[] = $filtro_funcionario_id;
    $types .= 'i';
}
$sql .= " ORDER BY RS.DataHora DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$registros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_atendimentos = count($registros);
$faturamento_total = array_sum(array_column($registros, 'Valor'));

$servico_mais_realizado = ['nome' => 'N/A', 'qtd' => 0];
if ($total_atendimentos > 0) {
    $contagem_servicos = array_count_values(array_column($registros, 'Nome_Servico'));
    arsort($contagem_servicos); 
    $nome_servico = key($contagem_servicos);
    $servico_mais_realizado = ['nome' => $nome_servico, 'qtd' => $contagem_servicos[$nome_servico]];
}

$funcionario_destaque = ['nome' => 'N/A', 'qtd' => 0];
if ($total_atendimentos > 0) {
    $contagem_funcionarios = array_count_values(array_column($registros, 'Nome_Funcionario'));
    arsort($contagem_funcionarios);
    $nome_funcionario = key($contagem_funcionarios);
    $funcionario_destaque = ['nome' => $nome_funcionario, 'qtd' => $contagem_funcionarios[$nome_funcionario]];
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Serviços Farmacêuticos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatórios Gerenciais</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Relatório de Serviços</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_servicos.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3"><label for="data_inicio">Período de:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-3"><label for="data_fim">Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-2">
                                    <label for="servico_id">Serviço:</label>
                                    <select name="servico_id" class="form-select">
                                        <option value="">Todos</option>
                                        <?php foreach ($servicos as $servico): ?>
                                            <option value="<?= $servico['ID_Servico'] ?>" <?= ($filtro_servico_id == $servico['ID_Servico']) ? 'selected' : '' ?>><?= htmlspecialchars($servico['Nome_Servico']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="funcionario_id">Funcionário:</label>
                                    <select name="funcionario_id" class="form-select">
                                        <option value="">Todos</option>
                                        <?php foreach ($funcionarios as $func): ?>
                                            <option value="<?= $func['ID_Funcionario'] ?>" <?= ($filtro_funcionario_id == $func['ID_Funcionario']) ? 'selected' : '' ?>><?= htmlspecialchars($func['Nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Total de Atendimentos</h5><p class="card-text fs-2 fw-bold text-primary"><?= $total_atendimentos ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Faturamento com Serviços</h5><p class="card-text fs-2 fw-bold text-success">R$ <?= number_format($faturamento_total, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Serviço Mais Realizado</h5><p class="card-text fs-5"><?= htmlspecialchars($servico_mais_realizado['nome']) ?> <span class="badge bg-info"><?= $servico_mais_realizado['qtd'] ?>x</span></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-body"><h5 class="card-title text-muted">Funcionário Destaque</h5><p class="card-text fs-5"><?= htmlspecialchars($funcionario_destaque['nome']) ?> <span class="badge bg-secondary"><?= $funcionario_destaque['qtd'] ?> atend.</span></p></div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h4>Registros Detalhados</h4></div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Serviço</th>
                                        <th>Paciente</th>
                                        <th>Funcionário</th>
                                        <th class="text-end">Valor (R$)</th>
                                        <th class="no-print">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($total_atendimentos > 0): ?>
                                        <?php foreach ($registros as $reg): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($reg['DataHora'])) ?></td>
                                                <td><?= htmlspecialchars($reg['Nome_Servico']) ?></td>
                                                <td><?= htmlspecialchars($reg['Nome_Paciente']) ?></td>
                                                <td><?= htmlspecialchars($reg['Nome_Funcionario']) ?></td>
                                                <td class="text-end"><?= number_format($reg['Valor'], 2, ',', '.') ?></td>
                                                <td class="no-print"><a href="../Servicos/dsf.php?id=<?= $reg['ID_Registro_Servico'] ?>" class="btn btn-sm btn-outline-primary" target="_blank" title="Imprimir DSF"><i class="bi bi-printer-fill"></i></a></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center p-4">Nenhum registro encontrado para os filtros selecionados.</td></tr>
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