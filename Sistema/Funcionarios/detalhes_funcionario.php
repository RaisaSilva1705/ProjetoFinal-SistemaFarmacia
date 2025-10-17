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

$id_funcionario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_funcionario) {
    header("Location: funcionarios.php"); 
    exit();
}

$stmt = $conn->prepare("SELECT F.*, U.Usuario, C.Cargo FROM FUNCIONARIOS F JOIN USUARIOS U ON F.ID_Funcionario = U.ID_Funcionario JOIN CARGOS C ON F.ID_Cargo = C.ID_Cargo WHERE F.ID_Funcionario = ?");
$stmt->bind_param("i", $id_funcionario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['msg'] = ['texto' => 'Funcionário não encontrado.', 'tipo' => 'danger'];
    header("Location: funcionarios.php"); 
    exit();
}
$funcionario = $result->fetch_assoc();

$atividades = null;
$stmtAtividades = $conn->prepare("
    (SELECT 'Venda' as Fonte, ID_Venda as ID, DataHora_Venda as Data, 'Venda Realizada' as Tipo, Valor_Total as Valor FROM VENDAS WHERE ID_Funcionario = ? ORDER BY DataHora_Venda DESC LIMIT 5) 
    UNION ALL 
    (SELECT 'MovCaixa' as Fonte, ID_MovimentacaoCaixa as ID, Data_Movimentacao as Data, CONCAT('Mov. Caixa (', Tipo, ')') as Tipo, Valor FROM MOVIMENTACOES_CAIXA WHERE ID_Funcionario = ? ORDER BY Data_Movimentacao DESC LIMIT 5) 
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
    <title>Detalhes de Funcionário: <?= htmlspecialchars($funcionario['Nome']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
</head>
<body class="bg-light">

    <?php include_once DEV_PATH . 'Views/sidebar.php';?>

    <div class="content d-flex flex-column min-vh-100">
        <div class="content flex-grow-1">
            <div class="container-fluid bg-secondary text-white text-center p-4">
                <h3>Funcionários</h3>
            </div>
            
            <div class="container p-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="m-0">Detalhes do Funcionário</h2>
                    <a href="funcionarios.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-circle"></i> Voltar para a Lista</a>
                </div>

                <div class="row">
                    <div class="col-lg-5 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="m-0"><?= htmlspecialchars($funcionario['Nome']) ?></h4>
                                <a href="editar_funcionario.php?id=<?= $funcionario['ID_Funcionario'] ?>" class="btn btn-warning btn-sm" title="Editar Cadastro"><i class="bi bi-pencil-fill"></i></a>
                            </div>
                            <div class="card-body">
                                <p><strong><i class="bi bi-person-badge-fill text-muted me-2"></i>Cargo:</strong> <?= htmlspecialchars($funcionario['Cargo']) ?></p>
                                <p><strong><i class="bi bi-envelope-fill text-muted me-2"></i>Email:</strong> <?= htmlspecialchars($funcionario['Email']) ?></p>
                                <p><strong><i class="bi bi-telephone-fill text-muted me-2"></i>Telefone:</strong> <?= htmlspecialchars($funcionario['Telefone'] ?? 'Não cadastrado') ?></p>
                                <p><strong><i class="bi bi-calendar-check-fill text-muted me-2"></i>Admissão:</strong> <?= ($funcionario['Data_Admissao']) ? date('d/m/Y', strtotime($funcionario['Data_Admissao'])) : 'Não informado' ?></p>
                                <hr>
                                <p><strong><i class="bi bi-person-fill-gear text-muted me-2"></i>Usuário de Acesso:</strong> <?= htmlspecialchars($funcionario['Usuario']) ?></p>
                                <p><strong><i class="bi bi-check-circle-fill text-muted me-2"></i>Status:</strong> <span class="badge <?= $funcionario['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= htmlspecialchars($funcionario['Status']) ?></span></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7 mb-4">
                        <?php if ($atividades): ?>
                        <div class="card shadow-sm h-100">
                            <div class="card-header"><h5 class="m-0">Atividades Recentes</h5></div>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Atividade</th>
                                            <th class="text-end">Valor (R$)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($atividades->num_rows > 0): ?>
                                            <?php while($ativ = $atividades->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y H:i', strtotime($ativ['Data'])) ?></td>
                                                    <td><?= htmlspecialchars($ativ['Tipo']) ?></td>
                                                    <td class="text-end fw-bold <?= str_contains($ativ['Tipo'], 'Saída') ? 'text-danger' : 'text-success' ?>">
                                                        R$ <?= number_format($ativ['Valor'], 2, ',', '.') ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center p-3">Nenhuma atividade recente registrada.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include_once DEV_PATH . 'Views/footer.php';?>
    </div>

    <?php include_once DEV_PATH . 'Views/toast.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
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