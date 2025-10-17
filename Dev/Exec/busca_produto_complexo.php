<?php
session_start();
header('Content-Type: application/json');

include "config.php";
include "conexao.php";
include "validar_sessao.php";

if (isset($_GET['nome'])) {
    $nome_pesquisa = $_GET['nome'];
    $like_pesquisa = '%' . $nome_pesquisa . '%';
    
    $resultados_categorizados = [
        'mesmo_nome' => [], 'referencia' => [], 'genericos' => [], 'similares' => []
    ];
    $ids_ja_listados = [];
    $principio_ativo_completo = null;

    $sql_diretos = "SELECT P.ID_Produto, P.Nome, P.EAN_GTIN, CAT.Categoria, M.Tipo AS Tipo_Medicamento, M.Prin_Ativo, M.Controlado, (SELECT SUM(IFNULL(E.Quantidade, 0)) FROM LOTES L LEFT JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote WHERE L.ID_Produto = P.ID_Produto) AS Estoque, (SELECT MAX(L2.Preco_Venda) FROM LOTES L2 WHERE L2.ID_Produto = P.ID_Produto) AS Preco_Venda FROM PRODUTOS P LEFT JOIN CATEGORIAS CAT ON P.ID_Categoria = CAT.ID_Categoria LEFT JOIN MEDICAMENTOS M ON P.ID_Produto = M.ID_Produto WHERE P.Status = 'Ativo' AND (P.Nome LIKE ? OR P.EAN_GTIN LIKE ?) GROUP BY P.ID_Produto, P.Nome, P.EAN_GTIN, CAT.Categoria, M.Tipo, M.Prin_Ativo, M.Controlado ORDER BY P.Nome LIMIT 15";
    $stmt_diretos = $conn->prepare($sql_diretos);
    $stmt_diretos->bind_param("ss", $like_pesquisa, $like_pesquisa);
    $stmt_diretos->execute();
    $result_diretos = $stmt_diretos->get_result();

    while ($produto = $result_diretos->fetch_assoc()) {
        $produto['Estoque'] = (int) ($produto['Estoque'] ?? 0);
        $produto['Controlado'] = $produto['Controlado'] ?? 'Não';
        $resultados_categorizados['mesmo_nome'][] = $produto;
        $ids_ja_listados[] = $produto['ID_Produto'];
        if ($principio_ativo_completo === null && !empty($produto['Prin_Ativo'])) 
            $principio_ativo_completo = $produto['Prin_Ativo'];
    }
    $stmt_diretos->close();

    if ($principio_ativo_completo !== null && count($ids_ja_listados) > 0) {
        $partes_pa = explode('+', $principio_ativo_completo);
        $componente_principal = trim($partes_pa[0]);
        $radical_pa = explode(' ', $componente_principal)[0];
        $like_radical = '%' . $radical_pa . '%';
        
        $in_clause_ids = implode(',', array_fill(0, count($ids_ja_listados), '?'));
        $types_ids = str_repeat('i', count($ids_ja_listados));
        
        $sql_relacionados = "SELECT P.ID_Produto, P.Nome, P.EAN_GTIN, CAT.Categoria, M.Tipo AS Tipo_Medicamento, M.Prin_Ativo, M.Controlado, (SELECT SUM(IFNULL(E.Quantidade, 0)) FROM LOTES L LEFT JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote WHERE L.ID_Produto = P.ID_Produto) AS Estoque, (SELECT MAX(L2.Preco_Venda) FROM LOTES L2 WHERE L2.ID_Produto = P.ID_Produto) AS Preco_Venda FROM PRODUTOS P JOIN MEDICAMENTOS M ON P.ID_Produto = M.ID_Produto LEFT JOIN CATEGORIAS CAT ON P.ID_Categoria = CAT.ID_Categoria WHERE P.Status = 'Ativo' AND M.Prin_Ativo LIKE ? AND P.ID_Produto NOT IN ($in_clause_ids) GROUP BY P.ID_Produto, P.Nome, P.EAN_GTIN, CAT.Categoria, M.Tipo, M.Prin_Ativo, M.Controlado ORDER BY P.Nome LIMIT 15";
        $stmt_relacionados = $conn->prepare($sql_relacionados);
        $params = array_merge([$like_radical], $ids_ja_listados);
        $stmt_relacionados->bind_param("s" . $types_ids, ...$params);
        $stmt_relacionados->execute();
        $result_relacionados = $stmt_relacionados->get_result();

        while ($produto = $result_relacionados->fetch_assoc()) {
            $produto['Estoque'] = (int) ($produto['Estoque'] ?? 0);
            $produto['Controlado'] = $produto['Controlado'] ?? 'Não';
            switch ($produto['Tipo_Medicamento']) {
                case 'Referência': $resultados_categorizados['referencia'][] = $produto; break;
                case 'Genérico': $resultados_categorizados['genericos'][] = $produto; break;
                case 'Similar': $resultados_categorizados['similares'][] = $produto; break;
            }
        }
        $stmt_relacionados->close();
    }

    echo json_encode($resultados_categorizados);
    $conn->close();
    exit;
}

echo json_encode([]);
?>