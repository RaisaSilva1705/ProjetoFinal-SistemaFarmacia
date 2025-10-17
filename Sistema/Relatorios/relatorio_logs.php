<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'RELATORIOS_VER');
include DEV_PATH . "Exec/validar_acesso.php";

// 1. LÓGICA DE FILTROS E PAGINAÇÃO
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$filtro_usuario_id = (isset($_GET['usuario_id']) && $_GET['usuario_id'] !== 'Todos') ? $_GET['usuario_id'] : '';
$busca_acao = $_GET['busca_acao'] ?? '';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$items_per_page = 50; // Quantos logs mostrar por página
$offset = ($page - 1) * $items_per_page;

// 2. BUSCAR DADOS PARA OS FILTROS
$usuarios_lista = $conn->query("SELECT U.ID_Usuario, F.Nome FROM USUARIOS U JOIN FUNCIONARIOS F ON U.ID_Funcionario = F.ID_Funcionario WHERE U.Status = 'Ativo' ORDER BY F.Nome")->fetch_all(MYSQLI_ASSOC);

// 3. CONSTRUIR A QUERY DE CONTAGEM (PARA PAGINAÇÃO)
$sql_count = "SELECT COUNT(L.ID_Log)
              FROM LOGS L
              JOIN USUARIOS U ON L.ID_Usuario = U.ID_Usuario
              WHERE DATE(L.Timestamp) BETWEEN ? AND ?";
$params_count = [$data_inicio, $data_fim];
$types_count = 'ss';

if ($filtro_usuario_id) { $sql_count .= " AND L.ID_Usuario = ?"; $params_count[] = $filtro_usuario_id; $types_count .= 'i'; }
if (!empty($busca_acao)) { $sql_count .= " AND L.Acao LIKE ?"; $params_count[] = "%" . $busca_acao . "%"; $types_count .= 's'; }

$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param($types_count, ...$params_count);
$stmt_count->execute();
$total_logs = $stmt_count->get_result()->fetch_row()[0];
$total_pages = ceil($total_logs / $items_per_page);

// 4. CONSTRUIR A QUERY PRINCIPAL (COM LIMIT E OFFSET)
$sql_main = "SELECT L.Timestamp, L.Acao, F.Nome AS Nome_Funcionario
             FROM LOGS L
             JOIN USUARIOS U ON L.ID_Usuario = U.ID_Usuario
             JOIN FUNCIONARIOS F ON U.ID_Funcionario = F.ID_Funcionario
             WHERE DATE(L.Timestamp) BETWEEN ? AND ?";
$params_main = [$data_inicio, $data_fim];
$types_main = 'ss';

if ($filtro_usuario_id) { $sql_main .= " AND L.ID_Usuario = ?"; $params_main[] = $filtro_usuario_id; $types_main .= 'i'; }
if (!empty($busca_acao)) { $sql_main .= " AND L.Acao LIKE ?"; $params_main[] = "%" . $busca_acao . "%"; $types_main .= 's'; }

$sql_main .= " ORDER BY L.Timestamp DESC LIMIT ? OFFSET ?";
$params_main[] = $items_per_page;
$params_main[] = $offset;
$types_main .= 'ii';

$stmt_main = $conn->prepare($sql_main);
$stmt_main->bind_param($types_main, ...$params_main);
$stmt_main->execute();
$logs = $stmt_main->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Atividades (Logs)</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Segurança e Auditoria</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Relatório de Atividades</h2>
                    </div>

                    <div class="card card-body mb-4 no-print">
                        <form method="GET" action="relatorio_logs.php">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3"><label>Período de:</label><input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>"></div>
                                <div class="col-md-3"><label>Até:</label><input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>"></div>
                                <div class="col-md-2">
                                    <label>Usuário:</label>
                                    <select name="usuario_id" class="form-select">
                                        <option value="Todos">Todos</option>
                                        <?php foreach ($usuarios_lista as $user): ?>
                                            <option value="<?= $user['ID_Usuario'] ?>" <?= ($filtro_usuario_id == $user['ID_Usuario']) ? 'selected' : '' ?>><?= htmlspecialchars($user['Nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2"><label>Ação contém:</label><input type="text" name="busca_acao" class="form-control" placeholder="Ex: cancelou, excluiu..." value="<?= htmlspecialchars($busca_acao) ?>"></div>
                                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Registros de Atividade</h4>
                            <span class="badge bg-secondary"><?= $total_logs ?> registro(s) encontrado(s)</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 20%;">Data/Hora</th>
                                        <th style="width: 20%;">Usuário</th>
                                        <th style="width: 60%;">Ação Realizada</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($logs) > 0): ?>
                                        <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i:s', strtotime($log['Timestamp'])) ?></td>
                                                <td><?= htmlspecialchars($log['Nome_Funcionario']) ?></td>
                                                <td><?= htmlspecialchars($log['Acao']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center p-4">Nenhum log encontrado para os filtros selecionados.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if ($total_pages > 1): ?>
                        <div class="card-footer d-flex justify-content-center no-print">
                            <nav>
                                <ul class="pagination mb-0">
                                    <?php
                                    $query_string = http_build_query(array_merge($_GET, ['page' => '']));
                                    ?>
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $query_string . ($page - 1) ?>">Anterior</a></li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>"><a class="page-link" href="?<?= $query_string . $i ?>"><?= $i ?></a></li>
                                    <?php endfor; ?>

                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>"><a class="page-link" href="?<?= $query_string . ($page + 1) ?>">Próxima</a></li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>