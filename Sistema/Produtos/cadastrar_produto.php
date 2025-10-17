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

// Busca categorias
$sqlCategorias = "SELECT ID_Categoria, Categoria FROM CATEGORIAS";
$categorias = $conn->query($sqlCategorias);

// Busca unidades
$sqlUnidades = "SELECT ID_Unidade, Unidade FROM UNIDADES";
$unidades = $conn->query($sqlUnidades);

// Busca categorias de medicamentos
$sqlCategoriasMed = "SELECT ID_CategoriaMed, Categoria_Med FROM CATEGORIAS_MEDICAMENTOS";
$categoriasMed = $conn->query($sqlCategoriasMed);

// Busca tarjas dos medicamentos
$sqlTarjasMed = "SELECT ID_Tarja, Tarja FROM TARJAS_MEDICAMENTOS";
$tarjasMed = $conn->query($sqlTarjasMed);

// Busca fornecedores
$sqlFornecedores = "SELECT ID_Fornecedor, Nome_Fantasia FROM FORNECEDORES";
$fornecedores = $conn->query($sqlFornecedores);

$is_edit = false;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cadastrar Produto</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Sidebar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Cadastrar novo Produto</h3>
                </div>
    
                <!-- Formulário de Cadastro -->
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