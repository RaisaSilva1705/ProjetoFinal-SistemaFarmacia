<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'PRODUTOS_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$id_produto = filter_input(INPUT_GET, 'codigo', FILTER_VALIDATE_INT);
if (!$id_produto) { 
    header("Location: produtos.php"); 
    exit();
}

$produto = null;
$medicamento = null;

// Busca os dados do produto
$stmt = $conn->prepare("SELECT * FROM PRODUTOS WHERE ID_Produto = ?");
$stmt->bind_param("i", $id_produto);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $produto = $result->fetch_assoc();
    if ($produto['ID_Categoria'] == 1) { 
        $stmtMed = $conn->prepare("SELECT * FROM MEDICAMENTOS WHERE ID_Produto = ?");
        $stmtMed->bind_param("i", $id_produto);
        $stmtMed->execute();
        $medicamento = $stmtMed->get_result()->fetch_assoc();
    }
} 
else {
    $_SESSION["msg"] = ['texto' => 'Produto não encontrado.', 'tipo' => 'danger'];
    header("Location: produtos.php");
    exit();
}

$categorias = $conn->query("SELECT ID_Categoria, Categoria FROM CATEGORIAS ORDER BY Categoria");
$unidades = $conn->query("SELECT ID_Unidade, Unidade FROM UNIDADES ORDER BY Unidade");
$fornecedores = $conn->query("SELECT ID_Fornecedor, Nome_Fantasia FROM FORNECEDORES WHERE Status = 'Ativo' ORDER BY Nome_Fantasia");
$categoriasMed = $conn->query("SELECT ID_CategoriaMed, Categoria_Med FROM CATEGORIAS_MEDICAMENTOS ORDER BY Categoria_Med");
$tarjasMed = $conn->query("SELECT ID_Tarja, Tarja FROM TARJAS_MEDICAMENTOS ORDER BY Tarja");

$is_edit = true;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Editar Produto</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Sidebar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Editar Produto</h3>
                </div>
    
                <!-- Formulário de Edição -->
                <div class="container p-5">
                    <?php include '_form_produto.php'; ?>
                </div>
            </div>

            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const categoria = document.getElementById("id_categoria");
                const camposMedicamento = document.getElementById("campos_medicamento");

                function toggleCamposMedicamento() {
                    const selectedOption = categoria.options[categoria.selectedIndex];
                    const nomeCategoria = selectedOption.dataset.nomeCategoria || '';

                    if (nomeCategoria.toLowerCase() === "medicamento") 
                        camposMedicamento.style.display = "block";
                    else
                        camposMedicamento.style.display = "none";
                }

                toggleCamposMedicamento();
                categoria.addEventListener("change", toggleCamposMedicamento);
            });
            
            function mostrarToast(texto, tipo = 'success', titulo = 'Notificação') {
                const toastLiveExample = document.getElementById('liveToast');
                const toastHeader = toastLiveExample.querySelector('.toast-header');
                
                // Define o título padrão baseado no tipo, se não for fornecido
                if (titulo === 'Notificação') 
                    titulo = ucfirst(tipo === 'danger' ? 'Erro' : (tipo === 'warning' ? 'Atenção' : 'Sucesso'));
                
                const headerClass = `text-bg-${tipo}`;

                document.getElementById('toastTitulo').innerText = titulo;
                document.getElementById('toastCorpo').innerText = texto;
                
                // Remove classes de cor antigas e adiciona a nova
                toastHeader.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning', 'text-bg-info');
                toastHeader.classList.add(headerClass);

                const toast = new bootstrap.Toast(toastLiveExample);
                toast.show();
            }

            // Função auxiliar para deixar a primeira letra maiúscula 
            function ucfirst(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }

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
