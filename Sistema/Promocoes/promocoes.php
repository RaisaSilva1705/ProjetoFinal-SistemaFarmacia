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
            GROUP_CONCAT(prod.Nome SEPARATOR ', ') AS Produtos
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
                                        <th>Vigência</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($promocoes) > 0): ?>
                                        <?php foreach ($promocoes as $promo): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($promo['Descricao']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo traduzirTipoPromocao($promo['Tipo']); ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                        echo date('d/m/Y', strtotime($promo['Data_Inicio']));
                                                        echo $promo['Data_Fim'] ? ' - ' . date('d/m/Y', strtotime($promo['Data_Fim'])) : ' - Sem data final';
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($promo['Status'] == 'Ativo'): ?>
                                                        <span class="badge bg-success">Ativa</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inativa</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="editar_promocao.php?id=<?php echo $promo['ID_Promocao']; ?>" class="btn btn-sm btn-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                                    <?php if ($promo['Status'] == 'Ativo'): ?>
                                                        <a href="processa_status_promocao.php?id=<?php echo $promo['ID_Promocao']; ?>&acao=inativar" class="btn btn-sm btn-danger" title="Inativar" onclick="return confirm('Tem certeza que deseja inativar esta promoção?')"><i class="bi bi-pause-circle"></i></a>
                                                    <?php else: ?>
                                                        <a href="processa_status_promocao.php?id=<?php echo $promo['ID_Promocao']; ?>&acao=ativar" class="btn btn-sm btn-success" title="Ativar" onclick="return confirm('Tem certeza que deseja ativar esta promoção?')"><i class="bi bi-play-circle"></i></a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center p-4">Nenhuma promoção cadastrada.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?php echo DEV_URL ?>JS/toast.js"></script>
        <script>
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