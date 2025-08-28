<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
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
$tarjaMed = $conn->query($sqlTarjasMed);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Dados gerais do produto
    $nome = $_POST['nome'];
    $marca = $_POST['marca'];
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

    // Foto
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $foto_nome = uniqid() . "_" . basename($_FILES["foto"]["name"]);
        $foto_destino = DEV_PATH . "Imagens/" . $foto_nome;

        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $foto_destino)) {
            $foto = $foto_nome;
        }
    }

    // Inserção na tabela PRODUTOS
    $sql = "INSERT INTO PRODUTOS 
                (ID_Categoria, Nome, Marca, Descricao, ID_Unidade,
                Quant_Minima, Status, OBS, NCM, EAN_GTIN, CBENEF, CEST, EXTIPI, CFOP, MVA, NFCI, Foto) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssiisssssssidss",
        $id_categoria, $nome, $marca, $descricao, $id_unidade,
        $quant_minima, $status, $obs, $ncm, $ean_gtin, $cbenef, $cest,
        $extipi, $cfop, $mva, $nfci, $foto
    );


    if ($stmt->execute()) {
        // Inserção na tabela PRODUTOS
        $id_produto = $stmt->insert_id;
        $quantidadeEstoque = 0;
        $sql = "INSERT INTO ESTOQUE 
                    (ID_Produto, Quantidade, Data_Atualizacao) 
                VALUES (?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $id_produto, $quantidadeEstoque);
        $stmt->execute();

        // Se for medicamento, insere também na tabela MEDICAMENTOS
        if ($id_categoria == 1) {
            $id_categoria_med = $_POST['id_categoria_med'];
            $prin_ativo = $_POST['prin_ativo'];
            $id_tarja = $_POST['id_tarja_med'];
            $tipo = $_POST['tipo_med'];

            $sql_medicamento = "INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo)
                                VALUES (?, ?, ?, ?, ?)";
            $stmt_medicamento = $conn->prepare($sql_medicamento);
            $stmt_medicamento->bind_param("iiiss", $id_produto, $id_categoria_med, $id_tarja, $tipo, $prin_ativo);
            $stmt_medicamento->execute();
        }

        $_SESSION["msg"] = "<div class='alert alert-primary'>Produto cadastrado com sucesso!</div>";
        header("Location: produtos.php");
        exit();
    }
    else {
        echo "Erro ao inserir produto: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cadastro</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
        <style>
            select > option:first-child {
                display: none;
            }
        </style>
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Cadastro de Produto</h3>
                </div>
    
                <!-- Formulário de Edição -->
                <div class="container p-5">
                    <form action="#" method="POST" enctype="multipart/form-data">
                        <h5>Informações do Produto</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome do Produto</label>
                                <input type="text" name="nome" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="marca" class="form-label">Marca</label>
                                <input type="text" name="marca" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="id_categoria" class="form-label">Categoria</label>
                                <select class="form-select" name="id_categoria" id="id_categoria" required>
                                    <option value="">Selecione</option>
                                    <?php while($cat = $categorias->fetch_assoc()): ?>
                                        <option value="<?= $cat['ID_Categoria'] ?>"><?= $cat['Categoria'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
    
                            <div class="col-md-6 mb-3">
                                <label for="descricao" class="form-label">Descrição</label>
                                <input type="text" name="descricao" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="id_unidade" class="form-label">Unidade</label>
                                <select class="form-select" name="id_unidade" id="id_unidade" required>
                                    <option value="">Selecione</option>
                                    <?php while($uni = $unidades->fetch_assoc()): ?>
                                        <option value="<?= $uni['ID_Unidade'] ?>"><?= $uni['Unidade'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="quant_minima" class="form-label">Quantidade Mínima</label>
                                <input type="number" name="quant_minima" id="quant_minima" class="form-control" value="10">
                            </div>
    
                            <div class="col-md-6 mb-3">
                                <label for="obs" class="form-label">Observações</label>
                                <textarea class="form-control" name="obs" id="obs" rows="1"></textarea>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Selecione</option>
                                    <option value="Ativo">Ativo</option>
                                    <option value="Inativo">Inativo</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="foto" class="form-label">Foto (URL ou nome do arquivo)</label>
                                <input type="file" name="foto" class="form-control">
                            </div>
    
                            <!-- Campos de Medicamento -->
                            <div id="campos_medicamento" style="display: none;">
                                <hr>
                                
                                <h5 class="mt-4">Informações do Medicamento</h5>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="id_categoria_med" class="form-label">Categoria Medicamento</label>
                                        <select class="form-select" name="id_categoria_med" id="id_categoria_med">
                                            <option value="">Selecione</option>
                                            <?php while($catMed = $categoriasMed->fetch_assoc()): ?>
                                                <option value="<?= $catMed['ID_CategoriaMed'] ?>"><?= $catMed['Categoria_Med'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="tipo_med" class="form-label">Tipo</label>
                                        <select name="tipo_med" class="form-select" id="tipo_med">
                                            <option value="">Selecione</option>
                                            <option value="Genérico">Genérico</option>
                                            <option value="Similar">Similar</option>
                                            <option value="Referência">Referência</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="id_tarja_med" class="form-label">Tarja</label>
                                        <select class="form-select" name="id_tarja_med" id="id_tarja_med">
                                            <option value="">Selecione</option>
                                            <?php while($tjMed = $tarjaMed->fetch_assoc()): ?>
                                                <option value="<?= $tjMed['ID_Tarja'] ?>"><?= $tjMed['Tarja'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="prin_ativo" class="form-label">Princípio Ativo</label>
                                        <input type="text" class="form-control" name="prin_ativo" id="prin_ativo">
                                    </div>
                                </div>
                            </div>              
                            
                        </div>
    
                        <hr>
    
                        <h5>Informações Fiscais</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="ncm" class="form-label">NCM</label>
                                <input type="text" name="ncm" class="form-control" maxlength="8" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="ean_gtin" class="form-label">EAN/GTIN</label>
                                <input type="text" name="ean_gtin" class="form-control" maxlength="14" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="cbnef" class="form-label">CBENEF</label>
                                <input type="text" name="cbenef" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="cest" class="form-label">CEST</label>
                                <input type="text" name="cest" class="form-control">
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label for="extipi" class="form-label">EXTIPI</label>
                                <input type="text" name="extipi" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="cfop" class="form-label">CFOP</label>
                                <input type="number" name="cfop" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="mva" class="form-label">MVA</label>
                                <input type="text" name="mva" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="nfci" class="form-label">NFCI</label>
                                <input type="text" name="nfci" class="form-control">
                            </div>
                        </div>
    
                        <button type="submit" class="btn btn-primary mt-4">Cadastrar Produto</button>
                        <a href="produtos.php" class="btn btn-secondary mt-4 ms-2">Cancelar</a>
                    </form>
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
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const categoria = document.getElementById("id_categoria");
                const camposMedicamento = document.getElementById("campos_medicamento");

                function toggleCamposMedicamento() {
                    if (categoria.value == "1")
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