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

$result_margem = $conn->query("SELECT Margem_Lucro_Padrao FROM CONFIGURACOES WHERE ID_Config = 1");
$margem_lucro = $result_margem->fetch_assoc()['Margem_Lucro_Padrao'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Entrada de Produtos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
            
                <div class="container p-5">
                    <div class="card card-body mb-4 bg-light">
                        <h5 class="card-title">Importar via XML da Nota Fiscal (NFe)</h5>
                        <p class="card-text text-muted small">Faça o upload do arquivo .xml da sua nota fiscal para preencher os itens automaticamente.</p>
                        <form action="processa_xml.php" method="POST" enctype="multipart/form-data">
                            <div class="input-group">
                                <input type="file" class="form-control" name="arquivo_xml" id="arquivo_xml" accept=".xml" required>
                                <button class="btn btn-primary" type="submit">Processar XML</button>
                            </div>
                        </form>
                    </div>

                    <hr>
                    <h4 class="text-center mb-4">OU</h4>

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
                                <div class="col-md-3">
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
                                    <label for="margem_lucro" class="form-label">Marg. (%)</label>
                                    <div class="input-group">
                                        <input type="text" id="margem_lucro" class="form-control" value="<?= $margem_lucro ?? '100.00' ?>">
                                    </div>
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
                <?php include_once DEV_PATH . 'Views/footer.php'?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-box-arrow-in-down"></i> Registrar Entrada de Estoque</h4>
            <hr>
            <p>Esta tela é utilizada para registrar a entrada de novos produtos no seu inventário, seja por compra de fornecedores ou outros motivos. O sistema oferece duas maneiras de realizar a entrada: uma automática (via XML) e uma manual.</p>

            <h6 class="mt-4"><i class="bi bi-filetype-xml"></i> Opção 1: Importar via XML da Nota Fiscal (Recomendado)</h6>
            <p>Esta é a forma mais rápida e segura de dar entrada em uma compra. O sistema lê o arquivo XML da Nota Fiscal Eletrônica (NFe) fornecida pelo seu distribuidor e preenche os dados dos produtos automaticamente.</p>
            <ol>
                <li>Clique em <strong>"Escolher arquivo"</strong> e selecione o arquivo .xml da sua nota fiscal.</li>
                <li>Clique em <strong>"Processar XML"</strong>.</li>
                <li>Você será levado a uma tela de revisão para confirmar os itens, preencher informações de lote e validade, e ajustar os preços de venda antes de salvar.</li>
            </ol>

            <h6 class="mt-4"><i class="bi bi-keyboard-fill"></i> Opção 2: Lançamento Manual</h6>
            <p>Use esta opção para entradas que não possuem um arquivo XML ou para ajustes manuais.</p>
            <ol>
                <li><strong>Dados da Nota:</strong> Selecione o <strong>Fornecedor</strong> e preencha o <strong>Número da Nota Fiscal</strong> e a data de emissão.</li>
                <li><strong>Adicionar Produto:</strong>
                    <ul>
                        <li>Busque o produto pelo nome ou código de barras.</li>
                        <li>Preencha as informações do <strong>Lote</strong>, <strong>Validade</strong> e a <strong>Quantidade</strong> que está entrando.</li>
                        <li>Informe o <strong>Preço de Custo</strong> unitário (conforme a nota de compra).</li>
                        <li>O sistema irá sugerir um <strong>Preço de Venda</strong> com base na sua margem de lucro padrão, mas você pode ajustá-lo. A <strong>Margem (%)</strong> será recalculada automaticamente.</li>
                        <li>Clique em <strong>"Add"</strong> para incluir o item na lista da nota.</li>
                    </ul>
                </li>
                <li>Repita o processo para todos os itens da nota.</li>
                <li>Após adicionar todos os itens, clique em <strong>"Salvar Entrada no Estoque"</strong> para finalizar.</li>
            </ol>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            const margemLucroPadrao = <?= $margem_lucro ?? 100 ?>;

            const campoCusto = document.getElementById('preco_custo');
            const campoMargem = document.getElementById('margem_lucro');
            const campoVenda = document.getElementById('preco_venda');

            function formatarMoeda(valor) {
                if (isNaN(valor) || valor === null) return '';
                return valor.toFixed(2).replace('.', ',');
            }

            campoCusto.addEventListener('blur', function() {
                let custo = parseFloat(this.value.replace(',', '.'));
                let margem = parseFloat(campoMargem.value.replace(',', '.'));
                if (!isNaN(custo) && !isNaN(margem)) {
                    let vendaCalculada = custo * (1 + (margem / 100));
                    campoVenda.value = formatarMoeda(vendaCalculada);
                }
                this.value = formatarMoeda(custo); 
            });

            campoMargem.addEventListener('blur', function() {
                let custo = parseFloat(campoCusto.value.replace(',', '.'));
                let margem = parseFloat(this.value.replace(',', '.'));
                if (!isNaN(custo) && !isNaN(margem)) {
                    let vendaCalculada = custo * (1 + (margem / 100));
                    campoVenda.value = formatarMoeda(vendaCalculada);
                }
                this.value = formatarMoeda(margem); 
            });

            campoVenda.addEventListener('blur', function() {
                let custo = parseFloat(campoCusto.value.replace(',', '.'));
                let venda = parseFloat(this.value.replace(',', '.'));
                if (!isNaN(custo) && !isNaN(venda) && custo > 0) {
                    let margemCalculada = ((venda / custo) - 1) * 100;
                    campoMargem.value = formatarMoeda(margemCalculada);
                }
                this.value = formatarMoeda(venda); 
            });
            
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