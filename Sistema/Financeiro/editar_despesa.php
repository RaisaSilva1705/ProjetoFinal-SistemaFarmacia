<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'FINANCEIRO_VER');
include DEV_PATH . "Exec/validar_acesso.php";

$id_despesa = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_despesa) {
    $_SESSION['msg'] = ['texto' => 'ID da despesa inválido.', 'tipo' => 'warning'];
    header('Location: despesas.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM DESPESAS WHERE ID_Despesa = ?");
$stmt->bind_param("i", $id_despesa);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['msg'] = ['texto' => 'Despesa não encontrada.', 'tipo' => 'danger'];
    header('Location: despesas.php');
    exit();
}
$despesa = $result->fetch_assoc();
$stmt->close();

$stmt_cat = $conn->prepare("SELECT ID_Categoria_Despesa, Nome_Categoria FROM DESPESAS_CATEGORIAS ORDER BY Nome_Categoria ASC");
$stmt_cat->execute();
$categorias = $stmt_cat->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_cat->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Editar Despesa: <?php echo htmlspecialchars($despesa['Descricao']); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Gestão de Despesas</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">
                            Editar Despesa
                        </h2>
                        <a href="despesas.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left-circle"></i> Voltar para a Lista
                        </a>
                    </div>

                    <div class="card card-body">
                        <form action="../../dev/Exec/processa_despesa.php" method="POST">
                            <input type="hidden" name="id_despesa" value="<?php echo $despesa['ID_Despesa']; ?>">

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="descricao" class="form-label">Descrição da Despesa</label>
                                    <input type="text" class="form-control" id="descricao" name="descricao" required value="<?php echo htmlspecialchars($despesa['Descricao']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="id_categoria" class="form-label">Categoria</label>
                                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                                        <option value="" disabled>Selecione...</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['ID_Categoria_Despesa']; ?>" <?php echo ($despesa['ID_Categoria_Despesa'] == $categoria['ID_Categoria_Despesa']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($categoria['Nome_Categoria']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="valor" class="form-label">Valor (R$)</label>
                                    <input type="number" class="form-control" id="valor" name="valor" step="0.01" min="0.01" required value="<?php echo $despesa['Valor']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="data_vencimento" class="form-label">Data de Vencimento</label>
                                    <input type="date" class="form-control" id="data_vencimento" name="data_vencimento" value="<?php echo $despesa['Data_Vencimento']; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Status</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="statusPendente" value="Pendente" <?php echo ($despesa['Status'] == 'Pendente') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="statusPendente">Pendente</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="status" id="statusPaga" value="Paga" <?php echo ($despesa['Status'] == 'Paga') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="statusPaga">Paga</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4" id="campo_data_pagamento" style="display: none;">
                                    <label for="data_pagamento" class="form-label">Data de Pagamento</label>
                                    <input type="date" class="form-control" id="data_pagamento" name="data_pagamento" value="<?php echo $despesa['Data_Pagamento']; ?>">
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">Atualizar Despesa</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-pencil-square"></i> Edição de Despesa</h4>
            <hr>
            <p>Esta tela permite corrigir ou atualizar as informações de um lançamento de despesa já existente.</p>
            
            <h6><i class="bi bi-pencil-fill"></i> Dando Baixa em uma Conta</h6>
            <p>O uso mais comum desta tela é para dar baixa em uma conta que estava "Pendente". Para fazer isso:</p>
            <ol>
                <li>Altere o <strong>Status</strong> de "Pendente" para <strong>"Paga"</strong>.</li>
                <li>O campo <strong>Data de Pagamento</strong> aparecerá. Informe a data em que o pagamento foi efetuado.</li>
                <li>Clique em <strong>"Atualizar Despesa"</strong>.</li>
            </ol>
            <p>Após a baixa, o valor desta despesa será contabilizado no seu Relatório Financeiro (DRE).</p>

            <h6><i class="bi bi-card-list"></i> Outras Alterações</h6>
            <p>Você também pode usar esta tela para corrigir qualquer outra informação do lançamento, como a descrição, o valor, o vencimento ou a categoria.</p>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const statusRadios = document.querySelectorAll('input[name="status"]');
                const campoDataPagamento = document.getElementById('campo_data_pagamento');
                const inputDataPagamento = document.getElementById('data_pagamento');

                function toggleDataPagamento() {
                    const statusSelecionado = document.querySelector('input[name="status"]:checked').value;
                    if (statusSelecionado === 'Paga') {
                        campoDataPagamento.style.display = 'block';
                        inputDataPagamento.required = true;
                    } 
                    else {
                        campoDataPagamento.style.display = 'none';
                        inputDataPagamento.required = false;
                    }
                }
                
                statusRadios.forEach(radio => radio.addEventListener('change', toggleDataPagamento));
                toggleDataPagamento();
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