<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$itens_iniciais_json = '[]';
$id_prevenda_existente = null;

if (isset($_GET['id_prevenda']) && filter_var($_GET['id_prevenda'], FILTER_VALIDATE_INT)) {
    $id_prevenda_existente = (int)$_GET['id_prevenda'];
    
    $sql_itens = "SELECT 
                    PVI.ID_Produto AS id,
                    'produto' AS tipo, 
                    P.Nome AS nome, 
                    PVI.Valor_Unitario AS valor, 
                    PVI.Quantidade AS qtd
                  FROM PRE_VENDAS_ITENS PVI
                  JOIN PRODUTOS P ON PVI.ID_Produto = P.ID_Produto
                  WHERE PVI.ID_PreVenda = ?
                  UNION ALL
                  SELECT 
                    PVI.ID_Servico AS id,
                    'servico' AS tipo, 
                    SF.Nome_Servico AS nome, 
                    PVI.Valor_Unitario AS valor, 
                    PVI.Quantidade AS qtd
                  FROM PRE_VENDAS_ITENS PVI
                  JOIN SERVICOS_FARMACEUTICOS SF ON PVI.ID_Servico = SF.ID_Servico
                  WHERE PVI.ID_PreVenda = ?";

    $stmt = $conn->prepare($sql_itens);
    $stmt->bind_param("ii", $id_prevenda_existente, $id_prevenda_existente);
    $stmt->execute();
    $itens_iniciais = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($itens_iniciais as &$item) {
        $item['id'] = (int)$item['id'];
        $item['valor'] = (float)$item['valor'];
        $item['qtd'] = (int)$item['qtd'];
    }
    unset($item);

    $itens_iniciais_json = json_encode($itens_iniciais);
}

$servicos = $conn->query("SELECT ID_Servico, Nome_Servico, Valor FROM SERVICOS_FARMACEUTICOS WHERE Status = 'Ativo' ORDER BY Nome_Servico");
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gerar Pré-Venda</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/prevenda.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

       <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Gerar Pré-Venda de Serviço</h3>
                </div>
            
                <div class="container p-5">
                    <div class="row">
                        <div class="col-lg-7">
                            <div class="card card-body mb-4">
                                <h5>1. Itens da Pré-Venda</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label for="busca_produto" class="form-label">Buscar Produto</label>
                                        <input type="text" id="busca_produto" class="form-control" placeholder="EAN ou Nome...">
                                    </div>
                                    <div class="col-md-8 mb-2">
                                        <label for="select_servico" class="form-label">Adicionar Serviço</label>
                                        <select id="select_servico" class="form-select">
                                            <option value="">Selecione um serviço...</option>
                                            <?php while ($s = $servicos->fetch_assoc()): ?>
                                                <option value="<?= $s['ID_Servico'] ?>" data-nome="<?= htmlspecialchars($s['Nome_Servico']) ?>" data-valor="<?= $s['Valor'] ?>"><?= htmlspecialchars($s['Nome_Servico']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card card-body mb-4">
                                <h5>2. Cliente (Opcional)</h5>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label for="busca_cliente_cpf" class="form-label">Buscar por CPF</label>
                                        <input type="text" id="busca_cliente_cpf" class="form-control" placeholder="CPF...">
                                        <input type="hidden" id="id_cliente" name="id_cliente">
                                    </div>
                                    <div class="col-md-8 mb-2">
                                        <label for="nome_cliente" class="form-label">Cliente Selecionado</label>
                                        <input type="text" id="nome_cliente" class="form-control" readonly disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5>Resultados da Busca:</h5>
                            <input type="hidden" name="id_prevenda_existente" value="<?= htmlspecialchars($id_prevenda_existente ?? '') ?>">
                            <button type="button" class="btn btn-primary" id="btn-ver-carrinho" data-bs-toggle="modal" data-bs-target="#modalCarrinho">
                                <i class="bi bi-cart3"></i> Ver Pré-Venda (<span id="cart-item-count">0</span>)
                            </button>
                        </div>
                        <div class="mt-2" id="search-results-container">
                            <p class="text-muted">Digite na busca para ver os resultados.</p>
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <!-- Modal carrinho -->
        <div class="modal fade" id="modalCarrinho" tabindex="-1" aria-labelledby="modalCarrinhoLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCarrinhoLabel">Itens da Pré-Venda</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="cart-items-container"></div>
                        <div id="cliente-selecionado-carrinho" class="mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continuar Adicionando</button>
                        <button type="button" class="btn btn-primary" id="btn-gerar-codigo"><i class="bi bi-receipt"> Gerar Código da Pré-Venda</i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Desconto -->
        <div class="modal fade" id="modalDesconto" tabindex="-1" aria-labelledby="modalDescontoLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalDescontoLabel">Aplicar Desconto no Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Item:</strong> <span id="desconto-item-nome"></span></p>
                        <input type="hidden" id="desconto-cart-index">
                        <div class="input-group mb-3">
                            <select class="form-select" id="desconto-tipo" style="flex-grow: 0; width: 30%;">
                                <option value="porcento">%</option>
                                <option value="valor">R$</option>
                            </select>
                            <input type="number" class="form-control" id="desconto-valor" placeholder="Valor do Desconto" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="desconto-senha">Senha de Autorização</label>
                            <input type="password" class="form-control" id="desconto-senha" required>
                        </div>
                        <div id="desconto-erro" class="text-danger mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="btn-aplicar-desconto">Aplicar Desconto</button>
                    </div>
                </div>
            </div>
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
                let cart = <?= $itens_iniciais_json ?>;
                cart.forEach(item => item.desconto = item.desconto || 0);
                let searchResults = [];
                const selectServico = document.getElementById('select_servico');
                const buscaProdutoInput = document.getElementById('busca_produto');
                const searchResultsContainer = document.getElementById('search-results-container');
                const btnVerCarrinho = document.getElementById('btn-ver-carrinho');
                const cartItemCount = document.getElementById('cart-item-count');
                const cartItemsContainer = document.getElementById('cart-items-container');
                const btnGerarCodigo = document.getElementById('btn-gerar-codigo');
                const modalCarrinho  = new bootstrap.Modal(document.getElementById('modalCarrinho'));
                const modalDesconto = new bootstrap.Modal(document.getElementById('modalDesconto')); 
                const btnAplicarDesconto = document.getElementById('btn-aplicar-desconto');
                const campoBuscaCliente = document.getElementById('busca_cliente_cpf');

                renderCart(); // Para preencher o modal
                cartItemCount.textContent = cart.length;

                function renderSearchResults(results) {
                    searchResultsContainer.innerHTML = '';
                    searchResults = []; 

                    const createItemRow = (item) => {
                        const currentIndex = searchResults.push(item) - 1;
                        
                        return `
                            <div class="item-list-row border-bottom mb-2">
                                <div class="item-name">
                                    <strong>${item.Nome}</strong><br>
                                    <small class="text-muted">EAN: ${item.EAN_GTIN} | Categoria: ${item.Categoria} | Estoque: ${item.Estoque}</small>
                                </div>
                                <div class="px-3"><strong>R$ ${parseFloat(item.Preco_Venda || 0).toFixed(2).replace('.', ',')}</strong></div>
                                <div><button type="button" class="btn btn-sm btn-success" onclick="addToCart(${currentIndex})" ${item.Estoque <= 0 ? 'disabled' : ''}>
                                    <i class="bi bi-plus-circle"></i> Adicionar
                                </button></div>
                            </div>`;
                    };

                    const createCategorySection = (title, items) => {
                        if (!items || items.length === 0) return '';
                        return `
                            <h6 class="text-primary mt-3">${title}</h6>
                            ${items.map(createItemRow).join('')}
                        `;
                    };

                    let content = '';
                    content += createCategorySection('Resultados Diretos', results.mesmo_nome);
                    content += createCategorySection('Medicamentos de Referência', results.referencia);
                    content += createCategorySection('Genéricos', results.genericos);
                    content += createCategorySection('Similares', results.similares);

                    if (content === '') 
                        searchResultsContainer.innerHTML = '<p class="text-muted">Nenhum produto encontrado.</p>';
                    else 
                        searchResultsContainer.innerHTML = content;
                }

                function renderCart() {
                    cartItemsContainer.innerHTML = '';
                    if (cart.length === 0) {
                        cartItemsContainer.innerHTML = '<p class="text-muted">O carrinho de pré-venda está vazio.</p>';
                        return;
                    }
                    let total = 0;
                    cart.forEach((item, index) => {
                        const precoOriginal = parseFloat(item.valor);
                        const desconto = parseFloat(item.desconto || 0);
                        const precoFinal = precoOriginal - desconto;
                        total += precoFinal * item.qtd;

                        const descontoTexto = desconto > 0 ? `<br><small class="text-danger">Desconto: - R$ ${desconto.toFixed(2).replace('.', ',')}</small>` : '';
                        
                        const itemHtml = `
                            <div class="item-list-row border-bottom mb-2">
                                <div class="item-name">
                                    <strong>${item.tipo === 'servico' ? 'Serviço' : 'Produto'}:</strong> ${item.nome}
                                    ${descontoTexto}
                                </div>
                                <div>
                                    ${desconto > 0 ? `<s class="text-muted">R$ ${precoOriginal.toFixed(2).replace('.', ',')}</s><br>` : ''}
                                    <strong>R$ ${precoFinal.toFixed(2).replace('.', ',')}</strong>
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-warning" onclick="abrirModalDesconto(${index})"><i class="bi bi-percent"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeFromCart(${index})"><i class="bi bi-trash-fill"></i></button>
                                </div>
                            </div>`;
                        cartItemsContainer.insertAdjacentHTML('beforeend', itemHtml);
                    });
                    cartItemsContainer.insertAdjacentHTML('beforeend', `<div class="text-end mt-3"><h4>Total: R$ ${total.toFixed(2).replace('.', ',')}</h4></div>`);
                }

                window.addToCart = function(searchIndex) {
                    const item = searchResults[searchIndex];
                    cart.push({
                        id: item.ID_Produto,
                        tipo: 'produto',
                        nome: item.Nome,
                        valor: item.Preco_Venda,
                        qtd: 1,
                        desconto: 0
                    });
                    cartItemCount.textContent = cart.length;
                    triggerCartButtonAnimation();
                    mostrarToast(`${item.Nome} adicionado à pré-venda!`, 'success');
                }

                window.removeFromCart = function(cartIndex) {
                    cart.splice(cartIndex, 1);
                    cartItemCount.textContent = cart.length;
                    renderCart();
                }

                function triggerCartButtonAnimation() {
                    btnVerCarrinho.classList.add('pulse-animation');
                    setTimeout(() => {
                        btnVerCarrinho.classList.remove('pulse-animation');
                    }, 1000);
                }

                selectServico.addEventListener('change', function() {
                    if (!this.value) return;
                    const selected = this.options[this.selectedIndex];
                    cart.push({
                        id: this.value,
                        tipo: 'servico',
                        nome: selected.dataset.nome,
                        valor: selected.dataset.valor,
                        qtd: 1,
                        desconto: 0
                    });
                    this.value = '';
                    cartItemCount.textContent = cart.length;
                    triggerCartButtonAnimation();
                    mostrarToast('Serviço adicionado à pré-venda!', 'success');
                });

                buscaProdutoInput.addEventListener('keyup', function() {
                    const query = this.value;
                    if (query.length < 3) {
                        searchResults = [];
                        return;
                    }
                    fetch(`../../Dev/Exec/busca_produto_complexo.php?nome=${query}`)
                        .then(response => response.json())
                        .then(data => renderSearchResults(data));
                });

                document.getElementById('modalCarrinho').addEventListener('show.bs.modal', function() {
                    renderCart();
                    const nomeCliente = document.getElementById('nome_cliente').value;
                    const clienteCarrinhoDiv = document.getElementById('cliente-selecionado-carrinho');
                    if (nomeCliente)
                        clienteCarrinhoDiv.innerHTML = `<p class="text-sucess"><strong>Cliente:</strong> ${nomeCliente}</p>`;
                    else
                        clienteCarrinhoDiv.innerHTML = '';
                });

                window.abrirModalDesconto = function(cartIndex) {
                    const item = cart[cartIndex];
                    document.getElementById('desconto-item-nome').textContent = item.nome;
                    document.getElementById('desconto-cart-index').value = cartIndex;
                    document.getElementById('desconto-valor').value = '';
                    document.getElementById('desconto-senha').value = '';
                    document.getElementById('desconto-erro').textContent = '';
                    modalDesconto.show();
                };

                btnAplicarDesconto.addEventListener('click', function() {
                    const index = document.getElementById('desconto-cart-index').value;
                    const item = cart[index];
                    const tipo = document.getElementById('desconto-tipo').value;
                    const valorDesconto = parseFloat(document.getElementById('desconto-valor').value);
                    const senha = document.getElementById('desconto-senha').value;
                    const erroDiv = document.getElementById('desconto-erro');
                    erroDiv.textContent = '';

                    if (isNaN(valorDesconto) || valorDesconto <= 0) {
                        erroDiv.textContent = 'Valor de desconto inválido.';
                        return;
                    }

                    const formData = new FormData();
                    formData.append('senha', senha);

                    fetch('../../Dev/Exec/validar_senha.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.sucesso) {
                            const precoItem = parseFloat(item.valor);
                            let descontoCalculado = 0;

                            if (tipo === 'porcento') {
                                if (valorDesconto > data.limite_max) {
                                    erroDiv.textContent = `Erro: Desconto máximo permitido é de ${data.limite_max}%.`;
                                    return;
                                }
                                descontoCalculado = (precoItem * valorDesconto) / 100;
                            } 
                            else { 
                                const percentualEquivalente = (valorDesconto / precoItem) * 100;
                                if (percentualEquivalente > data.limite_max) {
                                    erroDiv.textContent = `Erro: Desconto máximo permitido é de ${data.limite_max}%.`;
                                    return;
                                }
                                descontoCalculado = valorDesconto;
                            }

                            if (descontoCalculado > precoItem) {
                                erroDiv.textContent = 'Desconto não pode ser maior que o preço do item.';
                                return;
                            }

                            cart[index].desconto = descontoCalculado;
                            renderCart();
                            modalDesconto.hide();
                            mostrarToast('Desconto aplicado com sucesso!', 'success');
                        } 
                        else 
                            erroDiv.textContent = data.mensagem;
                    });
                });

                btnGerarCodigo.addEventListener('click', function() {
                    if (cart.length === 0) {
                        mostrarToast('O carrinho está vazio.', 'warning');
                        return;
                    }
                    const formData = new FormData();
                    formData.append('id_cliente', document.getElementById('id_cliente').value);
                    formData.append('itens', JSON.stringify(cart));
                    formData.append('id_prevenda_existente', document.querySelector('input[name="id_prevenda_existente"]').value);

                    fetch('processa_prevenda.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.sucesso) {
                                modalCarrinho.hide();
                                window.open(`cupom_prevenda.php?codigo=${data.codigo}`, '_blank');
                                setTimeout(() => window.location.href = 'nova_prevenda.php', 500);
                            }
                            else 
                                mostrarToast('Erro: ' + (data.mensagem || 'Ocorreu um problema.'), 'danger');
                        });
                });

                campoBuscaCliente.addEventListener('change', function() {
                    const documento = this.value.trim();
                    if (!documento) return;
                    fetch(`../../Dev/Exec/busca_cliente.php?documento=${documento}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.sucesso) {
                                document.getElementById('id_cliente').value = data.id_cliente;
                                document.getElementById('nome_cliente').value = data.nome_cliente;
                            }
                            else {
                                mostrarToast('Cliente não encontrado.', 'warning');
                                document.getElementById('id_cliente').value = '';
                                document.getElementById('nome_cliente').value = '';
                            }
                        });
                });

                const urlParams = new URLSearchParams(window.location.search);
                const vindoDeDispensacao = urlParams.has('id_prevenda');

                if (vindoDeDispensacao && cart.length > 0) {
                    setTimeout(() => {
                        mostrarToast(`Itens da dispensação carregados na pré-venda!`, 'info');
                        triggerCartButtonAnimation(); 
                    }, 1000);
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