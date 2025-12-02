<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'RELATORIOS_VER');
include DEV_PATH . "Exec/validar_acesso.php";

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t'); 
$data_fim_query = $data_fim . ' 23:59:59';

// --------------------------------------------------------------------------
// 1. CALCULAR RECEITA BRUTA TOTAL
// --------------------------------------------------------------------------
$stmt_receita = $conn->prepare(
    "SELECT SUM(Valor_Total) as total_vendas 
     FROM VENDAS 
     WHERE DataHora_Venda BETWEEN ? AND ?"
);
$stmt_receita->bind_param("ss", $data_inicio, $data_fim_query);
$stmt_receita->execute();
$receita_bruta = $stmt_receita->get_result()->fetch_assoc()['total_vendas'] ?? 0;
$stmt_receita->close();

$stmt_dev = $conn->prepare("SELECT SUM(Valor_Total_Devolvido) AS total_devolvido FROM DEVOLUCOES_CLIENTES WHERE Data_Devolucao BETWEEN ? AND ?");
$stmt_dev->bind_param("ss", $data_inicio, $data_fim_query);
$stmt_dev->execute();
$total_devolucoes = $stmt_dev->get_result()->fetch_assoc()['total_devolvido'] ?? 0;
$stmt_dev->close();

$receita_liquida = $receita_bruta - $total_devolucoes;

// --------------------------------------------------------------------------
// 2. CALCULAR DESPESAS OPERACIONAIS
// --------------------------------------------------------------------------
$stmt_despesas = $conn->prepare(
    "SELECT DC.Nome_Categoria, SUM(D.Valor) AS valor_total_categoria
     FROM DESPESAS D
     JOIN DESPESAS_CATEGORIAS DC ON D.ID_Categoria_Despesa = DC.ID_Categoria_Despesa
     WHERE D.Status = 'Paga' 
       AND D.Status_Registro = 'Ativo'
       AND D.Data_Pagamento BETWEEN ? AND ?
     GROUP BY DC.Nome_Categoria
     ORDER BY valor_total_categoria DESC"
);
$stmt_despesas->bind_param("ss", $data_inicio, $data_fim);
$stmt_despesas->execute();
$lista_despesas_detalhada = $stmt_despesas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_despesas->close();
$despesas_operacionais = array_sum(array_column($lista_despesas_detalhada, 'valor_total_categoria'));

// --------------------------------------------------------------------------
// 3. CALCULAR CUSTO DAS PERDAS DE ESTOQUE
// --------------------------------------------------------------------------
$stmt_perdas = $conn->prepare(
    "SELECT SUM(ME.Quantidade * CustoMedioProduto.avg_cost) as custo_total_perdas
     FROM MOVIMENTACAO_ESTOQUE ME
     JOIN (
         SELECT ID_Produto, AVG(Preco_Custo) as avg_cost
         FROM LOTES
         GROUP BY ID_Produto
     ) AS CustoMedioProduto ON ME.ID_Produto = CustoMedioProduto.ID_Produto
     WHERE ME.Tipo = 'Saída'
       AND ME.Motivo IS NOT NULL AND ME.Motivo != 'Venda'
       AND ME.Data_Movimentacao BETWEEN ? AND ?"
);
$stmt_perdas->bind_param("ss", $data_inicio, $data_fim_query);
$stmt_perdas->execute();
$custo_perdas_estoque = $stmt_perdas->get_result()->fetch_assoc()['custo_total_perdas'] ?? 0;
$stmt_perdas->close();
$despesas_operacionais += $custo_perdas_estoque;

// --------------------------------------------------------------------------
// 4. CALCULAR CUSTO DA MERCADORIA VENDIDA (CMV) 
// --------------------------------------------------------------------------
$stmt_cmv = $conn->prepare(
    "SELECT SUM(IV.Quantidade * CustoMedioProduto.avg_cost) as total_cmv
     FROM VENDAS V
     JOIN ITENS_VENDA IV ON V.ID_Venda = IV.ID_Venda
     JOIN (
         SELECT ID_Produto, AVG(Preco_Custo) as avg_cost
         FROM LOTES
         GROUP BY ID_Produto
     ) AS CustoMedioProduto ON IV.ID_Produto = CustoMedioProduto.ID_Produto
     WHERE V.DataHora_Venda BETWEEN ? AND ?"
);
$stmt_cmv->bind_param("ss", $data_inicio, $data_fim_query);
$stmt_cmv->execute();
$cmv = $stmt_cmv->get_result()->fetch_assoc()['total_cmv'] ?? 0;
$stmt_cmv->close();

// --------------------------------------------------------------------------
// 5. CALCULAR OS RESULTADOS FINAIS
// --------------------------------------------------------------------------
$lucro_bruto = $receita_liquida - $cmv;
$lucro_liquido = $lucro_bruto - $despesas_operacionais;
$margem_liquida = ($receita_liquida > 0) ? ($lucro_liquido / $receita_liquida) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório Financeiro (DRE)</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Análise Financeira</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Relatório Financeiro</h2>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-printer"></i> Imprimir Relatório
                        </button>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_financeiro.php">
                            <div class="row align-items-end">
                                <div class="col-md-5">
                                    <label for="data_inicio" class="form-label">Período de:</label>
                                    <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>">
                                </div>
                                <div class="col-md-5">
                                    <label for="data_fim" class="form-label">Até:</label>
                                    <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-funnel-fill"></i> Filtrar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-lg-3 col-md-6">
                            <div class="card text-center h-100 shadow-sm">
                                <div class="card-header">Receita Líquida</div>
                                <div class="card-body">
                                    <h4 class="card-title fw-bold text-success">R$ <?= number_format($receita_liquida, 2, ',', '.'); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card text-center h-100 shadow-sm">
                                <div class="card-header">CMV (Custo)</div>
                                <div class="card-body">
                                    <h4 class="card-title fw-bold text-danger">R$ <?= number_format($cmv, 2, ',', '.'); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card text-center h-100 shadow-sm">
                                <div class="card-header">Perdas + Despesas</div>
                                <div class="card-body">
                                    <h4 class="card-title fw-bold text-danger">R$ <?= number_format($despesas_operacionais, 2, ',', '.'); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="card text-center h-100 shadow-sm">
                                <div class="card-header">Lucro Líquido</div>
                                <div class="card-body">
                                    <h4 class="card-title fw-bold <?= $lucro_liquido >= 0 ? 'text-primary' : 'text-danger' ?>">
                                        R$ <?= number_format($lucro_liquido, 2, ',', '.'); ?>
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="m-0">Demonstrativo de Resultados</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <tbody>
                                    <tr class="table-light">
                                        <td class="fw-bold fs-5">(+) Receita Bruta de Vendas</td>
                                        <td class="text-end fw-bold fs-5">R$ <?= number_format($receita_bruta, 2, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">(-) Devoluções e Abatimentos</td>
                                        <td class="text-end text-danger">(R$ <?= number_format($total_devolucoes, 2, ',', '.'); ?>)</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td class="fw-bold fs-5">(=) Receita Líquida</td>
                                        <td class="text-end fw-bold fs-5">R$ <?= number_format($receita_liquida, 2, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">(-) Custo da Mercadoria Vendida (CMV)</td>
                                        <td class="text-end text-danger">(R$ <?= number_format($cmv, 2, ',', '.') ?>)</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td class="fw-bold fs-5">(=) Lucro Bruto</td>
                                        <td class="text-end fw-bold fs-5">R$ <?= number_format($lucro_bruto, 2, ',', '.'); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">(-) Despesas Operacionais</td>
                                    </tr>
                                    <?php if (empty($lista_despesas_detalhada)): ?>
                                        <tr>
                                            <td class="ps-4"><em>Nenhuma despesa registrada no período.</em></td>
                                            <td class="text-end text-danger">(R$ 0,00)</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($lista_despesas_detalhada as $despesa): ?>
                                            <tr>
                                                <td class="ps-4"><em>└ <?php echo htmlspecialchars($despesa['Nome_Categoria']); ?></em></td>
                                                <td class="text-end text-danger">(R$ <?= number_format($despesa['valor_total_categoria'], 2, ',', '.'); ?>)</td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if ($custo_perdas_estoque > 0): ?>
                                            <tr>
                                                <td class="ps-4 text-danger"><em>└ Perdas de Estoque (Avarias/Devoluções)</em></td>
                                                <td class="text-end text-danger">(R$ <?= number_format($custo_perdas_estoque, 2, ',', '.'); ?>)</td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <tr class="table-light border-top">
                                        <td class="fw-bold">Total Despesas Operacionais</td>
                                        <td class="text-end fw-bold text-danger">(R$ <?= number_format($despesas_operacionais, 2, ',', '.'); ?>)</td>
                                    </tr>

                                    <tr class="table-dark">
                                        <td class="fw-bold fs-5">(=) Resultado Líquido do Exercício</td>
                                        <td class="text-end fw-bold fs-5 <?= $lucro_liquido >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            R$ <?= number_format($lucro_liquido, 2, ',', '.'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php include_once DEV_PATH . 'Views/footer.php'; ?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-graph-up-arrow"></i> Relatório Financeiro (DRE)</h4>
            <hr>
            <p>O <strong>DRE (Demonstrativo do Resultado do Exercício)</strong> é o coração financeiro da farmácia. Ele foi ajustado para refletir a realidade do balcão, contabilizando vendas, cancelamentos e perdas de produtos.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtro por Período</h6>
            <p>Selecione as datas de início e fim no topo da página para analisar o desempenho de um mês, semana ou dia específico.</p>

            <h6><i class="bi bi-calculator-fill"></i> Estrutura do Relatório (Como ler os números)</h6>
            <p>A tabela segue a lógica contábil oficial para determinar se houve lucro ou prejuízo:</p>
            <ol>
                <li>
                    <strong>(+) Receita Bruta:</strong> A soma total de todas as vendas registradas no PDV.
                </li>
                <li>
                    <strong>(-) Devoluções e Abatimentos:</strong> Aqui subtraímos o valor de produtos que foram devolvidos pelos clientes e reembolsados. Dinheiro que voltou para o cliente não é receita.
                </li>
                <li>
                    <strong>(=) Receita Líquida:</strong> É o dinheiro real que ficou na farmácia após os descontos das devoluções. Todos os custos abaixo são calculados sobre este valor.
                </li>
                <li>
                    <strong>(-) CMV (Custo da Mercadoria Vendida):</strong> Quanto custou para comprar os produtos que foram vendidos.
                </li>
                <li>
                    <strong>(-) Despesas Operacionais:</strong> Contas pagas (água, luz, salários) e <strong>Perdas de Estoque</strong>.
                    <br><em class="text-muted">Nota: Se um produto for devolvido e marcado como "Avariado/Vencido", seu custo entra aqui como prejuízo, pois ele não volta para a prateleira.</em>
                </li>
                <li>
                    <strong>(=) Lucro Líquido:</strong> O resultado final. Se positivo <span class="text-success">(Verde)</span>, a operação deu lucro. Se negativo <span class="text-danger">(Vermelho)</span>, os custos superaram as vendas.
                </li>
            </ol>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
    </body>
</html>