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
    
                <div class="container p-5">
                    <?php include '_form_produto.php'; ?>
                </div>

                <!-- Footer -->
                <?php include_once DEV_PATH . 'Views/footer.php'; ?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-box-seam-fill"></i> Cadastro de Novo Produto</h4>
            <hr>
            <p>Este é o formulário mais detalhado do sistema, usado para registrar um novo item no seu inventário. O preenchimento correto de todas as informações é crucial para a gestão fiscal, de estoque e de vendas.</p>

            <h6><i class="bi bi-card-list"></i> Informações do Produto</h6>
            <p>Preencha os dados básicos do item:</p>
            <ul>
                <li><strong>Nome, Fornecedor, Marca, Categoria e Unidade:</strong> Informações essenciais para identificação e organização.</li>
                <li><strong>Quantidade Mínima:</strong> Defina o número mínimo de unidades em estoque. Quando a quantidade total for menor que este número, o produto será destacado em vermelho na listagem.</li>
                <li><strong>Foto do Produto:</strong> Envie uma imagem para facilitar a identificação visual no sistema.</li>
            </ul>

            <h6><i class="bi bi-capsule-pill"></i> Informações do Medicamento (Campos Dinâmicos)</h6>
            <p class="alert alert-info"><strong>Funcionalidade Inteligente:</strong> Se você selecionar a <strong>Categoria "Medicamento"</strong>, uma nova seção aparecerá no formulário com campos específicos para medicamentos, como <strong>Princípio Ativo, Tarja, MS e se é Controlado</strong>. Para outros tipos de produto, esta seção fica oculta.</p>

            <h6><i class="bi bi-receipt"></i> Informações Fiscais</h6>
            <p>Esta seção é de extrema importância para a correta emissão de notas e para a conformidade fiscal do seu negócio. Preencha os campos como <strong>NCM, EAN/GTIN e os códigos CST</strong> com base nas informações fornecidas pelo seu contador ou pelo fabricante do produto.</p>

            <h6><i class="bi bi-check-circle-fill"></i> Salvar</h6>
            <p>Após preencher todos os dados obrigatórios, clique em <strong>"Cadastrar Produto"</strong> para finalizar.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
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