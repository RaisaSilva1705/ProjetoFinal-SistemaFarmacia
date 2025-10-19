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
        <title>Devolução de Cliente</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Registrar Devolução de Cliente</h3>
                </div>
                
                <div class="container p-5">
                    <div class="card card-body mb-4">
                        <h5 class="card-title">1. Localizar Venda Original</h5>
                        <div class="input-group">
                            <input type="number" id="busca_id_venda" class="form-control" placeholder="Digite o número da venda (cupom)..." min="1">
                            <button class="btn btn-primary" type="button" id="btn_buscar_venda">Buscar Venda</button>
                        </div>
                    </div>

                    <form action="processa_devolucao_cliente.php" method="POST" id="form_devolucao" style="display: none;">
                        <input type="hidden" name="id_venda_original" id="id_venda_original">
                        <input type="hidden" name="id_cliente" id="id_cliente">

                        <div class="card card-body mb-4">
                            <h5 class="card-title">2. Dados da Venda</h5>
                            <div id="info_venda" class="alert alert-info"></div>
                        </div>

                        <div class="card card-body mb-4">
                            <h5 class="card-title">3. Selecione os Itens para Devolver</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Devolver?</th>
                                            <th>Produto</th>
                                            <th class="text-center">Qtd. Comprada</th>
                                            <th style="width: 15%;">Qtd. a Devolver</th>
                                            <th style="width: 20%;">Motivo</th>
                                            <th>Retornar ao Estoque?</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabela_itens_devolucao"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card card-body">
                            <h5 class="card-title">4. Resolução</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_resolucao" id="reembolso" value="Reembolso" checked>
                                <label class="form-check-label" for="reembolso">Reembolso</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_resolucao" id="credito_loja" value="Credito_Loja">
                                <label class="form-check-label" for="credito_loja">Gerar Crédito em Loja na conta do cliente</label>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Confirmar Devolução</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-arrow-return-left"></i> Registrar Devolução de Cliente</h4>
            <hr>
            <p>Esta tela gerencia o processo de devolução de produtos por parte de um cliente. O fluxo é projetado para ser seguro e garantir que todas as etapas sejam registradas corretamente no sistema.</p>

            <h6><i class="bi bi-search"></i> Passo 1: Localizar a Venda Original</h6>
            <p>Para iniciar uma devolução, é <strong>obrigatório</strong> localizar a transação original. Digite o <strong>número da venda</strong> (que pode ser encontrado no cupom do cliente) no campo de busca e clique em "Buscar Venda".</p>
            <p>O sistema carregará as informações da venda e a lista de produtos que foram comprados.</p>

            <h6><i class="bi bi-list-check"></i> Passo 2: Selecionar Itens para Devolver</h6>
            <p>Na tabela de itens, configure a devolução:</p>
            <ol>
                <li><strong>Marque a caixa "Devolver?"</strong> para os produtos que o cliente está devolvendo. Isso habilitará os outros campos da linha.</li>
                <li><strong>Qtd. a Devolver:</strong> Informe a quantidade de unidades que estão sendo devolvidas.</li>
                <li><strong>Motivo:</strong> Selecione o motivo da devolução.</li>
                <li><strong>Retornar ao Estoque?:</strong> Mantenha esta caixa marcada se o produto estiver em perfeitas condições e puder ser vendido novamente. Desmarque-a se o produto estiver avariado, vencido ou impróprio para venda (nesse caso, ele não voltará para o estoque disponível).</li>
            </ol>

            <h6><i class="bi bi-arrow-repeat"></i> Passo 3: Resolução</h6>
            <p>Escolha como o cliente será compensado pela devolução:</p>
            <ul>
                <li><strong>Reembolso:</strong> O valor será devolvido ao cliente em dinheiro. Esta opção exige que um caixa esteja aberto no sistema, pois registrará uma saída de dinheiro (sangria).</li>
                <li><strong>Gerar Crédito em Loja:</strong> O valor será adicionado como saldo na conta do cliente para ser usado em compras futuras. Esta opção só está disponível se o cliente foi identificado na venda original.</li>
            </ul>

            <h6><i class="bi bi-check-circle-fill"></i> Passo 4: Confirmar</h6>
            <p>Após preencher todos os dados, clique em <strong>"Confirmar Devolução"</strong>. O sistema registrará a devolução, ajustará o estoque (se aplicável) e processará o reembolso ou o crédito.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const btnBuscar = document.getElementById('btn_buscar_venda');
                const inputBusca = document.getElementById('busca_id_venda');
                const formDevolucao = document.getElementById('form_devolucao');
                const infoVendaDiv = document.getElementById('info_venda');
                const tabelaItens = document.getElementById('tabela_itens_devolucao');
                
                btnBuscar.addEventListener('click', function() {
                    const idVenda = inputBusca.value.trim();
                    if (!idVenda) {
                        mostrarToast('Por favor, digite o número da venda.', 'warning');
                        return;
                    }

                    formDevolucao.style.display = 'none';
                    tabelaItens.innerHTML = '';
                    
                    fetch(`../../Dev/Exec/busca_venda_devolucao.php?id_venda=${idVenda}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.sucesso) {
                                const venda = data.venda;
                                const itens = data.itens;
                                const dataVenda = new Date(venda.DataHora_Venda).toLocaleDateString('pt-BR');

                                document.getElementById('id_venda_original').value = venda.ID_Venda;
                                document.getElementById('id_cliente').value = venda.ID_Cliente;
                                infoVendaDiv.innerHTML = `
                                    <strong>Venda Nº:</strong> ${venda.ID_Venda}<br>
                                    <strong>Data:</strong> ${dataVenda}<br>
                                    <strong>Cliente:</strong> ${venda.Nome_Cliente}
                                `;
                                
                                itens.forEach(item => {
                                    const newRow = tabelaItens.insertRow();
                                    newRow.innerHTML = `
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="form-check-input" name="itens[${item.ID_Produto}][devolver]" value="1">
                                        </td>
                                        <td>
                                            ${item.Nome_Produto}
                                            <input type="hidden" name="itens[${item.ID_Produto}][valor_unitario]" value="${item.Valor_Unitario_Venda}">
                                        </td>
                                        <td class="text-center">${item.Quantidade}</td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" name="itens[${item.ID_Produto}][quantidade]" min="1" max="${item.Quantidade}" disabled>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="itens[${item.ID_Produto}][motivo]" disabled>
                                                <option value="">Selecione...</option>
                                                <option value="Produto avariado">Produto avariado</option>
                                                <option value="Arrependimento do cliente">Arrependimento</option>
                                                <option value="Outro">Outro</option>
                                            </select>
                                        </td>
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="form-check-input" name="itens[${item.ID_Produto}][retornar_estoque]" value="1" disabled checked>
                                        </td>
                                    `;
                                });

                                formDevolucao.style.display = 'block';
                            } 
                            else 
                                mostrarToast(data.erro, 'danger');
                        });
                });
                
                tabelaItens.addEventListener('change', function(e) {
                    if (e.target.type === 'checkbox' && e.target.name.includes('[devolver]')) {
                        const tr = e.target.closest('tr');
                        const inputs = tr.querySelectorAll('input[type="number"], select, input[type="checkbox"]:not([name*="[devolver]"])');
                        
                        inputs.forEach(input => {
                            input.disabled = !e.target.checked;
                            if (input.type === 'number' || input.tagName === 'SELECT') {
                            input.required = e.target.checked;
                            }
                            if (!e.target.checked) {
                                if (input.type === 'number') input.value = '';
                                if (input.tagName === 'SELECT') input.selectedIndex = 0;
                            }
                        });
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