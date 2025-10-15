<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'ESTOQUE_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$fornecedores = $conn->query("SELECT ID_Fornecedor, Nome_Fantasia FROM FORNECEDORES WHERE Status = 'Ativo' ORDER BY Nome_Fantasia")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Devolução para Fornecedor</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Registrar Devolução para Fornecedor</h3>
                </div>
                
                <div class="container p-5">
                    <form action="processa_devolucao_fornecedor.php" method="POST">
                        <div class="card card-body mb-4">
                            <h5 class="card-title">1. Selecione o Fornecedor</h5>
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="id_fornecedor" class="form-label">Fornecedor</label>
                                    <select name="id_fornecedor" id="id_fornecedor" class="form-select" required>
                                        <option value="" selected disabled>Selecione um fornecedor para carregar os produtos...</option>
                                        <?php foreach ($fornecedores as $fornecedor): ?>
                                            <option value="<?= $fornecedor['ID_Fornecedor'] ?>"><?= htmlspecialchars($fornecedor['Nome_Fantasia']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="motivo_geral" class="form-label">Motivo Geral (Opcional)</label>
                                    <input type="text" name="motivo_geral" class="form-control" placeholder="Ex: Avarias na entrega, acordo comercial...">
                                </div>
                            </div>
                        </div>

                        <div class="card card-body">
                            <h5 class="card-title">2. Selecione os Itens para Devolver</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Devolver?</th>
                                            <th>Produto</th>
                                            <th>Lote</th>
                                            <th>Validade</th>
                                            <th class="text-center">Qtd. em Estoque</th>
                                            <th style="width: 15%;">Qtd. a Devolver</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabela_itens_devolucao">
                                        <tr><td colspan="6" class="text-center text-muted">Aguardando seleção do fornecedor.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" id="btn-confirmar" disabled>Confirmar Devolução</button>
                        </div>
                    </form>
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

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectFornecedor = document.getElementById('id_fornecedor');
                const tabelaItens = document.getElementById('tabela_itens_devolucao');
                const btnConfirmar = document.getElementById('btn-confirmar');

                selectFornecedor.addEventListener('change', function() {
                    const idFornecedor = this.value;
                    tabelaItens.innerHTML = '<tr><td colspan="6" class="text-center">Carregando produtos...</td></tr>';
                    btnConfirmar.disabled = true;

                    if (!idFornecedor) {
                        tabelaItens.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Aguardando seleção do fornecedor.</td></tr>';
                        return;
                    }

                    fetch(`../../Dev/Exec/busca_lotes_fornecedor.php?id_fornecedor=${idFornecedor}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.sucesso && data.lotes.length > 0) {
                                tabelaItens.innerHTML = '';
                                data.lotes.forEach(lote => {
                                    const dataValidade = new Date(lote.Data_Validade + 'T00:00:00').toLocaleDateString('pt-BR');
                                    const newRow = tabelaItens.insertRow();
                                    newRow.innerHTML = `
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="form-check-input" name="itens[${lote.ID_Lote}][devolver]" value="1">
                                        </td>
                                        <td>
                                            ${lote.Nome_Produto}
                                            <input type="hidden" name="itens[${lote.ID_Lote}][id_produto]" value="${lote.ID_Produto}">
                                            <input type="hidden" name="itens[${lote.ID_Lote}][preco_custo]" value="${lote.Preco_Custo}">
                                        </td>
                                        <td>${lote.Nome_Lote}</td>
                                        <td>${dataValidade}</td>
                                        <td class="text-center">${lote.Quantidade_Estoque}</td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" name="itens[${lote.ID_Lote}][quantidade]" min="1" max="${lote.Quantidade_Estoque}" disabled>
                                        </td>
                                    `;
                                });
                                btnConfirmar.disabled = false;
                            } 
                            else 
                                tabelaItens.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Nenhum produto com estoque encontrado para este fornecedor.</td></tr>';
                        });
                });

                tabelaItens.addEventListener('change', function(e) {
                    if (e.target.type === 'checkbox') {
                        const inputQuantidade = e.target.closest('tr').querySelector('input[type="number"]');
                        inputQuantidade.disabled = !e.target.checked;
                        inputQuantidade.required = e.target.checked;
                        if (!e.target.checked) 
                            inputQuantidade.value = '';
                    }
                });
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