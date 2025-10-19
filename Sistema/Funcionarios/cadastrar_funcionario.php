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

$is_edit = false;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cadastro de Funcionário</title>
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
                    <h3>Cadastro de Funcionário</h3>
                </div>
            
                <div class="container p-5">
                    <?php include '_form_funcionario.php'; ?>
                </div>

                <!-- Footer -->
                <?php include_once DEV_PATH . 'Views/footer.php'?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-person-plus-fill"></i> Cadastro de Novo Funcionário</h4>
            <hr>
            <p>Este formulário é utilizado para registrar um novo membro da equipe no sistema. O cadastro é dividido em duas partes: os dados do funcionário e as credenciais para o seu acesso ao software.</p>

            <h6><i class="bi bi-card-list"></i> Passo 1: Dados Pessoais e Contrato</h6>
            <p>Preencha as informações cadastrais e contratuais do funcionário:</p>
            <ul>
                <li><strong>Nome Completo, CPF, Telefone e Email:</strong> Dados básicos de identificação.</li>
                <li><strong>Cargo:</strong> Selecione o cargo que o funcionário ocupará. Esta escolha é <strong>crucial</strong>, pois definirá a quais módulos e funcionalidades ele terá acesso.</li>
                <li><strong>Salário e Data de Admissão:</strong> Informações para fins de registro e gestão de RH.</li>
            </ul>

            <h6><i class="bi bi-key-fill"></i> Passo 2: Acesso ao Sistema</h6>
            <p>Crie as credenciais para que o funcionário possa fazer login no sistema:</p>
            <ul>
                <li><strong>Usuário (Login):</strong> Defina um nome de usuário único. <strong>Atenção:</strong> este nome não poderá ser alterado após o cadastro.</li>
                <li><strong>Senha:</strong> Crie uma senha inicial segura para o primeiro acesso. O funcionário poderá alterá-la posteriormente.</li>
            </ul>

            <h6><i class="bi bi-check-circle-fill"></i> Passo 3: Salvar</h6>
            <p>Após preencher todos os dados, clique em <strong>"Cadastrar Funcionário"</strong>. O sistema criará o registro do funcionário e o seu respectivo usuário de acesso simultaneamente.</p>
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