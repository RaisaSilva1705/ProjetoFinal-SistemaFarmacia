<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'PRODUTOS_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Impressão de Etiquetas de Preço</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Impressão de Etiquetas de Preço</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Gerador de Etiquetas</h2>
                    </div>

                    <div class="card card-body mb-4">
                        <label for="busca_produto" class="form-label fw-bold">1. Buscar Produto (Nome ou EAN)</label>
                        <div class="position-relative">
                            <input type="text" id="busca_produto" class="form-control" autocomplete="off" placeholder="Digite para buscar e adicionar à fila de impressão...">
                            <div id="sugestoes_produto" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                        </div>
                    </div>

                    <form action="imprimir_etiquetas.php" method="POST" target="_blank">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="m-0">2. Fila de Impressão</h4>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produto</th>
                                            <th style="width: 25%;">Modelo da Etiqueta</th>
                                            <th style="width: 20%;">Tamanho</th>
                                            <th class="text-center" style="width: 10%;">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fila-impressao">
                                        <tr id="placeholder-fila">
                                            <td colspan="4" class="text-center text-muted p-4">Nenhum produto na fila.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" id="btn-gerar-etiquetas" class="btn btn-primary btn-lg" disabled>
                                <i class="bi bi-printer-fill"></i> Gerar Etiquetas para Impressão
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const campoBusca = document.getElementById('busca_produto');
                const sugestoesDiv = document.getElementById('sugestoes_produto');
                const filaImpressao = document.getElementById('fila-impressao');
                const placeholderFila = document.getElementById('placeholder-fila');
                const btnGerarEtiquetas = document.getElementById('btn-gerar-etiquetas');

                campoBusca.addEventListener('input', function() {
                    const termo = this.value.trim();
                    if (termo.length < 2) {
                        sugestoesDiv.innerHTML = '';
                        return;
                    }

                    fetch(`../../Dev/Exec/busca_produto.php?nome=${encodeURIComponent(termo)}`)
                        .then(response => response.json())
                        .then(produtos => {
                            sugestoesDiv.innerHTML = '';
                            produtos.forEach(produto => {
                                const item = document.createElement('a');
                                item.href = '#';
                                item.classList.add('list-group-item', 'list-group-item-action');
                                item.textContent = `${produto.Nome} (EAN: ${produto.EAN_GTIN})`;
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    adicionarProdutoAFila(produto);
                                    campoBusca.value = '';
                                    sugestoesDiv.innerHTML = '';
                                });
                                sugestoesDiv.appendChild(item);
                            });
                        });
                });

                function adicionarProdutoAFila(produto) {
                    if (document.querySelector(`tr[data-id-produto="${produto.ID_Produto}"]`)) {
                        mostrarToast('Este produto já está na fila de impressão.', 'warning');
                        return;
                    }

                    if (placeholderFila) placeholderFila.remove();

                    const newRow = document.createElement('tr');
                    newRow.setAttribute('data-id-produto', produto.ID_Produto);
                    newRow.innerHTML = `
                        <td>
                            ${produto.Nome}
                            <input type="hidden" name="etiquetas[${produto.ID_Produto}][id]" value="${produto.ID_Produto}">
                            <input type="hidden" name="etiquetas[${produto.ID_Produto}][nome]" value="${produto.Nome}">
                            <input type="hidden" name="etiquetas[${produto.ID_Produto}][ean]" value="${produto.EAN_GTIN}">
                        </td>
                        <td>
                            <select class="form-select form-select-sm" name="etiquetas[${produto.ID_Produto}][modelo]" required>
                                <option value="normal_branca" selected>Normal (Branca)</option>
                                <option value="promocao_amarela">Promoção (Amarela)</option>
                                <option value="oferta_vermelha">Oferta Imperdível (Vermelha)</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm" name="etiquetas[${produto.ID_Produto}][tamanho]" required>
                                <option value="pequena" selected>Pequena (6x4 cm)</option>
                                <option value="media">Média (10x5 cm)</option>
                                <option value="grande">Grande (10x7 cm)</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm btn-remover-item"><i class="bi bi-trash"></i></button>
                        </td>
                    `;
                    filaImpressao.appendChild(newRow);
                    atualizarEstadoBotaoGerar();
                }

                filaImpressao.addEventListener('click', function(e) {
                    if (e.target && e.target.closest('.btn-remover-item')) {
                        e.target.closest('tr').remove();
                        atualizarEstadoBotaoGerar();
                        
                        if (filaImpressao.children.length === 0) 
                            filaImpressao.appendChild(placeholderFila);
                    }
                });

                function atualizarEstadoBotaoGerar() {
                    btnGerarEtiquetas.disabled = filaImpressao.children.length === 0 || (filaImpressao.children.length === 1 && filaImpressao.contains(placeholderFila));
                }

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