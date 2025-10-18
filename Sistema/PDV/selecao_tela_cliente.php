<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'PDV_ACESSAR');
include DEV_PATH . "Exec/validar_acesso.php";

$caixas = $conn->query("SELECT ID_Caixa, Caixa, Status FROM CAIXAS WHERE StatusCadastrado = 'Ativo' ORDER BY Caixa")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Iniciar Tela do Cliente</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body class="bg-light d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow" style="width: 500px;">
            <div class="card-header text-center bg-primary text-white">
                <h4>Iniciar Tela do Cliente</h4>
            </div>
            <div class="card-body p-4">
                <p class="text-muted text-center">Selecione para qual caixa esta tela será o monitor do cliente.</p>
                <div class="d-grid gap-3">
                    <?php foreach($caixas as $caixa): ?>
                        <?php 
                            // Lógica para desabilitar o botão se o caixa estiver fechado
                            $is_disabled = $caixa['Status'] !== 'Aberto';
                            $tooltip = $is_disabled ? 'data-bs-toggle="tooltip" title="Este caixa está fechado."' : '';
                            $url = $is_disabled ? '#' : 'tela_cliente.php?id_caixa=' . $caixa['ID_Caixa'];
                        ?>
                        <a href="<?= $url ?>" class="btn btn-outline-primary btn-lg p-3 <?= $is_disabled ? 'disabled' : '' ?>" <?= $tooltip ?>>
                            <i class="bi bi-cash-register-fill"></i> <?= htmlspecialchars($caixa['Caixa']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ativa os tooltips do Bootstrap para os botões desativados
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
    </body>
</html>