<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../Dev/Exec/config.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo NOME?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/login.css">
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-md-6 d-none d-md-flex left-panel">
                <div>
                    <h1 class="display-4"><?php echo NOME ?></h1>
                    <p class="lead">Seu sistema de gerenciamento de farmácia.</p>
                </div>
            </div>
            <div class="col-md-6 right-panel">
                <div class="login-form">
                    <div class="text-center mb-4">
                        <h2 class="mt-2">Bem-vindo(a) de volta!</h2>
                        <p class="text-muted">Acesse sua conta para continuar.</p>
                    </div>
    
                    <form action="../Dev/Exec/index-loginexec.php" method="POST">
                        <div class="mb-3">
                            <label for="user" class="form-label">Usuário ou Email</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="user" name="user" required>
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-custom-login btn-pill mt-2">Entrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
