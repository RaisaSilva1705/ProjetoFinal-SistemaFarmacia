<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Saída de Produtos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Registrar Saída de Estoque</h3>
                </div>
            
                <div class="container p-5">
                    <form action="processa_saida.php" method="POST">
                        <div class="mb-3">
                            <label for="busca_produto" class="form-label fw-bold">1. Buscar Produto (Nome ou EAN)</label>
                            <input type="text" id="busca_produto" class="form-control" autocomplete="off" required>
                            <input type="hidden" id="produto_id" name="id_produto">
                            <div id="sugestoes_produto" class="list-group position-absolute" style="z-index: 1000;"></div>
                        </div>

                        <div class="mb-3">
                            <label for="id_lote" class="form-label fw-bold">2. Selecionar o Lote para Baixa</label>
                            <select name="id_lote" id="id_lote" class="form-select" required disabled>
                                <option value="">Aguardando seleção do produto...</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="quantidade" class="form-label fw-bold">3. Quantidade a Retirar</label>
                                <input type="number" name="quantidade" id="quantidade" class="form-control" min="1" required disabled>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="motivo" class="form-label fw-bold">4. Motivo da Saída</label>
                                <select name="motivo" id="motivo" class="form-select" required disabled>
                                    <option value="">Selecione...</option>
                                    <option value="Perda / Avaria">Perda / Avaria</option>
                                    <option value="Vencimento">Vencimento</option>
                                    <option value="Ajuste de Inventário">Ajuste de Inventário</option>
                                    <option value="Uso Interno">Uso Interno</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-end">
                            <a href="estoque.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-danger">Registrar Saída</button>
                        </div>
                    </form>
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
            document.addEventListener('DOMContentLoaded', function() {
                const campoBusca = document.getElementById('busca_produto');
                const campoProdutoId = document.getElementById('produto_id');
                const sugestoesDiv = document.getElementById('sugestoes_produto');
                const selectLote = document.getElementById('id_lote');
                const inputQuantidade = document.getElementById('quantidade');
                const selectMotivo = document.getElementById('motivo');

                campoBusca.addEventListener('input', function() {
                    const termo = this.value.trim();
                    if (termo.length < 2) {
                        sugestoesDiv.innerHTML = '';
                        return;
                    }
                    fetch('../../Dev/Exec/busca_produto.php?nome=' + encodeURIComponent(termo))
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
                                    campoBusca.value = produto.Nome;
                                    campoProdutoId.value = produto.ID_Produto;
                                    sugestoesDiv.innerHTML = '';
                                    // AQUI ESTÁ A CONEXÃO
                                    carregarLotes(produto.ID_Produto);
                                });
                                sugestoesDiv.appendChild(item);
                            });
                        });
                });

                function carregarLotes(idProduto) {
                    selectLote.innerHTML = '<option>Carregando lotes...</option>';
                    selectLote.disabled = true;

                    // CAMINHO DO FETCH CORRIGIDO
                    fetch(`../../Dev/Exec/busca_lotes.php?id_produto=${idProduto}`)
                        .then(response => response.json())
                        .then(data => {
                            selectLote.innerHTML = '<option value="">Selecione um lote...</option>';
                            if (data.length > 0) {
                                data.forEach(lote => {
                                    const option = document.createElement('option');
                                    option.value = lote.ID_Lote;
                                    
                                    // Formata a data para o padrão brasileiro
                                    const dataValidade = new Date(lote.Data_Validade + 'T00:00:00'); // Adiciona T00:00 para evitar problemas de fuso
                                    const dataFormatada = dataValidade.toLocaleDateString('pt-BR');
                                    
                                    option.textContent = `Lote: ${lote.Nome_Lote} | Val: ${dataFormatada} | Qtd: ${lote.Quantidade}`;
                                    option.dataset.quantidadeMaxima = lote.Quantidade;
                                    selectLote.appendChild(option);
                                });
                                selectLote.disabled = false;
                            } else {
                                selectLote.innerHTML = '<option value="">Nenhum lote com estoque encontrado.</option>';
                            }
                        });
                }
                
                selectLote.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption.value) {
                        const maxQtd = selectedOption.dataset.quantidadeMaxima;
                        inputQuantidade.max = maxQtd;
                        inputQuantidade.placeholder = `Máx: ${maxQtd}`;
                        inputQuantidade.disabled = false;
                        selectMotivo.disabled = false;
                    } else {
                        inputQuantidade.disabled = true;
                        inputQuantidade.value = '';
                        selectMotivo.disabled = true;
                        inputQuantidade.placeholder = '';
                    }
                });

                // Bloco de toast do PHP
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