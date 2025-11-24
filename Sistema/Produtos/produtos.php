<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";

include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'PRODUTOS_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$busca_nome = $_GET['busca_nome'] ?? '';
$categoria_id = (isset($_GET['categoria']) && $_GET['categoria'] !== 'Todos') ? $_GET['categoria'] : '';
$status = (isset($_GET['status']) && $_GET['status'] !== 'Todos') ? $_GET['status'] : '';

$sql = "SELECT
            P.ID_Produto,
            P.Nome,
            P.Status,
            P.EAN_GTIN,
            P.Quant_Minima,
            F.Nome_Fantasia AS Nome_Fornecedor,
            C.Categoria,
            SUM(E.Quantidade) AS Quantidade_Total,
            MAX(L.Preco_Venda) AS Preco_Atual
        FROM PRODUTOS P 
        LEFT JOIN CATEGORIAS C ON C.ID_Categoria = P.ID_Categoria
        LEFT JOIN FORNECEDORES F ON F.ID_Fornecedor = P.ID_Fornecedor
        LEFT JOIN LOTES L ON L.ID_Produto = P.ID_Produto
        LEFT JOIN ESTOQUE E ON E.ID_Lote = L.ID_Lote";

$conditions = [];
$params = [];
$types = '';

if (!empty($busca_nome)) {
    $conditions[] = "(P.Nome LIKE ? OR P.EAN_GTIN LIKE ?)";
    $types .= 'ss';
    $params[] = "%" . $busca_nome . "%";
    $params[] = "%" . $busca_nome . "%";
}

if (!empty($categoria_id)) {
    $conditions[] = "P.ID_Categoria = ?";
    $types .= 'i';
    $params[] = $categoria_id;
}

if (!empty($status)) {
    $conditions[] = "P.Status = ?";
    $types .= 's';
    $params[] = $status;
}

if (count($conditions) > 0)
    $sql .= " WHERE " . implode(' AND ', $conditions);

$sql .= " GROUP BY P.ID_Produto ORDER BY P.Nome ASC";

$stmt = $conn->prepare($sql);
if (!empty($params))
    $stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestão de Produtos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Sidebar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Gestão de Produtos</h3>
                </div>
    
                <div class="container mt-3 p-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Lista de Produtos</h2>
                        <div>
                            <a href="cadastrar_produto.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Novo Produto</a>
                            <a href="../Relatorios/relatorio_produtos.php" class="btn btn-outline-secondary"><i class="bi bi-bar-chart-line-fill"></i> Ver Relatório</a>
                        </div>
                    </div>
    
                    <div class="card card-body mb-4">
                        <form method="GET" action="produtos.php" class="mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="busca_nome" class="form-label">Nome ou Cód. de Barras</label>
                                    <input type="text" name="busca_nome" id="busca_nome" class="form-control" placeholder="Buscar por nome ou EAN..." value="<?= htmlspecialchars($_GET['busca_nome'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="categoria" class="form-label">Categoria</label>
                                    <select name="categoria" id="categoria" class="form-select">
                                        <option value="Todos">Todas</option>
                                        <?php
                                        $categorias_result = $conn->query("SELECT ID_Categoria, Categoria FROM CATEGORIAS ORDER BY Categoria");
                                        while ($cat = $categorias_result->fetch_assoc()) {
                                            $selected = ($_GET['categoria'] ?? '') == $cat['ID_Categoria'] ? 'selected' : '';
                                            echo "<option value='{$cat['ID_Categoria']}' $selected>{$cat['Categoria']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
    
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <option value="Ativo" <?= ($_GET['status'] ?? '') == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="Inativo" <?= ($_GET['status'] ?? '') == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
    
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Produto</th>
                                    <th>Cód. Barras</th>
                                    <th>Categoria</th>
                                    <th class="text-center">Estoque</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Preço</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): 
                                        $estoque_total = $row['Quantidade_Total'] ?? 0;
                                        $classe_alerta = '';
                                        if ($estoque_total < $row['Quant_Minima']) $classe_alerta = 'table-danger';
                                    ?>
                                        <tr class="<?= $classe_alerta ?>">
                                            <td><?= htmlspecialchars($row["Nome"]) ?></td>
                                            <td><?= htmlspecialchars($row["EAN_GTIN"]) ?></td>
                                            <td><?= htmlspecialchars($row["Categoria"]) ?></td>
                                            <td class="text-center fw-bold"><?= intval($estoque_total) ?></td>
                                            <td class="text-center"><span class="badge <?= $row['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $row['Status'] ?></span></td>
                                            <td class="text-end">R$ <?= number_format($row['Preco_Atual'] ?? 0, 2, ',', '.') ?></td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="detalhes_produto.php?codigo=<?= $row['ID_Produto'] ?>" class="btn btn-info btn-sm" title="Ver Detalhes"><i class="bi bi-eye-fill"></i></a>
                                                    <a href="editar_produto.php?codigo=<?= $row['ID_Produto'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                                                    <button type="button" class="btn btn-sm <?= $row['Status'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>"
                                                            title="<?= $row['Status'] == 'Ativo' ? 'Inativar' : 'Ativar' ?>"
                                                            data-bs-toggle="modal" data-bs-target="#modalConfirmStatus"
                                                            data-id="<?= $row['ID_Produto'] ?>"
                                                            data-nome="<?= htmlspecialchars($row['Nome']) ?>"
                                                            data-status-atual="<?= $row['Status'] ?>">
                                                        <i class="bi <?= $row['Status'] == 'Ativo' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' ?>"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center">Nenhum produto encontrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <!-- Modal de confirmação -->
        <div class="modal fade" id="modalConfirmStatus" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Alteração de Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p id="confirmText"></p>
                    </div>
                    <div class="modal-footer">
                        <form action="processa_produto.php" method="POST">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="id_produto" id="id_status_change">
                            <input type="hidden" name="novo_status" id="novo_status">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary" id="btnConfirmStatus">Confirmar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-boxes"></i> Gestão de Produtos</h4>
            <hr>
            <p>Esta é a sua tela central para o gerenciamento de todo o catálogo de produtos da farmácia. Aqui você pode visualizar, filtrar e acessar as ações para cada item do seu inventário.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Busca</h6>
            <p>Utilize os filtros para encontrar produtos de forma rápida e precisa:</p>
            <ul>
                <li><strong>Nome ou Cód. de Barras:</strong> Digite o nome do produto ou seu código EAN para uma busca direta.</li>
                <li><strong>Categoria:</strong> Filtre a lista para exibir apenas produtos de uma categoria específica (ex: Medicamentos, Cosméticos).</li>
                <li><strong>Status:</strong> Filtre entre produtos <strong>Ativos</strong> (disponíveis para venda) e <strong>Inativos</strong> (arquivados).</li>
            </ul>

            <h6><i class="bi bi-plus-circle-fill"></i> Novo Produto e Relatório</h6>
            <ul>
                <li><strong>Novo Produto:</strong> Leva ao formulário de cadastro completo para adicionar um novo item ao seu catálogo.</li>
                <li><strong>Ver Relatório:</strong> Acessa a área de relatórios focada em produtos, com análises de vendas, margem e giro de estoque.</li>
            </ul>

            <h6><i class="bi bi-exclamation-triangle-fill text-danger"></i> Alerta de Estoque Baixo</h6>
            <p>As linhas destacadas em <strong>vermelho</strong> na tabela indicam produtos cujo estoque total está abaixo da quantidade mínima que você definiu no cadastro. Isso serve como um alerta visual para a necessidade de reposição.</p>

            <h6><i class="bi bi-pencil-fill"></i> Ações na Lista</h6>
            <p>Para cada produto listado, as seguintes ações estão disponíveis:</p>
            <ul>
                <li><i class="bi bi-pencil-fill text-warning"></i> <strong>Editar:</strong> Permite alterar todas as informações do produto, incluindo dados gerais, fiscais e de medicamentos.</li>
                <li><i class="bi bi-pause-circle-fill text-danger"></i> / <i class="bi bi-play-circle-fill text-success"></i> <strong>Inativar/Ativar:</strong> Altera o status do produto.</li>
            </ul>
            <p class="alert alert-danger mt-3"><strong>Regra de Negócio:</strong> Por segurança de inventário, o sistema não permitirá inativar um produto que ainda possui unidades em estoque. É necessário primeiro zerar o estoque (seja por venda, perda ou devolução) para poder inativá-lo.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            const modalConfirmStatus = document.getElementById('modalConfirmStatus');
            modalConfirmStatus.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                const id = button.getAttribute('data-id');
                const produto = button.getAttribute('data-nome');
                const statusAtual = button.getAttribute('data-status-atual');

                const confirmText = modalConfirmStatus.querySelector('#confirmText');
                const idInput = modalConfirmStatus.querySelector('#id_status_change');
                const novoStatusInput = modalConfirmStatus.querySelector('#novo_status');
                const btnConfirm = modalConfirmStatus.querySelector('#btnConfirmStatus');

                idInput.value = id;

                if (statusAtual === 'Ativo') {
                    confirmText.textContent = `Você tem certeza que deseja INATIVAR o produto "${produto}"?`;
                    novoStatusInput.value = 'Inativo';
                    btnConfirm.className = 'btn btn-danger';
                    btnConfirm.textContent = 'Sim, Inativar';
                } 
                else {
                    confirmText.textContent = `Você tem certeza que deseja ATIVAR o produto "${produto}"?`;
                    novoStatusInput.value = 'Ativo';
                    btnConfirm.className = 'btn btn-success';
                    btnConfirm.textContent = 'Sim, Ativar';
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
