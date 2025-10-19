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

$stmt = $conn->prepare("SELECT F.*, U.Usuario FROM FUNCIONARIOS F JOIN USUARIOS U ON F.ID_Funcionario = U.ID_Funcionario WHERE F.ID_Funcionario = ?");
$stmt->bind_param("i", $id_funcionario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $funcionario = $result->fetch_assoc();
    $usuario = ['Usuario' => $funcionario['Usuario']]; 
} 
else {
    $_SESSION['msg'] = ['texto' => 'Funcionário não encontrado.', 'tipo' => 'danger'];
    header("Location: funcionarios.php"); 
    exit();
}

$is_edit = true;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edição de Funcionário</title>
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
                    <h3>Editar Funcionário</h3>
                </div>
            
                <div class="container p-5">
                    <?php include '_form_funcionario.php'; ?>
                </div>

                <!-- Footer -->
                <?php include_once DEV_PATH . 'Views/footer.php'?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-person-lines-fill"></i> Edição de Funcionário</h4>
            <hr>
            <p>Utilize esta tela para atualizar as informações cadastrais, contratuais e de acesso de um funcionário já registrado.</p>

            <h6><i class="bi bi-pencil-square"></i> O que você pode alterar?</h6>
            <ul>
                <li><strong>Dados Pessoais e Contrato:</strong> Todos os campos, como nome, telefone, cargo e salário, podem ser atualizados.</li>
                <li><strong>Status:</strong> Altere o status do funcionário para <strong>Ativo</strong> ou <strong>Inativo</strong>. A mudança de status aqui afetará também a capacidade de login do usuário.</li>
                <li><strong>Acesso ao Sistema:</strong>
                    <ul>
                        <li>O <strong>Nome de Usuário</strong> não pode ser alterado por questões de segurança e integridade.</li>
                        <li>Para alterar a <strong>Senha</strong> do usuário, basta digitar uma nova no campo correspondente. <strong>Deixe este campo em branco se não desejar alterar a senha atual.</strong></li>
                    </ul>
                </li>
            </ul>

            <h6><i class="bi bi-save-fill"></i> Salvar</h6>
            <p>Após realizar as modificações, clique em <strong>"Salvar Alterações"</strong> para atualizar o registro do funcionário e seu usuário de acesso.</p>
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