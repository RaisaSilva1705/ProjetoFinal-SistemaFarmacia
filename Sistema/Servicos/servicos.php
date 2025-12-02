<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'SERVICOS_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

function formatar_resultado_servico($json_data, $id_servico, $conn) {
    if (empty($json_data)) return 'N/A';
    $dados = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) return 'Dados inválidos';

    $stmt = $conn->prepare("SELECT Name_Campo, Label_Campo FROM SERVICO_CAMPOS WHERE ID_Servico = ? ORDER BY Ordem");
    $stmt->bind_param("i", $id_servico);
    $stmt->execute();
    $campos_ordenados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $resumos = [];
    foreach ($campos_ordenados as $campo) {
        $name = $campo['Name_Campo'];
        if (isset($dados[$name]) && !empty($dados[$name])) {
            $label = $campo['Label_Campo'];
            $resumos[] = "<strong>" . htmlspecialchars($label) . "</strong> " . htmlspecialchars($dados[$name]);
        }
    }

    foreach (['autoriza_uso_dados', 'encaminhado_medico'] as $chave) {
        if (isset($dados[$chave]) && !empty($dados[$chave])) {
            $label = ucwords(str_replace('_', ' ', $chave));
            $resumos[] = "<strong>{$label}</strong> " . htmlspecialchars($dados[$chave]);
        }
    }

    return implode(' | ', $resumos);
}

$busca_texto = $_GET['busca_texto'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$busca_farma = $_GET['busca_farma'] ?? '';
$params = [];
$types = '';

$sql = "SELECT RS.*,
               C.Nome AS Nome_Cliente,
               S.Nome_Servico,
               F.Nome AS Nome_Funcionario
        FROM REGISTRO_SERVICOS RS
        LEFT JOIN CLIENTES C ON RS.ID_Cliente = C.ID_Cliente
        JOIN SERVICOS_FARMACEUTICOS S ON RS.ID_Servico = S.ID_Servico
        JOIN FUNCIONARIOS F ON RS.ID_Funcionario = F.ID_Funcionario";

if (!empty($busca_texto)) {
    $sql .= " WHERE RS.Nome_Paciente LIKE ? OR C.Nome LIKE ? OR C.Documento LIKE ?";
    $like_busca = "%" . $busca_texto . "%";
    $params = [$like_busca, $like_busca, $like_busca];
    $types = 'sss';
}

$sql .= " ORDER BY RS.DataHora DESC LIMIT 50";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$historico = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Serviços Farmacêuticos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

       <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Serviços Farmacêuticos</h3>
                </div>
            
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Histórico de Serviços Prestados</h2>
                        <div>
                            <a href="novo_servico.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Registrar Atendimento</a>
                            <a href="../Relatorios/relatorio_servicos.php" class="btn btn-outline-secondary"><i class="bi bi-bar-chart-line-fill"></i> Ver Relatório</a>
                        </div>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="servicos.php">
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="busca_texto" class="form-label">Buscar por Servico</label>
                                    <input type="text" name="busca_texto" id="busca_texto" class="form-control" value="<?= htmlspecialchars($busca_texto) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="data_inicio">Data Início:</label>
                                    <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?= htmlspecialchars($data_inicio) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="data_fim">Data Fim:</label>
                                    <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?= htmlspecialchars($data_fim) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="busca_farma" class="form-label">Buscar por Farmacêutico</label>
                                    <input type="text" name="busca_farma" id="busca_farma" class="form-control" value="<?= htmlspecialchars($busca_farma) ?>">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filtrar</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Cliente</th>
                                    <th>Serviço Prestado</th>
                                    <th>Resultado</th>
                                    <th>Farmacêutico</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($historico->num_rows > 0): ?>
                                    <?php while($reg = $historico->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i', strtotime($reg['DataHora'])) ?></td>
                                            <td><?= htmlspecialchars($reg['Nome_Cliente'] ?? $reg['Nome_Paciente']) ?></td>
                                            <td><?= htmlspecialchars($reg['Nome_Servico']) ?></td>
                                            <td><?= formatar_resultado_servico($reg['Dados_Servico'], $reg['ID_Servico'], $conn) ?></td>
                                            <td><?= htmlspecialchars($reg['Nome_Funcionario']) ?></td>
                                            <td class="text-center">
                                                <a href="dsf.php?id=<?= $reg['ID_Registro_Servico'] ?>" class="btn btn-info btn-sm" target="_blank" title="Gerar DSF"><i class="bi bi-file-earmark-medical-fill"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td class="text-center" colspan="6">Nenhum serviço encontrado.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-heart-pulse-fill"></i> Histórico de Serviços Prestados</h4>
            <hr>
            <p>Esta tela funciona como o prontuário de todos os serviços clínicos realizados na farmácia. É uma ferramenta essencial para auditoria, acompanhamento de pacientes e consulta ao histórico de atendimentos.</p>

            <h6><i class="bi bi-funnel-fill"></i> Filtros de Busca</h6>
            <p>Utilize os filtros para localizar um registro de atendimento específico:</p>
            <ul>
                <li><strong>Buscar por Serviço:</strong> Digite o nome do serviço para ver todos os registros daquele tipo (ex: "Aferição de Pressão").</li>
                <li><strong>Data Início / Data Fim:</strong> Defina um período para a busca.</li>
                <li><strong>Buscar por Farmacêutico:</strong> Filtre para ver todos os atendimentos realizados por um profissional específico.</li>
            </ul>

            <h6><i class="bi bi-plus-circle-fill"></i> Ações Principais</h6>
            <ul>
                <li><strong>Registrar Atendimento:</strong> Leva à tela de registro de um novo serviço, onde você poderá preencher o formulário específico para o atendimento.</li>
                <li><strong>Ver Relatório:</strong> Acessa a área de relatórios para uma análise gerencial do desempenho e faturamento dos serviços.</li>
            </ul>

            <h6><i class="bi bi-list-ol"></i> Entendendo a Tabela</h6>
            <p>Cada linha representa um serviço único prestado a um paciente:</p>
            <ul>
                <li><strong>Resultado:</strong> Exibe um resumo dos principais dados coletados durante o atendimento (ex: "Pressão Sistólica 120", "Glicemia Capilar 95").</li>
                <li><strong>Ações:</strong> O botão <strong>"Gerar DSF"</strong> <i class="bi bi-info-circle text-info"></i> permite visualizar e reimprimir a Declaração de Serviço Farmacêutico, que é o documento oficial do atendimento entregue ao paciente.</li>
            </ul>
        </div>
        
        <?php include_once DEV_PATH . 'Views/toast.php'?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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