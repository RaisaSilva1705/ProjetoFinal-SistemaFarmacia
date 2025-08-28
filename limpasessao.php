<?php
session_start(); // Inicia a sessão para poder acessá-la

session_unset(); // Remove todas as variáveis da sessão
session_destroy(); // Destrói a sessão por completo

echo "Sessão limpa com sucesso!";
?>