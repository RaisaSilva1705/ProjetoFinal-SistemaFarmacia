<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Relatórios</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Central de Relatórios</h3>
                </div>
                <div class="container p-5">
                    <h2 class="mb-4">Análises do Sistema</h2>
                    <div class="row">

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-cash-coin fs-1 text-success"></i>
                                    <h5 class="card-title mt-3">Relatório de Vendas</h5>
                                    <p class="card-text text-muted">Analise o faturamento detalhado por período, cliente, funcionário e caixa.</p>
                                    <a href="relatorio_pdv.php" class="btn btn-primary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-box-seam fs-1 text-warning"></i>
                                    <h5 class="card-title mt-3">Relatório de Posição de Estoque</h5>
                                    <p class="card-text text-muted">Visualize o valor total do seu inventário (custo e venda) e identifique produtos abaixo do mínimo.</p>
                                    <a href="relatorio_estoque.php" class="btn btn-primary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-tags-fill fs-1 text-info"></i>
                                    <h5 class="card-title mt-3">Relatório de Desempenho de Produtos</h5>
                                    <p class="card-text text-muted">Descubra os produtos mais vendidos, mais rentáveis e com maior giro de estoque.</p>
                                    <a href="relatorio_produtos.php" class="btn btn-primary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-archive-fill fs-1 text-dark"></i>
                                    <h5 class="card-title mt-3">Relatório de Caixas</h5>
                                    <p class="card-text text-muted">Consulte o histórico de fechamentos, com saldos iniciais, finais e valores vendidos por sessão.</p>
                                    <a href="relatorio_caixas.php" class="btn btn-primary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-person-lines-fill fs-1" style="color: purple;"></i>
                                    <h5 class="card-title mt-3">Relatório de Clientes</h5>
                                    <p class="card-text text-muted">Identifique seus clientes mais valiosos com base no histórico de compras e valor total gasto.</p>
                                    <a href="relatorio_clientes.php" class="btn btn-primary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-person-badge fs-1 text-primary"></i>
                                    <h5 class="card-title mt-3">Relatório de Funcionários</h5>
                                    <p class="card-text text-muted">Acompanhe o desempenho de vendas da sua equipe e identifique os vendedores destaque.</p>
                                    <a href="relatorio_funcionarios.php" class="btn btn-primary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="bi bi-truck fs-1" style="color: brown;"></i>
                                    <h5 class="card-title mt-3">Relatório de Fornecedores</h5>
                                    <p class="card-text text-muted">Analise quais fornecedores trazem os produtos que mais geram faturamento para a sua loja.</p>
                                    <a href="relatorio_fornecedores.php" class="btn btn-primary mt-auto">Acessar Relatório</a>
                                </div>
                            </div>
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