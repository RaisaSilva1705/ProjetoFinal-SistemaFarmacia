<?php
session_start();
include 'config.php';
include 'conexao.php';

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? '';
$index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
$senhaDigitada = $_POST['senha'] ?? '';

if ($index < 0 || !isset($_SESSION['carrinho'][$index])) {
    echo json_encode(['sucesso' => false, 'erro' => 'Item não encontrado.']);
    exit;
}

$stmt = $conn->prepare("SELECT U.Senha FROM USUARIOS U INNER JOIN FUNCIONARIOS F ON U.ID_Funcionario = F.ID_Funcionario INNER JOIN CARGOS C ON F.ID_Cargo = C.ID_Cargo WHERE C.Cargo = 'Gerente' OR C.Cargo = 'Administrador'");
$stmt->execute();
$result = $stmt->get_result();
$hashesDosGerentes = $result->fetch_all(MYSQLI_ASSOC);

$senhaValida = false;

foreach ($hashesDosGerentes as $item) {
    $hashDoBanco = $item['Senha'];
    if (password_verify($senhaDigitada, $hashDoBanco)){
        $senhaValida = true;
        break;
    }
}

if ($senhaValida) {
    if ($acao === 'remover') {
        unset($_SESSION['carrinho'][$index]);
        $_SESSION['carrinho'] = array_values($_SESSION['carrinho']);
    }
    elseif ($acao === 'diminuir') {
        if ($_SESSION['carrinho'][$index]['quantidade'] > 1)
            $_SESSION['carrinho'][$index]['quantidade']--;
        else {
            unset($_SESSION['carrinho'][$index]);
            $_SESSION['carrinho'] = array_values($_SESSION['carrinho']);
        }
    }
    echo json_encode(['sucesso' => true]);
}
else
    echo json_encode(['sucesso' => false, 'erro' => 'Senha de gerente inválida.']);

$stmt->close();
$conn->close();
?>