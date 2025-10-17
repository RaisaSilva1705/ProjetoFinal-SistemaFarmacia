<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CONFIGURACOES_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$categorias = $conn->query("SELECT * FROM CATEGORIAS ORDER BY Categoria")->fetch_all(MYSQLI_ASSOC);
$unidades = $conn->query("SELECT * FROM UNIDADES ORDER BY Unidade")->fetch_all(MYSQLI_ASSOC);
$cat_meds = $conn->query("SELECT * FROM CATEGORIAS_MEDICAMENTOS ORDER BY Categoria_Med")->fetch_all(MYSQLI_ASSOC);
$tarjas = $conn->query("SELECT * FROM TARJAS_MEDICAMENTOS ORDER BY Tarja")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Configurações de Produtos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>
        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Configurações de Produtos</h3>
                </div>
                <div class="container p-5">
                    <h2 class="mb-4">Configurações de Produtos</h2>

                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#categorias-pane" type="button">Categorias de Produtos</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#unidades-pane" type="button">Unidades de Medida</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#cat-meds-pane" type="button">Categorias de Medicamentos</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tarjas-pane" type="button">Tarjas</button></li>
                    </ul>

                    <div class="tab-content card card-body">
                        <div class="tab-pane fade show active" id="categorias-pane">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="m-0">Categorias de Produtos</h5>
                                <button type="button" class="btn btn-success btn-sm" onclick="abrirModal('categoria')"><i class="bi bi-plus-circle"></i> Adicionar</button>
                            </div>
                            <table class="table table-sm table-striped">
                                <?php foreach ($categorias as $item) echo "<tr><td>".htmlspecialchars($item['Categoria'])."</td><td class='text-end'><button class='btn btn-warning btn-sm' onclick='abrirModal(\"categoria\", ".json_encode($item).")'><i class='bi bi-pencil-fill'></i></button></td></tr>"; ?>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="unidades-pane">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="m-0">Unidades de Medida</h5>
                                <button type="button" class="btn btn-success btn-sm" onclick="abrirModal('unidade')"><i class="bi bi-plus-circle"></i> Adicionar</button>
                            </div>
                            <table class="table table-sm table-striped">
                                <?php foreach ($unidades as $item) echo "<tr><td>".htmlspecialchars($item['Unidade'])." (".htmlspecialchars($item['Abreviacao']).")</td><td class='text-end'><button class='btn btn-warning btn-sm' onclick='abrirModal(\"unidade\", ".json_encode($item).")'><i class='bi bi-pencil-fill'></i></button></td></tr>"; ?>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="cat-meds-pane">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="m-0">Categorias de Medicamentos</h5>
                                <button type="button" class="btn btn-success btn-sm" onclick="abrirModal('cat_med')"><i class="bi bi-plus-circle"></i> Adicionar</button>
                            </div>
                            <table class="table table-sm table-striped">
                                <?php foreach ($cat_meds as $item) echo "<tr><td>".htmlspecialchars($item['Categoria_Med'])."</td><td class='text-end'><button class='btn btn-warning btn-sm' onclick='abrirModal(\"cat_med\", ".json_encode($item).")'><i class='bi bi-pencil-fill'></i></button></td></tr>"; ?>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="tarjas-pane">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="m-0">Tarjas de Medicamentos</h5>
                                <button type="button" class="btn btn-success btn-sm" onclick="abrirModal('tarja')"><i class="bi bi-plus-circle"></i> Adicionar</button>
                            </div>
                            <table class="table table-sm table-striped">
                                <?php foreach ($tarjas as $item) echo "<tr><td>".htmlspecialchars($item['Tarja'])."</td><td class='text-end'><button class='btn btn-warning btn-sm' onclick='abrirModal(\"tarja\", ".json_encode($item).")'><i class='bi bi-pencil-fill'></i></button></td></tr>"; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <div class="modal fade" id="configModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="processa_configuracoes_produtos.php" method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="configModalLabel">Adicionar Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="tipo_config" id="tipo_config">
                            <input type="hidden" name="id_config" id="id_config">
                            <div class="mb-3" id="campo1-container">
                                <label for="valor1" id="label1" class="form-label">Nome</label>
                                <input type="text" name="valor1" id="valor1" class="form-control" required>
                            </div>
                            <div class="mb-3" id="campo2-container" style="display: none;">
                                <label for="valor2" id="label2" class="form-label">Abreviação</label>
                                <input type="text" name="valor2" id="valor2" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            const configModal = new bootstrap.Modal(document.getElementById('configModal'));
            const modalTitle = document.getElementById('configModalLabel');
            const tipoConfigInput = document.getElementById('tipo_config');
            const idConfigInput = document.getElementById('id_config');
            const campo1Container = document.getElementById('campo1-container');
            const campo2Container = document.getElementById('campo2-container');
            const valor1Input = document.getElementById('valor1');
            const label1 = document.getElementById('label1');
            const valor2Input = document.getElementById('valor2');
            const label2 = document.getElementById('label2');

            function abrirModal(tipo, dados = null) {
                tipoConfigInput.value = tipo;
                idConfigInput.value = dados ? (dados.ID_Categoria || dados.ID_Unidade || dados.ID_CategoriaMed || dados.ID_Tarja) : '';
                
                campo2Container.style.display = 'none'; // Esconde por padrão
                valor2Input.required = false;

                switch(tipo) {
                    case 'categoria':
                        modalTitle.textContent = dados ? 'Editar Categoria' : 'Nova Categoria de Produto';
                        label1.textContent = 'Nome da Categoria';
                        valor1Input.value = dados ? dados.Categoria : '';
                        break;
                    case 'unidade':
                        modalTitle.textContent = dados ? 'Editar Unidade' : 'Nova Unidade de Medida';
                        label1.textContent = 'Nome da Unidade';
                        valor1Input.value = dados ? dados.Unidade : '';
                        label2.textContent = 'Abreviação';
                        valor2Input.value = dados ? dados.Abreviacao : '';
                        campo2Container.style.display = 'block';
                        valor2Input.required = true;
                        break;
                    case 'cat_med':
                        modalTitle.textContent = dados ? 'Editar Categoria' : 'Nova Categoria de Medicamento';
                        label1.textContent = 'Nome da Categoria';
                        valor1Input.value = dados ? dados.Categoria_Med : '';
                        break;
                    case 'tarja':
                        modalTitle.textContent = dados ? 'Editar Tarja' : 'Nova Tarja de Medicamento';
                        label1.textContent = 'Nome da Tarja';
                        valor1Input.value = dados ? dados.Tarja : '';
                        break;
                }
                configModal.show();
            }

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
