<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'FUNCIONARIOS_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

if (!isset($_SESSION['Cargo']) || ($_SESSION['Cargo'] != 'Gerente' && $_SESSION['Cargo'] != 'Administrador')) {
    $_SESSION['msg'] = ['texto' => 'Acesso negado.', 'tipo' => 'warning'];
    header("Location: funcionarios.php");
    exit();
}

$id_funcionario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_funcionario) {
    header("Location: funcionarios.php"); 
    exit();
}

$stmt = $conn->prepare("
    SELECT F.*, U.Usuario, C.Cargo 
    FROM FUNCIONARIOS F 
    JOIN USUARIOS U ON F.ID_Funcionario = U.ID_Funcionario 
    JOIN CARGOS C ON F.ID_Cargo = C.ID_Cargo 
    WHERE F.ID_Funcionario = ?
");
$stmt->bind_param("i", $id_funcionario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['msg'] = ['texto' => 'Funcionário não encontrado.', 'tipo' => 'danger'];
    header("Location: funcionarios.php"); 
    exit();
}
$funcionario = $result->fetch_assoc();

$stmtAtividades = $conn->prepare("
    (SELECT 
        ID_Venda as ID_Atividade, 
        DataHora_Venda as Data, 
        'Venda Realizada' as Tipo, 
        Valor_Total as Valor 
    FROM VENDAS 
    WHERE ID_Funcionario = ?
    ORDER BY DataHora_Venda DESC
    LIMIT 5)
    UNION ALL
    (SELECT 
        ID_MovimentacaoCaixa as ID_Atividade, 
        Data_Movimentacao as Data, 
        CONCAT('Mov. Caixa (', Tipo, ')') as Tipo, 
        Valor 
    FROM MOVIMENTACOES_CAIXA 
    WHERE ID_Funcionario = ?
    ORDER BY Data_Movimentacao DESC
    LIMIT 5)
    ORDER BY Data DESC
    LIMIT 5
");
$stmtAtividades->bind_param("ii", $id_funcionario, $id_funcionario);
$stmtAtividades->execute();
$atividades = $stmtAtividades->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detalhes de Funcionário</title>
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
                    <h3>Detalhes do Funcionário</h3>
                </div>
            
                <div class="container p-5">
                    <a href="funcionarios.php" class="btn btn-outline-secondary mb-4">
                        <i class="bi bi-arrow-left"></i> Voltar para a Lista
                    </a>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="m-0"><?= htmlspecialchars($funcionario['Nome']) ?></h4>
                            <a href="editar_funcionario.php?id=<?= $funcionario['ID_Funcionario'] ?>" class="btn btn-info btn-sm">Editar Cadastro</a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong><i class="bi bi-person-badge-fill"></i>Cargo:</strong> <?= htmlspecialchars($funcionario['Cargo']) ?></p>
                                    <p><strong><i class="bi bi-envelope-fill me-2"></i>Email:</strong> <?= htmlspecialchars($funcionario['Email']) ?></p>
                                    <p><strong><i class="bi bi-telephone-fill me-2"></i>Telefone:</strong> <?= ($funcionario['Telefone']) ? htmlspecialchars($funcionario['Telefone']) : 'Telefone não cadastrado' ?></p>
                                    <p><strong><i class="bi bi-calendar-check-fill"></i>Data de Admissão:</strong> <?= ($funcionario['Data_Admissao']) ? date('d/m/Y', strtotime($funcionario['Data_Admissao'])) : 'Sem data' ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Usuário de Acesso:</strong> <?= htmlspecialchars($funcionario['Usuario']) ?></p>
                                    <p>
                                        <?php
                                        $badge_class = $funcionario['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger';
                                        echo "<strong>Status:</strong> <span class='badge {$badge_class}'>" . htmlspecialchars($funcionario['Status']) . "</span>";
                                        ?>
                                    </p>
                                    <p><strong>Salário:</strong> R$ <?= number_format($funcionario['Salario'] ?? 0, 2, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="m-0">Atividades Recentes</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Tipo de Atividade</th>
                                            <th class="text-end">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($atividades->num_rows > 0): ?>
                                            <?php while($ativ = $atividades->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y H:i', strtotime($ativ['Data'])) ?></td>
                                                    <td><?= htmlspecialchars($ativ['Tipo']) ?></td>
                                                    <td class="text-end">R$ <?= number_format($ativ['Valor'], 2, ',', '.') ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center">Nenhuma atividade recente registrada.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
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