<?php
function registrar_log($conexao, $id_usuario, $acao) {
    $stmt = $conexao->prepare("INSERT INTO LOGS (ID_Usuario, Acao) VALUES (?, ?)");
    $stmt->bind_param("is", $id_usuario, $acao);
    $stmt->execute();
}
?>