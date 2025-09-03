<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$id_fornecedor = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$fornecedor = null;

if (!$id_fornecedor) {
    $_SESSION['msg'] = ['texto' => 'ID do fornecedor inválido.', 'tipo' => 'warning'];
    header("Location: fornecedores.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM FORNECEDORES WHERE ID_Fornecedor = ?");
$stmt->bind_param("i", $id_fornecedor);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) 
    $fornecedor = $result->fetch_assoc();
else {
    $_SESSION['msg'] = ['texto' => 'Fornecedor não encontrado.', 'tipo' => 'danger'];
    header("Location: fornecedores.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coleta os dados do formulário
    $nome_fantasia = $_POST['nome_fantasia'];
    $razao_social = $_POST['razao_social'];
    $cnpj = $_POST['cnpj'];
    $tel = $_POST['tel'];
    $email = $_POST['email'];
    $cep = $_POST['cep'];
    $endereco = $_POST['endereco'];
    $numero = $_POST['numero'];
    $complemento = $_POST['complemento'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    $status = $_POST['status'];
    $obs = $_POST['obs'];

    // Prepara e executa a atualização
    $sql = "UPDATE FORNECEDORES SET Nome_Fantasia = ?, Nome = ?, CNPJ = ?, Tel = ?, Email = ?, CEP = ?, Endereco = ?, End_Numero = ?, Complemento = ?, Bairro = ?, Cidade = ?, Estado = ?, Status = ?, OBS = ?
            WHERE ID_Fornecedor = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssssssssi", $nome_fantasia, $razao_social, $cnpj, $tel, $email, $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado, $status, $obs, $id_fornecedor);

    if ($stmt->execute()) {
        registrar_log($conn, $_SESSION['ID_Usuario'], "Editou o fornecedor {$nome_fantasia} (ID: {$id_fornecedor})");
        $_SESSION['msg'] = ['texto' => 'Fornecedor atualizado com sucesso!', 'tipo' => 'success'];
        header("Location: fornecedores.php");
        exit();
    } 
    else
        $_SESSION['msg'] = ['texto' => 'Erro ao atualizar fornecedor: ' . $stmt->error, 'tipo' => 'danger'];
    
}

$is_edit = true; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edição de Fornecedor</title>
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
                    <h3>Editar Fornecedor: <?= htmlspecialchars($fornecedor['Nome_Fantasia']) ?></h3>
                </div>
            
                <div class="container p-5">
                    <?php include '_form_fornecedor.php'; ?>
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