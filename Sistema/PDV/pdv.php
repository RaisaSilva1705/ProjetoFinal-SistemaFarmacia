<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

// Confere se não há ID_Caixa aberto
if (!isset($_SESSION['ID_Caixa'])){
    header("Location: caixa_pdv.php");
    exit();
}

// Busca dados da empresa (quant_max_parcelas e valor_min_parcelas)
$sqlInfoParcelas =  "SELECT Quant_Max_Parcelas, Valor_Min_Parcelas FROM CONFIGURACOES";
$stmtInfoParcelas = $conn->prepare($sqlInfoParcelas);
$stmtInfoParcelas->execute();
$resultInfoParcelas = $stmtInfoParcelas->get_result();
$infoParcelas = $resultInfoParcelas->fetch_assoc();

// Cancela a venda
if (isset($_POST['cancelar_venda'])) {
    unset($_SESSION['carrinho']);
    unset($_SESSION['ultimo_produto']);
    header("Location: pdv.php");
    exit;
}

// Inicializa o carrinho
if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];

// Adiciona item no carrinho
if (isset($_POST['codigo'])) {
    $codigo = $_POST['codigo'];

    $stmt = $conn->prepare("SELECT P.Nome, 
                                   MAX(L.Preco_Venda) AS Preco_Venda,
                                   P.Foto 
                            FROM PRODUTOS P 
                            LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto
                            WHERE EAN_GTIN = ?
                            GROUP BY P.ID_Produto");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $produto = $result->fetch_assoc();
        $quantidade = max(1, intval($_POST['quantidade']));

        // Verificação se o valor do produto não é nulo ou 0
        if ($produto['Preco_Venda'] === null || $produto['Preco_Venda'] <= 0) {
            $_SESSION['msg'] = ['texto' => 'Produto sem preço!', 'tipo' => 'danger'];
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }

        $produtoEncontrado = false;

        foreach ($_SESSION['carrinho'] as &$item) {
            if ($item['codigo'] === $codigo) {
                $item['quantidade'] += $quantidade;
                $produtoEncontrado = true;
                break;
            }
        }
        unset($item); // evita problemas com referências

        if (!$produtoEncontrado) {
            $_SESSION['carrinho'][] = [
                'codigo' => $codigo,
                'nome' => $produto['Nome'],
                'preco' => $produto['Preco_Venda'],
                'foto' => $produto['Foto'] ?? 'sem-imagem.png',
                'quantidade' => $quantidade
            ];
        }

        // Salva info do último produto
        $_SESSION['ultimo_produto'] = [
            'descricao' => $produto['Nome'],
            'preco' => $produto['Preco_Venda'],
            'foto' => $produto['Foto']
        ];

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } 
    else {
        $_SESSION['msg'] = ['texto' => 'Produto não encontrado!', 'tipo' => 'danger'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Frente de Caixa</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <div class="content d-flex align-items-center justify-content-center">
            <div class="container">

                <!-- TOPO -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="id_venda" class="form-label">Nº Venda:</label>
                        <input type="text" name="id_venda" id="id_venda" class="form-control" value="NOVA VENDA" style="font-weight: bold; text-align: center;" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="data" class="form-label">Data Venda:</label>
                        <input type="text" name="data" id="data" class="form-control" value="<?= date('d/m/Y H:i') ?>" readonly>
                    </div>
                    <div class="col-md-3">
                        <label for="busca_cliente_cpf" class="form-label">CPF/CNPJ do Cliente</label>
                        <input type="text" id="busca_cliente_cpf" class="form-control" placeholder="Digite o documento...">
                        <input type="hidden" id="id_cliente_consulta" name="id_cliente_consulta" value="">
                    </div>
                    <div class="col-md-3">
                        <label for="funcionario" class="form-label">Vendedor:</label>
                        <input type="text" class="form-control" name="funcionario" id="funcionario" value="<?php echo $_SESSION['Nome'] ?>" readonly>
                    </div>
                </div>

                <!-- CENTRO -->
                <div class="row mt-4 mb-4">
                    <!-- COLUNA PRODUTO -->
                    <div class="col-md-7 border p-2">
                        <form action="pdv.php" method="POST" id="form-add-item">
                            <div class="row">
                                <!-- COLUNA IMAGEM -->
                                <div class="col-md-6 p-3">
                                    <div class="col-md-5 text-center ">
                                        <img src='<?php echo DEV_URL?>Imagens/ImgSistema/sem-imagem.jpg' id="foto" name="foto" class="product-img" alt="Imagem da Embalagem do Produto" height="380px" width="380px">
                                    </div>
                                </div>
                                <!-- COLUNA INFO -->
                                <div class="col-md-6 p-3 mt-4">
                                    <div class="col-md-12">
                                        <label for="descricao">Descrição:</label>
                                        <input type="text" id="descricao" name="descricao" class="form-control input-big" autocomplete="off">
                                        <div id="sugestoes_nome" class="list-group mt-1" style="position: absolute; z-index: 1000;"></div>
                                    </div>
                                    <div class="row mt-12 mt-4">
                                        <div class="col-6">
                                            <label for="quantidade">Quantidade:</label>
                                            <input type="number" id="quantidade" name="quantidade" class="form-control input-big" value="1" min="1">
                                        </div>
                                        <div class="col-6">
                                            <label for="preco">Preço Unitário:</label>
                                            <input type="text" id="preco" name="preco" class="form-control" value="R$ 0,00" readonly>
                                        </div>
                                    </div>
                                    <div class="row mt-12 mt-4">
                                        <div class="col-md-6">
                                            <label for="codigo">Código Barras:</label>
                                            <input type="text" id="codigo" name="codigo" class="form-control">
                                        </div>
                                        <div class="col-md-4 mt-4">
                                            <button type="submit" class="btn btn-primary">Adicionar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-5 border p-1" style="height: 430px; overflow-y: auto;">
                        <!-- COLUNA VALORES/LOGO -->
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th style="width: 200px;">Nome</th>
                                    <th>Valor</th>
                                    <th>Quant</th>
                                    <th>Total</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody style="min-height: 240px;">
                                <?php
                                    $totalGeral = 0;
                                    $totalItens = 0;
                                    $linhasDesejadas = 11; // Número total de linhas que você quer
                                    $linhasOcupadas = 0;

                                    if (!empty($_SESSION['carrinho'])):
                                        foreach ($_SESSION['carrinho'] as $index => $item):
                                            $preco = ($item['preco'] == null) ?  0.00 : $item['preco'];
                                            $subtotal = $preco * $item['quantidade'];
                                            $totalGeral += $subtotal;
                                            $totalItens += $item['quantidade'];
                                            $linhasOcupadas++;
                                            $classe_linha = '';
                                            $texto_quantidade = $item['quantidade'];

                                            if (isset($item['origem']) && $item['origem'] === 'prevenda') {
                                                $qtd_verificada = $item['quantidade_verificada'] ?? 0;
                                                if ($qtd_verificada < $item['quantidade']) {
                                                    $texto_quantidade = "({$qtd_verificada}/{$item['quantidade']})";
                                                    if ($qtd_verificada == 0) 
                                                        $classe_linha = 'table-danger';
                                                    else 
                                                        $classe_linha = 'table-warning';
                                                }
                                            }
                                    ?>
                                    <tr class="<?= $classe_linha ?>">
                                        <td><?= htmlspecialchars($item['nome']) ?></td>
                                        <td>R$ <?= number_format($preco, 2, ',', '.') ?></td>
                                        <td><?= $item['quantidade'] ?></td>
                                        <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                                        <td class="d-flex align-items-center justify-content-center gap-1">
                                            <button class="btn btn-sm btn-secondary" onclick="gerenciarItem(<?= $index ?>, 'diminuir')"><i class="bi bi-dash-lg"></i></button>
                                            <button class="btn btn-sm btn-danger" onclick="gerenciarItem(<?= $index ?>, 'remover')"><i class="bi bi-trash-fill"></i></button>
                                        </td>
                                    </tr>
                                    <?php
                                        endforeach;

                                        // Preenche o restante com linhas vazias
                                        for ($i = 0; $i < $linhasDesejadas - $linhasOcupadas; $i++): ?>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                    <?php endfor;

                                    else: 
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Nenhum item adicionado ao carrinho</td>
                                </tr>
                                <?php for ($i = 0; $i < $linhasDesejadas - 1; $i++): ?>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                <?php endfor; ?>
                            <?php endif; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total Geral:</strong></td>
                                    <td colspan="2"><strong>R$ <?= number_format($totalGeral, 2, ',', '.') ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- RODAPÉ -->
                <div class="row">
                    <div class="col-md-1">
                        <label>Total Bruto:</label>
                        <input type="text" id="total_bruto" class="form-control" value="R$ <?= number_format($totalGeral, 2, ',', '.') ?>" readonly>
                    </div>
                    <div class="col-md-1">
                        <label>Qtd. Itens:</label>
                        <input type="text" class="form-control" value="<?= $totalItens ?>" readonly>
                    </div>

                    <div class="col-md-4 d-flex align-items-center gap-2 mt-4">
                        <button type="button" class="btn btn-success" onclick="selecionarForma(1)">(1) Dinheiro</button>
                        <button type="button" class="btn btn-success" onclick="selecionarForma(2)">(2) Crédito</button>
                        <button type="button" class="btn btn-success" onclick="selecionarForma(3)">(3) Débito</button>
                        <button type="button" class="btn btn-success" onclick="selecionarForma(4)">(4) Pix</button>
                    </div>

                    <div class="col-md-6 d-flex align-items-center justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalConsultaPreco" id="btnConsultarPreco">
                            Consultar Preço
                        </button>
                        <form action="pdv.php" method="POST" id="formCancelarVenda">
                            <input type="hidden" name="cancelar_venda" value="1">
                            <button class="btn btn-danger" type="submit">Cancelar Venda</button>
                        </form>
                        <form action="finalizarcaixa_pdv.php" method="POST" id="formFecharCaixa">
                            <input type="hidden" name="finalizar_caixa" value="1">
                            <button class="btn btn-secondary" type="submit">Fechar Caixa</button>
                        </form>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#popupFuncionalidades" id="btnFuncionalidades">
                            Funcionalidades
                        </button>
                    </div>
                </div>

                <div id="caixaSugestoes" class="alert alert-info mt-3" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0"><i class="bi bi-star-fill"></i> Sugestões para este cliente:</h6>
                        <button type="button" class="btn-close" id="fecharSugestoes"></button>
                    </div>
                    <hr>
                    <div id="listaSugestoes" class="list-group"></div>
                </div>

            </div>

            <!-- Modal de Pagamento -->
            <div class="modal fade modal-lg" id="popupPagamento" tabindex="-1" aria-labelledby="popupPagamentoLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Formas de Pagamento</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <?php 
                                // Busca formas de pagamento cadastradas
                                $sqlFoPags = "SELECT ID_Forma_Pag, Tipo FROM FORMAS_PAGAMENTO";
                                $foPags = $conn->query($sqlFoPags);
                            ?>
                            <?php while($foPag = $foPags->fetch_assoc()): ?>
                                <div class="mb-3 row campo-forma" data-id="<?= $foPag['ID_Forma_Pag']; ?>" style="display: none;">
                                    <label class="col-sm-4 col-form-label"><?= $foPag['ID_Forma_Pag']; ?> - <?= $foPag['Tipo']; ?></label>
                                    <div class="col-sm-8 d-flex align-items-center">
                                        <input type="text" class="form-control forma" data-id="<?= $foPag['ID_Forma_Pag'] ?>" placeholder="R$">
                                        <?php if($foPag['ID_Forma_Pag'] == 2 && $totalGeral >= $infoParcelas['Valor_Min_Parcelas']): ?>
                                            <label class="me-2 mb-0">Qnt. Parcelas:</label>
                                            <input type="number" class="form-control parcela" id="parcelas" name="quant_parcelas" min="1" max="<?= $infoParcelas['Quant_Max_Parcelas']?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>

                            <div class="text-end fw-bold mt-3" id="troco" style="display: none;">Troco: R$ 0,00</div>
                        </div>
                        
                        <div class="modal-footer">
                            <input type="hidden" name="id_cliente_hidden" id="id_cliente_hidden" value="">
                            <button class="btn btn-success w-100" id="confirmarPagamento" type="submit">Confirmar Pagamento</button>
                            <div id="card-errors"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal PIX -->
            <div class="modal fade" id="modalPix" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Pagamento via PIX</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <p>Escaneie o QR Code abaixo para pagar:</p>
                            <img src="" alt="QRCode pagamento via PIX" id="pixImg" width="300">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary w-100" id="btnConfirmarPix">Pagamento Confirmado</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de Consulta de Preço -->
            <div class="modal fade" id="modalConsultaPreco" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Consulta Rápida de Preço</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label for="inputConsulta" class="form-label">Digite o nome ou código de barras do produto:</label>
                            <input type="text" id="inputConsulta" class="form-control" autocomplete="off" placeholder="Inicie a digitação...">
                            
                            <div id="resultadoConsulta" class="text-center mt-3" style="min-height: 150px;"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de Funcionalidades -->
            <div class="modal fade modal-lg" id="popupFuncionalidades" tabindex="-1" aria-labelledby="popupFuncionalidadesLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Funcionalidades do Caixa</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body" id="funcionalidadesConteudo">
                            <div class="text-center">
                                <button class="btn btn-danger m-2" onclick="selecionarFuncionalidade('saida')">Sangria (Saída de Dinheiro)</button>
                                <button class="btn btn-success m-2" onclick="selecionarFuncionalidade('entrada')">Entrada de Dinheiro</button>
                            </div>
                        </div>
                        <div class="modal-footer" id="funcionalidadesFooter" style="display: none;">
                            <button class="btn btn-primary w-100" onclick="registrarMovimentacao()">Confirmar</button>
                            <div id="erroFuncionalidade" class="text-danger mt-2 w-100 text-center"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de confirmação para remoção de produtos no carrinho -->
        <div class="modal fade" id="modalGerente" tabindex="-1">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Acesso Restrito</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Por favor, insira a senha do gerente para continuar.</p>
                        <div class="form-group">
                            <label for="senhaGerente">Senha:</label>
                            <input type="password" class="form-control" id="senhaGerente">
                            <div id="erroSenha" class="text-danger mt-2"></div>
                        </div>
                        <input type="hidden" id="acaoGerente">
                        <input type="hidden" id="itemIndex">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="validarSenha()">Confirmar</button>
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
            window.carrinhoSessao = <?= json_encode(array_values($_SESSION['carrinho'])) ?>;
            // MUDAR OS VALORES POR CÓDIGO DE BARRAS
            document.getElementById('codigo').addEventListener('change', function () {
                const codigo = this.value.trim();
                if (codigo === '' || codigo.startsWith('99')) 
                    return;
                
                fetch('../../Dev/Exec/busca_produto.php?codigo=' + encodeURIComponent(codigo))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) { 
                            if (data.controlado === 'Sim') {
                                    const itemPendente = window.carrinhoSessao.some(item => 
                                    item.codigo === codigo && 
                                    item.origem === 'prevenda' &&
                                    item.quantidade_verificada < item.quantidade
                                );

                                if (!itemPendente) {
                                    mostrarToast('Este é um medicamento controlado e não pode ser vendido diretamente no caixa.', 'danger', 'Acesso Negado');
                                    this.value = ''; 
                                    document.getElementById('descricao').value = '';
                                    document.getElementById('preco').value = 'R$ 0,00';
                                    return; 
                                }
                            }

                            document.getElementById('descricao').value = data.nome;
                            document.getElementById('preco').value = "R$ " + parseFloat(data.preco).toFixed(2).replace('.', ',');
                            
                            let fotoUrl = '../../Dev/Imagens/imgSistema/sem-imagem.jpg';
                            if(data.foto && data.foto !== 'sem-imagem.jpg') 
                                fotoUrl = '../../Dev/Imagens/imgProdutos/' + data.foto;
                            
                            document.getElementById('foto').src = fotoUrl;

                        } 
                        else {
                            mostrarToast(data.mensagem || 'Produto não encontrado', 'warning', 'Erro');
                            this.value = '';
                            document.getElementById('descricao').value = '';
                            document.getElementById('preco').value = 'R$ 0,00';
                            document.getElementById('foto').src = '../../Dev/Imagens/imgSistema/sem-imagem.jpg';
                        }
                    })
                    .catch(err => {
                        mostrarToast('Erro ao buscar produto.', 'warning', 'Erro');
                        console.error(err);
                    });
            });

            document.getElementById('form-add-item').addEventListener('submit', function (event) {
                event.preventDefault(); 
    
                const codigoInput = document.getElementById('codigo');
                const codigo = codigoInput.value.trim();
                if(codigo === '') return;

                // --- LÓGICA DE VERIFICAÇÃO PRIMEIRO ---
                let itemParaVerificarIndex = -1;
                if (Array.isArray(window.carrinhoSessao)) { 
                    itemParaVerificarIndex = window.carrinhoSessao.findIndex(item => 
                        item.codigo === codigo && 
                        item.origem === 'prevenda' &&
                        item.quantidade_verificada < item.quantidade
                    );
                }

                if (itemParaVerificarIndex > -1) {
                    fetch('../../Dev/Exec/gerenciar_carrinho.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `acao=verificar&index=${itemParaVerificarIndex}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.sucesso) 
                            location.reload(); 
                        else 
                            mostrarToast(data.erro || 'Não foi possível verificar o item.', 'danger');
                    });

                } 

                if (codigo.startsWith('99')) {
                    event.preventDefault(); 

                    fetch('../../Dev/Exec/processa_carrinho_prevenda.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `codigo_prevenda=${encodeURIComponent(codigo)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.sucesso) {
                            if (data.cliente) {
                                const idClienteHidden = document.getElementById('id_cliente_hidden');
                                const buscaClienteInput = document.getElementById('busca_cliente_cpf');
                                
                                if (idClienteHidden) idClienteHidden.value = data.cliente.id;
                                if (buscaClienteInput) {
                                    buscaClienteInput.value = data.cliente.nome;
                                    buscaClienteInput.dispatchEvent(new Event('change')); 
                                }
                            }
                            
                            mostrarToast(data.mensagem || 'Itens carregados com sucesso!', 'success');
                            setTimeout(() => location.reload(), 1000); 
                        } 
                        else {
                            mostrarToast(data.mensagem || 'Pré-venda não encontrada.', 'danger');
                            codigoInput.value = ''; 
                        }
                    })
                    .catch(err => {
                        mostrarToast('Erro de comunicação ao carregar pré-venda.', 'danger');
                        console.error(err);
                    });
                } 
            });

            // MUDAR OS VALORES POR NOME DO PRODUTO
            const campoDescricaoProduto = document.getElementById('descricao');
            const sugestoesDiv = document.getElementById('sugestoes_nome');

            campoDescricaoProduto.addEventListener('input', function(){
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
                            item.classList.add('list-group-item', 'list-group-item-action');
                            item.textContent = produto.Nome;
                            item.dataset.codigo = produto.EAN_GTIN;

                            item.addEventListener('click', function(){
                                document.getElementById('codigo').value = this.dataset.codigo;
                                campoDescricaoProduto.value = this.textContent;
                                sugestoesDiv.innerHTML = '';

                                document.getElementById('codigo').dispatchEvent(new Event('change'));
                            });

                            sugestoesDiv.appendChild(item);
                        });
                    });
            });

            document.addEventListener('click', function(e){
                if (!campoDescricaoProduto.contains(e.target) && !sugestoesDiv.contains(e.target)) {
                    sugestoesDiv.innerHTML = '';
                }
            });

            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------

            const valorTotalVenda = <?= $totalGeral ?>;
            let formasSelecionadas = [];
            const popupPagamento = new bootstrap.Modal(document.getElementById('popupPagamento'))

            function selecionarForma(id) {
                if (valorTotalVenda <= 0) {
                    mostrarToast('Adicione itens ao carrinho antes de pagar.', 'warning', 'Atenção');
                    return;
                }

                if (formasSelecionadas.length >= 2) {
                    if (!formasSelecionadas.includes(id)){
                        mostrarToast('Apenas 2 formas de pagamento permitidas.', 'warning', 'Atenção');
                        return;
                    }
                }

                if (!formasSelecionadas.includes(id)) {
                    formasSelecionadas.push(id);
                }

                atualizarModalPagamento();
                popupPagamento.show();
            }

            function atualizarModalPagamento() {
                let valorJaPago = 0;
                const camposForma = document.querySelectorAll('.campo-forma');

                camposForma.forEach(div => {
                    const idForma = parseInt(div.dataset.id);
                    const input = div.querySelector('.forma');

                    if (formasSelecionadas.includes(idForma))
                        div.style.display = 'flex';
                    else {
                        div.style.display = 'none';
                        input.value = ''; 
                    }

                    input.removeAttribute('readonly');
                });

                if (formasSelecionadas.length > 1) {
                    for (let i = 0; i < formasSelecionadas.length - 1; i++){
                        const idAnterior = formasSelecionadas[i];
                        const inputAnterior = document.querySelector(`.forma[data-id="${idAnterior}"]`);
                        if (inputAnterior && inputAnterior.value)
                            valorJaPago = parseFloat(inputAnterior.value.replace(',', '.')) || 0;
                    }   
                }

                const valorRestante = valorTotalVenda - valorJaPago;

                if (formasSelecionadas.length > 0) {
                    const ultimoId = formasSelecionadas[formasSelecionadas.length - 1];
                    const ultimoInput = document.querySelector(`.forma[data-id="${ultimoId}"]`);
                    
                    ultimoInput.value = valorRestante.toFixed(2).replace('.', ',');

                    if (formasSelecionadas.length === 2) 
                        ultimoInput.setAttribute('readonly', true);
                    else {
                        ultimoInput.removeAttribute('readonly');
                        ultimoInput.focus(); 
                    }
                }
                
                // Atualiza o troco
                calcularTroco();
            }

            function atualizarExibicaoTroco() {
                const temDinheiroSelecionado = formasSelecionadas.includes(1); // 1 = Dinheiro
                const trocoDiv = document.getElementById('troco');
                trocoDiv.style.display = temDinheiroSelecionado ? 'block' : 'none';
            }

            document.getElementById('popupPagamento').addEventListener('hidden.bs.modal', function () {
                formasSelecionadas = [];
                document.querySelectorAll('.campo-forma').forEach(div => {
                    div.querySelector('.forma').removeAttribute('readonly');
                });
            });
            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------
            
            let totalPago = 0;
            let formas_pagamento = [];
            let mapaFormas = {}; // para evitar entradas duplicadas no banco

            // Confirmação de pagamento com STRIPE
            document.getElementById('confirmarPagamento').addEventListener('click', async function(event){
                // ---- BLOCO DE VERIFICAÇÃO DO CARRINHO ----
                const itemNaoVerificado = window.carrinhoSessao.some(item =>
                    item.origem === 'prevenda' &&
                    item.quantidade_verificada < item.quantidade
                );

                if (itemNaoVerificado) {
                    event.preventDefault(); 
                    mostrarToast('Existem itens da pré-venda que ainda não foram verificados! Por favor, escaneie todos os produtos.', 'danger', 'Verificação Pendente');
                    return; 
                }
                event.preventDefault();

                document.querySelectorAll('.forma').forEach(function(input){
                    const style = window.getComputedStyle(input.closest('.campo-forma'));
                    if (style.display === 'none') return; // Ignora campos ocultos
                    
                    const id = parseInt(input.dataset.id);
                    const valor = parseFloat(input.value.replace(',', '.'));
                    if (!isNaN(valor) && valor > 0) {
                        totalPago += valor;

                        if (!mapaFormas[id]){
                            const inputParcelas = document.getElementById('parcelas');

                            mapaFormas[id] = {
                                id_forma_pag: id,
                                valor: 0,
                                quant_vezes: (inputParcelas && valor >= <?= $infoParcelas['Valor_Min_Parcelas'] ?>) ? inputParcelas.value : 1
                            };
                        }
                        mapaFormas[id].valor += valor;
                    }
                });

                formas_pagamento = Object.values(mapaFormas);
                console.log(formas_pagamento);

                if (totalPago < <?= $totalGeral ?>) {
                    mostrarToast("Pagamento Concluído. Ainda faltam R$ " + (<?= $totalGeral ?> - totalPago).toFixed(2).replace('.', ','), 'success', 'Sucesso');
                    calcularTroco();
                    return;
                }

                let erroPagamento = false;

                for (let forma of formas_pagamento) {
                    if (forma.id_forma_pag !== 1) {  // apenas Crédito (2), Débito (3) ou PIX (4)
                        let response;

                        if (forma.id_forma_pag === 4){ // PIX

                            // -------- STRIPE -> precisa de verificação com CNPJ --------
                            /*response = await fetch('../../Dev/Exec/stripe_pagamento.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    valor: forma.valor,
                                    tipo: 'pix'
                                })
                            });*/

                            // Simulação: gera um QR Code fake com o valor da venda
                            const qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=Pagamento_PIX_VALOR_' + encodeURIComponent(forma.valor);

                            // Abre o Modal do PIX
                            const pixModal = new bootstrap.Modal(document.getElementById('modalPix'));
                            const pixImg = document.getElementById('pixImg');
                            pixImg.src = qrCodeUrl;

                            pixModal.show();

                            await new Promise((resolve) => {
                                document.getElementById('btnConfirmarPix').onclick = function(){
                                    pixModal.hide();
                                    resolve();
                                }
                            })

                        }
                        else { // Cartão
                            response = await fetch('../../Dev/Exec/stripe_pagamento.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    valor: forma.valor,
                                    tipo: 'cartao'
                                })
                            });
                        
                            const result = await response.json();
                            // console.log(result);

                            if (!result.sucesso) {
                                document.getElementById('card-errors').textContent = 'Erro no pagamento: ' + result.mensagem;
                                erroPagamento = true;
                                break; // Cancela, não grava no banco
                            }
                        }
                    }
                }

                if (!erroPagamento) {
                    enviarJson(formas_pagamento);
                }
            });

            // pega o valor total pago 
            function calcularTotalPago() {
                const inputs = document.querySelectorAll('.forma');
                let totalPagoPag = 0;

                inputs.forEach(input => {
                    const valor = parseFloat(input.value.replace(',', '.')) || 0;
                    totalPagoPag += valor;
                });

                return totalPagoPag;
            }

            // APÓS CONFIRMAR PAGAMENTO, MONTA JSON E ENVIA
            function enviarJson(formas_pagamento){
                let dadosVenda = {
                    valor_total: <?= $totalGeral ?>,
                    total_pago: calcularTotalPago(),
                    total_itens: <?= $totalItens ?>,
                    id_cliente: document.getElementById('id_cliente_hidden').value || null,
                    id_funcionario: <?= $_SESSION['ID_Funcionario'] ?> || null,
                    desconto: 0.00,
                    formas_pagamento: formas_pagamento
                };

                fetch('../../Dev/Exec/finalizarvenda_pdv.php', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(dadosVenda)
                })
                .then(response => response.json())
                .then(data => {
                    if(data.sucesso){
                        totalPago = 0;
                        formasSelecionadas = [];
                        formas_pagamento = [];
                        console.log('Venda finalizada:', data);
                        mostrarToast('Venda finalizada com sucesso!', 'success', 'Sucesso');
                        window.open(`cupomNfiscal.php?ID_Venda=${data.id_venda}`, '_blank');
                        location.reload();
                    }
                    else { 
                        console.error('Erro ao finalizar:', data);
                        mostrarToast('Erro ao finalizar venda: ' + (data.erro || 'Desconhecido'), 'warning', 'Atenção');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    mostrarToast('Erro ao finalizar venda!', 'danger', 'Erro');
                });
            }

            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------
            
            // SCRIPT MODAL PAGAMENTOS
            const valorTotal = parseFloat("<?= $totalGeral ?>");
            const popup = new bootstrap.Modal(document.getElementById('popupPagamento'));

            function abrirPopup() {
                popup.show();
            }

            // Função para calcular o troco automaticamente
            function calcularTroco() {
                const totalPagoAtual = calcularTotalPago();
                const troco = Math.max(totalPagoAtual - valorTotal, 0);
                document.getElementById('troco').innerText = "Troco: R$ " + troco.toFixed(2).replace('.', ',');
            }

            // Atualiza troco enquanto digita valor
            document.querySelectorAll('.forma').forEach(input => {
                input.addEventListener('input', function() {
                    calcularTroco();
                });
            });

            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------

            // corrige valores inteiro (10) para números flutuantes (10,00)
            document.querySelectorAll('.forma').forEach(input => {
                input.addEventListener('blur', function () {
                    let valorTexto = this.value.trim();

                    // Corrige vírgulas e remove caracteres inválidos
                    let valorNumerico = parseFloat(valorTexto.replace(',', '.').replace(/[^\d.]/g, ''));

                    if (!isNaN(valorNumerico))
                        this.value = valorNumerico.toFixed(2).replace('.', ',');
                    else
                        this.value = "";
                });
            });

            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------

            function gerenciarAtalhos(e) {
                const isModalOpen = document.body.classList.contains('modal-open');
                const isTyping = document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA';

                // Atalhos de pagamento (1, 2, 3, 4) - só funcionam com modal fechado e se não estiver digitando em algum campo
                const teclasPagamento = {'1': 1, '2': 2, '3': 3, '4': 4};
                if (teclasPagamento[e.key]) {
                        if (!isModalOpen && !isTyping) {
                        e.preventDefault();
                        selecionarForma(teclasPagamento[e.key]);
                        return;
                    }
                }

                switch (e.key) {
                    case 'F2': // Foca no campo de código de barras
                        e.preventDefault();
                        document.getElementById('codigo').focus();
                        break;

                    case 'F3': // Abre modal de consulta de preços
                        e.preventDefault();
                        document.getElementById('btnConsultarPreco').click();
                        break;

                    case 'F4': // Foca no campo para buscar cliente
                        e.preventDefault();
                        document.getElementById('busca_cliente_cpf').focus();
                        break;
                    
                    case 'F8': // cancelamento de venda
                        e.preventDefault();
                        document.getElementById('formCancelarVenda').submit();
                        break;

                    case 'F9': // Abre modal de funcionalidades
                        e.preventDefault();
                        document.getElementById('btnFuncionalidades').click(); 
                        break;
                    
                    case 'F10': // fechamento de caixa
                        e.preventDefault();
                        document.getElementById('formFecharCaixa').submit();
                        break;
                    
                    case 'Escape':
                        break;
                }
            }

            document.addEventListener('keydown', gerenciarAtalhos);

            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------

            // BUSCA DE CLIENTE POR CPF/CNPJ
            document.addEventListener('DOMContentLoaded', function() {
                const campoBuscaCliente = document.getElementById('busca_cliente_cpf');
                const campoIdCliente = document.getElementById('id_cliente_consulta');
                const caixaSugestoes = document.getElementById('caixaSugestoes');
                const listaSugestoes = document.getElementById('listaSugestoes');
                const btnFecharSugestoes = document.getElementById('fecharSugestoes');

                btnFecharSugestoes.addEventListener('click', function() {
                    caixaSugestoes.style.display = 'none';
                });

                campoBuscaCliente.addEventListener('change', function() { 
                    const documento = this.value.trim();
                    caixaSugestoes.style.display = 'none'; 
                    listaSugestoes.innerHTML = '';

                    if (documento === '') {
                        campoIdCliente.value = '';
                        return;
                    }

                    fetch(`../../Dev/Exec/busca_cliente.php?documento=${encodeURIComponent(documento)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.sucesso) {
                                campoIdCliente.value = data.id_cliente;
                                document.getElementById('id_cliente_hidden').value = data.id_cliente;
                                campoBuscaCliente.value = data.nome_cliente;
                                buscarSugestoes(data.id_cliente);
                            }
                            else {
                                campoIdCliente.value = '';
                                document.getElementById('id_cliente_hidden').value = '';
                                campoBuscaCliente.value = '';
                                mostrarToast('Cliente não encontrado!', 'warning');
                            }
                        })
                        .catch(err => {
                            console.error('Erro ao buscar cliente:', err);
                            mostrarToast('Erro de comunicação ao buscar cliente.', 'danger');
                        });
                });

                function buscarSugestoes(idCliente) {
                    fetch(`../../Dev/Exec/busca_sugestoes.php?id_cliente=${idCliente}`)
                        .then(response => response.json())
                        .then(sugestoes => {
                            if (sugestoes.length > 0) {
                                sugestoes.forEach(sugestao => {
                                    const item = document.createElement('a');
                                    item.href = '#';
                                    item.classList.add('list-group-item', 'list-group-item-action', 'list-group-item-info');
                                    item.textContent = sugestao.Nome;
                                    
                                    // Adiciona evento de clique para adicionar o produto ao carrinho
                                    item.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        document.getElementById('codigo').value = sugestao.EAN_GTIN;
                                        // Dispara o evento 'change' para acionar a busca e adição do produto
                                        document.getElementById('codigo').dispatchEvent(new Event('change'));
                                    });
                                    listaSugestoes.appendChild(item);
                                });
                                caixaSugestoes.style.display = 'block'; // Mostra a caixa de sugestões
                            }
                        });
                }
            });
            
            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------

            // LÓGICA DA CONSULTA DE PREÇO
            const modalConsulta = document.getElementById('modalConsultaPreco');
            const inputConsulta = document.getElementById('inputConsulta');
            const resultadoConsultaDiv = document.getElementById('resultadoConsulta');

            // Limpa o resultado sempre que o modal for fechado
            modalConsulta.addEventListener('hidden.bs.modal', function () {
                inputConsulta.value = '';
                resultadoConsultaDiv.innerHTML = '';
            });

            modalConsulta.addEventListener('shown.bs.modal', function() {
                inputConsulta.focus();
            })

            inputConsulta.addEventListener('input', function() {
                const termo = this.value.trim();

                if (termo.length < 2) {
                    resultadoConsultaDiv.innerHTML = '<p class="text-muted">Aguardando digitação...</p>';
                    return;
                }

                fetch('../../Dev/Exec/busca_produto.php?codigo=' + encodeURIComponent(termo))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const precoFormatado = parseFloat(data.preco).toFixed(2).replace('.', ',');
                            const fotoUrl = `../../Dev/Imagens/imgProdutos/${data.foto || 'sem-imagem.jpg'}`;

                            resultadoConsultaDiv.innerHTML = `
                                <img src="${fotoUrl}" class="img-thumbnail mb-2" style="max-height: 100px;">
                                <h5>${data.nome}</h5>
                                <p class="fs-3 text-success fw-bold">R$ ${precoFormatado}</p>
                            `;
                        } 
                        else 
                            resultadoConsultaDiv.innerHTML = '<p class="text-danger mt-4">Produto não encontrado.</p>';
                    })
                    .catch(err => {
                        console.error('Erro na consulta de preço:', err);
                        resultadoConsultaDiv.innerHTML = '<p class="text-danger mt-4">Erro ao buscar o produto.</p>';
                    });
            });

            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------
            
            // SCRIPT MODAL FUNCIONALIDADES
            let tipoSelecionado = null;

            function selecionarFuncionalidade(tipo) {
                tipoSelecionado = tipo;

                let titulo = tipo === 'entrada' ? 'Entrada de Dinheiro' : 'Sangria (Saída de Dinheiro)';
                let placeholder = tipo === 'entrada' ? 'Valor da Entrada' : 'Valor da Sangria';

                document.getElementById('funcionalidadesConteudo').innerHTML = `
                    <div class="mb-3 row">
                        <label class="col-sm-4 col-form-label">${titulo}</label>
                        <div class="col-sm-8 d-flex align-items-center">
                            <input type="number" step="0.01" min="0" class="form-control" id="valorMovimentacao" placeholder="R$ ${placeholder}">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-4 col-form-label">Descrição</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" id="descricaoMovimentacao" placeholder="Ex: Reforço de caixa, sangria para cofre, etc">
                        </div>
                    </div>
                `;
                document.getElementById('funcionalidadesFooter').style.display = 'block';
            }

            function registrarMovimentacao() {
                let valor = parseFloat(document.getElementById('valorMovimentacao').value);
                let descricao = document.getElementById('descricaoMovimentacao').value.trim();

                if (isNaN(valor) || valor <= 0) {
                    document.getElementById('erroFuncionalidade').textContent = "Informe um valor válido.";
                    return;
                }

                if (descricao === "") {
                    document.getElementById('erroFuncionalidade').textContent = "Informe uma descrição para a movimentação.";
                    return;
                }

                // Envia os dados via POST para o PHP
                fetch('../../Dev/Exec/registrar_movimentacao.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `tipo=${tipoSelecionado}&valor=${valor}&descricao=${encodeURIComponent(descricao)}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === 'ok') {
                        mostrarToast('Movimentação registrada com sucesso!', 'success', 'Sucesso');
                        location.reload();
                    } 
                    else {
                        document.getElementById('erroFuncionalidade').textContent = data;
                    }
                });
            }

            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------
            
            // confirmação senha de gerente
            const modalGerente = new bootstrap.Modal(document.getElementById('modalGerente'));

            function gerenciarItem(index, acao) {
                document.getElementById('itemIndex').value = index;
                document.getElementById('acaoGerente').value = acao;

                document.getElementById('senhaGerente').value = '';
                document.getElementById('erroSenha').textContent = '';

                modalGerente.show();
            }

            function validarSenha() {
                const senha = document.getElementById('senhaGerente').value;
                const index = document.getElementById('itemIndex').value;
                const acao = document.getElementById('acaoGerente').value;

                if (senha === '') {
                    document.getElementById('erroSenha').textContent = 'Por favor, insira a senha.';
                    return;
                }

                fetch('../../Dev/Exec/gerenciar_carrinho.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `acao=${acao}&index=${index}&senha=${encodeURIComponent(senha)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso)
                        location.reload();
                    else
                        document.getElementById('erroSenha').textContent = data.erro || 'Ocorreu um erro.';
                });
            }

            // -------------------------------------------------------------------------
            // -------------------------------------------------------------------------
            
            // Lógica para ativar o Toast
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