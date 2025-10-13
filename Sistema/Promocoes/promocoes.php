<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$stmt = $conn->prepare("SELECT ID_Promocao, Descricao, Tipo, Data_Inicio, Data_Fim, Status FROM PROMOCOES ORDER BY ID_Promocao DESC");
$stmt->execute();
$promocoes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function traduzirTipoPromocao($tipo) {
    switch ($tipo) {
        case 'LEVE_X_PAGUE_Y':
            return 'Leve X, Pague Y';
        case 'DESCONTO_PROGRESSIVO':
            return 'Desconto Progressivo';
        case 'COMBO_PRECO_FIXO':
            return 'Combo com Preço Fixo';
        default:
            return 'Não identificado';
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
                            <i class="bi bi-tag-fill text-primary"></i>
                            Gestão de Promoções
                        </h2>
                        <a href="nova_promocao.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Nova Promoção
                        </a>
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