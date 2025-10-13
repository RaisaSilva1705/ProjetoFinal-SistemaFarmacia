<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function aplicarPromocoesAoCarrinho($conn) {
    if (!isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
        return; 
    }

    foreach ($_SESSION['carrinho'] as &$item) {
        unset($item['desconto_promocao_valor']);
        unset($item['desconto_promocao_desc']);
        $item['desconto'] = $item['desconto_gerencial'] ?? 0.00;
    }
    unset($item);

    $stmt = $conn->prepare(
        "SELECT p.ID_Promocao, p.Descricao AS Descricao_Promocao, p.Tipo, pi.Tipo_Item, pi.ID_Produto, pi.Quantidade, pi.Valor_Desconto_Percentual, pi.Preco_Fixo_Combo
         FROM PROMOCOES p
         JOIN PROMOCOES_ITENS pi ON p.ID_Promocao = pi.ID_Promocao
         WHERE p.Status = 'Ativo'
           AND p.Data_Inicio <= CURDATE()
           AND (p.Data_Fim IS NULL OR p.Data_Fim >= CURDATE())
         ORDER BY p.ID_Promocao"
    );
    $stmt->execute();
    $regras_db = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($regras_db)) return; 

    $promocoes_organizadas = [];
    foreach ($regras_db as $regra) {
        $promocoes_organizadas[$regra['ID_Promocao']]['tipo'] = $regra['Tipo'];
        $promocoes_organizadas[$regra['ID_Promocao']]['descricao'] = $regra['Descricao_Promocao'];
        $promocoes_organizadas[$regra['ID_Promocao']]['itens'][] = $regra;
    }

    $contagem_produtos_carrinho = [];
    foreach ($_SESSION['carrinho'] as $item) {
        $id_produto = $item['id_produto'];
        $contagem_produtos_carrinho[$id_produto] = ($contagem_produtos_carrinho[$id_produto] ?? 0) + $item['quantidade'];
    }

    foreach ($promocoes_organizadas as $promo) {
        $condicoes = array_filter($promo['itens'], fn($i) => $i['Tipo_Item'] == 'Condicao');
        $beneficios = array_filter($promo['itens'], fn($i) => $i['Tipo_Item'] == 'Beneficio');
        
        if (empty($condicoes) || empty($beneficios)) {
            continue;
        }

        // ---- Lógica para 'LEVE_X_PAGUE_Y' e 'DESCONTO_PROGRESSIVO' ----
        if ($promo['tipo'] == 'LEVE_X_PAGUE_Y' || $promo['tipo'] == 'DESCONTO_PROGRESSIVO') {
            $condicao_atendida = true;
            $vezes_aplicar_condicao = PHP_INT_MAX;

            foreach ($condicoes as $condicao) {
                $id_produto_condicao = $condicao['ID_Produto'];
                $qtd_necessaria = $condicao['Quantidade'];
                $qtd_no_carrinho = $contagem_produtos_carrinho[$id_produto_condicao] ?? 0;

                if ($qtd_no_carrinho < $qtd_necessaria) {
                    $condicao_atendida = false;
                    break;
                }
                $vezes_aplicar_condicao = min($vezes_aplicar_condicao, floor($qtd_no_carrinho / $qtd_necessaria));
            }
            
            if ($condicao_atendida && $vezes_aplicar_condicao > 0) {
                foreach ($beneficios as $beneficio) {
                    $id_produto_beneficio = $beneficio['ID_Produto'];
                    $desconto_percentual = $beneficio['Valor_Desconto_Percentual'];
                    
                    foreach ($_SESSION['carrinho'] as &$item_carrinho) {
                        if ($item_carrinho['id_produto'] == $id_produto_beneficio) {
                            $preco_unitario = $item_carrinho['preco'];
                            $valor_desconto_total = ($preco_unitario * $desconto_percentual / 100) * $vezes_aplicar_condicao;
                            
                            $item_carrinho['desconto'] += $valor_desconto_total;
                            $item_carrinho['desconto_promocao_valor'] = ($item_carrinho['desconto_promocao_valor'] ?? 0) + $valor_desconto_total;
                            $item_carrinho['desconto_promocao_desc'] = $promo['descricao'];
                            break; 
                        }
                    }
                    unset($item_carrinho);
                }
            }
        }
        // ---- NOVA LÓGICA PARA 'COMBO_PRECO_FIXO' ----
        elseif ($promo['tipo'] == 'COMBO_PRECO_FIXO') {
            $condicao_atendida = true;
            $preco_original_combo = 0;

            $itens_do_combo = array_merge($condicoes, $beneficios);
            $preco_fixo_final = $beneficios[0]['Preco_Fixo_Combo']; 

            foreach ($itens_do_combo as $item_regra) {
                $id_produto_regra = $item_regra['ID_Produto'];
                $qtd_necessaria = $item_regra['Quantidade'];
                $qtd_no_carrinho = $contagem_produtos_carrinho[$id_produto_regra] ?? 0;

                if ($qtd_no_carrinho < $qtd_necessaria) {
                    $condicao_atendida = false;
                    break;
                }
            }

            if ($condicao_atendida) {
                foreach ($_SESSION['carrinho'] as $item_carrinho) {
                    foreach ($itens_do_combo as $item_regra) {
                        if ($item_carrinho['id_produto'] == $item_regra['ID_Produto']) 
                            $preco_original_combo += $item_carrinho['preco'] * $item_regra['Quantidade'];
                    }
                }
                
                $desconto_total_combo = $preco_original_combo - $preco_fixo_final;

                if ($desconto_total_combo > 0) {
                    foreach ($_SESSION['carrinho'] as &$item_carrinho) {
                        foreach ($itens_do_combo as $item_regra) {
                            if ($item_carrinho['id_produto'] == $item_regra['ID_Produto']) {
                                // Distribui o desconto proporcionalmente, mas para simplificar vamos aplicar tudo no primeiro item encontrado
                                // Uma lógica mais complexa poderia distribuir o desconto.
                                $item_carrinho['desconto'] += $desconto_total_combo;
                                $item_carrinho['desconto_promocao_valor'] = $desconto_total_combo;
                                $item_carrinho['desconto_promocao_desc'] = $promo['descricao'];
                                $desconto_total_combo = 0; 
                            }
                        }
                    }
                    unset($item_carrinho);
                }
            }
        }
    }
}
?>