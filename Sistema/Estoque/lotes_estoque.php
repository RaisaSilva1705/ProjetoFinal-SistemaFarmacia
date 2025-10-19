<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'ESTOQUE_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";

$id_produto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_produto) {
    $_SESSION['msg'] = ['texto' => 'ID do produto inválido.', 'tipo' => 'warning'];
    header('Location: estoque.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT
        P.ID_Produto,
        P.Nome AS Nome_Produto,
        L.ID_Lote, L.Nome_Lote, L.Data_Validade, L.Preco_Custo, L.Preco_Venda,
        COALESCE(E.Quantidade, 0) AS Quantidade_Estoque,
        F.Nome_Fantasia AS Nome_Fornecedor
    FROM PRODUTOS P
    JOIN LOTES L ON P.ID_Produto = L.ID_Produto
    LEFT JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote
    LEFT JOIN FORNECEDORES F ON P.ID_Fornecedor = F.ID_Fornecedor
    WHERE P.ID_Produto = ?
    ORDER BY L.Data_Validade DESC
");
$stmt->bind_param("i", $id_produto);
$stmt->execute();
$result = $stmt->get_result();
$lotes = $result->fetch_all(MYSQLI_ASSOC);

if (empty($lotes)) {
    $_SESSION['msg'] = ['texto' => 'Nenhum lote encontrado para este produto.', 'tipo' => 'info'];
    header('Location: estoque.php');
    exit();
}
$nome_produto = $lotes[0]['Nome_Produto'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Lotes de: <?= htmlspecialchars($nome_produto) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Detalhes de Lotes</h3>
                </div>
                
                <div class="container p-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="m-0">Lotes de: <span class="fw-bold"><?= htmlspecialchars($nome_produto) ?></span></h2>
                        <a href="estoque.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-circle"></i> Voltar</a>
                    </div>

                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Lote</th>
                                        <th>Validade</th>
                                        <th class="text-center">Qtd. em Estoque</th>
                                        <th class="text-end">Preço Custo</th>
                                        <th class="text-end">Preço Venda</th>
                                        <th>Fornecedor</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $hoje = new DateTime();
                                        foreach ($lotes as $lote):
                                            $data_validade = new DateTime($lote['Data_Validade']);
                                            $diferenca_dias = $hoje->diff($data_validade)->days;
                                            $esta_vencido = $data_validade < $hoje;
                                            $perto_vencimento = !$esta_vencido && $diferenca_dias <= 60;
                                            
                                            $classe_alerta = '';
                                            if ($lote['Quantidade_Estoque'] == 0) {
                                                $classe_alerta = 'table-secondary text-muted';
                                            } elseif ($esta_vencido) {
                                                $classe_alerta = 'table-danger';
                                            } elseif ($perto_vencimento) {
                                                $classe_alerta = 'table-warning';
                                            }
                                    ?>
                                    <tr class="<?= $classe_alerta ?>">
                                        <td><?= htmlspecialchars($lote['Nome_Lote']) ?></td>
                                        <td>
                                            <?= $data_validade->format('d/m/Y') ?>
                                            <?php if($perto_vencimento): ?>
                                                <span class="badge bg-danger ms-1">Vence em <?= $diferenca_dias ?> dias!</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold"><?= $lote['Quantidade_Estoque'] ?></td>
                                        <td class="text-end">R$ <?= number_format($lote['Preco_Custo'], 2, ',', '.') ?></td>
                                        <td class="text-end">R$ <?= number_format($lote['Preco_Venda'], 2, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($lote['Nome_Fornecedor'] ?? 'N/A') ?></td>
                                        <td class="text-center no-print">
                                            <?php if($perto_vencimento && $lote['Quantidade_Estoque'] > 0): ?>
                                                <a href="../Promocoes/nova_promocao.php?id_produto=<?= $lote['ID_Produto'] ?>&nome_produto=<?= urlencode($lote['Nome_Produto']) ?>" 
                                                class="btn btn-success btn-sm" title="Criar promoção para este produto">
                                                    <i class="bi bi-tag-fill"></i> Criar Promoção
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include_once DEV_PATH . 'Views/footer.php'; ?>
        </div>
        
        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-list-ol"></i> Detalhes de Lotes do Produto</h4>
            <hr>
            <p>Esta tela oferece uma visão detalhada de todos os lotes, passados e presentes, de um produto específico. É uma ferramenta essencial para a rastreabilidade e para a gestão de validade.</p>

            <h6><i class="bi bi-table"></i> Informações da Tabela</h6>
            <ul>
                <li><strong>Lote:</strong> O código de identificação do lote.</li>
                <li><strong>Validade:</strong> A data de vencimento do lote.</li>
                <li><strong>Qtd. em Estoque:</strong> A quantidade de unidades restantes daquele lote específico.</li>
                <li><strong>Preço Custo / Venda:</strong> Os preços associados àquele lote no momento da entrada.</li>
                <li><strong>Fornecedor:</strong> O fornecedor que entregou aquele lote.</li>
            </ul>

            <h6><i class="bi bi-exclamation-triangle-fill"></i> Alertas Visuais de Validade</h6>
            <p>A tabela utiliza cores para alertar sobre a proximidade do vencimento:</p>
            <ul>
                <li><strong class="text-danger">Linha Vermelha:</strong> O lote já está <strong>vencido</strong>.</li>
                <li><strong class="text-warning">Linha Amarela:</strong> O lote está <strong>próximo de vencer</strong> (menos de 60 dias), indicado pela badge "Vence em X dias!".</li>
                <li><strong class="text-secondary">Linha Cinza:</strong> O lote não possui mais unidades em estoque.</li>
            </ul>

            <h6><i class="bi bi-tag-fill"></i> Ações Rápidas</h6>
            <ul>
                <li><strong>Criar Promoção:</strong> Para lotes que estão próximos do vencimento, o botão <button class="btn btn-success btn-sm"><i class="bi bi-tag-fill"></i> Criar Promoção</button> aparece como um atalho. Clicar nele te levará diretamente para a tela de criação de promoção, já com o produto pré-selecionado, facilitando a criação de ofertas para girar o estoque e evitar perdas.</li>
            </ul>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
    </body>
</html>