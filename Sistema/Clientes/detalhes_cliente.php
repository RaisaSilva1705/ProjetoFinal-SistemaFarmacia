<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CLIENTES_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$id_cliente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_cliente) {
    $_SESSION['msg'] = ['texto' => 'ID de cliente inválido.', 'tipo' => 'warning'];
    header("Location: clientes.php");
    exit();
}

$stmtCliente = $conn->prepare("SELECT * FROM CLIENTES WHERE ID_Cliente = ?");
$stmtCliente->bind_param("i", $id_cliente);
$stmtCliente->execute();
$resultCliente = $stmtCliente->get_result();
if ($resultCliente->num_rows === 0) {
    $_SESSION['msg'] = ['texto' => 'Cliente não encontrado.', 'tipo' => 'danger'];
    header("Location: clientes.php");
    exit();
}
$cliente = $resultCliente->fetch_assoc();

$stmtDocumentos = $conn->prepare("SELECT Tipo, Numero FROM CLIENTES_DOCUMENTOS WHERE ID_Cliente = ? ORDER BY Tipo");
$stmtDocumentos->bind_param("i", $id_cliente);
$stmtDocumentos->execute();
$documentos = $stmtDocumentos->get_result();

$stmtEnderecos = $conn->prepare("SELECT * FROM CLI_ENDERECOS WHERE ID_Cliente = ?");
$stmtEnderecos->bind_param("i", $id_cliente);
$stmtEnderecos->execute();
$enderecos = $stmtEnderecos->get_result();

$historico_compras = null;
if (isset($_SESSION['Cargo']) && ($_SESSION['Cargo'] == 'Gerente' || $_SESSION['Cargo'] == 'Administrador')) {
    $stmtHistorico = $conn->prepare("SELECT V.ID_Venda, V.DataHora_Venda, P.Nome AS Nome_Produto, IV.Quantidade, IV.Valor_Total FROM VENDAS AS V JOIN ITENS_VENDA AS IV ON V.ID_Venda = IV.ID_Venda JOIN PRODUTOS AS P ON IV.ID_Produto = P.ID_Produto WHERE V.ID_Cliente = ? ORDER BY V.DataHora_Venda DESC LIMIT 20");
    $stmtHistorico->bind_param("i", $id_cliente);
    $stmtHistorico->execute();
    $historico_compras = $stmtHistorico->get_result();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Detalhes de Cliente: <?= htmlspecialchars($cliente['Nome']) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php';?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Detalhes do Cliente</h3>
                </div>
                
                <div class="container p-5">
                    <a href="clientes.php" class="btn btn-outline-secondary mb-4"><i class="bi bi-arrow-left-circle"></i> Voltar para a Lista</a>

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h4 class="m-0"><i class="bi bi-person-circle text-primary"></i> <?= htmlspecialchars($cliente['Nome']) ?></h4>
                                    <a href="editar_cliente.php?id=<?= $cliente['ID_Cliente'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                </div>
                                <div class="card-body">
                                    <p><strong><i class="bi bi-person-vcard-fill me-2"></i>Gênero:</strong> <?= htmlspecialchars($cliente['Genero'] ?? 'Não informado') ?></p>
                                    <p><strong><i class="bi bi-calendar-heart-fill me-2"></i>Nascimento:</strong> <?= $cliente['Data_Nascimento'] ? date('d/m/Y', strtotime($cliente['Data_Nascimento'])) : 'Não informado' ?></p>
                                    <hr>
                                    <p><strong><i class="bi bi-telephone-fill me-2"></i>Contato:</strong> <?= htmlspecialchars($cliente['Tel']) ?></p>
                                    <p><strong><i class="bi bi-envelope-fill me-2"></i>Email:</strong> <?= htmlspecialchars($cliente['Email']) ?></p>
                                    <p><strong>Status:</strong> <span class="badge <?= $cliente['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= htmlspecialchars($cliente['Status']) ?></span></p>
                                    <p><strong>Crédito em Loja:</strong> <span class="badge bg-info">R$ <?= number_format($cliente['Saldo_Credito'], 2, ',', '.') ?></span></p>
                                </div>
                            </div>

                            <div class="card shadow-sm">
                                <div class="card-header"><h5 class="m-0">Documentos</h5></div>
                                <ul class="list-group list-group-flush">
                                    <?php if ($documentos->num_rows > 0): ?>
                                        <?php while($doc = $documentos->fetch_assoc()): ?>
                                            <li class="list-group-item"><strong><?= htmlspecialchars($doc['Tipo']) ?>:</strong> <?= htmlspecialchars($doc['Numero']) ?></li>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <li class="list-group-item">Nenhum documento cadastrado.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="m-0">Endereços</h5>
                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEndereco"><i class="bi bi-plus-circle"></i> Novo Endereço</button>
                                </div>
                                <div class="card-body">
                                    <?php if ($enderecos->num_rows > 0): ?>
                                        <?php while($end = $enderecos->fetch_assoc()): ?>
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <p class="mb-1"><strong><?= htmlspecialchars("{$end['Endereco']}, {$end['End_Numero']} - {$end['Bairro']}") ?></strong></p>
                                                    <p class="text-muted small"><?= htmlspecialchars("{$end['Cidade']}/{$end['Estado']} - CEP: {$end['CEP']}") ?></p>
                                                </div>
                                                <div class="ms-3 d-flex gap-2">
                                                    <button type="button" class="btn btn-warning btn-sm" title="Editar Endereço"
                                                            onclick="abrirModalEdicaoEndereco(this)"
                                                            data-id="<?= $end['ID_Endereco_Cli'] ?>"
                                                            data-cep="<?= htmlspecialchars($end['CEP']) ?>"
                                                            data-endereco="<?= htmlspecialchars($end['Endereco']) ?>"
                                                            data-numero="<?= htmlspecialchars($end['End_Numero']) ?>"
                                                            data-complemento="<?= htmlspecialchars($end['Complemento']) ?>"
                                                            data-bairro="<?= htmlspecialchars($end['Bairro']) ?>"
                                                            data-cidade="<?= htmlspecialchars($end['Cidade']) ?>"
                                                            data-estado="<?= htmlspecialchars($end['Estado']) ?>"
                                                            data-obs="<?= htmlspecialchars($end['OBS']) ?>">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" title="Remover Endereço" onclick="abrirModalRemocao(<?= $end['ID_Endereco_Cli'] ?>)">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </div>
                                                </div>
                                            <hr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p>Nenhum endereço cadastrado.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($historico_compras && $historico_compras->num_rows > 0): ?>
                            <div class="card shadow-sm">
                                <div class="card-header"><h5 class="m-0">Histórico de Compras Recentes</h5></div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped table-hover mb-0">
                                        <tbody>
                                            <?php while($compra = $historico_compras->fetch_assoc()): ?>
                                                <tr>
                                                    <td><small><?= date('d/m/Y', strtotime($compra['DataHora_Venda'])) ?></small></td>
                                                    <td><?= htmlspecialchars($compra['Nome_Produto']) ?></td>
                                                    <td class="text-end">R$ <?= number_format($compra['Valor_Total'], 2, ',', '.') ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php';?>
        </div>

        <div class="modal fade" id="modalEndereco" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEnderecoLabel">Adicionar Endereço</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formEndereco">
                            <input type="hidden" name="id_endereco_cli" id="id_endereco_cli">
                            <input type="hidden" name="id_cliente" value="<?= $cliente['ID_Cliente'] ?>">

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="cep" class="form-label">CEP</label>
                                    <input type="text" id="cep" name="cep" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-9 mb-3">
                                    <label for="endereco" class="form-label">Endereço</label>
                                    <input type="text" id="endereco" name="endereco" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="numero" class="form-label">Número</label>
                                    <input type="text" id="numero" name="numero" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="complemento" class="form-label">Complemento</label>
                                    <input type="text" id="complemento" name="complemento" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="bairro" class="form-label">Bairro</label>
                                    <input type="text" id="bairro" name="bairro" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cidade" class="form-label">Cidade</label>
                                    <input type="text" id="cidade" name="cidade" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="estado" class="form-label">Estado</label>
                                    <input type="text" id="estado" name="estado" class="form-control" required maxlength="2">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="obs" class="form-label">Observações</label>
                                <textarea name="obs" id="obs" class="form-control" rows="2"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" id="btnSalvarEndereco">Salvar Endereço</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalConfirmarRemocao" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirmar Remoção</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja remover este endereço? Esta ação não pode ser desfeita.</p>
                    </div>
                    <div class="modal-footer">
                        <form id="formRemoverEndereco" method="POST">
                            <input type="hidden" name="action" value="remover">
                            <input type="hidden" name="id_cliente" value="<?= $cliente['ID_Cliente'] ?>">
                            <input type="hidden" name="id_endereco_cli" id="id_endereco_remover">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">Sim, Remover</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-eye-fill"></i> Detalhes do Cliente</h4>
            <hr>
            <p>Esta tela oferece uma visão completa de 360° do cliente, reunindo todas as suas informações cadastrais, de contato e seu histórico com a farmácia.</p>

            <h6><i class="bi bi-person-circle"></i> Painel de Informações</h6>
            <p>O primeiro card à esquerda resume os dados principais do cliente, como contato, status e o saldo de <strong>Crédito em Loja</strong> disponível. Você pode clicar no botão de editar <i class="bi bi-pencil-fill text-warning"></i> para ir diretamente à tela de edição.</p>

            <h6><i class="bi bi-file-earmark-text-fill"></i> Documentos</h6>
            <p>Lista todos os documentos cadastrados para este cliente.</p>
            
            <h6><i class="bi bi-house-fill"></i> Endereços</h6>
            <p>Exibe todos os endereços de entrega cadastrados. Para adicionar um novo endereço:</p>
            <ol>
                <li>Clique em <strong>"Novo Endereço"</strong>.</li>
                <li>Na janela que se abre, comece digitando o <strong>CEP</strong>. O sistema buscará automaticamente o restante do endereço.</li>
                <li>Complete com o <strong>Número</strong> e o <strong>Complemento</strong>, se houver.</li>
                <li>Clique em <strong>"Salvar Endereço"</strong>.</li>
            </ol>

            <h6><i class="bi bi-receipt"></i> Histórico de Compras Recentes</h6>
            <p>Este painel (visível apenas para Gerentes e Administradores) exibe um resumo das últimas compras realizadas pelo cliente, permitindo um atendimento mais personalizado e a identificação de seus produtos de interesse.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php';?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modalEndereco = new bootstrap.Modal(document.getElementById('modalEndereco'));
                const modalConfirmarRemocao = new bootstrap.Modal(document.getElementById('modalConfirmarRemocao'));
                const formEndereco = document.getElementById('formEndereco');
                const btnSalvar = document.getElementById('btnSalvarEndereco');
                const campoCep = document.getElementById('cep');

                document.getElementById('modalEndereco').addEventListener('hidden.bs.modal', function () {
                    formEndereco.reset(); 
                    document.getElementById('id_endereco_cli').value = ''; 
                    document.getElementById('modalEnderecoLabel').textContent = 'Adicionar Endereço'; 
                });

                campoCep.addEventListener('input', function() {
                    let cepValue = this.value.replace(/\D/g, '');
                    if (cepValue.length === 8) {
                        fetch(`https://viacep.com.br/ws/${cepValue}/json/`)
                            .then(response => response.json())
                            .then(data => {
                                if (!data.erro) {
                                    document.getElementById('endereco').value = data.logradouro;
                                    document.getElementById('bairro').value = data.bairro;
                                    document.getElementById('cidade').value = data.localidade;
                                    document.getElementById('estado').value = data.uf;
                                    document.getElementById('numero').focus();
                                }
                            });
                    }
                });

                btnSalvar.addEventListener('click', function() {
                    const formData = new FormData(formEndereco);

                    fetch('gerenciar_endereco.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.sucesso) {
                            modalEndereco.hide();
                            location.reload();
                        } 
                        else 
                            mostrarToast('Erro: ' + data.erro, 'warning');
                    })
                    .catch(error => {
                        console.error('Erro no fetch:', error);
                        mostrarToast('Ocorreu um erro de comunicação. Tente novamente.', 'danger');
                    });
                });

                window.abrirModalEdicaoEndereco = function(button) {
                    const data = button.dataset;
                    document.getElementById('modalEnderecoLabel').textContent = 'Editar Endereço';

                    document.getElementById('id_endereco_cli').value = data.id;
                    document.getElementById('cep').value = data.cep;
                    document.getElementById('endereco').value = data.endereco;
                    document.getElementById('numero').value = data.numero;
                    document.getElementById('complemento').value = data.complemento;
                    document.getElementById('bairro').value = data.bairro;
                    document.getElementById('cidade').value = data.cidade;
                    document.getElementById('estado').value = data.estado;
                    document.getElementById('obs').value = data.obs;

                    modalEndereco.show();
                }

                window.abrirModalRemocao = function(idEndereco) {
                    document.getElementById('id_endereco_remover').value = idEndereco;
                    modalConfirmarRemocao.show();
                }

                document.getElementById('formRemoverEndereco').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('gerenciar_endereco.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.sucesso){
                            location.reload();
                            mostrarToast('Endereço removido com sucesso!', 'success');
                        }
                        else 
                            mostrarToast('Erro: ' + (data.erro || 'Não foi possível remover o endereço.'), 'danger');
                    });
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