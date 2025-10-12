<?php
session_start();
header('Content-Type: application/json');

include "config.php";
include "conexao.php";
include "validar_sessao.php";

$senhaDigitada = $_POST['senha'] ?? '';

if (empty($senhaDigitada)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Senha não fornecida.']);
    exit;
}

$sql = "SELECT U.Senha FROM USUARIOS U 
        JOIN FUNCIONARIOS F ON U.ID_Funcionario = F.ID_Funcionario
        JOIN CARGOS C ON F.ID_Cargo = C.ID_Cargo
        WHERE C.Cargo IN ('Gerente', 'Administrador', 'Farmacêutico') AND U.Status = 'Ativo'";
$result = $conn->query($sql);
$hashesPermitidos = $result->fetch_all(MYSQLI_ASSOC);

$senhaValida = false;
foreach ($hashesPermitidos as $item) {
    if (password_verify($senhaDigitada, $item['Senha'])) {
        $senhaValida = true;
        break;
    }
}

if ($senhaValida) {
    $limite = $conn->query("SELECT Max_Desconto_Item FROM CONFIGURACOES LIMIT 1")->fetch_assoc();
    echo json_encode(['sucesso' => true, 'limite_max' => $limite['Max_Desconto_Item'] ?? 20.00]);
} 
else 
    echo json_encode(['sucesso' => false, 'mensagem' => 'Senha inválida ou permissão insuficiente.']);

exit;
?>