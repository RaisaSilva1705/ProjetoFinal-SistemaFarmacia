<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$id_caixa = $_GET['id_caixa'] ?? '';
$id_funcionario = $_GET['id_funcionario'] ?? '';
$id_cliente = $_GET['id_cliente'] ?? '';

$caixas = $conn->query("SELECT ID_Caixa, Caixa FROM CAIXAS WHERE StatusCadastrado = 'Ativo' ORDER BY Caixa");
$funcionarios = $conn->query("SELECT ID_Funcionario, Nome FROM FUNCIONARIOS WHERE Status = 'Ativo' ORDER BY Nome");
$clientes = $conn->query("SELECT ID_Cliente, Nome FROM CLIENTES WHERE Status = 'Ativo' ORDER BY Nome");

$sql = "SELECT
            V.ID_Venda,
            V.DataHora_Venda,
            V.Valor_Total,
            F.Nome AS Nome_Funcionario,
            C.Nome AS Nome_Cliente,
            CX.Caixa,
            GROUP_CONCAT(FP.Tipo SEPARATOR ', ') AS Formas_Pagamento
        FROM VENDAS V
        LEFT JOIN FUNCIONARIOS F ON V.ID_Funcionario = F.ID_Funcionario
        LEFT JOIN CLIENTES C ON V.ID_Cliente = C.ID_Cliente
        LEFT JOIN CAIXAS_ABERTOS CA ON V.ID_CaixaAberto = CA.ID_CaixaAberto
        LEFT JOIN CAIXAS CX ON CA.ID_Caixa = CX.ID_Caixa
        LEFT JOIN VENDA_PAGAMENTOS VP ON V.ID_Venda = VP.ID_Venda
        LEFT JOIN FORMAS_PAGAMENTO FP ON VP.ID_Forma_Pag = FP.ID_Forma_Pag
";

$conditions = [];
$params = [];
$types = '';

if (!empty($data_inicio) && !empty($data_fim)) {
    $conditions[] = "DATE(V.DataHora_Venda) BETWEEN ? AND ?";
    $types .= 'ss';
    $params[] = $data_inicio;
    $params[] = $data_fim;
}
if (!empty($id_caixa)) {
    $conditions[] = "CX.ID_Caixa = ?";
    $types .= 'i';
    $params[] = $id_caixa;
}
if (!empty($id_funcionario)) {
    $conditions[] = "V.ID_Funcionario = ?";
    $types .= 'i';
    $params[] = $id_funcionario;
}
if (!empty($id_cliente)) {
    $conditions[] = "V.ID_Cliente = ?";
    $types .= 'i';
    $params[] = $id_cliente;
}
if (count($conditions) > 0) 
    $sql .= " WHERE " . implode(' AND ', $conditions);

$sql .= " GROUP BY V.ID_Venda ORDER BY V.DataHora_Venda DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) 
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$vendas = $result->fetch_all(MYSQLI_ASSOC);

// --- LÓGICA PARA OS CARDS DE RESUMO ---
$total_faturado = 0;
foreach ($vendas as $venda) {
    $total_faturado += $venda['Valor_Total'];
}
$numero_vendas = count($vendas);
$ticket_medio = ($numero_vendas > 0) ? $total_faturado / $numero_vendas : 0;
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Vendas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Relatório de Vendas</h3>
                </div>
                <div class="container p-5">
                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_pdv.php">
                            <div class="row align-items-end">
                                <div class="col-md-2">
                                    <label for="data_inicio">Data Início:</label>
                                    <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="data_fim">Data Fim:</label>
                                    <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="id_caixa">Caixa:</label>
                                    <select name="id_caixa" class="form-select">
                                        <option value="">Selecione</option>
                                        <?php while($caixa = $caixas->fetch_assoc()): 
                                            $selected = ($id_caixa == $caixa['ID_Caixa']) ? 'selected' : '';
                                            echo "<option value='{$caixa['ID_Caixa']}' {$selected}>{$caixa['Caixa']}</option>";
                                        ?>
                                        <?php endwhile; ?>
                                    </select>
                                </div>  
                                <div class="col-md-2">
                                    <label for="id_funcionario">Funcionário:</label>
                                    <select name="id_funcionario" class="form-select">
                                        <option value="">Selecione</option>
                                        <?php while($funcionario = $funcionarios->fetch_assoc()): 
                                            $selected = ($id_funcionario == $funcionario['ID_Funcionario']) ? 'selected' : ''; 
                                            echo "<option value='{$funcionario['ID_Funcionario']}' {$selected}>{$funcionario['Nome']}</option>"; 
                                        ?>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">  
                                    <label for="id_cliente">Cliente:</label>
                                    <select name="id_cliente" class="form-select">
                                        <option value="">Selecione</option>
                                        <?php while($cliente = $clientes->fetch_assoc()): 
                                            $selected = ($id_cliente == $cliente['ID_Cliente']) ? 'selected' : '';    
                                        ?>
                                            <option value="<?= $cliente['ID_Cliente'] ?> <?= $selected ?>"><?= $cliente['Nome'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Total Faturado</h5>
                                    <p class="card-text fs-4">R$ <?= number_format($total_faturado, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Número de Vendas</h5>
                                    <p class="card-text fs-4"><?= $numero_vendas ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="card-title">Ticket Médio</h5>
                                    <p class="card-text fs-4">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            Resultados do Período
                            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm no-print">Imprimir Relatório</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Venda Nº</th>
                                        <th>Data/Hora</th>
                                        <th>Cliente</th>
                                        <th>Vendedor</th>
                                        <th>Caixa</th>
                                        <th>Formas de Pag.</th>
                                        <th class="text-end">Valor Total</th>
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
                                                <td><?= htmlspecialchars($venda['Caixa']) ?></td>
                                                <td><?= htmlspecialchars($venda['Formas_Pagamento']) ?></td>
                                                <td class="text-end">R$ <?= number_format($venda['Valor_Total'], 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="7" class="text-center">Nenhuma venda encontrada para os filtros selecionados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
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