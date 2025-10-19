<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'PRODUTOS_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$id_produto = filter_input(INPUT_GET, 'codigo', FILTER_VALIDATE_INT);
if (!$id_produto) {
    header("Location: produtos.php"); 
    exit();
}

// 1. Busca dados principais do produto e de tabelas relacionadas
$sql_principal = "SELECT P.*, C.Categoria, F.Nome_Fantasia as Fornecedor, U.Unidade 
                  FROM PRODUTOS P
                  LEFT JOIN CATEGORIAS C ON P.ID_Categoria = C.ID_Categoria
                  LEFT JOIN FORNECEDORES F ON P.ID_Fornecedor = F.ID_Fornecedor
                  LEFT JOIN UNIDADES U ON P.ID_Unidade = U.ID_Unidade
                  WHERE P.ID_Produto = ?";
$stmt = $conn->prepare($sql_principal);
$stmt->bind_param("i", $id_produto);
$stmt->execute();
$produto = $stmt->get_result()->fetch_assoc();

if (!$produto) {
    $_SESSION['msg'] = ['texto' => 'Produto não encontrado.', 'tipo' => 'danger'];
    header("Location: produtos.php"); 
    exit();
}

// 2. Busca dados de medicamento, se aplicável
$medicamento = null;
if ($produto['ID_Categoria'] == 1) { // Supondo que 1 é o ID da categoria "Medicamento"
    $sql_med = "SELECT M.*, TM.Tarja, CM.Categoria_Med
                FROM MEDICAMENTOS M
                LEFT JOIN TARJAS_MEDICAMENTOS TM ON M.ID_Tarja = TM.ID_Tarja
                LEFT JOIN CATEGORIAS_MEDICAMENTOS CM ON M.ID_CategoriaMed = CM.ID_CategoriaMed
                WHERE M.ID_Produto = ?";
    $stmt_med = $conn->prepare($sql_med);
    $stmt_med->bind_param("i", $id_produto);
    $stmt_med->execute();
    $medicamento = $stmt_med->get_result()->fetch_assoc();
}

// 3. Busca dados de estoque e lotes
$sql_lotes = "SELECT L.Nome_Lote, L.Preco_Custo, L.Preco_Venda, L.Data_Validade, E.Quantidade 
              FROM LOTES L
              JOIN ESTOQUE E ON L.ID_Lote = E.ID_Lote
              WHERE L.ID_Produto = ? AND E.Quantidade > 0
              ORDER BY L.Data_Validade ASC";
$stmt_lotes = $conn->prepare($sql_lotes);
$stmt_lotes->bind_param("i", $id_produto);
$stmt_lotes->execute();
$lotes_em_estoque = $stmt_lotes->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcula o estoque total e o preço de venda mais recente
$estoque_total = array_sum(array_column($lotes_em_estoque, 'Quantidade'));
$preco_venda_atual = !empty($lotes_em_estoque) ? end($lotes_em_estoque)['Preco_Venda'] : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Detalhes do Produto: <?= htmlspecialchars($produto['Nome']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
</head>
<body class="bg-light">

    <?php include_once DEV_PATH . 'Views/sidebar.php';?>

    <div class="content d-flex flex-column min-vh-100">
        <div class="content flex-grow-1">
            <div class="container-fluid bg-secondary text-white text-center p-4">
                <h3>Detalhes do Produto</h3>
            </div>
            
            <div class="container p-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="m-0"><?= htmlspecialchars($produto['Nome']) ?></h2>
                    <a href="produtos.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-circle"></i> Voltar para a Lista</a>
                </div>

                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm mb-4">
                            <img src="<?= $produto['Foto'] ? DEV_URL . 'Imagens/imgProdutos/' . htmlspecialchars($produto['Foto']) : DEV_URL . 'Imagens/ImgSistema/sem-imagem.jpg' ?>" class="card-img-top p-3" alt="Foto do Produto">
                        </div>
                        <div class="card shadow-sm">
                            <div class="card-header"><h5 class="m-0"><i class="bi bi-boxes me-2"></i>Estoque e Preço</h5></div>
                            <div class="card-body">
                                <p><strong>Preço de Venda Atual:</strong> <span class="fs-4 fw-bold text-success">R$ <?= number_format($preco_venda_atual, 2, ',', '.') ?></span></p>
                                <p><strong>Estoque Total:</strong> <span class="fs-4 fw-bold"><?= $estoque_total ?></span> unidades</p>
                                <p><strong>Estoque Mínimo:</strong> <?= $produto['Quant_Minima'] ?> unidades</p>
                                <?php if($estoque_total < $produto['Quant_Minima']): ?>
                                    <div class="alert alert-danger p-2 text-center"><strong>ATENÇÃO:</strong> Estoque abaixo do mínimo!</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="m-0"><i class="bi bi-info-circle-fill me-2"></i>Informações Gerais</h5>
                                <a href="editar_produto.php?codigo=<?= $produto['ID_Produto'] ?>" class="btn btn-warning btn-sm" title="Editar Produto"><i class="bi bi-pencil-fill"></i></a>
                            </div>
                            <div class="card-body">
                                <p><strong>Marca:</strong> <?= htmlspecialchars($produto['Marca'] ?? 'Não informada') ?></p>
                                <p><strong>Fornecedor:</strong> <?= htmlspecialchars($produto['Fornecedor'] ?? 'Não informado') ?></p>
                                <p><strong>Categoria:</strong> <?= htmlspecialchars($produto['Categoria'] ?? 'Não informada') ?></p>
                                <p><strong>Unidade de Venda:</strong> <?= htmlspecialchars($produto['Unidade'] ?? 'Não informada') ?></p>
                                <p><strong>Descrição:</strong> <?= htmlspecialchars($produto['Descricao'] ?? 'Sem descrição.') ?></p>
                                <p><strong>Observações:</strong> <?= htmlspecialchars($produto['OBS'] ?? 'Sem observações.') ?></p>
                                <p><strong>Status:</strong> <span class="badge <?= $produto['Status'] == 'Ativo' ? 'bg-success' : 'bg-danger' ?>"><?= htmlspecialchars($produto['Status']) ?></span></p>
                            </div>
                        </div>
                        
                        <?php if ($medicamento): ?>
                        <div class="card shadow-sm mt-4">
                            <div class="card-header"><h5 class="m-0"><i class="bi bi-capsule-pill me-2"></i>Detalhes do Medicamento</h5></div>
                            <div class="card-body">
                                <p><strong>Princípio Ativo:</strong> <?= htmlspecialchars($medicamento['Prin_Ativo']) ?></p>
                                <p><strong>Registro MS:</strong> <?= htmlspecialchars($medicamento['MS']) ?></p>
                                <p><strong>Tarja:</strong> <?= htmlspecialchars($medicamento['Tarja']) ?></p>
                                <p><strong>Categoria do Medicamento:</strong> <?= htmlspecialchars($medicamento['Categoria_Med']) ?></p>
                                <p><strong>Tipo:</strong> <?= htmlspecialchars($medicamento['Tipo']) ?></p>
                                <p><strong>Controlado:</strong> <span class="badge <?= $medicamento['Controlado'] == 'Sim' ? 'bg-warning text-dark' : 'bg-secondary' ?>"><?= htmlspecialchars($medicamento['Controlado']) ?></span></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="accordion mt-4" id="accordionFiscais">
                            <div class="accordion-item shadow-sm">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiscais">
                                        <i class="bi bi-receipt me-2"></i>Informações Fiscais
                                    </button>
                                </h2>
                                <div id="collapseFiscais" class="accordion-collapse collapse">
                                    <div class="card-body row">
                                        <p class="col-md-6"><strong>EAN/GTIN:</strong> <?= htmlspecialchars($produto['EAN_GTIN']) ?></p>
                                        <p class="col-md-6"><strong>NCM:</strong> <?= htmlspecialchars($produto['NCM']) ?></p>
                                        <p class="col-md-6"><strong>CEST:</strong> <?= htmlspecialchars($produto['CEST'] ?? 'N/A') ?></p>
                                        <p class="col-md-6"><strong>CFOP:</strong> <?= htmlspecialchars($produto['CFOP'] ?? 'N/A') ?></p>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header"><h4 class="m-0">Lotes em Estoque</h4></div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Lote</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-end">Preço de Custo</th>
                                    <th class="text-end">Preço de Venda</th>
                                    <th class="text-center">Data de Validade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($lotes_em_estoque)): ?>
                                    <?php foreach ($lotes_em_estoque as $lote): 
                                        $validade = new DateTime($lote['Data_Validade']);
                                        $hoje = new DateTime();
                                        $diff = $hoje->diff($validade)->days;
                                        $classe_validade = $diff < 30 ? 'table-danger' : ($diff < 90 ? 'table-warning' : '');
                                    ?>
                                        <tr class="<?= $classe_validade ?>">
                                            <td><?= htmlspecialchars($lote['Nome_Lote']) ?></td>
                                            <td class="text-center"><?= $lote['Quantidade'] ?></td>
                                            <td class="text-end">R$ <?= number_format($lote['Preco_Custo'], 2, ',', '.') ?></td>
                                            <td class="text-end">R$ <?= number_format($lote['Preco_Venda'], 2, ',', '.') ?></td>
                                            <td class="text-center"><?= $validade->format('d/m/Y') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center p-3">Não há lotes com estoque para este produto.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php include_once DEV_PATH . 'Views/footer.php';?>
        </div>
    </div>

    <div id="manual-content-container" style="display: none;">
        <h4><i class="bi bi-eye-fill"></i> Detalhes do Produto</h4>
        <hr>
        <p>Esta tela oferece uma visão completa e amigável de um produto específico, reunindo suas informações de cadastro, estoque, preço e lotes em um só lugar de fácil consulta.</p>

        <h6><i class="bi bi-image-fill"></i> Imagem, Estoque e Preço</h6>
        <p>A coluna da esquerda destaca as informações mais importantes para a operação diária:</p>
        <ul>
            <li><strong>Imagem:</strong> Facilita a identificação visual do produto.</li>
            <li><strong>Preço de Venda Atual:</strong> Mostra o preço que está sendo praticado no PDV, baseado no lote mais recente.</li>
            <li><strong>Estoque Total:</strong> A soma de todas as unidades disponíveis, de todos os lotes.</li>
            <li><strong>Alerta de Estoque Baixo:</strong> Um aviso em vermelho aparece se o estoque total estiver abaixo do mínimo definido no cadastro.</li>
        </ul>

        <h6><i class="bi bi-info-circle-fill"></i> Informações Gerais</h6>
        <p>O card principal na coluna da direita resume os dados cadastrais do produto, como marca, fornecedor e categoria. O botão de editar <i class="bi bi-pencil-fill text-warning"></i> é um atalho para a tela de edição completa.</p>

        <h6><i class="bi bi-capsule-pill"></i> Detalhes do Medicamento</h6>
        <p>Se o produto for da categoria "Medicamento", este card aparecerá com todas as informações específicas, como Princípio Ativo, Tarja e Registro MS.</p>
        
        <h6><i class="bi bi-receipt"></i> Informações Fiscais</h6>
        <p>Clique nesta seção para expandir e visualizar os dados fiscais do produto, como EAN, NCM e CEST.</p>

        <h6><i class="bi bi-list-ol"></i> Lotes em Estoque</h6>
        <p>A tabela no final da página é uma ferramenta poderosa para a gestão de inventário. Ela lista todos os lotes deste produto que ainda possuem unidades em estoque.</p>
        <ul>
            <li><strong>Alerta de Validade:</strong> As linhas são destacadas em <strong>amarelo</strong> para lotes com vencimento em menos de 90 dias e em <strong>vermelho</strong> para lotes com vencimento em menos de 30 dias, ajudando no controle de perdas.</li>
        </ul>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
</body>
</html>