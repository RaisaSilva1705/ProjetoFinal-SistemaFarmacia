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

// 1. Validar e buscar os dados da promoção a ser editada
$id_promocao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_promocao) {
    $_SESSION['msg'] = ['texto' => 'ID da promoção inválido.', 'tipo' => 'warning'];
    header('Location: promocoes.php');
    exit();
}

// Busca os dados gerais da promoção
$stmt_promo = $conn->prepare("SELECT * FROM PROMOCOES WHERE ID_Promocao = ?");
$stmt_promo->bind_param("i", $id_promocao);
$stmt_promo->execute();
$result_promo = $stmt_promo->get_result();
if ($result_promo->num_rows === 0) {
    $_SESSION['msg'] = ['texto' => 'Promoção não encontrada.', 'tipo' => 'danger'];
    header('Location: promocoes.php');
    exit();
}
$promocao = $result_promo->fetch_assoc();
$stmt_promo->close();

// Busca os itens (regras) associados a esta promoção, já com o nome do produto
$stmt_itens = $conn->prepare(
    "SELECT pi.*, p.Nome as Nome_Produto 
     FROM PROMOCOES_ITENS pi
     JOIN PRODUTOS p ON pi.ID_Produto = p.ID_Produto
     WHERE pi.ID_Promocao = ? ORDER BY pi.ID_Item_Promocao ASC"
);
$stmt_itens->bind_param("i", $id_promocao);
$stmt_itens->execute();
$itens_promocao = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_itens->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Editar Promoção: <?php echo htmlspecialchars($promocao['Descricao']); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                            <i class="bi bi-pencil-square text-info"></i>
                            Editar Promoção
                        </h2>
                        <a href="promocoes.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left-circle"></i> Voltar para a Lista
                        </a>
                    </div>

                    <form action="processa_promocao.php" method="POST">
                        <input type="hidden" name="id_promocao" value="<?php echo $promocao['ID_Promocao']; ?>">

                        <div class="card card-body mb-4">
                            <h5 class="card-title mb-3">1. Informações Gerais</h5>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="descricao" class="form-label">Descrição da Promoção</label>
                                    <input type="text" class="form-control" id="descricao" name="descricao" required value="<?php echo htmlspecialchars($promocao['Descricao']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="tipo_promocao" class="form-label">Tipo de Promoção</label>
                                    <select class="form-select" id="tipo_promocao" name="tipo_promocao" required>
                                        <option value="LEVE_X_PAGUE_Y" <?php echo ($promocao['Tipo'] == 'LEVE_X_PAGUE_Y') ? 'selected' : ''; ?>>Leve X, Pague Y</option>
                                        <option value="DESCONTO_PROGRESSIVO" <?php echo ($promocao['Tipo'] == 'DESCONTO_PROGRESSIVO') ? 'selected' : ''; ?>>Desconto Progressivo / Venda Cruzada</option>
                                        <option value="COMBO_PRECO_FIXO" <?php echo ($promocao['Tipo'] == 'COMBO_PRECO_FIXO') ? 'selected' : ''; ?>>Combo com Preço Fixo</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="data_inicio" class="form-label">Data de Início</label>
                                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" required value="<?php echo $promocao['Data_Inicio']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="data_fim" class="form-label">Data Final (Opcional)</label>
                                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $promocao['Data_Fim']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="card card-body">
                            <h5 class="card-title mb-3">2. Itens e Regras da Promoção</h5>
                            <div id="itens-container">
                                <?php foreach ($itens_promocao as $index => $item): ?>
                                    <div class="item-regra-promo p-3 border rounded mb-3" data-id="<?php echo $index; ?>">
                                        <div class="row g-2 align-items-center justify-content-between">
                                            <div class="row col-md-11">
                                                <div class="col-md-6 position-relative">
                                                    <label class="form-label">Produto</label>
                                                    <input type="text" class="form-control busca-produto-promo" placeholder="Digite nome ou EAN" autocomplete="off" value="<?php echo htmlspecialchars($item['Nome_Produto']); ?>">
                                                    <input type="hidden" name="itens[<?php echo $index; ?>][id_produto]" value="<?php echo $item['ID_Produto']; ?>">
                                                    <div class="sugestoes-produto list-group position-absolute w-100" style="z-index: 10;"></div>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Tipo do Item</label>
                                                    <select name="itens[<?php echo $index; ?>][tipo_item]" class="form-select">
                                                        <option value="Condicao" <?php echo ($item['Tipo_Item'] == 'Condicao') ? 'selected' : ''; ?>>Condição</option>
                                                        <option value="Beneficio" <?php echo ($item['Tipo_Item'] == 'Beneficio') ? 'selected' : ''; ?>>Benefício</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Quantidade</label>
                                                    <input type="number" name="itens[<?php echo $index; ?>][quantidade]" class="form-control" min="1" value="<?php echo $item['Quantidade']; ?>">
                                                </div>
                                                <div class="col-md-2 campo-dinamico campo-desconto" style="display: none;">
                                                    <label class="form-label">Desconto</label>
                                                    <div class="input-group">
                                                        <input type="number" name="itens[<?php echo $index; ?>][valor_desconto]" class="form-control" step="0.01" min="0" value="<?php echo $item['Valor_Desconto_Percentual']; ?>">
                                                        <span class="input-group-text" id="basic-addon2"> % </span>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 campo-dinamico campo-preco-fixo" style="display: none;">
                                                    <label class="form-label">Preço Fixo do Combo</label>
                                                    <div class="input-group">
                                                        <input type="number" name="itens[<?php echo $index; ?>][preco_fixo]" class="form-control" step="0.01" min="0" value="<?php echo $item['Preco_Fixo_Combo']; ?>">
                                                        <span class="input-group-text" id="basic-addon2"> R$ </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-danger btn-remover-item w-100"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="btn-adicionar-item" class="btn btn-outline-success mt-3">
                                <i class="bi bi-plus"></i> Adicionar Item à Regra
                            </button>
                        </div>
                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">Atualizar Promoção</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo DEV_URL ?>JS/toast.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoPromocaoSelect = document.getElementById('tipo_promocao');
            const btnAdicionarItem = document.getElementById('btn-adicionar-item');
            const itensContainer = document.getElementById('itens-container');
            let itemCounter = <?php echo count($itens_promocao); ?>;

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