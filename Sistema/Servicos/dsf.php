<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'SERVICOS_GERENCIAR');

$id_registro = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_registro) die("Registro de serviço não encontrado.");

$empresa = $conn->query("SELECT * FROM CONFIGURACOES WHERE ID_Config = 1")->fetch_assoc();
$cnes_empresa = $empresa['CNES'] ?? '[PREENCHER CNES]'; 

$sql = "SELECT 
            RS.*, 
            S.Nome_Servico,
            C.Nome as Nome_Cliente, 
            C.Documento as Doc_Cliente, 
            C.Email as Email_Cliente, 
            C.Tel as Tel_Cliente, 
            C.Data_Nascimento as DN_Cliente, 
            C.Sexo as Sexo_Cliente,
            F.Nome as Nome_Funcionario,
            F.CRF
        FROM REGISTRO_SERVICOS RS
        JOIN SERVICOS_FARMACEUTICOS S ON RS.ID_Servico = S.ID_Servico
        JOIN FUNCIONARIOS F ON RS.ID_Funcionario = F.ID_Funcionario
        LEFT JOIN CLIENTES C ON RS.ID_Cliente = C.ID_Cliente
        WHERE RS.ID_Registro_Servico = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_registro);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
if (!$registro) die("Registro de serviço não encontrado.");

$endereco_cliente_formatado = "Endereço não cadastrado.";
if (!empty($registro['ID_Cliente'])) {
    $stmtEnd = $conn->prepare("SELECT * FROM CLI_ENDERECOS WHERE ID_Cliente = ? LIMIT 1");
    $stmtEnd->bind_param("i", $registro['ID_Cliente']);
    $stmtEnd->execute();
    $endereco = $stmtEnd->get_result()->fetch_assoc();
    if ($endereco) {
        $endereco_cliente_formatado = "{$endereco['Endereco']}, {$endereco['End_Numero']}" . 
                                     ($endereco['Complemento'] ? ", {$endereco['Complemento']}" : "") . 
                                     ", {$endereco['Bairro']}, {$endereco['Cidade']} - {$endereco['Estado']} CEP: {$endereco['CEP']}";
    }
}

$dados_servico = json_decode($registro['Dados_Servico'], true);

$stmt_campos = $conn->prepare("SELECT Name_Campo, Label_Campo, Unidade_Medida, Tipo_Campo FROM SERVICO_CAMPOS WHERE ID_Servico = ? ORDER BY Ordem");
$stmt_campos->bind_param("i", $registro['ID_Servico']);
$stmt_campos->execute();
$campos_ordenados = $stmt_campos->get_result()->fetch_all(MYSQLI_ASSOC);

$autoriza_dados = $dados_servico['autoriza_uso_dados'] ?? 'Não informado';
$encaminhado_medico = $dados_servico['encaminhado_medico'] ?? 'Não informado';

unset($dados_servico['autoriza_uso_dados']);
unset($dados_servico['encaminhado_medico']);

$nome_paciente = $registro['Nome_Cliente'] ?? $registro['Nome_Paciente'];
$doc_paciente = $registro['Doc_Cliente'] ?? $registro['Doc_Paciente'];
$email_paciente = $registro['Email_Cliente'] ?? '____________';
$tel_paciente = $registro['Tel_Cliente'] ?? '(__) _________';
$dn_paciente = $registro['DN_Cliente'] ?? $registro['Data_Nascimento_Paciente'];
$sexo_paciente = $registro['Sexo_Cliente'] ?? $registro['Sexo_Paciente'];
$data_atendimento = new DateTime($registro['DataHora']);
$crf = $registro['CRF'] ?? '__________';
$nome_responsavel = $dados_servico['nome_responsavel'] ?? null;
$doc_responsavel = $dados_servico['doc_responsavel'] ?? null;
unset($dados_servico['nome_responsavel']);
unset($dados_servico['doc_responsavel']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSF Nº <?= $id_registro ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/dsf.css">
</head>
    <body>
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content">
            <div class="container-fluid bg-secondary text-white text-center p-4 no-print">
                <h3>Declaração de Serviço Farmacêutico</h3>
                <button onclick="window.print()" class="btn btn-primary mt-2">Imprimir Declaração</button>
                <a href="servicos.php" class="btn btn-light mt-2">Voltar</a>
            </div>

            <div class="dsf-document">
                <header class="dsf-header">
                    <div class="left-col">
                        <?= htmlspecialchars($empresa['Nome_Fantasia']) ?><br>
                        Declaração de serviços farmacêuticos<br>
                        Data e horário do atendimento: <?= $data_atendimento->format('d/m/Y H:i') ?>
                    </div>
                    <div class="right-col">
                        CNES: <?= htmlspecialchars($cnes_empresa) ?><br>
                        Loja: <?= htmlspecialchars($empresa['Loja']) ?><br>
                        CNPJ: <?= htmlspecialchars($empresa['Documento']) ?><br>
                        <?= htmlspecialchars($empresa['Endereco']) ?>, <?= htmlspecialchars($empresa['End_Numero']) ?>, <?= htmlspecialchars($empresa['Bairro']) ?><br>
                        <?= htmlspecialchars($empresa['Cidade']) ?> - <?= htmlspecialchars($empresa['Estado']) ?><br>
                        Celular: <?= htmlspecialchars($empresa['Telefone']) ?>
                    </div>
                </header>

                <section class="dsf-section">
                    <h6>DADOS DO PACIENTE</h6>
                    <div class="info-pair">
                        <span><strong><?= strtoupper(htmlspecialchars($nome_paciente)) ?></strong></span>
                        <span><strong>CPF:</strong> <?= htmlspecialchars($doc_paciente) ?></span>
                    </div>
                    <div class="info-pair">
                        <span><strong>Email:</strong> <?= htmlspecialchars($email_paciente) ?></span>
                        <span><strong>Telefone:</strong> <?= htmlspecialchars($tel_paciente) ?></span>
                    </div>
                    <div class="info-pair">
                        <span><strong>Data de Nascimento:</strong> <?= $dn_paciente ? date('d/m/Y', strtotime($dn_paciente)) : '[NÃO INFORMADO]' ?></span>
                        <span><strong>Sexo biológico:</strong> <?= htmlspecialchars($sexo_paciente) ?></span>
                    </div>
                    <div>
                        <strong>Endereço:</strong><br>
                        <?= htmlspecialchars($endereco_cliente_formatado) ?>
                    </div>
                </section>
                
                <div class="section-title"><?= strtoupper(htmlspecialchars($registro['Nome_Servico'])) ?></div>
                
                <section class="dsf-section">
                    <h6>RESULTADOS E PARÂMETROS</h6>
                    <?php foreach($campos_ordenados as $campo): ?>
                        <?php 
                            $name = $campo['Name_Campo'];
                            if(isset($dados_servico[$name]) && !empty($dados_servico[$name])):
                                $label = $campo['Label_Campo'];
                                $valor = $dados_servico[$name]; 
                                if ($campo['Tipo_Campo'] === 'date' && !empty($valor))
                                    $valor = (new DateTime($valor))->format('d/m/Y');
                                $unidade = $campo['Unidade_Medida'];
                        ?>
                            <div class="info-pair">
                                <div>
                                    <span><strong><?= htmlspecialchars($label) ?>:</strong></span>
                                    <span><?= htmlspecialchars($valor) ?> <?= htmlspecialchars($unidade) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </section>

                <section class="dsf-section">
                    <h6>Dados do Prescritor</h6>
                    <div class="info-pair">
                        <div>
                            <span><strong>Nome:</strong> <?php echo "Nenhum."; ?></span><br>
                            <span><strong>Conselho Regional:</strong> <?php echo "Nenhum."; ?></span>
                        </div>
                    </div>
                </section>

                <section class="dsf-section">
                    <h6>OBSERVAÇÕES</h6>
                    <p><?= nl2br(htmlspecialchars($registro['OBS'] ?? 'Sem observações.')) ?></p>
                    <p>O usuário autoriza o uso das informações fornecidas para a assistência farmacêutica, que consiste em ações voltadas para a promoção, a proteção e a recuperação da saúde, seja ela individual ou coletiva, tendo o medicamento como elemento essencial e com o objetivo ao seu acesso ao seu uso racional. Desta forma, o usuário autoriza o uso de suas informações pela <?= htmlspecialchars($empresa['Nome_Fantasia']) ?> para acompanhamento do histórico de avaliações: <strong><?= htmlspecialchars(ucfirst($autoriza_dados)) ?></strong></p>
                    <p>Paciente encaminhado ao médico: <strong><?= htmlspecialchars(ucfirst($encaminhado_medico)) ?></strong></p>
                </section>
                
                <div class="signature-area">
                    <div class="signature-line">
                        <?php if ($nome_responsavel): ?>
                            <strong><?= strtoupper(htmlspecialchars($nome_responsavel)) ?></strong><br>
                            (Responsável Legal por <?= strtoupper(htmlspecialchars($nome_paciente)) ?>)
                        <?php else: ?>
                            <strong><?= strtoupper(htmlspecialchars($nome_paciente)) ?></strong>
                        <?php endif; ?><br>
                        Declaro que recebi as orientações<br>
                        referentes a este atendimento
                    </div>
                    <div class="signature-line">
                        <strong><?= htmlspecialchars($registro['Nome_Funcionario']) ?></strong><br>
                        CRF: <?= $crf ?><br>
                        <?= htmlspecialchars($empresa['Nome_Fantasia']) ?>
                    </div>
                </div>

                <div class="termo-ciencia">
                    <div class="section-title" style="border:none; margin: 0 0 1rem 0;">TERMO DE CIÊNCIA</div>
                    <p>Eu, <?php if ($nome_responsavel): ?> <?= "<strong>" . strtoupper(htmlspecialchars($nome_responsavel)) . "</strong>" ?>, inscrito(a) no CPF sob o n° <strong><?= htmlspecialchars($doc_responsavel) ?></strong>, responsável legal por <?php endif; ?> <?= "<strong>" . strtoupper(htmlspecialchars($nome_paciente)) . "</strong>" ?> , inscrito(a) no CPF sob o n° <strong><?= htmlspecialchars($doc_paciente) ?></strong>, declaro estar ciente de que o serviço farmacêutico prestado pela <?= htmlspecialchars($empresa['Nome_Fantasia']) ?> é um procedimento de atenção à saúde que não constitui um diagnóstico médico e não substitui uma consulta com um profissional de saúde qualificado. As informações e os resultados obtidos servem como um auxílio no monitoramento da saúde e na promoção do uso racional de medicamentos. Tenho ciência de que os dados coletados serão tratados com sigilo e confidencialidade, conforme a Lei Geral de Proteção de Dados (LGPD), e serão utilizados para fins de assistência farmacêutica. Concordo com o conteúdo do termo e com a realização do procedimento.</p>
                </div>

                <div class="signature-area" style="margin-top: 2rem; justify-content: center;">
                    <div class="signature-line">
                        <?php if ($nome_responsavel): ?>
                            <strong><?= strtoupper(htmlspecialchars($nome_responsavel)) ?></strong><br>
                            (Responsável Legal)
                        <?php else: ?>
                            <strong><?= strtoupper(htmlspecialchars($nome_paciente)) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>

                <footer class="dsf-footer">
                    <?= htmlspecialchars($empresa['Endereco']) ?>, <?= htmlspecialchars($empresa['End_Numero']) ?>, <?= htmlspecialchars($empresa['Bairro']) ?>, CNES: <?= htmlspecialchars($cnes_empresa) ?> - <?= $data_atendimento->format('d/m/Y H:i:s') ?>
                </footer>
            </div>
        </div>
    </body>
</html>