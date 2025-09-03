<?php
include "config.php";
include DEV_PATH . "Exec/conexao.php";

if (isset($_GET['codigo'])) { // POR CÓDIGO DE BARRAS
    $codigo = $_GET['codigo'];

    $stmt = $conn->prepare("SELECT P.Nome, 
                                   MAX(L.Preco_Venda) AS Preco_Venda,
                                   P.Foto 
                            FROM PRODUTOS P 
                            LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto
                            WHERE P.EAN_GTIN = ?
                            GROUP BY P.ID_Produto");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $produto= $result->fetch_assoc();

        echo json_encode([
            'success' => true,
            'nome' => $produto['Nome'],
            'preco' => $produto['Preco_Venda'] ?? 0.00,
            'foto' => $produto['Foto'] ?? 'sem-imagem.jpg'
        ]);
    } 
    else
        echo json_encode(['success' => false, 'msg' => 'Produto não encontrado.']);

    $stmt->close();
    $conn->close();
    exit;
}

if (isset($_GET['nome'])) { // PELO NOME DO PRODUTO
    $nome = '%' . $_GET['nome'] . '%';

    $stmt = $conn->prepare("SELECT P.EAN_GTIN, P.Nome, P.ID_Produto
                            FROM PRODUTOS P
                            WHERE P.Nome LIKE ? OR P.EAN_GTIN LIKE ?
                            LIMIT 10");
    $stmt->bind_param("ss", $nome, $nome);
    $stmt->execute();
    $result = $stmt->get_result();

    $produtos = [];
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }

    echo json_encode($produtos);

    $stmt->close();
    $conn->close();
    exit;
}
?>
