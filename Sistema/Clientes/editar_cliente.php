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

$stmt_docs = $conn->prepare("SELECT ID_Documento, Tipo, Numero FROM CLIENTES_DOCUMENTOS WHERE ID_Cliente = ? ORDER BY ID_Documento");
$stmt_docs->bind_param("i", $id_cliente);
$stmt_docs->execute();
$documentos_cliente = $stmt_docs->get_result()->fetch_all(MYSQLI_ASSOC);

$is_edit = true; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edição de Cliente</title>
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
                    <h3>Editar Cliente: <?= htmlspecialchars($cliente['Nome']) ?></h3>
                </div>
            
                <div class="container p-5">
                    <?php include '_form_cliente.php'; ?>
                </div>

                <!-- Footer -->
                <?php include_once DEV_PATH . 'Views/footer.php'?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-person-lines-fill"></i> Edição de Cliente</h4>
            <hr>
            <p>Utilize esta tela para atualizar ou corrigir as informações de um cliente já cadastrado no sistema.</p>

            <h6><i class="bi bi-pencil-square"></i> O que você pode alterar?</h6>
            <ul>
                <li><strong>Dados Pessoais:</strong> Altere o nome, tipo de pessoa, data de nascimento, sexo, gênero, telefone, email e observações.</li>
                <li><strong>Documentos:</strong> Você pode adicionar novos documentos clicando em <strong>"Adicionar Documento"</strong>, remover documentos existentes clicando no ícone de lixeira <i class="bi bi-trash"></i>, ou corrigir o número de um documento já cadastrado.</li>
                <li><strong>Status:</strong> Altere o status do cliente para <strong>Ativo</strong> ou <strong>Inativo</strong>. Um cliente inativo não poderá realizar novas compras.</li>
                <li><strong>Senha:</strong> Para alterar a senha do cliente, basta digitar uma nova no campo "Senha". <strong>Deixe este campo em branco se não desejar alterar a senha atual.</strong></li>
            </ul>

            <h6><i class="bi bi-check-circle-fill"></i> Salvar</h6>
            <p>Após realizar todas as alterações necessárias, clique em <strong>"Salvar Alterações"</strong> para que as novas informações sejam registradas no sistema.</p>
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