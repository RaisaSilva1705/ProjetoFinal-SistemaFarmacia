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

                <!-- Footer -->
                <?php include_once DEV_PATH . 'Views/footer.php'?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-pencil-square"></i> Edição de Fornecedor</h4>
            <hr>
            <p>Esta tela permite atualizar e corrigir todas as informações de um fornecedor já cadastrado. Mantenha os dados, especialmente de contato e endereço, sempre atualizados.</p>
            
            <h6><i class="bi bi-pencil-fill"></i> O que você pode alterar?</h6>
            <p>Todos os campos do cadastro do fornecedor estão disponíveis para edição, incluindo:</p>
            <ul>
                <li>Dados da Empresa (Razão Social, CNPJ, etc.).</li>
                <li>Informações de Contato (Telefone e Email).</li>
                <li>Endereço completo.</li>
                <li>Status (Ativo/Inativo).</li>
                <li>Observações.</li>
            </ul>

            <h6><i class="bi bi-save-fill"></i> Salvar</h6>
            <p>Após realizar as modificações, clique em <strong>"Salvar Alterações"</strong> para registrar as novas informações no sistema.</p>
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