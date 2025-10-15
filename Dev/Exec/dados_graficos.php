<?php
session_start();
include "config.php"; 
include "conexao.php";
//include "validar_sessao.php";

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha na conexão com o banco de dados.']);
    exit;
}

header('Content-Type: application/json');

// 1. Gráfico: Vendas dos Últimos 7 Dias
$sqlVendasSemana = "SELECT 
                        DATE_FORMAT(DataHora_Venda, '%d/%m') as dia, 
                        SUM(Valor_Total) as total 
                    FROM VENDAS 
                    WHERE DataHora_Venda >= CURDATE() - INTERVAL 7 DAY 
                    GROUP BY dia 
                    ORDER BY MIN(DataHora_Venda)"; 
$stmtVendas = $conn->prepare($sqlVendasSemana);
$stmtVendas->execute();
$resultVendasSemana = $stmtVendas->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtVendas->close();

// 2. Gráfico: Vendas por Forma de Pagamento (no mês atual)
$sqlPagamentos = "SELECT FP.Tipo, SUM(VP.Valor) as total 
                  FROM VENDA_PAGAMENTOS VP 
                  JOIN FORMAS_PAGAMENTO FP ON VP.ID_Forma_Pag = FP.ID_Forma_Pag
                  JOIN VENDAS V ON VP.ID_Venda = V.ID_Venda
                  WHERE MONTH(V.DataHora_Venda) = MONTH(CURDATE()) AND YEAR(V.DataHora_Venda) = YEAR(CURDATE())
                  GROUP BY FP.Tipo";
$stmtPagamentos = $conn->prepare($sqlPagamentos);
$stmtPagamentos->execute();
$resultPagamentos = $stmtPagamentos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtPagamentos->close();

// 3. Gráfico: Top 5 Categorias Mais Vendidas (no mês atual)
$sqlCategorias = "SELECT C.Categoria, SUM(IV.Valor_Total) as total 
                  FROM ITENS_VENDA IV 
                  JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto 
                  JOIN CATEGORIAS C ON P.ID_Categoria = C.ID_Categoria
                  JOIN VENDAS V ON IV.ID_Venda = V.ID_Venda
                  WHERE MONTH(V.DataHora_Venda) = MONTH(CURDATE()) AND YEAR(V.DataHora_Venda) = YEAR(CURDATE())
                  GROUP BY C.Categoria 
                  ORDER BY total DESC 
                  LIMIT 5";
$stmtCategorias = $conn->prepare($sqlCategorias);
$stmtCategorias->execute();
$resultCategorias = $stmtCategorias->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtCategorias->close();

// 4. Top 5 Produtos Mais Rentáveis (mês atual)
$sqlTopProdutos = "SELECT P.Nome, SUM(IV.Valor_Total) as total 
                   FROM ITENS_VENDA IV 
                   JOIN PRODUTOS P ON IV.ID_Produto = P.ID_Produto
                   JOIN VENDAS V ON IV.ID_Venda = V.ID_Venda
                   WHERE MONTH(V.DataHora_Venda) = MONTH(CURDATE()) AND YEAR(V.DataHora_Venda) = YEAR(CURDATE())
                   GROUP BY P.ID_Produto, P.Nome 
                   ORDER BY total DESC 
                   LIMIT 5";
$stmtTopProdutos = $conn->prepare($sqlTopProdutos);
$stmtTopProdutos->execute();
$resultTopProdutos = $stmtTopProdutos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtTopProdutos->close();

// 5. Vendas por Hora (últimos 30 dias)
$sqlVendasHora = "SELECT HOUR(DataHora_Venda) as hora, COUNT(ID_Venda) as total_vendas 
                  FROM VENDAS 
                  WHERE DataHora_Venda >= CURDATE() - INTERVAL 30 DAY 
                  GROUP BY hora 
                  ORDER BY hora ASC";
$stmtVendasHora = $conn->prepare($sqlVendasHora);
$stmtVendasHora->execute();
$resultVendasHora = $stmtVendasHora->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtVendasHora->close();

// Monta o JSON de resposta
echo json_encode([
    'vendasSemana' => $resultVendasSemana,
    'vendasPorPagamento' => $resultPagamentos,
    'topCategorias' => $resultCategorias,
    'topProdutos' => $resultTopProdutos,
    'vendasPorHora' => $resultVendasHora,
]);

$conn->close();
?>
