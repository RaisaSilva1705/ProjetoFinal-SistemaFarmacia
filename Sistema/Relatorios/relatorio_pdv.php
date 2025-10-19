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
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$filtro_id_caixa = (isset($_GET['id_caixa']) && $_GET['id_caixa'] !== 'Todos') ? $_GET['id_caixa'] : '';
$filtro_id_funcionario = (isset($_GET['id_funcionario']) && $_GET['id_funcionario'] !== 'Todos') ? $_GET['id_funcionario'] : '';
$filtro_id_cliente = (isset($_GET['id_cliente']) && $_GET['id_cliente'] !== 'Todos') ? $_GET['id_cliente'] : '';

$caixas_lista = $conn->query("SELECT ID_Caixa, Caixa FROM CAIXAS WHERE StatusCadastrado = 'Ativo' ORDER BY Caixa")->fetch_all(MYSQLI_ASSOC);
$funcionarios_lista = $conn->query("SELECT ID_Funcionario, Nome FROM FUNCIONARIOS WHERE Status = 'Ativo' ORDER BY Nome")->fetch_all(MYSQLI_ASSOC);
$clientes_lista = $conn->query("SELECT ID_Cliente, Nome FROM CLIENTES WHERE Status = 'Ativo' ORDER BY Nome")->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT
            V.ID_Venda, V.DataHora_Venda, V.Valor_Total, V.Desconto,
            F.Nome AS Nome_Funcionario,
            C.Nome AS Nome_Cliente,
            CX.Caixa AS Nome_Caixa,
            GROUP_CONCAT(FP.Tipo SEPARATOR ', ') AS Formas_Pagamento
        FROM VENDAS V
        LEFT JOIN FUNCIONARIOS F ON V.ID_Funcionario = F.ID_Funcionario
        LEFT JOIN CLIENTES C ON V.ID_Cliente = C.ID_Cliente
        LEFT JOIN CAIXAS_ABERTOS CA ON V.ID_CaixaAberto = CA.ID_CaixaAberto
        LEFT JOIN CAIXAS CX ON CA.ID_Caixa = CX.ID_Caixa
        LEFT JOIN VENDA_PAGAMENTOS VP ON V.ID_Venda = VP.ID_Venda
        LEFT JOIN FORMAS_PAGAMENTO FP ON VP.ID_Forma_Pag = FP.ID_Forma_Pag";

$conditions = [];
$params = [];
$types = '';

$conditions[] = "DATE(V.DataHora_Venda) BETWEEN ? AND ?";
$types .= 'ss';
$params[] = $data_inicio;
$params[] = $data_fim;

if (!empty($filtro_id_caixa)) { $conditions[] = "CX.ID_Caixa = ?"; $types .= 'i'; $params[] = $filtro_id_caixa; }
if (!empty($filtro_id_funcionario)) { $conditions[] = "V.ID_Funcionario = ?"; $types .= 'i'; $params[] = $filtro_id_funcionario; }
if (!empty($filtro_id_cliente)) { $conditions[] = "V.ID_Cliente = ?"; $types .= 'i'; $params[] = $filtro_id_cliente; }

$sql .= " WHERE " . implode(' AND ', $conditions);
$sql .= " GROUP BY V.ID_Venda ORDER BY V.DataHora_Venda DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$vendas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total_faturado = array_sum(array_column($vendas, 'Valor_Total'));
$total_descontos = array_sum(array_column($vendas, 'Desconto'));
$numero_vendas = count($vendas);
$ticket_medio = ($numero_vendas > 0) ? $total_faturado / $numero_vendas : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório Detalhado de Vendas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatórios Operacionais</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Relatório de Vendas (PDV)</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print"><i class="bi bi-printer"></i> Imprimir</button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_pdv.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3"><label>De:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-3"><label>Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-2"><label>Caixa:</label><select name="id_caixa" class="form-select"><option value="Todos">Todos</option><?php foreach ($caixas_lista as $caixa) echo "<option value='{$caixa['ID_Caixa']}' ".($filtro_id_caixa == $caixa['ID_Caixa'] ? 'selected' : '').">{$caixa['Caixa']}</option>"; ?></select></div>
                                <div class="col-md-2"><label>Funcionário:</label><select name="id_funcionario" class="form-select"><option value="Todos">Todos</option><?php foreach ($funcionarios_lista as $func) echo "<option value='{$func['ID_Funcionario']}' ".($filtro_id_funcionario == $func['ID_Funcionario'] ? 'selected' : '').">{$func['Nome']}</option>"; ?></select></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Faturamento Total</div><div class="card-body"><p class="card-text fs-2 fw-bold text-success">R$ <?= number_format($total_faturado, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Nº de Vendas</div><div class="card-body"><p class="card-text fs-2 fw-bold text-primary"><?= $numero_vendas ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Ticket Médio</div><div class="card-body"><p class="card-text fs-2 fw-bold text-info">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></p></div></div></div>
                        <div class="col-lg-3 col-md-6"><div class="card text-center h-100 shadow-sm"><div class="card-header">Total de Descontos</div><div class="card-body"><p class="card-text fs-2 fw-bold text-danger">R$ <?= number_format($total_descontos, 2, ',', '.') ?></p></div></div></div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Detalhes das Vendas</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#Venda</th>
                                        <th>Data/Hora</th>
                                        <th>Cliente</th>
                                        <th>Vendedor</th>
                                        <th>Caixa</th>
                                        <th>Formas Pag.</th>
                                        <th class="text-end">Desconto</th>
                                        <th class="text-end">Valor Total</th>
                                        <th class="text-end">Cupom Fiscal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($vendas) > 0): ?>
                                        <?php foreach ($vendas as $venda): ?>
                                            <tr>
                                                <td><?= $venda['ID_Venda'] ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($venda['DataHora_Venda'])) ?></td>
                                                <td><?= htmlspecialchars($venda['Nome_Cliente'] ?? 'Consumidor Final') ?></td>
                                                <td><?= htmlspecialchars($venda['Nome_Funcionario']) ?></td>
                                                <td><?= htmlspecialchars($venda['Nome_Caixa']) ?></td>
                                                <td><?= htmlspecialchars($venda['Formas_Pagamento']) ?></td>
                                                <td class="text-end text-danger">R$ <?= number_format($venda['Desconto'], 2, ',', '.') ?></td>
                                                <td class="text-end fw-bold text-success">R$ <?= number_format($venda['Valor_Total'], 2, ',', '.') ?></td>
                                                <td class="text-center">
                                                    <a href="../PDV/cupomNfiscal.php?ID_Venda=<?= $venda['ID_Venda'] ?>" class="btn btn-success" title="Acessar Cupom Fiscal" target="_blank"><i class="bi bi-receipt"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="9" class="text-center p-4">Nenhuma venda encontrada para os filtros selecionados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php include_once DEV_PATH . 'Views/footer.php'; ?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-cart-check-fill"></i> Relatório de Vendas (PDV)</h4>
            <hr>
            <p>Este relatório oferece uma visão detalhada de todas as vendas realizadas no Ponto de Venda. É a ferramenta principal para auditar transações, entender o fluxo de vendas e analisar o desempenho por diferentes ângulos.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Busca</h6>
            <p>Utilize a combinação de filtros para extrair informações precisas:</p>
            <ul>
                <li><strong>Período (De/Até):</strong> Defina o intervalo de datas que deseja analisar.</li>
                <li><strong>Caixa:</strong> Filtre as vendas realizadas em um caixa específico.</li>
                <li><strong>Funcionário:</strong> Veja o desempenho de vendas de um vendedor em particular.</li>
                <li><strong>Cliente:</strong> Filtre para ver o histórico de compras de um cliente específico (esta funcionalidade está desabilitada no momento).</li>
            </ul>

            <h6><i class="bi bi-bar-chart-line-fill"></i> Indicadores de Desempenho</h6>
            <p>Os cards no topo da página fornecem um resumo rápido do período filtrado:</p>
            <ul>
                <li><strong>Faturamento Total:</strong> A soma do valor de todas as vendas.</li>
                <li><strong>Nº de Vendas:</strong> A quantidade total de transações realizadas.</li>
                <li><strong>Ticket Médio:</strong> O valor médio de cada venda (Faturamento Total / Nº de Vendas). É um indicador chave da performance de vendas.</li>
                <li><strong>Total de Descontos:</strong> A soma de todos os descontos concedidos, ajudando a medir o impacto das promoções.</li>
            </ul>

            <h6><i class="bi bi-list-ol"></i> Detalhes das Vendas</h6>
            <p>A tabela principal lista cada venda individualmente, com detalhes como data, hora, cliente, vendedor, caixa, formas de pagamento utilizadas e valores. Você pode clicar no ícone de cupom <i class="bi bi-receipt text-success"></i> para visualizar e reimprimir o cupom não fiscal de qualquer venda.</p>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
    </body>
</html>