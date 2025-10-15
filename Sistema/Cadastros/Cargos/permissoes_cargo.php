<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CONFIGURACOES_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$cargos = $conn->query("SELECT ID_Cargo, Cargo FROM CARGOS WHERE Status = 'Ativo' ORDER BY Cargo ASC")->fetch_all(MYSQLI_ASSOC);
$modulos = $conn->query("SELECT ID_Modulo, Nome_Modulo, Chave_Acesso FROM MODULOS ORDER BY Nome_Modulo ASC")->fetch_all(MYSQLI_ASSOC);

$cargo_selecionado_id = null;
$permissoes_atuais = [];

// Se um cargo foi selecionado para edição, busca as permissões atuais dele
if (isset($_GET['cargo_id'])) {
    $cargo_selecionado_id = filter_var($_GET['cargo_id'], FILTER_VALIDATE_INT);
    if ($cargo_selecionado_id) {
        $stmt_perm = $conn->prepare("SELECT ID_Modulo FROM CARGOS_MODULOS WHERE ID_Cargo = ?");
        $stmt_perm->bind_param("i", $cargo_selecionado_id);
        $stmt_perm->execute();
        // O resultado é um array simples com os IDs dos módulos permitidos, ex: [1, 3, 5]
        $permissoes_atuais = array_column($stmt_perm->get_result()->fetch_all(MYSQLI_ASSOC), 'ID_Modulo');
        $stmt_perm->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Gerenciar Permissões por Cargo</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">

        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Configurações do Sistema</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Permissões por Cargo</h2>
                        <a href="cargos.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-circle"></i> Voltar para Cargos</a>
                    </div>

                    <div class="card card-body mb-4">
                        <form method="GET" action="permissoes_cargo.php">
                            <div class="row align-items-end">
                                <div class="col-md-10">
                                    <label for="cargo_id" class="form-label fw-bold">Selecione um Cargo para Editar suas Permissões</label>
                                    <select name="cargo_id" id="cargo_id" class="form-select" required>
                                        <option value="" disabled selected>Selecione...</option>
                                        <?php foreach ($cargos as $cargo): ?>
                                            <option value="<?= $cargo['ID_Cargo'] ?>" <?= ($cargo_selecionado_id == $cargo['ID_Cargo']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cargo['Cargo']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Carregar Permissões</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php if ($cargo_selecionado_id): ?>
                    <form action="processa_permissoes.php" method="POST">
                        <input type="hidden" name="id_cargo" value="<?= $cargo_selecionado_id ?>">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="m-0">Módulos do Sistema</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($modulos as $modulo): 
                                        // Verifica se o checkbox deve vir pré-marcado
                                        $checked = in_array($modulo['ID_Modulo'], $permissoes_atuais) ? 'checked' : '';
                                    ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="permissoes[]" value="<?= $modulo['ID_Modulo'] ?>" id="modulo_<?= $modulo['ID_Modulo'] ?>" <?= $checked ?>>
                                                <label class="form-check-label" for="modulo_<?= $modulo['ID_Modulo'] ?>">
                                                    <?= htmlspecialchars($modulo['Nome_Modulo']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-success btn-lg">Salvar Permissões</button>
                        </div>
                    </form>
                    <?php endif; ?>

                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            <?php if (isset($_SESSION['msg']) && is_array($_SESSION['msg'])) { echo "mostrarToast('".addslashes($_SESSION['msg']['texto'])."', '".$_SESSION['msg']['tipo']."');"; unset($_SESSION['msg']); } ?>
        </script>
    </body>
</html>