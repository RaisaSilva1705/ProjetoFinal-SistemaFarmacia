<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'FINANCEIRO_VER');
include DEV_PATH . "Exec/validar_acesso.php";

// Buscar as categorias de despesas ativas para preencher o <select>
$stmt = $conn->prepare("SELECT ID_Categoria_Despesa, Nome_Categoria FROM DESPESAS_CATEGORIAS ORDER BY Nome_Categoria ASC");
$stmt->execute();
$categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Nova Despesa</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Gestão de Despesas</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">
                            Cadastrar Nova Despesa
                        </h2>
                        <a href="despesas.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left-circle"></i> Voltar para a Lista
                        </a>
                    </div>

                    <div class="card card-body">
                        <form action="../../dev/Exec/processa_despesa.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="descricao" class="form-label">Descrição da Despesa</label>
                                    <input type="text" class="form-control" id="descricao" name="descricao" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="id_categoria" class="form-label">Categoria</label>
                                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                                        <option value="" selected disabled>Selecione...</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['ID_Categoria_Despesa']; ?>">
                                                <?php echo htmlspecialchars($categoria['Nome_Categoria']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="valor" class="form-label">Valor (R$)</label>
                                    <input type="number" class="form-control" id="valor" name="valor" step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="data_vencimento" class="form-label">Data de Vencimento</label>
                                    <input type="date" class="form-control" id="data_vencimento" name="data_vencimento">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <div class="row"></div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="statusPendente" value="Pendente" checked>
                                        <label class="form-check-label" for="statusPendente">Pendente</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="statusPaga" value="Paga">
                                        <label class="form-check-label" for="statusPaga">Paga</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4" id="campo_data_pagamento" style="display: none;">
                                    <label for="data_pagamento" class="form-label">Data de Pagamento</label>
                                    <input type="date" class="form-control" id="data_pagamento" name="data_pagamento">
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">Salvar Despesa</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
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
        <script src="<?php echo DEV_URL ?>JS/toast.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const statusRadios = document.querySelectorAll('input[name="status"]');
                const campoDataPagamento = document.getElementById('campo_data_pagamento');
                const inputDataPagamento = document.getElementById('data_pagamento');

                function toggleDataPagamento() {
                    const statusSelecionado = document.querySelector('input[name="status"]:checked').value;
                    if (statusSelecionado === 'Paga') {
                        campoDataPagamento.style.display = 'block';
                        inputDataPagamento.required = true;
                        if (!inputDataPagamento.value) 
                            inputDataPagamento.valueAsDate = new Date();
                    } 
                    else {
                        campoDataPagamento.style.display = 'none';
                        inputDataPagamento.required = false;
                        inputDataPagamento.value = ''; 
                    }
                }

                statusRadios.forEach(radio => radio.addEventListener('change', toggleDataPagamento));
            });

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