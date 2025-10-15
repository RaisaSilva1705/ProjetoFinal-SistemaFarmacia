<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'FORNECEDORES_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$id_fornecedor = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_fornecedor) {
    $_SESSION['msg'] = ['texto' => 'ID de fornecedor inválido.', 'tipo' => 'warning'];
    header("Location: fornecedores.php");
    exit();
}

$stmtFornecedor = $conn->prepare("SELECT * FROM FORNECEDORES WHERE ID_Fornecedor = ?");
$stmtFornecedor->bind_param("i", $id_fornecedor);
$stmtFornecedor->execute();
$resultFornecedor = $stmtFornecedor->get_result();

if ($resultFornecedor->num_rows === 0) {
    $_SESSION['msg'] = ['texto' => 'Fornecedor não encontrado.', 'tipo' => 'danger'];
    header("Location: fornecedores.php");
    exit();
}
$fornecedor = $resultFornecedor->fetch_assoc();

$stmtProdutos = $conn->prepare("
    SELECT
        P.ID_Produto,
        P.Nome,
        P.EAN_GTIN,
        P.Status,
        MAX(L.Preco_Venda) as Preco_Venda,
        SUM(COALESCE(E.Quantidade, 0)) as Estoque_Total
    FROM PRODUTOS P
    LEFT JOIN LOTES L ON P.ID_Produto = L.ID_Produto
    LEFT JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote
    WHERE P.ID_Fornecedor = ?
    GROUP BY P.ID_Produto
    ORDER BY P.Nome
");
$stmtProdutos->bind_param("i", $id_fornecedor);
$stmtProdutos->execute();
$produtos_fornecidos = $stmtProdutos->get_result();
$total_produtos_distintos = $produtos_fornecidos->num_rows;
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Detalhes de Fornecedor</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

       <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Detalhes do Fornecedor</h3>
                </div>
            
                <div class="container p-5">
                    <a href="fornecedores.php" class="btn btn-outline-secondary mb-4">
                        <i class="bi bi-arrow-left"></i> Voltar para a Lista
                    </a>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card shadow-sm h-100">
                                <div class="card-header">
                                    <h4><?= htmlspecialchars($fornecedor['Nome_Fantasia']) ?></h4>
                                </div>
                                <div class="card-body">
                                    <p><strong>Razão Social:</strong> <?= htmlspecialchars($fornecedor['Nome']) ?></p>
                                    <p><strong>CNPJ:</strong> <?= htmlspecialchars($fornecedor['CNPJ']) ?></p>
                                    <p><strong>Endereço:</strong> <?= htmlspecialchars("{$fornecedor['Endereco']}, {$fornecedor['End_Numero']} - {$fornecedor['Bairro']}, {$fornecedor['Cidade']}/{$fornecedor['Estado']}") ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card shadow-sm h-100">
                                <div class="card-header">
                                    <h5>Contato</h5>
                                </div>
                                <div class="card-body">
                                    <p><i class="bi bi-telephone-fill me-2"></i><?= htmlspecialchars($fornecedor['Tel']) ?></p>
                                    <p><i class="bi bi-envelope-fill me-2"></i><?= htmlspecialchars($fornecedor['Email']) ?></p>
                                    <p>
                                        <?php
                                        $badge_class = $fornecedor['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger';
                                        echo "<strong>Status:</strong> <span class='badge {$badge_class}'>" . htmlspecialchars($fornecedor['Status']) . "</span>";
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-5">Produtos Fornecidos (<?= $total_produtos_distintos ?>)</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th>Cód. Barras</th>
                                    <th>Estoque Atual</th>
                                    <th>Preço de Venda</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($produtos_fornecidos->num_rows > 0): ?>
                                    <?php while($produto = $produtos_fornecidos->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($produto['Nome']) ?></td>
                                            <td><?= htmlspecialchars($produto['EAN_GTIN']) ?></td>
                                            <td><?= intval($produto['Estoque_Total']) ?></td>
                                            <td>R$ <?= number_format($produto['Preco_Venda'] ?? 0, 2, ',', '.') ?></td>
                                            <td>
                                                <?php
                                                $badge_class = $produto['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger';
                                                echo "<span class='badge {$badge_class}'>" . htmlspecialchars($produto['Status']) . "</span>";
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Nenhum produto está associado a este fornecedor.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>