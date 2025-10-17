<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php'; 
include DEV_PATH . 'Exec/validar_sessao.php';
define('MODULO_SOLICITADO', 'PRODUTOS_GERENCIAR'); 
include DEV_PATH . 'Exec/validar_acesso.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: produtos.php");
    exit;
}

$id_usuario_logado = $_SESSION['ID_Usuario']; 
$action = $_POST['action'] ?? '';

if ($action === 'change_status') {
    $id_produto = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);
    $novo_status = $_POST['novo_status'] === 'Ativo' ? 'Ativo' : 'Inativo';

    $stmtCheck = $conn->prepare("SELECT SUM(E.Quantidade) as total FROM ESTOQUE E JOIN LOTES L ON E.ID_Lote = L.ID_Lote WHERE L.ID_Produto = ?");
    $stmtCheck->bind_param("i", $id_produto);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->fetch_assoc()['total'] > 0 && $novo_status === 'Inativo') {
        $_SESSION['msg'] = ['texto' => 'Não é possível inativar um produto que ainda possui estoque.', 'tipo' => 'warning'];
        header("Location: produtos.php");
        exit;
    }

    $stmt = $conn->prepare("UPDATE PRODUTOS SET Status = ? WHERE ID_Produto = ?");
    $stmt->bind_param("si", $novo_status, $id_produto);
    if ($stmt->execute()) {
        registrar_log($conn, $id_usuario_logado, "Alterou o status para '{$novo_status}' do produto ID: {$id_produto}");
        $_SESSION['msg'] = ['texto' => 'Status alterado com sucesso!', 'tipo' => 'success'];
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao alterar o status.', 'tipo' => 'danger'];
    
    header("Location: produtos.php");
    exit;
}

$id_produto = filter_input(INPUT_POST, 'id_produto', FILTER_VALIDATE_INT);

$nome = $_POST['nome'];
$marca = $_POST['marca'] ?? null;
$id_fornecedor = $_POST['id_fornecedor'];
$descricao = $_POST['descricao'];
$id_categoria = $_POST['id_categoria'];
$id_unidade = $_POST['id_unidade'];
$quant_minima = $_POST['quant_minima'];
$obs = $_POST['obs'];
$status = $_POST['status'];
$ncm = $_POST['ncm'];
$ean_gtin = $_POST['ean_gtin'];
$cbenef = $_POST['cbenef'];
$cest = $_POST['cest'];
$extipi = $_POST['extipi'];
$cfop = $_POST['cfop'] ?? 0.00;
$mva = $_POST['mva'] ?? 0.00;
$nfci = $_POST['nfci'];
$cst_icms = $_POST['cst_icms'];
$cst_pis = $_POST['cst_pis'];
$cst_cofins = $_POST['cst_cofins'];

$conn->begin_transaction();

try {
    if ($id_produto) { // MODO UPDATE
        $stmt_old_prod = $conn->prepare("SELECT Foto FROM PRODUTOS WHERE ID_Produto = ?");
        $stmt_old_prod->bind_param("i", $id_produto);
        $stmt_old_prod->execute();
        $produto_antigo = $stmt_old_prod->get_result()->fetch_assoc();
        $stmt_old_prod->close();

        $stmt_old_med = $conn->prepare("SELECT ID_Medicamento FROM MEDICAMENTOS WHERE ID_Produto = ?");
        $stmt_old_med->bind_param("i", $id_produto);
        $stmt_old_med->execute();
        $era_medicamento = $stmt_old_med->get_result()->num_rows > 0;
        $stmt_old_med->close();
        
        $foto = $produto_antigo['Foto'];
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $foto_nome = uniqid() . "_" . basename($_FILES["foto"]["name"]);
            $foto_destino = realpath(DEV_PATH . "../Imagens/imgProdutos/") . "/" . $foto_nome;
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $foto_destino)) $foto = $foto_nome;
        }

        $sql = "UPDATE PRODUTOS SET ID_Categoria=?, Nome=?, Marca=?, ID_Fornecedor=?, Descricao=?, ID_Unidade=?, Quant_Minima=?, Status=?, OBS=?, NCM=?, EAN_GTIN=?, CBENEF=?, CEST=?, EXTIPI=?, CFOP=?, MVA=?, NFCI=?, CST_ICMS=?, CST_PIS=?, CST_COFINS=?, Foto=? WHERE ID_Produto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issisiissssssddssssssi", $id_categoria, $nome, $marca, $id_fornecedor, $descricao, $id_unidade, $quant_minima, $status, $obs, $ncm, $ean_gtin, $cbenef, $cest, $extipi, $cfop, $mva, $nfci, $cst_icms, $cst_pis, $cst_cofins, $foto, $id_produto);
        if (!$stmt->execute()) throw new Exception("Falha ao atualizar produto: " . $stmt->error);

        $eh_medicamento_agora = ($id_categoria == 1); 

        if ($eh_medicamento_agora) {
            $id_categoria_med = $_POST['id_categoria_med']; 
            $prin_ativo = $_POST['prin_ativo']; 
            $id_tarja = $_POST['id_tarja_med']; 
            $tipo = $_POST['tipo_med']; 
            $ms = $_POST['ms']; 
            $controlado = $_POST['controlado'];
            
            if ($era_medicamento) {
                $stmtMed = $conn->prepare("UPDATE MEDICAMENTOS SET ID_CategoriaMed=?, ID_Tarja=?, Tipo=?, Prin_Ativo=?, MS=?, Controlado=? WHERE ID_Produto=?");
                $stmtMed->bind_param("iissssi", $id_categoria_med, $id_tarja, $tipo, $prin_ativo, $ms, $controlado, $id_produto);
            } 
            else { 
                $stmtMed = $conn->prepare("INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtMed->bind_param("iiissss", $id_produto, $id_categoria_med, $id_tarja, $tipo, $prin_ativo, $ms, $controlado);
            }
            if (!$stmtMed->execute()) throw new Exception("Falha ao salvar dados do medicamento: " . $stmtMed->error);
        } 
        else {
            if ($era_medicamento) 
                $conn->query("DELETE FROM MEDICAMENTOS WHERE ID_Produto = $id_produto");
        }
        $msg_sucesso = "Produto atualizado com sucesso!";
        $acao_log = "Atualizou o produto '{$nome}' (ID: {$id_produto})";

    } 
    else { // MODO INSERT
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $foto_nome = uniqid() . "_" . basename($_FILES["foto"]["name"]);
            $foto_destino = realpath(DEV_PATH . "../Imagens/imgProdutos/") . "/" . $foto_nome;
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $foto_destino)) $foto = $foto_nome;
        }

        $sql = "INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, Quant_Minima, Status, OBS, NCM, EAN_GTIN, CBENEF, CEST, EXTIPI, CFOP, MVA, NFCI, CST_ICMS, CST_PIS, CST_COFINS, Foto) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issisiissssssddssssss", $id_categoria, $nome, $marca, $id_fornecedor, $descricao, $id_unidade, $quant_minima, $status, $obs, $ncm, $ean_gtin, $cbenef, $cest, $extipi, $cfop, $mva, $nfci, $cst_icms, $cst_pis, $cst_cofins, $foto);
        if (!$stmt->execute()) throw new Exception("Falha ao inserir produto: " . $stmt->error);
        
        $id_produto_novo = $stmt->insert_id;

        if ($id_categoria == 1) { // Se for medicamento
            $id_categoria_med = $_POST['id_categoria_med']; $prin_ativo = $_POST['prin_ativo']; $id_tarja = $_POST['id_tarja_med']; $tipo = $_POST['tipo_med']; $ms = $_POST['ms']; $controlado = $_POST['controlado'];
            $sql_medicamento = "INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_medicamento = $conn->prepare($sql_medicamento);
            $stmt_medicamento->bind_param("iiissss", $id_produto_novo, $id_categoria_med, $id_tarja, $tipo, $prin_ativo, $ms, $controlado);
            if (!$stmt_medicamento->execute()) throw new Exception("Falha ao inserir dados do medicamento: " . $stmt_medicamento->error);
        }
        $msg_sucesso = "Produto cadastrado com sucesso!";
        $acao_log = "Cadastrou o novo produto '{$nome}' (ID: {$id_produto_novo})";
    }

    $conn->commit();
    $_SESSION["msg"] = ['texto' => $msg_sucesso, 'tipo' => 'success'];
    registrar_log($conn, $id_usuario_logado, $acao_log);
    header("Location: produtos.php");
    exit();

} 
catch (Exception $e) {
    $conn->rollback();
    $_SESSION['msg'] = ['texto' => 'Erro ao salvar o produto: ' . $e->getMessage(), 'tipo' => 'danger'];
    error_log("Erro ao salvar produto: " . $e->getMessage());
    $redirect_url = $id_produto ? "editar_produto.php?codigo=$id_produto" : "cadastrar_produto.php";
    header("Location: $redirect_url");
    exit;
}
?>