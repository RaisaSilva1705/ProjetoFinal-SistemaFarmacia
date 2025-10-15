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
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Central de Relatórios</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Central de Relatórios</h3>
                </div>
                
                <div class="container p-5">

                    <h3 class="mb-4">Análises Estratégicas e Financeiras</h3>
                    <div class="row">
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-pie-chart-fill fs-1 text-success"></i>
                                    <h5 class="card-title mt-3">Relatório Financeiro (DRE)</h5>
                                    <p class="card-text text-muted">Acompanhe a saúde financeira da farmácia: Receita, Custos, Despesas e Lucro Líquido.</p>
                                    <a href="relatorio_financeiro.php" class="btn btn-success mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-receipt-cutoff fs-1 text-primary"></i>
                                    <h5 class="card-title mt-3">Relatório de Vendas (PDV)</h5>
                                    <p class="card-text text-muted">Visualize o registro detalhado de todas as vendas realizadas, com filtros avançados.</p>
                                    <a href="relatorio_pdv.php" class="btn btn-primary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-box-seam-fill fs-1 text-warning"></i>
                                    <h5 class="card-title mt-3">Posição de Estoque</h5>
                                    <p class="card-text text-muted">Analise o valor do seu inventário e identifique produtos que precisam de reposição.</p>
                                    <a href="relatorio_estoque.php" class="btn btn-warning mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h3 class="mb-4 mt-5">Análises de Desempenho</h3>
                    <div class="row">
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-bar-chart-line-fill fs-1 text-info"></i>
                                    <h5 class="card-title mt-3">Desempenho de Produtos</h5>
                                    <p class="card-text text-muted">Descubra os produtos mais vendidos, mais rentáveis e o lucro bruto por item.</p>
                                    <a href="relatorio_produtos.php" class="btn btn-info mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-people-fill fs-1" style="color: #6f42c1;"></i>
                                    <h5 class="card-title mt-3">Desempenho de Clientes</h5>
                                    <p class="card-text text-muted">Identifique seus clientes mais valiosos e analise o ranking de compras.</p>
                                    <a href="relatorio_clientes.php" class="btn btn-primary mt-auto" style="background-color: #6f42c1; border-color: #6f42c1;">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-person-badge-fill fs-1 text-secondary"></i>
                                    <h5 class="card-title mt-3">Desempenho de Funcionários</h5>
                                    <p class="card-text text-muted">Acompanhe o desempenho de vendas da sua equipe e o ticket médio por vendedor.</p>
                                    <a href="relatorio_funcionarios.php" class="btn btn-secondary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h3 class="mb-4 mt-5">Auditoria e Conformidade</h3>
                    <div class="row">
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-file-earmark-medical-fill fs-1 text-danger"></i>
                                    <h5 class="card-title mt-3">Dispensação de Controlados</h5>
                                    <p class="card-text text-muted">Rastreie todas as vendas de medicamentos controlados para fins de auditoria e SNGPC.</p>
                                    <a href="relatorio_controlados.php" class="btn btn-danger mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-shield-lock-fill fs-1 text-secondary"></i>
                                    <h5 class="card-title mt-3">Atividades do Sistema (Logs)</h5>
                                    <p class="card-text text-muted">Audite todas as ações realizadas pelos usuários no sistema, com filtros de data e funcionário.</p>
                                    <a href="relatorio_logs.php" class="btn btn-secondary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-archive-fill fs-1" style="color: #6c757d;"></i>
                                    <h5 class="card-title mt-3">Histórico de Caixas</h5>
                                    <p class="card-text text-muted">Consulte o histórico de todos os caixas fechados, com valores e operadores.</p>
                                    <a href="relatorio_caixas.php" class="btn btn-secondary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-clipboard2-pulse-fill fs-1" style="color: #0dcaf0;"></i>
                                    <h5 class="card-title mt-3">Serviços Farmacêuticos</h5>
                                    <p class="card-text text-muted">Analise o faturamento e o desempenho dos serviços clínicos prestados.</p>
                                    <a href="relatorio_servicos.php" class="btn btn-info mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-truck fs-1" style="color: #343a40;"></i>
                                    <h5 class="card-title mt-3">Análise de Fornecedores</h5>
                                    <p class="card-text text-muted">Veja quais fornecedores representam a maior parte do seu faturamento.</p>
                                    <a href="relatorio_fornecedores.php" class="btn btn-dark mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>