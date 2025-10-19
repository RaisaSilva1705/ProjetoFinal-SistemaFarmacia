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
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Saída de Produtos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Registrar Saída de Estoque Manual</h3>
                </div>
            
                <div class="container p-5">
                    <form action="processa_saida.php" method="POST" enctype="multipart/form-data">
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
                                    <option value="Furto">Furto</option>
                                    <option value="Ajuste de Inventário">Ajuste de Inventário</option>
                                    <option value="Uso Interno">Uso Interno</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="obs" class="form-label fw-bold">5. Observações (Opcional)</label>
                            <textarea name="obs" id="obs" class="form-control" rows="3" placeholder="Ex: Caixa amassada, produto quebrado..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="foto" class="form-label fw-bold">6. Foto da Ocorrência (Opcional)</label>
                            <input type="file" name="foto" id="foto" class="form-control" accept="image/png, image/jpeg">
                        </div>
                        
                        <div class="mt-4 text-end">
                            <a href="estoque.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-danger">Registrar Saída</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-box-arrow-up"></i> Registrar Saída de Estoque Manual</h4>
            <hr>
            <p>Esta tela é usada para dar baixa em produtos do seu inventário por motivos que <strong>não são vendas</strong>. O uso correto desta ferramenta é crucial para manter a precisão do seu estoque e para a apuração de perdas.</p>

            <h6><i class="bi bi-123"></i> Passo a Passo para Registrar uma Saída</h6>
            <ol>
                <li><strong>Buscar Produto:</strong> Comece digitando o nome ou o código de barras do produto que deseja retirar do estoque.</li>
                <li><strong>Selecionar o Lote:</strong> Após selecionar o produto, o sistema carregará todos os lotes disponíveis com estoque. Selecione o lote específico do qual a unidade será retirada.</li>
                <li><strong>Quantidade a Retirar:</strong> Informe quantas unidades deste lote serão retiradas. O sistema não permitirá retirar uma quantidade maior do que a disponível no lote selecionado.</li>
                <li><strong>Motivo da Saída:</strong> Selecione o motivo que melhor descreve a razão da baixa. Esta informação é fundamental para os seus relatórios de perdas.
                    <ul>
                        <li><strong>Perda / Avaria:</strong> Produto danificado.</li>
                        <li><strong>Vencimento:</strong> Produto expirado.</li>
                        <li><strong>Furto:</strong> Produto subtraído.</li>
                        <li><strong>Ajuste de Inventário:</strong> Para corrigir discrepâncias encontradas na contagem física.</li>
                        <li><strong>Uso Interno:</strong> Produto consumido pela própria farmácia.</li>
                    </ul>
                </li>
                <li><strong>Observações (Opcional):</strong> Detalhe o motivo da saída, se necessário.</li>
                <li><strong>Foto da Ocorrência (Opcional):</strong> Em casos de avaria, é uma boa prática anexar uma foto como evidência.</li>
            </ol>
            
            <h6><i class="bi bi-check-circle-fill"></i> Finalizar</h6>
            <p>Após preencher todos os campos obrigatórios, clique em <strong>"Registrar Saída"</strong>. O sistema dará baixa no estoque e registrará a movimentação para fins de auditoria.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
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