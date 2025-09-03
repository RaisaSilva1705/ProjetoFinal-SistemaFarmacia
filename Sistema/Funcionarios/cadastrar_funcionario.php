<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $documento = $_POST['documento'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $id_cargo = $_POST['id_cargo'];
    $salario = $_POST['salario'];
    $data_admissao = $_POST['data_admissao'];
    $status = $_POST['status'];
    $obs = $_POST['obs'];

    $usuario = $_POST['usuario'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $conn->begin_transaction();
    try {
        $sqlFunc = "INSERT INTO FUNCIONARIOS (Nome, Documento, Telefone, Email, ID_Cargo, Salario, Data_Admissao, Status, OBS)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtFunc = $conn->prepare($sqlFunc);
        $stmtFunc->bind_param("ssssidsss", $nome, $documento, $telefone, $email, $id_cargo, $salario, $data_admissao, $status, $obs);
        $stmtFunc->execute();
        $id_funcionario_novo = $conn->insert_id;
        if ($id_funcionario_novo == 0) throw new Exception("Falha ao criar o registro do funcionário.");

        $sqlUser = "INSERT INTO USUARIOS (ID_Funcionario, Usuario, Senha, Status) VALUES (?, ?, ?, ?)";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("isss", $id_funcionario_novo, $usuario, $senha, $status);
        $stmtUser->execute();

        $conn->commit();
        $novo_funcionario = $stmt->insert_id;
        registrar_log($conn, $_SESSION['ID_Usuario'], "Cadastrou o funcionário {$nome} (ID: {$novo_funcionario})");
        $_SESSION['msg'] = ['texto' => 'Funcionário e usuário criados com sucesso!', 'tipo' => 'success'];
        header("Location: funcionarios.php");
        exit();
    } 
    catch (Exception $e) {
        $conn->rollback();
        $_SESSION['msg'] = ['texto' => 'Erro ao cadastrar funcionário: ' . $e->getMessage(), 'tipo' => 'danger'];
    }
}

$is_edit = false;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cadastro de Funcionário</title>
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
                    <h3>Cadastro de Funcionário</h3>
                </div>
            
                <div class="container p-5">
                    <?php include '_form_funcionario.php'; ?>
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