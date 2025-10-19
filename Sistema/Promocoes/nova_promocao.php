<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'PROMOCOES_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$produto_pre_selecionado = null;
if (isset($_GET['id_produto']) && isset($_GET['nome_produto'])) {
    $produto_pre_selecionado = [
        'id' => filter_var($_GET['id_produto'], FILTER_VALIDATE_INT),
        'nome' => $_GET['nome_produto']
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Nova Promoção</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Marketing e Vendas</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">
                            Cadastrar Nova Promoção
                        </h2>
                        <a href="promocoes.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left-circle"></i> Voltar para a Lista
                        </a>
                    </div>

                    <form action="processa_promocao.php" method="POST">
                        <div class="card card-body mb-4">
                            <h5 class="card-title mb-3">1. Informações Gerais</h5>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="descricao" class="form-label">Descrição da Promoção</label>
                                    <input type="text" class="form-control" id="descricao" name="descricao" placeholder="Ex: Leve 3 Pague 2 - Nome do produto" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="tipo_promocao" class="form-label">Tipo de Promoção</label>
                                    <select class="form-select" id="tipo_promocao" name="tipo_promocao" required>
                                        <option value="" selected disabled>Selecione...</option>
                                        <option value="LEVE_X_PAGUE_Y">Leve X, Pague Y</option>
                                        <option value="DESCONTO_PROGRESSIVO">Desconto Progressivo / Venda Cruzada</option>
                                        <option value="COMBO_PRECO_FIXO">Combo com Preço Fixo</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="data_inicio" class="form-label">Data de Início</label>
                                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="data_fim" class="form-label">Data Final (Opcional)</label>
                                    <input type="date" class="form-control" id="data_fim" name="data_fim" min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card card-body">
                            <h5 class="card-title mb-3">2. Itens e Regras da Promoção</h5>
                            <div id="itens-container">
                                </div>
                            <button type="button" id="btn-adicionar-item" class="btn btn-outline-success mt-3" disabled>
                                <i class="bi bi-plus"></i> Adicionar Item à Regra
                            </button>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">Salvar Promoção</button>
                        </div>
                    </form>

                </div>
                <?php include_once DEV_PATH . 'Views/footer.php'; ?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-tools"></i> Construtor de Promoções</h4>
            <hr>
            <p>Esta tela é um construtor de regras flexível que permite criar diferentes tipos de ofertas para seus clientes. O processo é dividido em duas partes: as informações gerais e a definição das regras.</p>

            <h6><i class="bi bi-card-heading"></i> Passo 1: Informações Gerais</h6>
            <ul>
                <li><strong>Descrição:</strong> Dê um nome claro e objetivo para a promoção (ex: "Leve 3 Pague 2 - Dipirona 500mg").</li>
                <li><strong>Tipo de Promoção:</strong> Selecione o tipo de regra que você quer criar. Esta escolha mudará os campos disponíveis no passo 2.</li>
                <li><strong>Datas de Vigência:</strong> Defina a data de início e, opcionalmente, uma data de término para a promoção.</li>
            </ul>

            <h6><i class="bi bi-list-check"></i> Passo 2: Itens e Regras da Promoção</h6>
            <p>Clique em <strong>"Adicionar Item à Regra"</strong> para definir os produtos e as condições da sua oferta. Cada item da regra tem dois papéis possíveis:</p>
            <ul>
                <li><strong>Condição:</strong> É o "SE" da regra. Representa o que o cliente precisa ter no carrinho para a promoção ser ativada. Ex: "SE o cliente levar 3 unidades do Produto A".</li>
                <li><strong>Benefício:</strong> É o "ENTÃO" da regra. Representa o que o cliente ganha. Ex: "ENTÃO ele recebe 100% de desconto em 1 unidade do Produto A".</li>
            </ul>
            
            <p><strong>Exemplo prático de "Leve 3, Pague 2":</strong></p>
            <ol>
                <li>Adicione um item para o produto desejado. Defina o "Tipo do Item" como <strong>Condição</strong> e a "Quantidade" como <strong>3</strong>.</li>
                <li>Adicione um segundo item para o mesmo produto. Defina o "Tipo do Item" como <strong>Benefício</strong>, a "Quantidade" como <strong>1</strong> e o "Desconto" como <strong>100%</strong>.</li>
            </ol>

            <h6><i class="bi bi-save-fill"></i> Passo 3: Salvar</h6>
            <p>Após definir todas as regras, clique em <strong>"Salvar Promoção"</strong>. Se o status for "Ativo" e a data for vigente, ela começará a ser aplicada no PDV e na Pré-Venda imediatamente.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo DEV_URL ?>JS/toast.js"></script>
        <script src="<?php echo DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tipoPromocaoSelect = document.getElementById('tipo_promocao');
                const btnAdicionarItem = document.getElementById('btn-adicionar-item');
                const itensContainer = document.getElementById('itens-container');
                let itemCounter = 0;

                tipoPromocaoSelect.addEventListener('change', function() {
                    btnAdicionarItem.disabled = this.value === '';
                    itensContainer.innerHTML = ''; 
                    controlarVisibilidadeCampos();
                });

                btnAdicionarItem.addEventListener('click', function() {
                    itemCounter++;
                    const newItemHtml = `
                        <div class="item-regra-promo p-3 border rounded mb-3" data-id="${itemCounter}">
                            <div class="row g-2 align-items-center justify-content-between">
                                <div class="row col-md-11">
                                    <div class="col-md-6 position-relative">
                                        <label class="form-label">Produto</label>
                                        <input type="text" class="form-control busca-produto-promo" placeholder="Digite nome ou EAN" autocomplete="off">
                                        <input type="hidden" name="itens[${itemCounter}][id_produto]">
                                        <div class="sugestoes-produto list-group position-absolute w-100" style="z-index: 10;"></div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Tipo do Item</label>
                                        <select name="itens[${itemCounter}][tipo_item]" class="form-select">
                                            <option value="Condicao">Condição</option>
                                            <option value="Beneficio">Benefício</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Quantidade</label>
                                        <input type="number" name="itens[${itemCounter}][quantidade]" class="form-control" min="1" value="1">
                                    </div>
                                    <div class="col-md-2 campo-dinamico campo-desconto" style="display: none;">
                                        <label class="form-label">Desconto</label>
                                        <div class="input-group">
                                            <input type="number" name="itens[${itemCounter}][valor_desconto]" class="form-control" step="0.01" min="0">
                                            <span class="input-group-text" id="basic-addon2"> % </span>
                                        </div>
                                    </div>
                                    <div class="col-md-2 campo-dinamico campo-preco-fixo" style="display: none;">
                                        <label class="form-label">Preço Fixo</label>
                                        <div class="input-group">
                                            <input type="number" name="itens[${itemCounter}][preco_fixo]" class="form-control" step="0.01" min="0">
                                            <span class="input-group-text" id="basic-addon2"> R$ </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger btn-remover-item w-100"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    `;
                    itensContainer.insertAdjacentHTML('beforeend', newItemHtml);
                    
                    const novoItem = itensContainer.querySelector(`.item-regra-promo[data-id="${itemCounter}"]`);
                    inicializarBuscaProduto(novoItem.querySelector('.busca-produto-promo'));
                    
                    controlarVisibilidadeCampos();
                });

                itensContainer.addEventListener('change', function(e) {
                    if (e.target && e.target.name.includes('[tipo_item]')) 
                        controlarVisibilidadeCampos();
                });

                itensContainer.addEventListener('click', function(e) {
                    if (e.target && e.target.closest('.btn-remover-item')) 
                        e.target.closest('.item-regra-promo').remove();
                });

                function controlarVisibilidadeCampos() {
                    const tipoSelecionado = tipoPromocaoSelect.value;
                    const todosItens = document.querySelectorAll('.item-regra-promo');

                    todosItens.forEach(item => {
                        const tipoItemSelect = item.querySelector('select[name*="[tipo_item]"]');
                        const isCondicao = tipoItemSelect.value === 'Condicao';

                        const campoDesconto = item.querySelector('.campo-desconto');
                        const inputDesconto = campoDesconto.querySelector('input');
                        
                        const campoPrecoFixo = item.querySelector('.campo-preco-fixo');
                        const inputPrecoFixo = campoPrecoFixo.querySelector('input');

                        campoDesconto.style.display = 'none';
                        campoPrecoFixo.style.display = 'none';

                        if (isCondicao) {
                            inputDesconto.disabled = true;
                            inputPrecoFixo.disabled = true;
                            inputDesconto.value = '';
                            inputPrecoFixo.value = '';
                        } 
                        else { 
                            inputDesconto.disabled = false;
                            inputPrecoFixo.disabled = false;
                        }

                        if (!isCondicao) {
                            if (tipoSelecionado === 'LEVE_X_PAGUE_Y' || tipoSelecionado === 'DESCONTO_PROGRESSIVO') 
                                campoDesconto.style.display = 'block';
                            else if (tipoSelecionado === 'COMBO_PRECO_FIXO') 
                                campoPrecoFixo.style.display = 'block';
                        }
                    });
                }
                
                function inicializarBuscaProduto(inputElement) {
                    const container = inputElement.closest('.item-regra-promo');
                    const idProdutoInput = container.querySelector('input[type="hidden"]');
                    const sugestoesDiv = container.querySelector('.sugestoes-produto');

                    inputElement.addEventListener('input', function() {
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
                                        inputElement.value = produto.Nome;
                                        idProdutoInput.value = produto.ID_Produto;
                                        sugestoesDiv.innerHTML = '';
                                    });
                                    sugestoesDiv.appendChild(item);
                                });
                            });
                    });
                }

                const produtoPreSelecionado = <?= json_encode($produto_pre_selecionado) ?>;
                if (produtoPreSelecionado) {
                    tipoPromocaoSelect.value = 'DESCONTO_PROGRESSIVO';
                    
                    btnAdicionarItem.disabled = false;
                    btnAdicionarItem.click();

                    const primeiroItem = document.querySelector('.item-regra-promo');
                    if (primeiroItem) {
                        primeiroItem.querySelector('.busca-produto-promo').value = produtoPreSelecionado.nome;
                        primeiroItem.querySelector('input[name*="[id_produto]"]').value = produtoPreSelecionado.id;

                        const tipoItemSelect = primeiroItem.querySelector('select[name*="[tipo_item]"]');
                        tipoItemSelect.value = 'Condicao';
                        primeiroItem.querySelector('input[name*="[quantidade]"]').value = 1;

                        tipoItemSelect.dispatchEvent(new Event('change'));
                    }
                    
                    document.getElementById('itens-container').scrollIntoView({ behavior: 'smooth' });
                }
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