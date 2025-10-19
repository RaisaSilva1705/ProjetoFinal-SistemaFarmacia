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

$stmtAvaliacoes = $conn->prepare("SELECT Nota, COUNT(*) as Quantidade FROM AVALIACOES WHERE ID_Funcionario = ? GROUP BY Nota");
$stmtAvaliacoes->bind_param("i", $id_funcionario);
$stmtAvaliacoes->execute();
$avaliacoes_result = $stmtAvaliacoes->get_result();

$contagem_notas = [
    5 => 0, // Excelente
    4 => 0, // Bom
    3 => 0, // Neutro
    2 => 0, // Ruim
    1 => 0  // Péssimo
];
$total_avaliacoes = 0;
$soma_notas = 0;
while ($row = $avaliacoes_result->fetch_assoc()) {
    $contagem_notas[$row['Nota']] = $row['Quantidade'];
    $total_avaliacoes += $row['Quantidade'];
    $soma_notas += $row['Nota'] * $row['Quantidade'];
}
$media_geral = ($total_avaliacoes > 0) ? $soma_notas / $total_avaliacoes : 0;

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

                    <div class="col-lg-12 mb-2">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="m-0">Resumo de Avaliações de Clientes</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($total_avaliacoes > 0): ?>
                                    <div class="row align-items-center">
                                        <div class="col-md-3 text-center border-end">
                                            <h6 class="text-muted">MÉDIA GERAL</h6>
                                            <h1 class="display-4 fw-bold text-primary"><?= number_format($media_geral, 2, ',', '.') ?></h1>
                                            <p class="text-muted">(<?= $total_avaliacoes ?> avaliações)</p>
                                        </div>
                                        <div class="col-md-9 px-4">
                                            <?php
                                                $labels = [5 => 'Excelente', 4 => 'Bom', 3 => 'Neutro', 2 => 'Ruim', 1 => 'Péssimo'];
                                                $cores = [5 => 'success', 4 => 'info', 3 => 'secondary', 2 => 'warning', 1 => 'danger'];
                                                $icones = [5 => 'emoji-laughing', 4 => 'emoji-smile', 3 => 'emoji-neutral', 2 => 'emoji-frown', 1 => 'emoji-angry'];
                                            ?>
                                            <?php foreach($contagem_notas as $nota => $qtd): 
                                                $percentual = ($total_avaliacoes > 0) ? ($qtd / $total_avaliacoes) * 100 : 0;
                                            ?>
                                                <div class="mb-2">
                                                    <div class="d-flex justify-content-between">
                                                        <span><i class="bi bi-<?= $icones[$nota] ?>-fill text-<?= $cores[$nota] ?>"></i> <?= $labels[$nota] ?></span>
                                                        <span class="fw-bold"><?= $qtd ?></span>
                                                    </div>
                                                    <div class="progress" style="height: 10px;">
                                                        <div class="progress-bar bg-<?= $cores[$nota] ?>" style="width: <?= $percentual ?>%;"></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center text-muted p-3">Este funcionário ainda não recebeu nenhuma avaliação.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php';?>
        </div>
    </div>

    <div id="manual-content-container" style="display: none;">
        <h4><i class="bi bi-eye-fill"></i> Detalhes do Funcionário</h4>
        <hr>
        <p>Esta tela oferece um perfil completo do funcionário, consolidando suas informações de cadastro, atividades recentes no sistema e o feedback recebido dos clientes.</p>

        <h6><i class="bi bi-person-circle"></i> Painel de Informações</h6>
        <p>O primeiro card resume os dados principais do funcionário, como cargo, informações de contato e o nome de usuário para acesso ao sistema. O botão de editar <i class="bi bi-pencil-fill text-warning"></i> serve como um atalho rápido para a tela de edição.</p>

        <h6><i class="bi bi-graph-up-arrow"></i> Atividades Recentes</h6>
        <p>Este painel exibe uma linha do tempo das últimas operações significativas realizadas pelo funcionário no sistema, como vendas e movimentações de caixa (sangrias e suprimentos). É uma ferramenta útil para auditoria e acompanhamento.</p>
        
        <h6><i class="bi bi-star-half"></i> Resumo de Avaliações de Clientes</h6>
        <p>Este é o painel de feedback de performance. Ele apresenta a <strong>média geral</strong> das notas que o funcionário recebeu dos clientes após as vendas e um detalhamento visual da distribuição dessas notas (de "Péssimo" a "Excelente"). Use esta ferramenta para identificar pontos fortes e oportunidades de treinamento para a equipe.</p>
    </div>

    <?php include_once DEV_PATH . 'Views/toast.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= DEV_URL ?>JS/toast.js"></script>
    <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
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