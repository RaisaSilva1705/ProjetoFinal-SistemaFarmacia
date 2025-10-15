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

$stmtEnderecos = $conn->prepare("SELECT * FROM CLI_ENDERECOS WHERE ID_Cliente = ? ORDER BY ID_Endereco_Cli");
$stmtEnderecos->bind_param("i", $id_cliente);
$stmtEnderecos->execute();
$enderecos = $stmtEnderecos->get_result();

$historico_compras = []; 
// Verifica se o cargo do usuário na sessão é Gerente ou Administrador
if (isset($_SESSION['Cargo']) && ($_SESSION['Cargo'] == 'Gerente' || $_SESSION['Cargo'] == 'Administrador')) {
    $stmtHistorico = $conn->prepare("
        SELECT
            V.ID_Venda,
            V.DataHora_Venda,
            P.Nome AS Nome_Produto,
            IV.Quantidade,
            IV.Valor_Total
        FROM VENDAS AS V
        JOIN ITENS_VENDA AS IV ON V.ID_Venda = IV.ID_Venda
        JOIN PRODUTOS AS P ON IV.ID_Produto = P.ID_Produto
        WHERE V.ID_Cliente = ?
        ORDER BY V.DataHora_Venda DESC
        LIMIT 20 -- Limita aos últimos 20 itens para não sobrecarregar a página
    ");
    $stmtHistorico->bind_param("i", $id_cliente);
    $stmtHistorico->execute();
    $historico_compras = $stmtHistorico->get_result();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detalhes de Cliente</title>
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
                    <h3>Detalhes do Cliente</h3>
                </div>
            
                <div class="container p-5">
                    <a href="fornecedores.php" class="btn btn-outline-secondary mb-4">
                        <i class="bi bi-arrow-left"></i> Voltar para a Lista
                    </a>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="m-0"><?= htmlspecialchars($cliente['Nome']) ?></h4>
                            <a href="editar_cliente.php?id=<?= $cliente['ID_Cliente'] ?>" class="btn btn-warning btn-sm">Editar Dados Principais</a>
                        </div>
                        <div class="card-body">
                            <p><strong><i class="bi bi-file-person-fill"></i> Documento:</strong> <?= htmlspecialchars($cliente['Documento']) ?> (<?= $cliente['Tipo'] ?>)</p>
                            <p><strong><i class="bi bi-telephone-fill me-2"></i> Contato:</strong> <?= htmlspecialchars($cliente['Tel']) ?></p>
                            <p><strong><i class="bi bi-envelope-fill me-2"></i> Email:</strong> <?= htmlspecialchars($cliente['Email']) ?></p>
                            <p>
                                <?php
                                $badge_class = $cliente['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger';
                                echo "<strong>Status:</strong> <span class='badge {$badge_class}'>" . htmlspecialchars($cliente['Status']) . "</span>";
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0">Endereços Cadastrados</h5>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEndereco">
                                Adicionar Novo Endereço
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <tbody>
                                        <?php if ($enderecos->num_rows > 0): ?>
                                            <?php while($end = $enderecos->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <strong>CEP:</strong> <?= htmlspecialchars($end['CEP']) ?><br>
                                                        <?= htmlspecialchars("{$end['Endereco']}, {$end['End_Numero']} - {$end['Bairro']}, {$end['Cidade']}/{$end['Estado']}") ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <button class="btn btn-outline-warning btn-sm">Editar</button>
                                                        <button class="btn btn-outline-danger btn-sm">Excluir</button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td>Nenhum endereço cadastrado.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['Cargo']) && ($_SESSION['Cargo'] == 'Gerente' || $_SESSION['Cargo'] == 'Administrador')): ?>
                    <div class="card shadow-sm mt-4">
                        <div class="card-header">
                            <h5 class="m-0">Histórico de Compras Recentes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Venda Nº</th>
                                            <th>Produto</th>
                                            <th class="text-center">Qtd.</th>
                                            <th class="text-end">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($historico_compras->num_rows > 0): ?>
                                            <?php while($compra = $historico_compras->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y H:i', strtotime($compra['DataHora_Venda'])) ?></td>
                                                    <td><?= $compra['ID_Venda'] ?></td>
                                                    <td><?= htmlspecialchars($compra['Nome_Produto']) ?></td>
                                                    <td class="text-center"><?= $compra['Quantidade'] ?></td>
                                                    <td class="text-end">R$ <?= number_format($compra['Valor_Total'], 2, ',', '.') ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center">Nenhum histórico de compras para este cliente.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
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
                const modalEndereco = new bootstrap.Modal(document.getElementById('modalEndereco'));
                const formEndereco = document.getElementById('formEndereco');
                const btnSalvar = document.getElementById('btnSalvarEndereco');
                const campoCep = document.getElementById('cep');

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