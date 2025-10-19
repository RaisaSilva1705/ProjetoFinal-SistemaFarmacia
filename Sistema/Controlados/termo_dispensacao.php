<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CONTROLADOS_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$id_prescricao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_prescricao){
    $_SESSION['msg'] = ['texto' => 'ID da prescricao inválido.', 'tipo' => 'warning'];
    header("Location: controlados.php");
    exit();
}

// 1. Busca dados da empresa
$empresa = $conn->query("SELECT Nome_Fantasia, Nome_RazaoSocial FROM CONFIGURACOES LIMIT 1")->fetch_assoc();
$nome_fantasia_farmacia = $empresa['Nome_Fantasia'];
$razao_social_farmacia = $empresa['Nome_RazaoSocial'];

// 2. Busca dados da prescrição
$sql_presc = "SELECT PR.*, F.Nome as Nome_Funcionario 
              FROM PRESCRICOES PR 
              JOIN FUNCIONARIOS F ON PR.ID_Funcionario = F.ID_Funcionario 
              WHERE PR.ID_Prescricao = ?";
$stmt_presc = $conn->prepare($sql_presc);
$stmt_presc->bind_param("i", $id_prescricao);
$stmt_presc->execute();
$prescricao = $stmt_presc->get_result()->fetch_assoc();
$dados_adicionais = json_decode($prescricao['Dados_Adicionais'], true);

// 3. Busca os medicamentos dispensados, que estão nos itens da pré-venda associada
$sql_itens = "SELECT P.Nome AS Nome_Produto, PVI.Quantidade
              FROM PRE_VENDAS_ITENS PVI
              JOIN PRODUTOS P ON PVI.ID_Produto = P.ID_Produto
              JOIN PRE_VENDAS PV ON PVI.ID_PreVenda = PV.ID_PreVenda
              WHERE PV.ID_Prescricao = ?";
$stmt_itens = $conn->prepare($sql_itens);
$stmt_itens->bind_param("i", $id_prescricao);
$stmt_itens->execute();
$itens_dispensados = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);

// Determina o nome do comprador (se não for o paciente)
$nome_comprador = $dados_adicionais['comprador_eh_paciente'] 
    ? ($dados_adicionais['paciente_na_receita'] ?? '') 
    : ($dados_adicionais['comprador_nome'] ?? '');
$doc_comprador = $dados_adicionais['comprador_eh_paciente']
    ? ($prescricao['doc_paciente'] ?? '') // Supondo que você salve o doc do paciente
    : ($dados_adicionais['comprador_doc'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Termo de Dispensação #<?= $id_prescricao ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/termo_dispensacao.css">
    </head>
    <body>
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

       <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                    <h3>Termo de Dispensação</h3>
                </div>
            
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                        <h2 class="m-0">Imprimir Termo de Dispensação</h2>
                        <div>
                            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer-fill"></i> Imprimir</button>
                            <a href="controlados.php" class="btn btn-outline-secondary">Voltar</a>
                        </div>
                    </div>
                    <div class="termo-document mt-4">
                        <div class="termo-header">
                            <h4>TERMO DE CIÊNCIA E DISPENSAÇÃO DE MEDICAMENTO CONTROLADO</h4>
                        </div>
                        
                        <p>Eu, <strong><?= strtoupper(htmlspecialchars($nome_comprador)) ?></strong>, portador(a) do documento de identidade nº <strong><?= htmlspecialchars($doc_comprador) ?></strong>, declaro para os devidos fins que recebi da <?= htmlspecialchars($nome_fantasia_farmacia) ?> (<?= htmlspecialchars($razao_social_farmacia) ?>), o(s) seguinte(s) medicamento(s), mediante apresentação da prescrição do(a) profissional <strong><?= htmlspecialchars($prescricao['Nome_Profissional']) ?></strong> (<?= htmlspecialchars($prescricao['Conselho'] . ' ' . $prescricao['Num_Conselho']) ?>), emitida em <?= date('d/m/Y', strtotime($prescricao['Data_Receita'])) ?>.</p>

                        <h5 class="mt-4 text-center">Medicamentos Dispensados</h5>
                        <table class="table table-bordered table-sm mt-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicamento</th>
                                    <th class="text-center">Quantidade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($itens_dispensados as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['Nome_Produto']) ?></td>
                                    <td class="text-center"><?= $item['Quantidade'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <p class="mt-4">Declaro ainda ter recebido todas as orientações necessárias sobre o uso correto, os potenciais efeitos adversos e as condições de armazenamento do(s) medicamento(s) acima listado(s).</p>
                        
                        <div class="assinatura">
                            <p class="mb-0"><?= strtoupper(htmlspecialchars($nome_comprador)) ?></p>
                            <small>(Assinatura do Comprador/Responsável)</small>
                        </div>
                        <div class="assinatura">
                            <p class="mb-0"><?= strtoupper(htmlspecialchars($prescricao['Nome_Funcionario'])) ?></p>
                            <small>(Assinatura do Farmacêutico)</small>
                        </div>
                    </div>
                </div>
                <?php include_once DEV_PATH . 'Views/footer.php'?>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>


