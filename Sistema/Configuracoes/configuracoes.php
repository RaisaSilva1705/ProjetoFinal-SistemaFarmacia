<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$id_usuario_logado = $_SESSION['ID_Usuario'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_razao = $_POST['nome_razaosocial'];
    $nome_fantasia = $_POST['nome_fantasia'];
    $slogan = $_POST['slogan'];
    $documento = $_POST['documento'];
    $loja = $_POST['loja'];
    $cep = $_POST['cep'];
    $endereco = $_POST['endereco'];
    $end_numero = $_POST['end_numero'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    $valor_min_parcelas = $_POST['valor_min_parcelas'];
    $quant_max_parcelas = $_POST['quant_max_parcelas'];
    $margem_lucro = $_POST['margem_lucro'];
    
    // A query de UPDATE sempre vai alterar o registro com ID = 1
    $sql = "UPDATE CONFIGURACOES SET 
                Nome_RazaoSocial = ?, Nome_Fantasia = ?, Slogan = ?, Documento = ?, Loja = ?, 
                CEP = ?, Endereco = ?, End_Numero = ?, Bairro = ?, Cidade = ?, Estado = ?, 
                Valor_Min_Parcelas = ?, Quant_Max_Parcelas = ?, Margem_Lucro_Padrao = ?
            WHERE ID_Config = 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssdid", 
        $nome_razao, $nome_fantasia, $slogan, $documento, $loja, $cep, 
        $endereco, $end_numero, $bairro, $cidade, $estado, 
        $valor_min_parcelas, $quant_max_parcelas, $margem_lucro
    );

    if ($stmt->execute()) {
        registrar_log($conn, $id_usuario_logado, "Atualizou as configurações da empresa.");
        $_SESSION['msg'] = ['texto' => 'Configurações salvas com sucesso!', 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao salvar as configurações: ' . $stmt->error, 'tipo' => 'danger'];
    
    header("Location: configuracoes.php");
    exit();
}

$sql_busca = "SELECT * FROM CONFIGURACOES WHERE ID_Config = 1";
$result = $conn->query($sql_busca);
$config = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Configurações</title>
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
                    <h3>CONFIGURAÇÕES</h3>
                </div>
            
                <div class="container p-5">
                    <form action="configuracoes.php" method="POST">
                        <h5 class="card-title">Dados da Empresa</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome_razaosocial" class="form-label">Nome / Razão Social</label>
                                <input type="text" name="nome_razaosocial" id="nome_razaosocial" class="form-control" value="<?= htmlspecialchars($config['Nome_RazaoSocial']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                                <input type="text" name="nome_fantasia" id="nome_fantasia" class="form-control" value="<?= htmlspecialchars($config['Nome_Fantasia']) ?>" required>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="slogan" class="form-label">Slogan</label>
                                <input type="text" name="slogan" id="slogan" class="form-control" value="<?= htmlspecialchars($config['Slogan']) ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="documento" class="form-label">CNPJ</label>
                                <input type="text" name="documento" id="documento" class="form-control" value="<?= htmlspecialchars($config['Documento']) ?>" required>
                            </div>
                        </div>
                        
                        <hr>
                        <h5 class="mt-3">Endereço</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3"><label for="loja" class="form-label">Loja</label><input type="text" name="loja" id="loja" class="form-control" value="<?= htmlspecialchars($config['Loja']) ?>"></div>
                            <div class="col-md-4 mb-3"><label for="cep" class="form-label">CEP</label><input type="text" name="cep" id="cep" class="form-control" value="<?= htmlspecialchars($config['CEP']) ?>"></div>
                            <div class="col-md-4 mb-3"><label for="endereco" class="form-label">Endereço</label><input type="text" name="endereco" id="endereco" class="form-control" value="<?= htmlspecialchars($config['Endereco']) ?>"></div>
                            <div class="col-md-4 mb-3"><label for="end_numero" class="form-label">Número</label><input type="text" name="end_numero" id="end_numero" class="form-control" value="<?= htmlspecialchars($config['End_Numero']) ?>"></div>
                            <div class="col-md-4 mb-3"><label for="bairro" class="form-label">Bairro</label><input type="text" name="bairro" id="bairro" class="form-control" value="<?= htmlspecialchars($config['Bairro']) ?>"></div>
                            <div class="col-md-4 mb-3"><label for="cidade" class="form-label">Cidade</label><input type="text" name="cidade" id="cidade" class="form-control" value="<?= htmlspecialchars($config['Cidade']) ?>"></div>
                            <div class="col-md-4 mb-3"><label for="estado" class="form-label">Estado</label><input type="text" name="estado" id="estado" class="form-control" maxlength="2" value="<?= htmlspecialchars($config['Estado']) ?>"></div>
                        </div>

                        <hr>
                        <h5 class="mt-3">Regras de Negócio</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="valor_min_parcelas" class="form-label">Valor Mínimo para Parcelamento (R$)</label>
                                <input type="text" name="valor_min_parcelas" id="valor_min_parcelas" class="form-control" value="<?= htmlspecialchars($config['Valor_Min_Parcelas']) ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="quant_max_parcelas" class="form-label">Quantidade Máxima de Parcelas</label>
                                <input type="number" name="quant_max_parcelas" id="quant_max_parcelas" class="form-control" value="<?= htmlspecialchars($config['Quant_Max_Parcelas']) ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="margem_lucro" class="form-label">Margem de Lucro Padrão (%)</label>
                                <div class="input-group">
                                    <input type="text" name="margem_lucro" id="margem_lucro" class="form-control" value="<?= htmlspecialchars($config['Margem_Lucro_Padrao']) ?>" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
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