<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

if (!isset($_SESSION['entrada_xml'])) {
    header('Location: entrada_estoque.php');
    exit;
}

$result_margem = $conn->query("SELECT Margem_Lucro_Padrao FROM CONFIGURACOES WHERE ID_Config = 1");
$margem_lucro = $result_margem->fetch_assoc()['Margem_Lucro_Padrao'];

$dados_xml = $_SESSION['entrada_xml'];
$produtos_encontrados = $dados_xml['encontrados'];
$produtos_nao_encontrados = $dados_xml['nao_encontrados'];
$cnpj_fornecedor_xml = $dados_xml['cnpj_fornecedor'];
$numero_nota_xml = $dados_xml['numero_nota'];

$fornecedor_db = null;
$stmt_forn = $conn->prepare("SELECT ID_Fornecedor, Nome_Fantasia FROM FORNECEDORES WHERE CNPJ = ?");
$stmt_forn->bind_param("s", $cnpj_fornecedor_xml);
$stmt_forn->execute();
$result_forn = $stmt_forn->get_result();
if ($result_forn->num_rows > 0) 
    $fornecedor_db = $result_forn->fetch_assoc();

unset($_SESSION['entrada_xml']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmar Entrada de Estoque via XML</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Revisão da Nota Fiscal (XML)</h3>
                </div>
                <div class="container p-4">
                    <form action="processa_entrada.php" method="POST">
                        
                        <?php if ($fornecedor_db): ?>
                            <div class="alert alert-info">
                                <strong>Fornecedor:</strong> <?= htmlspecialchars($fornecedor_db['Nome_Fantasia']) ?><br>
                                <strong>CNPJ:</strong> <?= htmlspecialchars($cnpj_fornecedor_xml) ?><br>
                                <strong>Nota Fiscal Nº:</strong> <?= htmlspecialchars($numero_nota_xml) ?>
                                <input type="hidden" name="id_fornecedor" value="<?= $fornecedor_db['ID_Fornecedor'] ?>">
                                <input type="hidden" name="numero_nota" value="<?= htmlspecialchars($numero_nota_xml) ?>">
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <h4><i class="bi bi-exclamation-triangle-fill"></i> Fornecedor não Cadastrado</h4>
                                <p>O fornecedor com CNPJ <strong><?= htmlspecialchars($cnpj_fornecedor_xml) ?></strong> não foi encontrado no seu sistema.
                                Você precisa cadastrá-lo antes de dar entrada nesta nota.</p>
                                <a href="../Fornecedores/cadastrar_fornecedor.php?cnpj=<?= urlencode($cnpj_fornecedor_xml) ?>" target="_blank" class="btn btn-danger">
                                    Cadastrar este Fornecedor
                                </a>
                            </div>
                        <?php endif; ?>

                        <h4 class="mt-4">Produtos Encontrados no Sistema</h4>
                        <p>Preencha os campos de Lote, Validade e Preço de Venda para os itens que deseja importar.</p>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Importar?</th>
                                        <th>Produto</th>
                                        <th>Qtd.</th>
                                        <th>Custo</th>
                                        <th>Lote</th>
                                        <th>Validade</th>
                                        <th>Margem (%)</th>
                                        <th>Preço de Venda</th>
                                    </tr>
                                </thead>
                                <tbody id="tabelaItens">
                                    <?php foreach ($produtos_encontrados as $index => $item): 
                                        $preco_venda_sugerido = $item['custo_xml'] * (1 + ($margem_lucro / 100));
                                        $preco_venda_formatado = number_format($preco_venda_sugerido, 2, ',', '');
                                    ?>
                                    <tr>
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="form-check-input" name="produtos[<?= $item['id_produto'] ?>][importar]" value="1" checked>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($item['nome_db']) ?>
                                            <small class="d-block text-muted">EAN: <?= htmlspecialchars($item['ean_xml']) ?></small>
                                            <input type="hidden" name="produtos[<?= $item['id_produto'] ?>][id]" value="<?= $item['id_produto'] ?>">
                                            <input type="hidden" name="produtos[<?= $item['id_produto'] ?>][quantidade]" value="<?= $item['qtd_xml'] ?>">
                                            <input type="hidden" name="produtos[<?= $item['id_produto'] ?>][custo]" class="custo-hidden" value="<?= $item['custo_xml'] ?>">
                                        </td>
                                        <td><?= $item['qtd_xml'] ?></td>
                                        <td>R$ <?= number_format($item['custo_xml'], 2, ',', '.') ?></td>
                                        <td><input type="text" name="produtos[<?= $item['id_produto'] ?>][lote]" class="form-control form-control-sm" required></td>
                                        <td><input type="date" name="produtos[<?= $item['id_produto'] ?>][validade]" class="form-control form-control-sm" required></td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="produtos[<?= $item['id_produto'] ?>][margem]" class="form-control margem-lucro" value="<?= number_format($margem_lucro, 2, ',', '.') ?>">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="produtos[<?= $item['id_produto'] ?>][venda]" class="form-control form-control-sm preco-venda" value="<?= $preco_venda_formatado ?>" required>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (!empty($produtos_nao_encontrados)): ?>
                        <h4 class="mt-5">Produtos Não Cadastrados no Sistema</h4>
                        <div class="alert alert-warning">
                            <p>Os itens abaixo não foram encontrados no seu sistema pelo código de barras. Cadastre-os primeiro e depois processe o XML novamente.</p>
                            <ul class="list-group">
                                <?php foreach ($produtos_nao_encontrados as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($item['nome_xml']) ?></strong><br>
                                        <small class="text-muted">EAN: <?= htmlspecialchars($item['ean_xml']) ?></small>
                                    </div>
                                    <a href="../Produtos/cadastrar_produto.php?ean=<?= urlencode($item['ean_xml']) ?>&nome=<?= urlencode($item['nome_xml']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                        Cadastrar
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Confirmar e Dar Entrada no Estoque</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabelaItens = document.getElementById('tabelaItens');
                const checkboxes = document.querySelectorAll('input[type="checkbox"][name^="produtos"]');

                function formatarMoeda(valor) {
                    let valorNumerico = parseFloat(String(valor).replace(',', '.'));
                    if (isNaN(valorNumerico)) return '';
                    return valorNumerico.toFixed(2).replace('.', ',');
                }

                function toggleRequired(checkbox) {
                    const tr = checkbox.closest('tr');
                    const inputs = tr.querySelectorAll('input[type="text"], input[type="date"]');
                    inputs.forEach(input => input.required = checkbox.checked);
                }

                tabelaItens.addEventListener('input', function(event) {
                    const target = event.target;
                    const tr = target.closest('tr');
                    if (!tr) return;

                    const campoCustoHidden = tr.querySelector('.custo-hidden');
                    const campoMargem = tr.querySelector('.margem-lucro');
                    const campoVenda = tr.querySelector('.preco-venda');

                    if (!campoCustoHidden || !campoMargem || !campoVenda) return;
                    
                    let custo = parseFloat(campoCustoHidden.value);
                    let margem = parseFloat(campoMargem.value.replace(',', '.'));
                    let venda = parseFloat(campoVenda.value.replace(',', '.'));

                    if (target === campoMargem) {
                        if (!isNaN(custo) && !isNaN(margem)) {
                            let vendaCalculada = custo * (1 + (margem / 100));
                            campoVenda.value = formatarMoeda(vendaCalculada);
                        }
                    }
                    
                    if (target === campoVenda) {
                        if (!isNaN(custo) && !isNaN(venda) && custo > 0) {
                            let margemCalculada = ((venda / custo) - 1) * 100;
                            campoMargem.value = formatarMoeda(margemCalculada);
                        }
                    }
                });

                tabelaItens.addEventListener('blur', function(event) {
                    const target = event.target;
                    if (target.classList.contains('margem-lucro') || target.classList.contains('preco-venda')) {
                        target.value = formatarMoeda(target.value);
                    }
                }, true);

                checkboxes.forEach(checkbox => {
                    toggleRequired(checkbox);
                    checkbox.addEventListener('change', function() {
                        toggleRequired(this);
                    });
                });
            });
        </script>
    </body>
</html>