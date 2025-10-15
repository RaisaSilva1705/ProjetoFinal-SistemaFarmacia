<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CLIENTES_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$id_cliente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_cliente) {
    $_SESSION['msg'] = ['texto' => 'ID do cliente inválido.', 'tipo' => 'warning'];
    header("Location: clientes.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM CLIENTES WHERE ID_Cliente = ?");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) 
    $cliente = $result->fetch_assoc();
else {
    $_SESSION['msg'] = ['texto' => 'Cliente não encontrado.', 'tipo' => 'danger'];
    header("Location: clientes.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coleta os dados do formulário
    $nome = $_POST['nome'];
    $tipo = $_POST['tipo'];
    $documento = $_POST['documento'];
    $tel = $_POST['tel'];
    $email = $_POST['email'];
    $status = $_POST['status'];
    $obs = $_POST['obs'];

    // Lógica para atualizar a senha apenas se uma nova for fornecida
    if (!empty($_POST['senha'])) {
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $sql = "UPDATE CLIENTES SET Nome = ?, Tipo = ?, Documento = ?, Tel = ?, Email = ?, Senha = ?, Status = ?, OBS = ? WHERE ID_Cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $nome, $tipo, $documento, $tel, $email, $senha, $status, $obs, $id_cliente);
    } 
    else {
        $sql = "UPDATE CLIENTES SET Nome = ?, Tipo = ?, Documento = ?, Tel = ?, Email = ?, Status = ?, OBS = ? WHERE ID_Cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $nome, $tipo, $documento, $tel, $email, $status, $obs, $id_cliente);
    }

    if ($stmt->execute()) {
        registrar_log($conn, $_SESSION['ID_Usuario'], "Editou o cliente '{$nome}' (ID: {$id_cliente})");
        $_SESSION['msg'] = ['texto' => 'Cliente atualizado com sucesso!', 'tipo' => 'success'];
        header("Location: clientes.php");
        exit();
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao atualizar cliente: ' . $stmt->error, 'tipo' => 'danger'];
}

$is_edit = true; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edição de Cliente</title>
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
                    <h3>Editar Cliente: <?= htmlspecialchars($cliente['Nome']) ?></h3>
                </div>
            
                <div class="container p-5">
                    <?php include '_form_cliente.php'; ?>
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