<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include "../Dev/Exec/config.php";

include DEV_PATH . "Exec/validar_sessao.php";


$cargo = $_SESSION['ID_Cargo'];

if ($cargo == 4 || $cargo == 5 || $cargo == 9){
    $mostrarFunc = true;
    $tamGridCima = 'col-lg-3';
    $tamGridBaixo = 'col-lg-3';
    $corCli = 'bg-info';
    $corPre = 'bg-primary';
}
else {
    $mostrarFunc = false;
    $tamGridCima = 'col-lg-3';
    $tamGridBaixo = 'col-lg-3';
    $corCli = 'bg-primary';
    $corPre = 'bg-success';
}
    
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Painel Administrativo</h3>
                </div>
    
                <!-- Dashboard Cards -->
                <div class="container mt-4">
                    <div class="row justify-content-center">
    
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-success shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Vendas Hoje</h5>
                                    <p class="card-text fs-4">R$ 1.235,00</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-warning shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Estoque Baixo</h5>
                                    <p class="card-text fs-4">12 itens</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-primary shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Clientes Ativos</h5>
                                    <p class="card-text fs-4">215</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card text-white bg-danger shadow">
                                <div class="card-body">
                                    <h5 class="card-title">Caixa Atual</h5>
                                    <p class="card-text fs-4">R$ 3.480,00</p>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
    
                <!-- Área para gráficos ou relatórios -->
                <div class="row m-4">
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header bg-light">
                                Vendas da Semana
                            </div>
                            <div class="card-body">
                                <p>Gráfico aqui (ex: Chart.js)</p>
                            </div>
                        </div>
                    </div>
    
                    <div class="col-md-6">
                        <div class="card shadow">
                            <div class="card-header bg-light">
                                Últimas Movimentações
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <li class="list-group-item">+ R$ 200,00 - Venda #124</li>
                                    <li class="list-group-item">- R$ 50,00 - Saída de Caixa</li>
                                    <li class="list-group-item">+ R$ 110,00 - Venda #125</li>
                                    <li class="list-group-item">+ R$ 89,90 - Venda #126</li>
                                </ul>
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

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function mostrarToast(texto, tipo = 'success', titulo = 'Notificação') {
                const toastLiveExample = document.getElementById('liveToast');
                const toastHeader = toastLiveExample.querySelector('.toast-header');
                
                // Define o título padrão baseado no tipo, se não for fornecido
                if (titulo === 'Notificação') 
                    titulo = ucfirst(tipo === 'danger' ? 'Erro' : (tipo === 'warning' ? 'Atenção' : 'Sucesso'));
                
                const headerClass = `text-bg-${tipo}`;

                document.getElementById('toastTitulo').innerText = titulo;
                document.getElementById('toastCorpo').innerText = texto;
                
                // Remove classes de cor antigas e adiciona a nova
                toastHeader.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning', 'text-bg-info');
                toastHeader.classList.add(headerClass);

                const toast = new bootstrap.Toast(toastLiveExample);
                toast.show();
            }

            // Função auxiliar para deixar a primeira letra maiúscula (o PHP faz isso, o JS não)
            function ucfirst(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
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