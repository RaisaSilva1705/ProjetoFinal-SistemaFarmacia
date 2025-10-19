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
$action = $_POST['action'] ?? '';
$id_config = filter_input(INPUT_POST, 'id_config', FILTER_VALIDATE_INT);
$valor1 = trim($_POST['valor1'] ?? '');
$valor2 = trim($_POST['valor2'] ?? ''); 
$id_usuario_logado = $_SESSION['ID_Usuario'];

if ($action === 'change_status') {
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';
    
    $mapa_verificacao = [
        'categoria' => ['tabela' => 'PRODUTOS', 'coluna' => 'ID_Categoria'],
        'unidade' => ['tabela' => 'PRODUTOS', 'coluna' => 'ID_Unidade'],
        'cat_med' => ['tabela' => 'MEDICAMENTOS', 'coluna' => 'ID_CategoriaMed'],
        'tarja' => ['tabela' => 'MEDICAMENTOS', 'coluna' => 'ID_Tarja']
    ];
    $verificacao = $mapa_verificacao[$tipo_config];
    $tabela_verif = $verificacao['tabela'];
    $coluna_verif = $verificacao['coluna'];

    if ($novo_status === 'Inativo') {
        $stmtCheck = $conn->prepare("SELECT COUNT(*) as total FROM $tabela_verif WHERE $coluna_verif = ?");
        $stmtCheck->bind_param("i", $id_config);
        $stmtCheck->execute();
        $total_usos = $stmtCheck->get_result()->fetch_assoc()['total'];

        if ($total_usos > 0) {
            $_SESSION['msg'] = ['texto' => 'Não é possível inativar este item, pois ele está em uso por ' . $total_usos . ' produto(s).', 'tipo' => 'warning'];
            header("Location: configuracoes_produtos.php");
            exit;
        }
    }
    
    $tabela_map = ['categoria' => 'CATEGORIAS', 'unidade' => 'UNIDADES', 'cat_med' => 'CATEGORIAS_MEDICAMENTOS', 'tarja' => 'TARJAS_MEDICAMENTOS'];
    $id_coluna_map = ['categoria' => 'ID_Categoria', 'unidade' => 'ID_Unidade', 'cat_med' => 'ID_CategoriaMed', 'tarja' => 'ID_Tarja'];
    $tabela = $tabela_map[$tipo_config];
    $id_coluna = $id_coluna_map[$tipo_config];
    
    $stmt = $conn->prepare("UPDATE $tabela SET Status = ? WHERE $id_coluna = ?");
    $stmt->bind_param("si", $novo_status, $id_config);
    if ($stmt->execute()) 
        $_SESSION['msg'] = ['texto' => 'Status alterado com sucesso!', 'tipo' => 'success'];
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status.', 'tipo' => 'danger'];

    header("Location: configuracoes_produtos.php");
    exit;
}

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