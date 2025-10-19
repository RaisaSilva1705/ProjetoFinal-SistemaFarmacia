<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../dev/Exec/config.php"; 
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'FINANCEIRO_VER');
include DEV_PATH . "Exec/validar_acesso.php"; 

$stmt = $conn->prepare("SELECT ID_Categoria_Despesa, Nome_Categoria FROM DESPESAS_CATEGORIAS ORDER BY Nome_Categoria ASC");
$stmt->execute();
$categorias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Categorias de Despesas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Gestão Financeira</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Categorias de Despesas</h2>
                        <div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoriaModal" id="btnNovaCategoria">
                                <i class="bi bi-plus-circle"></i> Nova Categoria
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered m-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome da Categoria</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($categorias) > 0): ?>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <tr>
                                            <td><?php echo $categoria['ID_Categoria_Despesa']; ?></td>
                                            <td><?php echo htmlspecialchars($categoria['Nome_Categoria']); ?></td>
                                            <td class="text-center">
                                                <a href="historico_categoria.php?id=<?php echo $categoria['ID_Categoria_Despesa']; ?>" 
                                                class="btn btn-sm btn-info" title="Ver Histórico e Análise">
                                                    <i class="bi bi-graph-up"></i>
                                                </a>
                                                <button class="btn btn-sm btn-warning btn-editar" title="Editar"
                                                        data-id="<?php echo $categoria['ID_Categoria_Despesa']; ?>" 
                                                        data-nome="<?php echo htmlspecialchars($categoria['Nome_Categoria']); ?>"
                                                        data-bs-toggle="modal" data-bs-target="#categoriaModal">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#confirmDeleteModal" 
                                                        data-url="excluir_categoria_despesa.php?id=<?php echo $categoria['ID_Categoria_Despesa']; ?>"
                                                        title="Excluir Categoria">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Nenhuma categoria cadastrada.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <!-- CRUD -->
        <div class="modal fade" id="categoriaModal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel">Adicionar Nova Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="categoriaForm" action="processa_categorias.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id_categoria" id="id_categoria">
                            <div class="mb-3">
                                <label for="nome_categoria" class="form-label">Nome da Categoria</label>
                                <input type="text" class="form-control" id="nome_categoria" name="nome_categoria" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="modalLabelDelete" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="modalLabelDelete">Confirmação de Exclusão</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Atenção! Excluir uma categoria é uma ação irreversível.
                        <br><br>
                        <strong>Tem certeza que deseja continuar?</strong>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <a href="#" id="btnConfirmDelete" class="btn btn-danger">Sim, Excluir</a>
                    </div>
                </div>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-tags-fill"></i> Categorias de Despesas</h4>
            <hr>
            <p>Organizar suas despesas em categorias é a chave para um bom controle financeiro. Esta tela permite que você crie e gerencie as categorias que serão usadas para classificar todos os gastos da sua farmácia (ex: Aluguel, Fornecedores, Marketing, Salários, Impostos).</p>

            <h6><i class="bi bi-plus-circle-fill"></i> Cadastrar e Editar</h6>
            <ul>
                <li><strong>Nova Categoria:</strong> Clique no botão "Nova Categoria", digite o nome e salve.</li>
                <li><strong>Editar:</strong> Clique no ícone de lápis <i class="bi bi-pencil text-warning"></i> para renomear uma categoria existente.</li>
            </ul>

            <h6><i class="bi bi-graph-up"></i> Ver Histórico e Análise</h6>
            <p>Clique no ícone de gráfico <i class="bi bi-graph-up text-success"></i> para acessar uma tela de análise detalhada daquela categoria. Nela, você poderá ver o total gasto, a média de gastos e o histórico de todos os pagamentos classificados com aquela categoria.</p>

            <h6><i class="bi bi-trash-fill"></i> Excluir Categoria</h6>
            <p>Clique no ícone de lixeira <i class="bi bi-trash text-danger"></i> para remover uma categoria. O sistema perguntará por uma confirmação.</p>
            <p class="alert alert-warning"><strong>Atenção:</strong> A exclusão de uma categoria é uma ação poderosa. Certifique-se de que ela não está mais em uso antes de removê-la.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalLabel = document.getElementById('modalLabel');
                const categoriaForm = document.getElementById('categoriaForm');
                const idCategoriaInput = document.getElementById('id_categoria');
                const confirmDeleteModal = document.getElementById('confirmDeleteModal');

                document.getElementById('btnNovaCategoria').addEventListener('click', function () {
                    modalLabel.textContent = 'Adicionar Nova Categoria';
                    idCategoriaInput.value = ''; 
                    categoriaForm.reset();
                });

                document.querySelectorAll('.btn-editar').forEach(button => {
                    button.addEventListener('click', function () {
                        const id = this.getAttribute('data-id');
                        const nome = this.getAttribute('data-nome');

                        modalLabel.textContent = 'Editar Categoria';
                        idCategoriaInput.value = id;
                        document.getElementById('nome_categoria').value = nome;
                    });
                });

                if (confirmDeleteModal) {
                    confirmDeleteModal.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;
                        const url = button.getAttribute('data-url');
                        const confirmBtn = confirmDeleteModal.querySelector('#btnConfirmDelete');         
                        confirmBtn.setAttribute('href', url);
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