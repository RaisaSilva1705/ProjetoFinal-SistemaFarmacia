<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'PROMOCOES_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($_GET['descricao'])) {
    $where_clauses[] = "p.Descricao LIKE ?";
    $params[] = '%' . $_GET['descricao'] . '%';
    $types .= 's';
}

if (!empty($_GET['tipo']) && $_GET['tipo'] !== 'Todos') {
    $where_clauses[] = "p.Tipo = ?";
    $params[] = $_GET['tipo'];
    $types .= 's';
}

if (!empty($_GET['status']) && $_GET['status'] !== 'Todos') {
    $where_clauses[] = "p.Status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$sql = "SELECT 
            p.ID_Promocao, p.Descricao, p.Tipo, p.Data_Inicio, p.Data_Fim, p.Status,
            GROUP_CONCAT(DISTINCT prod.Nome SEPARATOR ', ') AS Produtos
        FROM PROMOCOES p
        LEFT JOIN PROMOCOES_ITENS pi ON p.ID_Promocao = pi.ID_Promocao
        LEFT JOIN PRODUTOS prod ON pi.ID_Produto = prod.ID_Produto
        {$where_sql}
        GROUP BY p.ID_Promocao
        ORDER BY p.ID_Promocao DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$promocoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function traduzirTipoPromocao($tipo) {
    switch ($tipo) {
        case 'LEVE_X_PAGUE_Y': return 'Leve X, Pague Y';
        case 'DESCONTO_PROGRESSIVO': return 'Desconto Progressivo';
        case 'COMBO_PRECO_FIXO': return 'Combo com Preço Fixo';
        default: return 'Não identificado';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Gestão de Promoções</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Marketing e Vendas</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">
                            Gestão de Promoções
                        </h2>
                        <a href="nova_promocao.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nova Promoção
                        </a>
                    </div>

                    <div class="card card-body mb-4">
                        <form action="promocoes.php" method="GET" class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label for="descricao" class="form-label">Buscar por Descrição</label>
                                <input type="text" class="form-control" name="descricao" id="descricao" value="<?= htmlspecialchars($_GET['descricao'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="tipo" class="form-label">Tipo</label>
                                <select name="tipo" id="tipo" class="form-select">
                                    <option value="Todos">Todos</option>
                                    <option value="LEVE_X_PAGUE_Y" <?= ($_GET['tipo'] ?? '') == 'LEVE_X_PAGUE_Y' ? 'selected' : '' ?>>Leve X, Pague Y</option>
                                    <option value="DESCONTO_PROGRESSIVO" <?= ($_GET['tipo'] ?? '') == 'DESCONTO_PROGRESSIVO' ? 'selected' : '' ?>>Desconto Progressivo</option>
                                    <option value="COMBO_PRECO_FIXO" <?= ($_GET['tipo'] ?? '') == 'COMBO_PRECO_FIXO' ? 'selected' : '' ?>>Combo com Preço Fixo</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Todos">Todos</option>
                                    <option value="Ativo" <?= ($_GET['status'] ?? '') == 'Ativo' ? 'selected' : '' ?>>Ativas</option>
                                    <option value="Inativo" <?= ($_GET['status'] ?? '') == 'Inativo' ? 'selected' : '' ?>>Inativas</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Descrição</th>
                                        <th>Tipo</th>
                                        <th>Produtos</th>
                                        <th>Vigência</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($promocoes) > 0): ?>
                                        <?php foreach ($promocoes as $promo): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($promo['Descricao']); ?></strong></td>
                                                <td><span class="badge bg-info"><?php echo traduzirTipoPromocao($promo['Tipo']); ?></span></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php 
                                                            $produtos = $promo['Produtos'] ?? 'Nenhum produto associado.';
                                                            if (strlen($produtos) > 50)
                                                                echo htmlspecialchars(substr($produtos, 0, 50)) . '...';
                                                            else 
                                                                echo htmlspecialchars($produtos);
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php 
                                                        echo date('d/m/Y', strtotime($promo['Data_Inicio']));
                                                        echo $promo['Data_Fim'] ? ' - ' . date('d/m/Y', strtotime($promo['Data_Fim'])) : ' - Indeterminado';
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge <?= $promo['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= $promo['Status'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="editar_promocao.php?id=<?php echo $promo['ID_Promocao']; ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                    <button type="button" class="btn btn-sm <?= $promo['Status'] == 'Ativo' ? 'btn-danger' : 'btn-success' ?>" 
                                                            onclick="abrirModalStatus(<?= $promo['ID_Promocao'] ?>, '<?= htmlspecialchars($promo['Descricao']) ?>', '<?= $promo['Status'] ?>')"
                                                            title="<?= $promo['Status'] == 'Ativo' ? 'Inativar' : 'Ativar' ?>">
                                                        <i class="bi <?= $promo['Status'] == 'Ativo' ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center p-4">Nenhuma promoção encontrada para os filtros selecionados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
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
                    <div class="modal-header"><h5 class="modal-title">Confirmar Alteração</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><p id="confirmText"></p></div>
                    <div class="modal-footer">
                        <a href="#" id="btnConfirmStatus" class="btn btn-primary">Confirmar</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-tag-fill"></i> Gestão de Promoções</h4>
            <hr>
            <p>Esta é a sua central de marketing, onde você pode criar, gerenciar e analisar todas as promoções e ofertas da sua farmácia. Um bom gerenciamento de promoções pode aumentar significativamente suas vendas e a fidelidade dos clientes.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Busca</h6>
            <p>Utilize os filtros para encontrar uma promoção específica de forma rápida:</p>
            <ul>
                <li><strong>Buscar por Descrição:</strong> Digite uma palavra-chave da descrição da promoção.</li>
                <li><strong>Tipo:</strong> Filtre por um tipo específico de promoção (ex: Leve X, Pague Y).</li>
                <li><strong>Status:</strong> Filtre entre promoções <strong>Ativas</strong> (em vigor no PDV) e <strong>Inativas</strong>.</li>
            </ul>

            <h6><i class="bi bi-plus-circle-fill"></i> Nova Promoção</h6>
            <p>Clique no botão <strong>"Nova Promoção"</strong> para ser direcionado ao construtor de promoções, onde você poderá definir as regras de uma nova oferta.</p>

            <h6><i class="bi bi-pencil-fill"></i> Ações na Lista</h6>
            <p>Para cada promoção listada, as seguintes ações estão disponíveis:</p>
            <ul>
                <li><i class="bi bi-pencil-fill text-warning"></i> <strong>Editar:</strong> Permite alterar todas as regras e informações de uma promoção existente.</li>
                <li><i class="bi bi-pause-circle-fill text-danger"></i> / <i class="bi bi-play-circle-fill text-success"></i> <strong>Inativar/Ativar:</strong> Altera o status da promoção. Uma promoção inativa não será aplicada automaticamente no PDV.</li>
            </ul>
            <p class="alert alert-info mt-3"><strong>Lembrete:</strong> O sistema possui um evento automático que inativa as promoções cuja data final já passou, garantindo que ofertas expiradas não sejam aplicadas.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            const modalStatus = new bootstrap.Modal(document.getElementById('modalConfirmStatus'));
            function abrirModalStatus(id, nome, statusAtual) {
                const confirmText = document.getElementById('confirmText');
                const btnConfirm = document.getElementById('btnConfirmStatus');
                let acao = '';
                
                if (statusAtual === 'Ativo') {
                    acao = 'inativar';
                    confirmText.textContent = `Tem certeza que deseja INATIVAR a promoção "${nome}"?`;
                    btnConfirm.className = 'btn btn-danger';
                    btnConfirm.textContent = 'Sim, Inativar';
                } 
                else {
                    acao = 'ativar';
                    confirmText.textContent = `Tem certeza que deseja ATIVAR a promoção "${nome}"?`;
                    btnConfirm.className = 'btn btn-success';
                    btnConfirm.textContent = 'Sim, Ativar';
                }
                
                btnConfirm.href = `processa_status_promocao.php?id=${id}&acao=${acao}`;
                modalStatus.show();
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