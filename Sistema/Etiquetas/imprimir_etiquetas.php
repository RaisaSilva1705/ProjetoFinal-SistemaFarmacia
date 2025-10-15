<?php
session_start();
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';

$etiquetas_selecionadas = $_POST['etiquetas'] ?? [];

if (empty($etiquetas_selecionadas)) {
    echo "Nenhum item selecionado para impressão.";
    exit;
}

function getDadosEtiqueta($conn, $id_produto) {
    $dados = [
        'preco_normal' => 0,
        'preco_final' => 0,
        'texto_promocao' => null,
        'tipo_promocao' => null,
        'desconto_percentual' => null,
        'desconto_fixo' => null,
        'data_fim' => null,
    ];

    $stmt_preco = $conn->prepare("SELECT MAX(Preco_Venda) AS Preco FROM LOTES WHERE ID_Produto = ?");
    $stmt_preco->bind_param("i", $id_produto);
    $stmt_preco->execute();
    $preco_base = $stmt_preco->get_result()->fetch_assoc()['Preco'] ?? 0;
    $dados['preco_normal'] = $preco_base;
    $dados['preco_final'] = $preco_base;
    $stmt_preco->close();

    $stmt_promo = $conn->prepare("SELECT p.Descricao, p.Tipo, pi.Valor_Desconto_Percentual, pi.Preco_Fixo_Combo, p.Data_Fim FROM PROMOCOES p JOIN PROMOCOES_ITENS pi ON p.ID_Promocao = pi.ID_Promocao WHERE p.Status = 'Ativo' AND p.Data_Inicio <= CURDATE() AND (p.Data_Fim IS NULL OR p.Data_Fim >= CURDATE()) AND pi.ID_Produto = ? AND pi.Tipo_Item = 'Beneficio' LIMIT 1");
    $stmt_promo->bind_param("i", $id_produto);
    $stmt_promo->execute();
    $promo = $stmt_promo->get_result()->fetch_assoc();
    $stmt_promo->close();

    if ($promo) {
        $dados['tipo_promocao'] = $promo['Tipo']; 
        $dados['texto_promocao'] = $promo['Descricao'];
        $dados['data_fim'] = $promo['Data_Fim'];

        if ($promo['Tipo'] == 'DESCONTO_PROGRESSIVO' && !empty($promo['Valor_Desconto_Percentual'])) {
            $dados['desconto_percentual'] = (float)$promo['Valor_Desconto_Percentual'];
            $dados['preco_final'] = $preco_base * (1 - ($dados['desconto_percentual'] / 100));
        }
        if ($promo['Tipo'] == 'COMBO_PRECO_FIXO') {
            $dados['desconto_fixo'] = (float)$promo['Preco_Fixo_Combo'];
            $dados['preco_final'] = $preco_base - $dados['desconto_fixo'];
        }
    }
    
    return $dados;
}

$etiquetas_para_impressao = [];
foreach ($etiquetas_selecionadas as $id_produto => $dados) {
    $dados['info_preco'] = getDadosEtiqueta($conn, $id_produto);
    $etiquetas_para_impressao[] = $dados;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Impressão de Etiquetas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/etiquetas.css">
    </head>
    <body>
        <div class="text-center p-3 no-print">
            <p>Sua folha de etiquetas está pronta.</p>
            <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer-fill"></i> Imprimir Agora</button>
            <button onclick="window.close()" class="btn btn-secondary">Fechar</button>
        </div>

        <div id="pagina-etiquetas">
            <?php foreach ($etiquetas_para_impressao as $etiqueta): 
                $info_preco = $etiqueta['info_preco'];
            ?>
                <div class="etiqueta modelo-<?= $etiqueta['modelo'] ?> tamanho-<?= $etiqueta['tamanho'] ?>">
                    
                    <?php if ($etiqueta['modelo'] === 'promocao_amarela'): ?>
                        <div class="etiqueta-titulo-promo">PROMOÇÃO <?= ($info_preco['data_fim']) ? " - VÁLIDA ATÉ " . date('d/m', strtotime($info_preco['data_fim'])) : '' ?></div>
                    <?php elseif ($etiqueta['modelo'] === 'oferta_vermelha'): ?>
                        <div class="etiqueta-titulo-oferta">IMPERDÍVEL! <?= ($info_preco['data_fim']) ? " - ATÉ " . date('d/m', strtotime($info_preco['data_fim'])) : '' ?></div>
                    <?php endif; ?>

                    <div class="etiqueta-corpo">
                        <div class="etiqueta-nome"><?= htmlspecialchars($etiqueta['nome']) ?></div>
                        <span class="preco-texto-promo"><?= htmlspecialchars($info_preco['texto_promocao']) ?></span>

                        <div class="etiqueta-preco">
                            <?php if ($info_preco['tipo_promocao'] == 'LEVE_X_PAGUE_Y'): ?>
                                <div class="preco-bloco-texto">
                                    <span class="preco-normal">R$ <?= number_format($info_preco['preco_normal'], 2, ',', '.') ?></span>
                                </div>
                            <?php elseif ($info_preco['tipo_promocao'] == 'DESCONTO_PROGRESSIVO'): ?>
                                <div class="preco-bloco-de">
                                    <span class="etiqueta-de">De:</span>
                                    <del class="preco-antigo">R$ <?= number_format($info_preco['preco_normal'], 2, ',', '.') ?></del>
                                </div>
                                <div class="preco-bloco-por">
                                    <span class="etiqueta-por">Por:</span>
                                    <span class="preco-novo">R$ <?= number_format($info_preco['preco_final'], 2, ',', '.') ?></span>
                                </div>
                            <?php elseif ($info_preco['tipo_promocao'] == 'COMBO_PRECO_FIXO'): ?>
                                <div class="preco-bloco-de">
                                    <span class="etiqueta-de">De:</span>
                                    <del class="preco-antigo">R$ <?= number_format($info_preco['preco_normal'], 2, ',', '.') ?></del>
                                </div>
                                <div class="preco-bloco-por">
                                    <span class="etiqueta-por">Por:</span>
                                    <span class="preco-novo">R$ <?= number_format($info_preco['preco_final'], 2, ',', '.') ?></span>
                                </div>
                            <?php else: // Preço Normal ?>
                                <div class="preco-bloco-por">
                                    <span class="preco-normal">R$ <?= number_format($info_preco['preco_normal'], 2, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="etiqueta-barcode">
                        <img class="barcode"
                            jsbarcode-value="<?= htmlspecialchars($etiqueta['ean']) ?>"
                            jsbarcode-format="CODE128"
                            jsbarcode-displayValue="false"
                            jsbarcode-margin="0"
                            jsbarcode-height="30"
                            jsbarcode-width="1.5">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                try {
                    JsBarcode(".barcode").init();
                } catch (e) {
                    console.error("Erro ao gerar códigos de barra:", e);
                }
                // window.print(); 
            });
        </script>
    </body>
</html>