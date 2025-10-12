<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';

if (!isset($_GET['codigo'])){
    $_SESSION['msg'] = ['texto' => 'Nenhum código foi fornecido', 'tipo' => 'warning'];
    header("Location: nova_prevenda.php");
    exit();
}
$codigo_prevenda = $_GET['codigo'];

// Busca todos os dados da pré-venda
$sql = "SELECT pv.*, c.Nome as Nome_Cliente, f.Nome as Nome_Funcionario, conf.*
        FROM PRE_VENDAS pv
        LEFT JOIN CLIENTES c ON pv.ID_Cliente = c.ID_Cliente
        JOIN FUNCIONARIOS f ON pv.ID_Funcionario = f.ID_Funcionario
        CROSS JOIN CONFIGURACOES conf
        WHERE pv.Codigo_PreVenda = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codigo_prevenda);
$stmt->execute();
$dados_cupom = $stmt->get_result()->fetch_assoc();

if (!$dados_cupom) die("Pré-venda não encontrada.");

$sql_itens = "(SELECT 'produto' AS TipoItem, p.Nome, pvi.Quantidade, pvi.Valor_Unitario FROM PRE_VENDAS_ITENS pvi JOIN PRODUTOS p ON pvi.ID_Produto = p.ID_Produto WHERE pvi.ID_PreVenda = ?)
              UNION ALL
              (SELECT 'servico' AS TipoItem, sf.Nome_Servico, pvi.Quantidade, pvi.Valor_Unitario FROM PRE_VENDAS_ITENS pvi JOIN SERVICOS_FARMACEUTICOS sf ON pvi.ID_Servico = sf.ID_Servico WHERE pvi.ID_PreVenda = ?)";
$stmt_itens = $conn->prepare($sql_itens);
$stmt_itens->bind_param("ii", $dados_cupom['ID_PreVenda'], $dados_cupom['ID_PreVenda']);
$stmt_itens->execute();
$itens = $stmt_itens->get_result()->fetch_all(MYSQLI_ASSOC);
$total = array_sum(array_column($itens, 'Valor_Unitario'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pré-Venda #<?= $codigo_prevenda ?></title>
    <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/cupomNfiscal.css">
    <style> 
        .barcode { 
            padding: 1.5rem 0; 
            text-align: center; 
        } 
    </style>
</head>
<body>
    <div class="cupom">
        <div class="text-center small">
            <strong><?= $dados_cupom['Nome_Fantasia'] ?></strong><br>
            <?= $dados_cupom['Endereco'] ?>, <?= $dados_cupom['End_Numero'] ?><br>
            <?= $dados_cupom['Cidade'] ?>/<?= $dados_cupom['Estado'] ?>
        </div>
        <hr>
        <div class="text-center small">
            <strong>COMPROVANTE DE PRÉ-VENDA</strong>
        </div>
        <hr>
        <div class="small">
            Data: <?= date('d/m/Y H:i:s', strtotime($dados_cupom['Data_Criacao'])) ?><br>
            Atendente: <?= explode(' ', $dados_cupom['Nome_Funcionario'])[0] ?><br>
            <?php if ($dados_cupom['Nome_Cliente']): ?>
                Cliente: <?= $dados_cupom['Nome_Cliente'] ?><br>
            <?php endif; ?>
        </div>
        <hr>
        <div class="small">
            <?php foreach($itens as $item): ?>
                <div><?= $item['Quantidade'] ?>x <?= $item['Nome'] ?></div>
            <?php endforeach; ?>
        </div>
        <hr>
        <div class="text-center">
            Apresente este código no caixa para pagamento.
        </div>
        <div class="barcode">
            <svg id="barcode"></svg>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        const codigo = "<?= $codigo_prevenda ?>";
        JsBarcode("#barcode", codigo, {
            format: "CODE128", 
            lineColor: "#000",
            width: 2,
            height: 80,
            displayValue: true 
        });
        //window.print(); 
    </script>
</body>
</html>