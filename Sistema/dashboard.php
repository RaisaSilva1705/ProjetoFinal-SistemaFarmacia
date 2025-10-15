<?php
// ... seu bloco PHP de buscar os dados para os cards continua o mesmo ...
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include "../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . 'Exec/validar_sessao.php';

// Card: Vendas Hoje
$totalVendasHoje = $conn->query("SELECT SUM(Valor_Total) AS total_hoje FROM VENDAS WHERE DATE(DataHora_Venda) = CURDATE()")->fetch_assoc()['total_hoje'] ?? 0.00; 

// Card: Contas a Pagar Hoje
$contasPagarHoje = $conn->query("SELECT COUNT(ID_Despesa) as total FROM DESPESAS WHERE Status = 'Pendente' AND Data_Vencimento = CURDATE()")->fetch_assoc()['total'] ?? 0;

// Card: Clientes Aniversariantes do Mês
$clientesAtivos = $conn->query("SELECT COUNT(ID_Cliente) as total FROM CLIENTES WHERE Status = 'Ativo'")->fetch_assoc()['total'] ?? 0;

// Card: Estoque Baixo
$totalEstoqueBaixo = $conn->query("SELECT COUNT(*) AS total_baixo FROM (SELECT P.Quant_Minima, SUM(E.Quantidade) AS Quantidade_Total FROM PRODUTOS P LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto LEFT JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote WHERE P.Status = 'Ativo' GROUP BY P.ID_Produto, P.Quant_Minima) AS subquery WHERE Quantidade_Total < Quant_Minima")->fetch_assoc()['total_baixo'] ?? 0;

// Box: Últimas Movimentações (sem alteração)
$resultMovimentacoes = $conn->query("SELECT Tipo, Valor, Descricao FROM MOVIMENTACOES_CAIXA ORDER BY Data_Movimentacao DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    <style> a.card-link { text-decoration: none; } </style>
</head>
<body class="bg-light">

    <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

    <div class="content d-flex flex-column min-vh-100">
        <div class="flex-grow-1">
            <div class="container-fluid bg-secondary text-white text-center p-4">
                <h3>Painel de Controle</h3>
            </div>
    
            <div class="container mt-2 p-4">
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="<?= SISTEMA_URL ?>Relatorios/relatorio_pdv.php?data_inicio=<?= date('Y-m-d') ?>&data_fim=<?= date('Y-m-d') ?>" class="card-link">
                            <div class="card text-white bg-success shadow h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Vendas Hoje</h5><i class="bi bi-cash-coin fs-2"></i></div><p class="card-text fs-3 fw-bold mt-2">R$ <?= number_format($totalVendasHoje, 2, ',', '.') ?></p></div></div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="<?= SISTEMA_URL ?>Financeiro/despesas.php?status=Pendente&data_fim=<?= date('Y-m-d') ?>" class="card-link">
                            <div class="card text-white bg-warning shadow h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Contas a Pagar Hoje</h5><i class="bi bi-calendar-day fs-2"></i></div><p class="card-text fs-3 fw-bold mt-2"><?= $contasPagarHoje ?> conta(s)</p></div></div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="<?= SISTEMA_URL ?>Relatorios/relatorio_clientes.php" class="card-link">
                             <div class="card text-white bg-primary shadow h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Clientes Cadastrados</h5><i class="bi bi-person-check-fill fs-2"></i></div><p class="card-text fs-3 fw-bold mt-2"><?= $clientesAtivos ?></p></div></div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="<?= SISTEMA_URL ?>Estoque/estoque.php?status=Abaixo" class="card-link">
                            <div class="card text-white bg-danger shadow h-100"><div class="card-body"><div class="d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">Estoque Baixo</h5><i class="bi bi-box-seam-fill fs-2"></i></div><p class="card-text fs-3 fw-bold mt-2"><?= $totalEstoqueBaixo ?> itens</p></div></div>
                        </a>
                    </div>
                </div>
    
                <div class="row m-4">
                    <div class="col-lg-7 mb-4"><div class="card shadow h-100"><div class="card-header">Vendas nos Últimos 7 Dias</div><div class="card-body"><canvas id="graficoVendasSemana"></canvas></div></div></div>
                    <div class="col-lg-5 mb-4"><div class="card shadow h-100"><div class="card-header">Vendas por Pagamento (Mês Atual)</div><div class="card-body"><canvas id="graficoVendasPagamento"></canvas></div></div></div>
                    <div class="col-lg-7 mb-4"><div class="card shadow h-100"><div class="card-header">Top 5 Categorias (Mês Atual)</div><div class="card-body"><canvas id="graficoTopCategorias"></canvas></div></div></div>
                    <div class="col-lg-5 mb-4"><div class="card shadow h-100"><div class="card-header">Top 5 Produtos (Mês Atual)</div><div class="card-body"><canvas id="graficoTopProdutos"></canvas></div></div></div>
                    <div class="col-lg-7 mb-4"><div class="card shadow h-100"><div class="card-header">Volume de Vendas por Hora (Últimos 30 dias)</div><div class="card-body"><canvas id="graficoVendasHora"></canvas></div></div></div>
                    <div class="col-lg-5 mb-4"><div class="card shadow h-100"><div class="card-header">Últimas Movimentações de Caixa</div><div class="card-body"><ul class="list-group list-group-flush"><?php if ($resultMovimentacoes->num_rows > 0): while($mov = $resultMovimentacoes->fetch_assoc()): ?><li class="list-group-item d-flex justify-content-between align-items-center"><?= htmlspecialchars($mov['Descricao']) ?><span class="fw-bold <?= $mov['Tipo'] == 'Entrada' ? 'text-success' : 'text-danger' ?>"><?= $mov['Tipo'] == 'Entrada' ? '+' : '-' ?> R$ <?= number_format($mov['Valor'], 2, ',', '.') ?></span></li><?php endwhile; else: ?><li class="list-group-item">Nenhuma movimentação.</li><?php endif; ?></ul></div></div></div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>
        
        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="<?php echo DEV_URL ?>JS/toast.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const generateColors = (num) => Array.from({length: num}, () => `rgba(${Math.floor(Math.random()*200)}, ${Math.floor(Math.random()*200)}, ${Math.floor(Math.random()*200)}, 0.7)`);

                fetch('../Dev/Exec/dados_graficos.php')
                    .then(response => response.json())
                    .then(data => {
                        // Gráfico 1: Vendas da Semana
                        new Chart(document.getElementById('graficoVendasSemana'), {type: 'line', data: {labels: data.vendasSemana.map(i=>i.dia), datasets: [{label: 'Faturamento R$', data: data.vendasSemana.map(i=>i.total), borderColor: 'rgba(25,135,84,1)', backgroundColor: 'rgba(25,135,84,0.2)', fill: true, tension: 0.1}]}, options: {responsive: true, maintainAspectRatio: false}});
                        
                        // Gráfico 2: Vendas por Pagamento
                        new Chart(document.getElementById('graficoVendasPagamento'), {type: 'doughnut', data: {labels: data.vendasPorPagamento.map(i=>i.Tipo), datasets: [{data: data.vendasPorPagamento.map(i=>i.total), backgroundColor: generateColors(data.vendasPorPagamento.length)}]}, options: {responsive: true, maintainAspectRatio: false}});
                        
                        // Gráfico 3: Top Categorias
                        new Chart(document.getElementById('graficoTopCategorias'), {type: 'bar', data: {labels: data.topCategorias.map(i=>i.Categoria), datasets: [{label: 'Faturamento R$', data: data.topCategorias.map(i=>i.total), backgroundColor: generateColors(data.topCategorias.length)}]}, options: {responsive: true, maintainAspectRatio: false, indexAxis: 'y'}});

                        // NOVO Gráfico 4: Top Produtos
                        new Chart(document.getElementById('graficoTopProdutos'), {type: 'pie', data: {labels: data.topProdutos.map(i=>i.Nome), datasets: [{data: data.topProdutos.map(i=>i.total), backgroundColor: generateColors(data.topProdutos.length)}]}, options: {responsive: true, maintainAspectRatio: false}});

                        // NOVO Gráfico 5: Vendas por Hora
                        const horasDoDia = Array.from({length: 24}, (_, i) => `${i}:00`);
                        const vendasPorHoraData = new Array(24).fill(0);
                        data.vendasPorHora.forEach(item => { vendasPorHoraData[item.hora] = item.total_vendas; });
                        new Chart(document.getElementById('graficoVendasHora'), {type: 'bar', data: {labels: horasDoDia, datasets: [{label: 'Nº de Vendas', data: vendasPorHoraData, backgroundColor: 'rgba(13, 110, 253, 0.7)'}]}, options: {responsive: true, maintainAspectRatio: false}});
                    });

                <?php
                if (isset($_SESSION['msg']) && is_array($_SESSION['msg'])) {
                    $texto = addslashes($_SESSION['msg']['texto']);
                    $tipo = $_SESSION['msg']['tipo']; 
                    
                    echo "mostrarToast('{$texto}', '{$tipo}');";
                    
                    unset($_SESSION['msg']);
                }
                ?>
            });
        </script>
    </body>
</html>