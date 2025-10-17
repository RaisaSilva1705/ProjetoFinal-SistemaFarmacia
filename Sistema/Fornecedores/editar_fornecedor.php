<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'FORNECEDORES_GERENCIAR');
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

$is_edit = true; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edição de Fornecedor</title>
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
                    <h3>Editar Fornecedor: <?= htmlspecialchars($fornecedor['Nome_Fantasia']) ?></h3>
                </div>
            
                <div class="container p-5">
                    <?php include '_form_fornecedor.php'; ?>
                </div>
            </div>
        
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
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