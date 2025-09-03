<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

// Verificar se o parâmetro "codigo" foi passado pela URL
if (isset($_GET['codigo'])) {
    $id_produto = intval($_GET['codigo']);

    // Buscar dados do produto
    $sqlProduto = "SELECT * FROM PRODUTOS WHERE ID_Produto = ?";
    $stmt = $conn->prepare($sqlProduto);
    $stmt->bind_param("i", $id_produto);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $produto = $result->fetch_assoc();

        // Se for medicamento, buscar também os dados
        $medicamento = null;
        if ($produto['ID_Categoria'] == 1) {
            $sqlMedicamento = "SELECT * FROM MEDICAMENTOS WHERE ID_Produto = ?";
            $stmtMed = $conn->prepare($sqlMedicamento);
            $stmtMed->bind_param("i", $id_produto);
            $stmtMed->execute();
            $resMed = $stmtMed->get_result();
            if ($resMed->num_rows > 0)
                $medicamento = $resMed->fetch_assoc();
        }

        // Categorias
        $sqlCategorias = "SELECT ID_Categoria, Categoria FROM CATEGORIAS";
        $categorias = $conn->query($sqlCategorias);

        // Unidades
        $sqlUnidades = "SELECT ID_Unidade, Unidade FROM UNIDADES";
        $unidades = $conn->query($sqlUnidades);

        // Categorias de medicamentos
        $sqlCategoriasMed = "SELECT ID_CategoriaMed, Categoria_Med FROM CATEGORIAS_MEDICAMENTOS";
        $categoriasMed = $conn->query($sqlCategoriasMed);

        // Tarjas de medicamentos
        $sqlTarjasMed = "SELECT ID_Tarja, Tarja FROM TARJAS_MEDICAMENTOS";
        $tarjasMed = $conn->query($sqlTarjasMed);
    }
    else {
        $_SESSION["msg"] = ['texto' => 'Unidade não encontrada.', 'tipo' => 'danger'];
        header("Location: produtos.php");
        exit();
    }
}
else {
    $_SESSION["msg"] = ['texto' => 'Código da unidade não fornecida.', 'tipo' => 'warning'];
    header("Location: produtos.php");
    exit();
}

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recebe dados do produto
    $nome = $_POST['nome'];
    $id_fornecedor = $_POST['id_fornecedor'];
    $descricao = $_POST['descricao'];
    $id_categoria = $_POST['id_categoria'];
    $id_unidade = $_POST['id_unidade'];
    $quant_minima = $_POST['quant_minima'];
    $obs = $_POST['obs'];
    $status = $_POST['status'];
    $ncm = $_POST['ncm'];
    $ean_gtin = $_POST['ean_gtin'];
    $cbenef = $_POST['cbenef'];
    $cest = $_POST['cest'];
    $extipi = $_POST['extipi'];
    $cfop = $_POST['cfop'];
    $mva = $_POST['mva'];
    $nfci = $_POST['nfci'];

    // Atualiza foto se enviada
    $foto = $produto['Foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $foto_nome = uniqid() . "_" . basename($_FILES["foto"]["name"]);
        $foto_destino = DEV_PATH . "Imagens/" . $foto_nome;

        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $foto_destino)) 
            $foto = $foto_nome;
    }

    // Atualiza tabela PRODUTOS
    $sqlUpdate = "UPDATE PRODUTOS 
                  SET ID_Categoria = ?, Nome = ?, ID_Fornecedor = ?, Descricao = ?, ID_Unidade = ?, 
                      Quant_Minima = ?, Status = ?, OBS = ?, NCM = ?, EAN_GTIN = ?, CBENEF = ?, 
                      CEST = ?, EXTIPI = ?, CFOP = ?, MVA = ?, NFCI = ?, Foto = ?
                  WHERE ID_Produto = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("isisiisssssssidssi",
        $id_categoria, $nome, $id_fornecedor, $descricao, $id_unidade,
        $quant_minima, $status, $obs, $ncm, $ean_gtin, $cbenef, $cest,
        $extipi, $cfop, $mva, $nfci, $foto, $id_produto
    );

    if ($stmtUpdate->execute()) {
        if ($id_categoria == 1) {
            // Dados medicamento
            $id_categoria_med = $_POST['id_categoria_med'];
            $prin_ativo = $_POST['prin_ativo'];
            $id_tarja = $_POST['id_tarja_med'];
            $tipo = $_POST['tipo_med'];

            if ($medicamento) {
                $sqlUpdateMed = "UPDATE MEDICAMENTOS 
                                 SET ID_CategoriaMed = ?, ID_Tarja = ?, Tipo = ?, Prin_Ativo = ?
                                 WHERE ID_Produto = ?";
                $stmtUpdateMed = $conn->prepare($sqlUpdateMed);
                $stmtUpdateMed->bind_param("iissi", $id_categoria_med, $id_tarja, $tipo, $prin_ativo, $id_produto);
                $stmtUpdateMed->execute();
            } 
            else {
                $sqlInsertMed = "INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo)
                                 VALUES (?, ?, ?, ?, ?)";
                $stmtInsertMed = $conn->prepare($sqlInsertMed);
                $stmtInsertMed->bind_param("iiiss", $id_produto, $id_categoria_med, $id_tarja, $tipo, $prin_ativo);
                $stmtInsertMed->execute();
            }
        } 
        else {
            if ($medicamento) 
                $conn->query("DELETE FROM MEDICAMENTOS WHERE ID_Produto = $id_produto");
        }

        $_SESSION["msg"] = ['texto' => 'Produto atualizado com sucesso!', 'tipo' => 'success'];
        header("Location: produtos.php");
        exit();
    } 
    else {
        $_SESSION["msg"] = ['texto' => "Erro ao atualizar produto: " . $stmtUpdate->error, 'tipo' => 'danger'];
        header("Location: editar_produto.php");
        exit();
    }
}

$is_edit = true;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Editar Produto</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
        <style>
            select > option:first-child {
                display: none;
            }
        </style>
    </head>
    <body>
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Editar Produto</h3>
                </div>
    
                <!-- Formulário de Edição -->
                <div class="container p-4">
                    <?php include '_form_produto.php'; ?>
                </div>
            </div>

            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
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

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

            // Função auxiliar para deixar a primeira letra maiúscula (o PHP faz isso, o JS não)
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
