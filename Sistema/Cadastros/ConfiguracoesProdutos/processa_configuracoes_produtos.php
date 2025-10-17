<?php
session_start();
include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: configuracoes_produto.php");
    exit;
}

$tipo_config = $_POST['tipo_config'] ?? '';
$id_config = filter_input(INPUT_POST, 'id_config', FILTER_VALIDATE_INT);
$valor1 = trim($_POST['valor1'] ?? '');
$valor2 = trim($_POST['valor2'] ?? ''); 
$id_usuario_logado = $_SESSION['ID_Usuario'];

$tabela = '';
$coluna1 = '';
$coluna2 = '';
$id_coluna = '';

switch ($tipo_config) {
    case 'categoria':
        $tabela = 'CATEGORIAS'; $coluna1 = 'Categoria'; $id_coluna = 'ID_Categoria'; 
        break;
    case 'unidade':
        $tabela = 'UNIDADES'; $coluna1 = 'Unidade'; $coluna2 = 'Abreviacao'; $id_coluna = 'ID_Unidade'; 
        break;
    case 'cat_med':
        $tabela = 'CATEGORIAS_MEDICAMENTOS'; $coluna1 = 'Categoria_Med'; $id_coluna = 'ID_CategoriaMed';
        break;
    case 'tarja':
        $tabela = 'TARJAS_MEDICAMENTOS'; $coluna1 = 'Tarja'; $id_coluna = 'ID_Tarja'; 
        break;
    default:
        $_SESSION['msg'] = ['texto' => 'Tipo de configuração inválido.', 'tipo' => 'danger'];
        header("Location: configuracoes_produtos.php");
        exit;
}

if (empty($valor1)) {
    $_SESSION['msg'] = ['texto' => 'O campo principal não pode estar vazio.', 'tipo' => 'warning'];
    header("Location: configuracoes_produtos.php");
    exit;
}

if ($id_config) { // MODO UPDATE
    if ($tipo_config === 'unidade') {
        $sql = "UPDATE $tabela SET $coluna1 = ?, $coluna2 = ? WHERE $id_coluna = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $valor1, $valor2, $id_config);
    } 
    else {
        $sql = "UPDATE $tabela SET $coluna1 = ? WHERE $id_coluna = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $valor1, $id_config);
    }
    $msg_sucesso = "Item atualizado com sucesso!";
    $acao_log = "Atualizou em {$tabela}: '{$valor1}'";
} 
else { // MODO INSERT
    if ($tipo_config === 'unidade') {
        $sql = "INSERT INTO $tabela ($coluna1, $coluna2) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $valor1, $valor2);
    } 
    else {
        $sql = "INSERT INTO $tabela ($coluna1) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $valor1);
    }
    $msg_sucesso = "Item cadastrado com sucesso!";
    $acao_log = "Cadastrou em {$tabela}: '{$valor1}'";
}

if ($stmt->execute()) {
    registrar_log($conn, $id_usuario_logado, $acao_log);
    $_SESSION['msg'] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
} 
else 
    $_SESSION['msg'] = ['texto' => 'Erro ao salvar o item: ' . $stmt->error, 'tipo' => 'danger'];


header("Location: configuracoes_produtos.php");
exit;
?>