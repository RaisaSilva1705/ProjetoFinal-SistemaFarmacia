<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CLIENTES_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$is_edit = false;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cadastro de Cliente</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

       <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Cadastro de Cliente</h3>
                </div>
            
                <div class="container p-5">
                    <?php include '_form_cliente.php'; ?>
                </div>

                <!-- Footer -->
                <?php include_once DEV_PATH . 'Views/footer.php'?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-person-plus-fill"></i> Cadastro de Novo Cliente</h4>
            <hr>
            <p>Esta tela é usada para adicionar novos clientes à base de dados do sistema. Preencha as informações de forma completa para garantir um bom relacionamento e agilizar futuros atendimentos.</p>

            <h6><i class="bi bi-card-list"></i> Passo 1: Dados Pessoais</h6>
            <ul>
                <li><strong>Nome Completo:</strong> Insira o nome do cliente ou a razão social, caso seja uma empresa.</li>
                <li><strong>Tipo de Pessoa:</strong> Selecione "Pessoa Física (PF)" para clientes individuais ou "Pessoa Jurídica (PJ)" para empresas.</li>
                <li><strong>Data de Nascimento:</strong> Essencial para o cálculo da idade, útil em serviços farmacêuticos e promoções de aniversário.</li>
                <li><strong>Sexo Biológico e Gênero:</strong> Preencha o sexo biológico para fins de referência de saúde (exames, etc.) e o gênero para um atendimento mais respeitoso e personalizado.</li>
            </ul>

            <h6><i class="bi bi-file-earmark-text-fill"></i> Passo 2: Documentos</h6>
            <ul>
                <li>Por padrão, um campo para CPF é exibido. Selecione o tipo de documento e preencha o número.</li>
                <li>Clique em <strong>"Adicionar Documento"</strong> para inserir outros documentos relevantes, como RG ou CNH.</li>
            </ul>

            <h6><i class="bi bi-telephone-fill"></i> Passo 3: Contato e Outras Informações</h6>
            <ul>
                <li>Preencha o <strong>Telefone</strong> e <strong>Email</strong> do cliente.</li>
                <li>Use o campo de <strong>Observações</strong> para registrar qualquer informação adicional relevante.</li>
            </ul>
            
            <h6><i class="bi bi-key-fill"></i> Passo 4: Segurança</h6>
            <ul>
                <li>Crie uma <strong>Senha</strong> para o cliente. Esta senha pode ser utilizada futuramente em um portal do cliente ou para validações.</li>
            </ul>

            <h6><i class="bi bi-check-circle-fill"></i> Passo 5: Salvar</h6>
            <p>Após preencher todos os dados, clique em <strong>"Cadastrar Cliente"</strong> para finalizar o processo.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'?>
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