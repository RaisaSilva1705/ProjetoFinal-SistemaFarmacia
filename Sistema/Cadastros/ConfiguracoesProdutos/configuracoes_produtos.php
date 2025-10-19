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

                    <div class="mb-3">
                        <input type="text" id="filtro-geral" class="form-control" placeholder="Digite para filtrar a lista na aba atual...">
                    </div>

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
                                <button type="button" class="btn btn-primary btn-sm" onclick="abrirModal('categoria')"><i class="bi bi-plus-circle"></i> Adicionar</button>
                            </div>
                            <table class="table table-sm table-striped">
                                <?php foreach ($categorias as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['Categoria']) ?></td>
                                        <td><span class="badge <?= ($item['Status'] ?? 'Ativo') == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $item['Status'] ?? 'Ativo' ?></span></td>
                                        <td class="text-end">
                                            <button class="btn btn-warning btn-sm" onclick='abrirModal("categoria", <?= json_encode($item) ?>)'><i class="bi bi-pencil-fill"></i></button>
                                            <button class="btn btn-sm <?= ($item['Status'] ?? 'Ativo') == 'Ativo' ? 'btn-danger' : 'btn-success' ?>" onclick='abrirModalStatus("categoria", <?= json_encode($item) ?>)'><i class="bi <?= ($item['Status'] ?? 'Ativo') == 'Ativo' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="unidades-pane">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="m-0">Unidades de Medida</h5>
                                <button type="button" class="btn btn-primary btn-sm" onclick="abrirModal('unidade')"><i class="bi bi-plus-circle"></i> Adicionar</button>
                            </div>
                            <table class="table table-sm table-striped">
                                <?php foreach ($unidades as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['Unidade']) ?> (<?= htmlspecialchars($item['Abreviacao']) ?>)</td>
                                        <td><span class="badge <?= $item['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $item['Status'] ?></span></td>
                                        <td class="text-end">
                                            <button class="btn btn-warning btn-sm" onclick='abrirModal("unidade", <?= json_encode($item) ?>)'><i class="bi bi-pencil-fill"></i></button>
                                            <button class="btn btn-sm <?= $item['Status'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>" onclick='abrirModalStatus("unidade", <?= json_encode($item) ?>)'><i class="bi <?= $item['Status'] == 'Ativo' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="cat-meds-pane">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="m-0">Categorias de Medicamentos</h5>
                                <button type="button" class="btn btn-primary btn-sm" onclick="abrirModal('cat_med')"><i class="bi bi-plus-circle"></i> Adicionar</button>
                            </div>
                            <table class="table table-sm table-striped">
                                <?php foreach ($cat_meds as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['Categoria_Med']) ?></td>
                                        <td><span class="badge <?= $item['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $item['Status'] ?></span></td>
                                        <td class="text-end">
                                            <button class="btn btn-warning btn-sm" onclick='abrirModal("cat_med", <?= json_encode($item) ?>)'><i class="bi bi-pencil-fill"></i></button>
                                            <button class="btn btn-sm <?= $item['Status'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>" onclick='abrirModalStatus("cat_med", <?= json_encode($item) ?>)'><i class="bi <?= $item['Status'] == 'Ativo' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="tarjas-pane">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="m-0">Tarjas de Medicamentos</h5>
                                <button type="button" class="btn btn-primary btn-sm" onclick="abrirModal('tarja')"><i class="bi bi-plus-circle"></i> Adicionar</button>
                            </div>
                            <table class="table table-sm table-striped">
                                <?php foreach ($tarjas as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['Tarja']) ?></td>
                                        <td><span class="badge <?= $item['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $item['Status'] ?></span></td>
                                        <td class="text-end">
                                            <button class="btn btn-warning btn-sm" onclick='abrirModal("tarja", <?= json_encode($item) ?>)'><i class="bi bi-pencil-fill"></i></button>
                                            <button class="btn btn-sm <?= $item['Status'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>" onclick='abrirModalStatus("tarja", <?= json_encode($item) ?>)'><i class="bi <?= $item['Status'] == 'Ativo' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <div class="modal fade" id="modalConfirmStatus" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Alteração de Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="processa_configuracoes_produtos.php" method="POST">
                        <div class="modal-body">
                            <p id="confirmText"></p>
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="tipo_config" id="status_tipo_config">
                            <input type="hidden" name="id_config" id="status_id_config">
                            <input type="hidden" name="novo_status" id="novo_status">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="btnConfirmStatus">Confirmar</button>
                        </div>
                    </form>
                </div>
            </div>
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

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-box-seam-fill"></i> Configurações de Produtos</h4>
            <hr>
            <p>Esta tela é essencial para a organização e classificação de todo o seu inventário. A página está dividida em abas, cada uma gerenciando um tipo diferente de atributo do produto. O sistema lembrará da última aba que você utilizou, reabrindo-a automaticamente na sua próxima visita.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtro Dinâmico</h6>
            <p>No topo da página, há um campo de busca único. Ao digitar nele, o sistema filtrará <strong>em tempo real</strong> a lista de itens da aba que estiver atualmente ativa, facilitando a localização de qualquer item rapidamente.</p>

            <h6><i class="bi bi-tags-fill"></i> Abas de Configuração</h6>
            <p>Cada aba gerencia uma lista de atributos que serão usados no cadastro de produtos:</p>
            <ul>
                <li><strong>Categorias de Produtos:</strong> Agrupa produtos por afinidade (ex: Higiene Pessoal, Dermocosméticos).</li>
                <li><strong>Unidades de Medida:</strong> Define as formas como os produtos são vendidos (ex: Unidade, Caixa, Frasco).</li>
                <li><strong>Categorias de Medicamentos:</strong> Classificação específica para medicamentos (ex: Analgésico, Antibiótico).</li>
                <li><strong>Tarjas:</strong> Define as tarjas regulatórias dos medicamentos (ex: Tarja Vermelha, Tarja Preta).</li>
            </ul>

            <h6><i class="bi bi-plus-circle-fill"></i> Adicionar um Novo Item</h6>
            <p>Dentro de cada aba, clique no botão <strong>"Adicionar"</strong> para abrir uma janela onde você poderá cadastrar um novo item para aquela categoria específica.</p>

            <h6><i class="bi bi-pencil-fill"></i> Ações na Lista</h6>
            <p>Para cada item listado, as seguintes ações estão disponíveis:</p>
            <ul>
                <li><i class="bi bi-pencil-fill text-warning"></i> <strong>Editar:</strong> Permite alterar as informações de um item já cadastrado (como o nome ou a abreviação).</li>
                <li><i class="bi bi-pause-circle-fill text-danger"></i> <strong>Inativar:</strong> Se um item está "Ativo", esta opção o tornará "Inativo". Um item inativo não aparecerá como opção no cadastro de novos produtos, mas seu histórico é mantido.</li>
                <li><i class="bi bi-play-circle-fill text-success"></i> <strong>Ativar:</strong> Se um item está "Inativo", esta opção o tornará "Ativo" novamente, disponibilizando-o para uso.</li>
            </ul>
            
            <p class="alert alert-warning mt-3"><strong>Atenção:</strong> O sistema protege a integridade dos seus dados. Você não poderá inativar uma categoria, unidade, etc., se houver algum produto no seu sistema que ainda esteja utilizando essa configuração.</p>
        </div>
        
        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            const configModal = new bootstrap.Modal(document.getElementById('configModal'));
            const statusModal = new bootstrap.Modal(document.getElementById('modalConfirmStatus'));
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

            function abrirModalStatus(tipo, dados) {
                const id = dados.ID_Categoria || dados.ID_Unidade || dados.ID_CategoriaMed || dados.ID_Tarja;
                const nome = dados.Categoria || dados.Unidade || dados.Categoria_Med || dados.Tarja;
                const statusAtual = dados.Status || 'Ativo';
                
                document.getElementById('status_tipo_config').value = tipo;
                document.getElementById('status_id_config').value = id;
                const confirmText = document.getElementById('confirmText');
                const novoStatusInput = document.getElementById('novo_status');
                const btnConfirm = document.getElementById('btnConfirmStatus');

                if (statusAtual === 'Ativo') {
                    confirmText.textContent = `Você tem certeza que deseja INATIVAR o item "${nome}"?`;
                    novoStatusInput.value = 'Inativo';
                    btnConfirm.className = 'btn btn-danger';
                    btnConfirm.textContent = 'Sim, Inativar';
                } 
                else {
                    confirmText.textContent = `Você tem certeza que deseja ATIVAR o item "${nome}"?`;
                    novoStatusInput.value = 'Ativo';
                    btnConfirm.className = 'btn btn-success';
                    btnConfirm.textContent = 'Sim, Ativar';
                }
                statusModal.show();
            }

            // --- NOVO SCRIPT PARA FILTRO E ESTADO DAS ABAS ---
            document.addEventListener('DOMContentLoaded', function() {
                const filtroInput = document.getElementById('filtro-geral');
                const tabs = document.querySelectorAll('#myTab .nav-link');
                const savedTabId = localStorage.getItem('activeConfigTab');

                if (savedTabId) {
                    const tabToActivate = document.querySelector(`button[data-bs-target="${savedTabId}"]`);
                    if (tabToActivate) 
                        new bootstrap.Tab(tabToActivate).show();
                }

                tabs.forEach(tab => {
                    tab.addEventListener('shown.bs.tab', function (event) {
                        localStorage.setItem('activeConfigTab', event.target.dataset.bsTarget);
                        aplicarFiltro(); 
                    });
                });

                filtroInput.addEventListener('input', aplicarFiltro);

                function aplicarFiltro() {
                    const termo = filtroInput.value.toLowerCase();
                    const activeTabPaneId = document.querySelector('.tab-pane.active').id;
                    const activeTabPane = document.getElementById(activeTabPaneId);
                    const linhas = activeTabPane.querySelectorAll('tbody tr');

                    linhas.forEach(linha => {
                        const textoLinha = linha.querySelector('td').textContent.toLowerCase();
                        if (textoLinha.includes(termo)) 
                            linha.style.display = '';
                        else 
                            linha.style.display = 'none';
                    });
                }
                
                aplicarFiltro(); 
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
