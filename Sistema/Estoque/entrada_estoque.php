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
        <title>Entrada de Produtos</title>
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
                    <h3>Registrar Entrada de Estoque</h3>
                </div>
            
                <div class="container p-4">
                    <form action="processa_entrada.php" method="POST">
                        <div class="card card-body mb-4">
                            <h5 class="card-title">Dados da Nota Fiscal</h5>
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label for="id_fornecedor" class="form-label">Fornecedor</label>
                                    <select name="id_fornecedor" id="id_fornecedor" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <?php
                                        $fornecedores = $conn->query("SELECT ID_Fornecedor, Nome_Fantasia FROM FORNECEDORES WHERE Status = 'Ativo' ORDER BY Nome_Fantasia");
                                        while($f = $fornecedores->fetch_assoc()) {
                                            echo "<option value='{$f['ID_Fornecedor']}'>{$f['Nome_Fantasia']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="numero_nota" class="form-label">Número da Nota Fiscal</label>
                                    <input type="text" name="numero_nota" id="numero_nota" class="form-control">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="data_emissao" class="form-label">Data de Emissão</label>
                                    <input type="date" name="data_emissao" id="data_emissao" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card card-body mb-4">
                            <h5 class="card-title">Adicionar Produto à Nota</h5>
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label for="busca_produto" class="form-label">Buscar Produto (Nome ou EAN)</label>
                                    <input type="text" id="busca_produto" class="form-control" autocomplete="off">
                                    <input type="hidden" id="produto_id">
                                    <div id="sugestoes_produto" class="list-group position-absolute" style="z-index: 1000;"></div>
                                </div>
                                <div class="col-md-2">
                                    <label for="lote" class="form-label">Lote</label>
                                    <input type="text" id="lote" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label for="validade" class="form-label">Validade</label>
                                    <input type="date" id="validade" class="form-control">
                                </div>
                                <div class="col-md-1">
                                    <label for="quantidade" class="form-label">Qtd.</label>
                                    <input type="number" id="quantidade" class="form-control" value="1" min="1">
                                </div>
                                <div class="col-md-1">
                                    <label for="preco_custo" class="form-label">Custo</label>
                                    <input type="text" id="preco_custo" class="form-control" placeholder="0,00">
                                </div>
                                <div class="col-md-1">
                                    <label for="preco_venda" class="form-label">Venda</label>
                                    <input type="text" id="preco_venda" class="form-control" placeholder="0,00">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" id="btn_add_item" class="btn btn-success w-100">Add</button>
                                </div>
                            </div>
                        </div>

                        <h4 class="mt-4">Itens da Nota</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produto</th>
                                        <th>Lote</th>
                                        <th>Validade</th>
                                        <th>Qtd.</th>
                                        <th>Custo Unit.</th>
                                        <th>Venda Unit.</th>
                                        <th class="text-center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela_itens">
                                    </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Salvar Entrada no Estoque</button>
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
            // --- BUSCA DE PRODUTOS ---
            const campoBusca = document.getElementById('busca_produto');
            const campoProdutoId = document.getElementById('produto_id');
            const sugestoesDiv = document.getElementById('sugestoes_produto');

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
                            });
                            sugestoesDiv.appendChild(item);
                        });
                    });
            });

            // --- LÓGICA PARA ADICIONAR ITEM À LISTA ---
            const btnAddItem = document.getElementById('btn_add_item');
            const tabelaItens = document.getElementById('tabela_itens');
            let itemCounter = 0;

            btnAddItem.addEventListener('click', function() {
                const produtoId = campoProdutoId.value;
                const produtoNome = campoBusca.value;
                const lote = document.getElementById('lote').value;
                const validade = document.getElementById('validade').value;
                const quantidade = document.getElementById('quantidade').value;
                const precoCusto = document.getElementById('preco_custo').value;
                const precoVenda = document.getElementById('preco_venda').value;

                if (!produtoId || !lote || !validade || !quantidade || !precoCusto || !precoVenda) {
                    alert('Por favor, preencha todos os campos do produto.');
                    return;
                }
                
                const newRow = tabelaItens.insertRow();
                newRow.innerHTML = `
                    <td>
                        ${produtoNome}
                        <input type="hidden" name="produtos[${itemCounter}][id]" value="${produtoId}">
                        <input type="hidden" name="produtos[${itemCounter}][lote]" value="${lote}">
                        <input type="hidden" name="produtos[${itemCounter}][validade]" value="${validade}">
                        <input type="hidden" name="produtos[${itemCounter}][quantidade]" value="${quantidade}">
                        <input type="hidden" name="produtos[${itemCounter}][custo]" value="${precoCusto.replace(',', '.')}">
                        <input type="hidden" name="produtos[${itemCounter}][venda]" value="${precoVenda.replace(',', '.')}">
                    </td>
                    <td>${lote}</td>
                    <td>${new Date(validade).toLocaleDateString('pt-BR', {timeZone: 'UTC'})}</td>
                    <td>${quantidade}</td>
                    <td>R$ ${precoCusto}</td>
                    <td>R$ ${precoVenda}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">Remover</button>
                    </td>
                `;
                
                itemCounter++;
                limparCamposItem();
            });

            function limparCamposItem() {
                campoBusca.value = '';
                campoProdutoId.value = '';
                document.getElementById('lote').value = '';
                document.getElementById('validade').value = '';
                document.getElementById('quantidade').value = '1';
                document.getElementById('preco_custo').value = '';
                document.getElementById('preco_venda').value = '';
                campoBusca.focus();
            }

            const camposDePreco = document.querySelectorAll('#preco_custo, #preco_venda');

            camposDePreco.forEach(function(campo) {
                campo.addEventListener('blur', function() {
                    
                    let valor = this.value.trim();

                    if (valor === '') return;

                    let valorNumerico = parseFloat(valor.replace(',', '.'));

                    if (!isNaN(valorNumerico)) 
                        this.value = valorNumerico.toFixed(2).replace('.', ',');
                    else 
                        this.value = '';
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