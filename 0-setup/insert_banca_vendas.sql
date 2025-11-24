-- =================================================================
-- HISTÓRICO DIA 1: 20/11/2025 (QUINTA-FEIRA)
-- =================================================================

-- 1. ABERTURA DE CAIXAS (Manhã, Tarde, Noite)
-- IDs de CaixaAberto iniciam em 100 para não conflitar
INSERT INTO CAIXAS_ABERTOS (ID_CaixaAberto, ID_Caixa, ID_Funcionario, ID_Turno, Data_Abertura, Saldo_Inicial, Data_Fechamento, Saldo_Final) VALUES
(100, 1, 8, 1, '2025-11-20 07:00:00', 200.00, '2025-11-20 15:00:00', 1500.00), -- Manhã (Patricia)
(101, 2, 9, 2, '2025-11-20 14:00:00', 150.00, '2025-11-20 22:00:00', 2200.00), -- Tarde (Lucas)
(102, 2, 5, 3, '2025-11-20 22:00:00', 100.00, '2025-11-20 23:59:59', 500.00);  -- Noite (Fernanda)

-- 3. MOVIMENTAÇÃO DO CAIXA 
INSERT INTO MOVIMENTACOES_CAIXA (ID_Caixa, ID_Funcionario, Tipo, Valor, Descricao, Data_Movimentacao) VALUES
-- Manhã
(1, 8, 'Entrada', 200.00, 'Suprimento - Abertura de Caixa', '2025-11-20 07:05:00'),
(1, 8, 'Saída', 300.00, 'Sangria - Finalização de Caixa', '2025-11-20 14:55:00'),
-- Tarde
(2, 9, 'Entrada', 150.00, 'Suprimento - Abertura de Caixa', '2025-11-20 14:00:00'),
(2, 9, 'Saída', 205.00, 'Sangria - Finalização de Caixa', '2025-11-20 22:00:00'),
-- Noite
(2, 5, 'Entrada', 100.00, 'Suprimento - Abertura de Caixa', '2025-11-20 22:05:00'),
(2, 5, 'Saída', 112.00, 'Sangria - Finalização de Caixa', '2025-11-20 23:55:00');

-- 2. VENDAS (IDs 100 a 108)
INSERT INTO VENDAS (ID_Venda, ID_Funcionario, ID_CaixaAberto, ID_Cliente, DataHora_Venda, Valor_Total, Desconto) VALUES
-- --- MANHÃ (Patricia) ---
(100, 8, 100, 1, '2025-11-20 08:30:00', 85.00, 0.00),  -- Cliente Idosa (Uso contínuo)
(101, 8, 100, NULL, '2025-11-20 10:15:00', 15.00, 0.00), -- Passante (Band-aid)
(102, 8, 100, 4, '2025-11-20 12:45:00', 120.00, 10.00), -- Cosméticos
-- --- TARDE (Lucas) ---
(103, 9, 101, 20, '2025-11-20 15:30:00', 450.00, 0.00), -- Cliente Famoso (Whey + Creatina)
(104, 9, 101, 11, '2025-11-20 17:00:00', 80.00, 0.00),  -- PJ Escola (Algodão + Soro)
(105, 9, 101, NULL, '2025-11-20 19:30:00', 55.00, 5.00), -- Hora do Rush (Antibiótico)
-- --- NOITE (Fernanda - Emergências e Conveniência) ---
(106, 5, 102, NULL, '2025-11-20 22:15:00', 25.00, 0.00), -- Dor de cabeça noturna
(107, 5, 102, 2, '2025-11-20 23:10:00', 55.00, 0.00),   -- Pai comprando Fralda
(108, 5, 102, NULL, '2025-11-20 23:45:00', 12.00, 0.00); -- Soro

-- 3. ITENS DA VENDA
INSERT INTO ITENS_VENDA (ID_Venda, ID_Produto, Quantidade, Valor_Total, Desconto) VALUES
-- Venda 100 (85.00) -> 1 Losartana (5.00) + 1 Metformina (6.00) + 2 Tylenol (28*2=56) + 1 Dipirona (8.00) = 75. Falta 10. +1 Nimesulida (10). Total 85.
(100, 16, 1, 5.00, 0.00),
(100, 19, 1, 6.00, 0.00),
(100, 3, 2, 56.00, 0.00),
(100, 1, 1, 8.00, 0.00),
(100, 9, 1, 10.00, 0.00),
-- Venda 101 (15.00) -> 2 Band-aids
(101, 48, 2, 15.00, 0.00),
-- Venda 102 (120.00) -> La Roche (89.90) + Dove Shampoo (22.90) = 112.80. + Soro (8.00) = 120.80. Desconto 0.80.
(102, 44, 1, 89.90, 0.00),
(102, 36, 1, 22.90, 0.00),
(102, 50, 1, 8.00, 0.80), -- Ajuste tecnico no desconto
-- Venda 103 (450.00) -> 2 Whey (169.90*2 = 339.80) + 1 Creatina (99.90) = 439.70. + 1 Nimesulida (10.30?).
-- Vamos ajustar: 2 Whey (339.80) + 1 Creatina (99.90) + 1 Ibuprofeno (10.30 no preço cheio).
(103, 46, 2, 339.80, 0.00),
(103, 47, 1, 99.90, 0.00),
(103, 8, 1, 10.30, 1.70), -- Ibuprofeno era 12, saiu por 10.30 para fechar a conta
-- Venda 104 (80.00) -> 10 Soro (8*10 = 80)
(104, 50, 10, 80.00, 0.00),
-- Venda 105 (55.00 com 5 desc = 60 bruto) -> Amoxicilina+Clav (55.00) + Losartana (5.00)
(105, 12, 1, 55.00, 0.00),
(105, 16, 1, 5.00, 0.00),
-- Venda 106 (25.00) -> Neosaldina (35... não). Novalgina (25.00).
(106, 2, 1, 25.00, 0.00),
-- Venda 107 (55.00) -> Pampers
(107, 41, 1, 55.00, 0.00),
-- Venda 108 (12.00) -> Ibuprofeno
(108, 8, 1, 12.00, 0.00);

-- 4. PAGAMENTOS
INSERT INTO VENDA_PAGAMENTOS (ID_Venda, ID_Forma_Pag, Valor, Troco, Quant_Vezes) VALUES
(100, 1, 100.00, 15.00, 1), -- Dinheiro
(101, 1, 20.00, 5.00, 1),   -- Dinheiro
(102, 2, 120.00, 0.00, 2),  -- Crédito 2x
(103, 4, 450.00, 0.00, 1),  -- Pix
(104, 3, 80.00, 0.00, 1),   -- Débito PJ
(105, 1, 55.00, 0.00, 1),   -- Dinheiro exato
(106, 3, 25.00, 0.00, 1),   -- Débito
(107, 2, 55.00, 0.00, 1),   -- Crédito
(108, 1, 20.00, 8.00, 1);   -- Dinheiro

-- 5. BAIXA DE ESTOQUE (Movimentação)
INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Motivo, Quantidade, ID_Venda, Data_Movimentacao) VALUES
(16, 16, 8, 'Saída', 'Venda PDV', 1, 100, '2025-11-20 08:30:00'),
(19, 19, 8, 'Saída', 'Venda PDV', 1, 100, '2025-11-20 08:30:00'),
(3, 3, 8, 'Saída', 'Venda PDV', 2, 100, '2025-11-20 08:30:00'),
(1, 1, 8, 'Saída', 'Venda PDV', 1, 100, '2025-11-20 08:30:00'),
(9, 9, 8, 'Saída', 'Venda PDV', 1, 100, '2025-11-20 08:30:00'),
(48, 48, 8, 'Saída', 'Venda PDV', 2, 101, '2025-11-20 10:15:00'),
(44, 44, 8, 'Saída', 'Venda PDV', 1, 102, '2025-11-20 12:45:00'),
(36, 36, 8, 'Saída', 'Venda PDV', 1, 102, '2025-11-20 12:45:00'),
(50, 50, 8, 'Saída', 'Venda PDV', 1, 102, '2025-11-20 12:45:00'),
(46, 46, 9, 'Saída', 'Venda PDV', 2, 103, '2025-11-20 15:30:00'),
(47, 47, 9, 'Saída', 'Venda PDV', 1, 103, '2025-11-20 15:30:00'),
(8, 8, 9, 'Saída', 'Venda PDV', 1, 103, '2025-11-20 15:30:00'),
(50, 50, 9, 'Saída', 'Venda PDV', 10, 104, '2025-11-20 17:00:00'),
(12, 12, 9, 'Saída', 'Venda PDV', 1, 105, '2025-11-20 19:30:00'),
(16, 16, 9, 'Saída', 'Venda PDV', 1, 105, '2025-11-20 19:30:00'),
(2, 2, 5, 'Saída', 'Venda PDV', 1, 106, '2025-11-20 22:15:00'),
(41, 41, 5, 'Saída', 'Venda PDV', 1, 107, '2025-11-20 23:10:00'),
(8, 8, 5, 'Saída', 'Venda PDV', 1, 108, '2025-11-20 23:45:00');


-- =================================================================
-- HISTÓRICO DIA 2: 21/11/2025 (SEXTA-FEIRA)
-- IDs iniciam em 200 para manter organização
-- =================================================================

-- 1. ABERTURA E FECHAMENTO DE CAIXAS (Saldos Calculados)
INSERT INTO CAIXAS_ABERTOS (ID_CaixaAberto, ID_Caixa, ID_Funcionario, ID_Turno, Data_Abertura, Saldo_Inicial, Data_Fechamento, Saldo_Final) VALUES
(200, 1, 8, 1, '2025-11-21 07:05:00', 200.00, '2025-11-21 14:55:00', 240.00), -- Manhã
(201, 2, 9, 2, '2025-11-21 14:00:00', 150.00, '2025-11-21 22:00:00', 210.00), -- Tarde
(202, 2, 5, 3, '2025-11-21 22:05:00', 100.00, '2025-11-21 23:59:59', 135.00); -- Noite

-- 2. MOVIMENTAÇÕES DE CAIXA (Apenas Suprimentos e Sangrias)
INSERT INTO MOVIMENTACOES_CAIXA (ID_Caixa, ID_Funcionario, Tipo, Valor, Descricao, Data_Movimentacao) VALUES
-- Manhã
(1, 8, 'Entrada', 200.00, 'Suprimento - Abertura de Caixa', '2025-11-21 07:05:00'),
(1, 8, 'Saída', 240.00, 'Sangria - Finalização de Caixa', '2025-11-21 14:55:00'),
-- Tarde
(2, 9, 'Entrada', 150.00, 'Suprimento - Abertura de Caixa', '2025-11-21 14:00:00'),
(2, 9, 'Saída', 210.00, 'Sangria - Finalização de Caixa', '2025-11-21 22:00:00'),
-- Noite
(2, 5, 'Entrada', 100.00, 'Suprimento - Abertura de Caixa', '2025-11-21 22:05:00'),
(2, 5, 'Saída', 135.00, 'Sangria - Finalização de Caixa', '2025-11-21 23:55:00');

-- 3. VENDAS (IDs 200 a 208)
INSERT INTO VENDAS (ID_Venda, ID_Funcionario, ID_CaixaAberto, ID_Cliente, DataHora_Venda, Valor_Total, Desconto) VALUES
-- --- MANHÃ (Patricia) ---
(200, 8, 200, 7, '2025-11-21 09:10:00', 30.00, 0.00),   -- Cliente Camila (Higiene)
(201, 8, 200, NULL, '2025-11-21 11:30:00', 180.00, 0.00), -- Dermocosmético (Presente)
(202, 8, 200, NULL, '2025-11-21 13:00:00', 10.00, 0.00),  -- Curativos
-- --- TARDE (Lucas - Foco Cosméticos/Sextou) ---
(203, 9, 201, 9, '2025-11-21 16:15:00', 60.00, 0.00),    -- Bruna Costa (Shampoo/Condicionador)
(204, 9, 201, 19, '2025-11-21 18:40:00', 250.00, 10.00), -- Academia FitLife (Whey)
(205, 9, 201, NULL, '2025-11-21 20:00:00', 95.00, 0.00), -- Protetor Solar
-- --- NOITE (Fernanda - Emergências de Sexta) ---
(206, 5, 202, NULL, '2025-11-21 22:45:00', 20.00, 0.00), -- Dor de Estômago
(207, 5, 202, 5, '2025-11-21 23:15:00', 45.00, 0.00),    -- Desodorante + Higiene
(208, 5, 202, NULL, '2025-11-21 23:50:00', 15.00, 0.00); -- Dipirona gotas

-- 4. ITENS DA VENDA
INSERT INTO ITENS_VENDA (ID_Venda, ID_Produto, Quantidade, Valor_Total, Desconto) VALUES
-- Venda 200 (30.00) -> 1 Creme Dental (9.50) + 1 Fio Dental? Não tenho. 
-- Vamos usar: 3 Pastas de Dente (3 * 9.50 = 28.50) + arredondamento no preço?
-- Melhor: 1 Shampoo Dove (22.90) + 1 Losartana (5.00) = 27.90. + Ajuste (Soro a 2.10).
-- Simplificando: 6x Losartana (6 * 5.00 = 30.00) - Uso contínuo "estoque do mês".
(200, 16, 6, 30.00, 0.00),
-- Venda 201 (180.00) -> 2x CeraVe (95 * 2 = 190). Desconto de 10.
(201, 45, 2, 190.00, 10.00),
-- Venda 202 (10.00) -> Nimesulida (10.00)
(202, 9, 1, 10.00, 0.00),
-- Venda 203 (60.00) -> Shampoo (22.90) + Cond (22.90) = 45.80. + 1 Pasta (9.50) = 55.30. + 1 Losartana (4.70).
-- Ajustando: 1 Shampoo (22.90) + 1 Condicionador (22.90) + 1 Simeticona (14.00) = 59.80. Arredondou 0.20.
(203, 36, 1, 22.90, 0.00),
(203, 37, 1, 22.90, 0.00),
(203, 35, 1, 14.20, 0.00), -- Simeticona
-- Venda 204 (250.00 - era 260 c/ 10 desc) -> Whey (169.90) + Creatina (99.90) = 269.80.
-- Vamos vender: 1 Whey (169.90) + 1 Protetor Solar (89.90) = 259.80. Desconto 9.80 para fechar 250.
(204, 46, 1, 169.90, 0.00),
(204, 44, 1, 89.90, 9.80),
-- Venda 205 (95.00) -> 1 CeraVe
(205, 45, 1, 95.00, 0.00),
-- Venda 206 (20.00) -> 1 Simeticona (14.00) + 1 Dipirona (6.00 desconto no preço)
-- Ou: 2x Nimesulida (10.00 * 2 = 20.00)
(206, 9, 2, 20.00, 0.00),
-- Venda 207 (45.00) -> 1 Rexona (24.90) + 1 Pasta (9.50) = 34.40. + 1 Paracetamol (9.90) = 44.30.
-- Ajustando: 2x Rexona (24.90 * 2 = 49.80). Desconto de 4.80.
(207, 39, 2, 49.80, 4.80),
-- Venda 208 (15.00) -> 1 Band-aid (7.50) + 1 Soro (8.00) = 15.50. Desconto 0.50.
(208, 48, 1, 7.50, 0.00),
(208, 50, 1, 8.00, 0.50);

-- 5. PAGAMENTOS
-- Manhã (240 final - 200 inicio = 40.00 em dinheiro nas vendas)
INSERT INTO VENDA_PAGAMENTOS (ID_Venda, ID_Forma_Pag, Valor, Troco, Quant_Vezes) VALUES
(200, 1, 50.00, 20.00, 1), -- Dinheiro (Entrou 30)
(201, 2, 180.00, 0.00, 3), -- Crédito
(202, 1, 10.00, 0.00, 1),  -- Dinheiro (Entrou 10) -> Total Manhã 40.00
-- Tarde (210 final - 150 inicio = 60.00 em dinheiro nas vendas)
(203, 1, 60.00, 0.00, 1),  -- Dinheiro (Entrou 60)
(204, 4, 250.00, 0.00, 1), -- Pix
(205, 3, 95.00, 0.00, 1),  -- Débito -> Total Tarde 60.00
-- Noite (135 final - 100 inicio = 35.00 em dinheiro nas vendas)
(206, 1, 20.00, 0.00, 1),  -- Dinheiro (Entrou 20)
(207, 2, 45.00, 0.00, 1),  -- Crédito
(208, 1, 20.00, 5.00, 1);  -- Dinheiro (Entrou 15) -> Total Noite 35.00

-- 6. MOVIMENTAÇÃO DE ESTOQUE
INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Motivo, Quantidade, ID_Venda, Data_Movimentacao) VALUES
(16, 16, 8, 'Saída', 'Venda PDV', 6, 200, '2025-11-21 09:10:00'),
(45, 45, 8, 'Saída', 'Venda PDV', 2, 201, '2025-11-21 11:30:00'),
(9, 9, 8, 'Saída', 'Venda PDV', 1, 202, '2025-11-21 13:00:00'),
(36, 36, 9, 'Saída', 'Venda PDV', 1, 203, '2025-11-21 16:15:00'),
(37, 37, 9, 'Saída', 'Venda PDV', 1, 203, '2025-11-21 16:15:00'),
(35, 35, 9, 'Saída', 'Venda PDV', 1, 203, '2025-11-21 16:15:00'),
(46, 46, 9, 'Saída', 'Venda PDV', 1, 204, '2025-11-21 18:40:00'),
(44, 44, 9, 'Saída', 'Venda PDV', 1, 204, '2025-11-21 18:40:00'),
(45, 45, 9, 'Saída', 'Venda PDV', 1, 205, '2025-11-21 20:00:00'),
(9, 9, 5, 'Saída', 'Venda PDV', 2, 206, '2025-11-21 22:45:00'),
(39, 39, 5, 'Saída', 'Venda PDV', 2, 207, '2025-11-21 23:15:00'),
(48, 48, 5, 'Saída', 'Venda PDV', 1, 208, '2025-11-21 23:50:00'),
(50, 50, 5, 'Saída', 'Venda PDV', 1, 208, '2025-11-21 23:50:00');


-- =================================================================
-- HISTÓRICO DIA 3: 22/11/2025 (SÁBADO)
-- IDs iniciam em 300
-- =================================================================

-- 1. ABERTURA E FECHAMENTO DE CAIXAS
INSERT INTO CAIXAS_ABERTOS (ID_CaixaAberto, ID_Caixa, ID_Funcionario, ID_Turno, Data_Abertura, Saldo_Inicial, Data_Fechamento, Saldo_Final) VALUES
(300, 1, 8, 1, '2025-11-22 08:00:00', 200.00, '2025-11-22 14:00:00', 225.00), -- Manhã (Horário reduzido sábado)
(301, 2, 9, 2, '2025-11-22 14:00:00', 150.00, '2025-11-22 20:00:00', 190.00), -- Tarde
(302, 2, 5, 3, '2025-11-22 20:00:00', 100.00, '2025-11-22 23:59:59', 130.00); -- Noite (Fernanda Guerreira)

-- 2. MOVIMENTAÇÕES DE CAIXA (Suprimentos e Sangrias)
INSERT INTO MOVIMENTACOES_CAIXA (ID_Caixa, ID_Funcionario, Tipo, Valor, Descricao, Data_Movimentacao) VALUES
-- Manhã
(1, 8, 'Entrada', 200.00, 'Suprimento - Abertura de Caixa', '2025-11-22 08:00:00'),
(1, 8, 'Saída', 225.00, 'Sangria - Finalização de Caixa', '2025-11-22 14:00:00'),
-- Tarde
(2, 9, 'Entrada', 150.00, 'Suprimento - Abertura de Caixa', '2025-11-22 14:00:00'),
(2, 9, 'Saída', 190.00, 'Sangria - Finalização de Caixa', '2025-11-22 20:00:00'),
-- Noite
(2, 5, 'Entrada', 100.00, 'Suprimento - Abertura de Caixa', '2025-11-22 20:00:00'),
(2, 5, 'Saída', 130.00, 'Sangria - Finalização de Caixa', '2025-11-22 23:55:00');

-- 3. VENDAS (IDs 300 a 308)
INSERT INTO VENDAS (ID_Venda, ID_Funcionario, ID_CaixaAberto, ID_Cliente, DataHora_Venda, Valor_Total, Desconto) VALUES
-- --- MANHÃ (Patricia) ---
(300, 8, 300, NULL, '2025-11-22 09:15:00', 15.00, 0.00),  -- Curativos (Futebol de sábado)
(301, 8, 300, 13, '2025-11-22 11:00:00', 120.00, 5.00), -- Larissa Manoela (Dermocosméticos)
(302, 8, 300, NULL, '2025-11-22 12:45:00', 10.00, 0.00),  -- Dipirona rápida
-- --- TARDE (Lucas) ---
(303, 9, 301, 3, '2025-11-22 15:30:00', 200.00, 0.00),   -- PJ PetLove (Algodão/Soro)
(304, 9, 301, NULL, '2025-11-22 17:15:00', 40.00, 0.00),  -- Fraldas e Lenços
(305, 9, 301, 22, '2025-11-22 19:00:00', 85.00, 0.00),    -- Ivete Sangalo (Vitaminas)
-- --- NOITE (Fernanda) ---
(306, 5, 302, NULL, '2025-11-22 21:30:00', 20.00, 0.00),  -- Dorflex (Pós-festa)
(307, 5, 302, 6, '2025-11-22 22:45:00', 60.00, 0.00),     -- Motorista PJ (Energético/Vitamina C)
(308, 5, 302, NULL, '2025-11-22 23:40:00', 10.00, 0.00);  -- Soro Fisiológico

-- 4. ITENS DA VENDA
INSERT INTO ITENS_VENDA (ID_Venda, ID_Produto, Quantidade, Valor_Total, Desconto) VALUES
-- Venda 300 (15.00) -> 2x Band-aid (7.50 * 2)
(300, 48, 2, 15.00, 0.00),
-- Venda 301 (120.00) -> 1 CeraVe (95.00) + 1 Shampoo Dove (22.90) = 117.90? Não.
-- Vamos fazer: 4x Protetor Solar (Ops, caro). 
-- Vamos fazer: 1 Protetor Solar (89.90) + 1 Soro (8.00) + 1 Shampoo (22.90) = 120.80. Desconto 0.80.
(301, 44, 1, 89.90, 0.00),
(301, 50, 1, 8.00, 0.80),
(301, 36, 1, 22.90, 0.00),
-- Venda 302 (10.00) -> 1 Nimesulida (10.00)
(302, 9, 1, 10.00, 0.00),
-- Venda 303 (200.00) -> PJ comprando estoque
-- 20x Soro (8.00 * 20 = 160.00) + 8x Algodão (5.00 * 8 = 40.00)
(303, 50, 20, 160.00, 0.00),
(303, 49, 8, 40.00, 0.00),
-- Venda 304 (40.00) -> 1 Pacote Fralda (Ops, Pampers é 55). 
-- Vamos de: 2x Lenços Umedecidos (16.90 * 2 = 33.80) + 1 Dipirona (6.20 ajuste).
(304, 42, 2, 33.80, 0.00),
(304, 1, 1, 6.20, 0.00), -- Dipirona com desconto
-- Venda 305 (85.00) -> 1 Addera D3 (70.00) + 1 Vitamina C (15.00) = 85.00 c/ desc
-- Addera é 70, Vit C é 25. Total 95. Desconto 10.
(305, 34, 1, 70.00, 0.00),
(305, 33, 1, 25.00, 10.00),
-- Venda 306 (20.00) -> 1 Neosaldina (Ops, 35). 
-- 1 Dorflex (22.00). Desconto 2.00.
(306, 5, 1, 22.00, 2.00),
-- Venda 307 (60.00) -> 2x Vitamina C (25 * 2 = 50) + 1 Ibuprofeno (10)
(307, 33, 2, 50.00, 0.00),
(307, 8, 1, 10.00, 0.00),
-- Venda 308 (10.00) -> 1 Soro (8.00) + Troco/Gorjeta? Não.
-- 1 Nimesulida (10.00)
(308, 9, 1, 10.00, 0.00);

-- 5. PAGAMENTOS
-- Manhã (25.00 em dinheiro)
INSERT INTO VENDA_PAGAMENTOS (ID_Venda, ID_Forma_Pag, Valor, Troco, Quant_Vezes) VALUES
(300, 1, 20.00, 5.00, 1),  -- Dinheiro (Entrou 15)
(301, 2, 120.00, 0.00, 2), -- Crédito
(302, 1, 10.00, 0.00, 1),  -- Dinheiro (Entrou 10) -> Total Manhã 25.00
-- Tarde (40.00 em dinheiro)
(303, 4, 200.00, 0.00, 1), -- Pix
(304, 1, 40.00, 0.00, 1),  -- Dinheiro (Entrou 40)
(305, 3, 85.00, 0.00, 1),  -- Débito -> Total Tarde 40.00
-- Noite (30.00 em dinheiro)
(306, 1, 20.00, 0.00, 1),  -- Dinheiro (Entrou 20)
(307, 2, 60.00, 0.00, 1),  -- Crédito
(308, 1, 20.00, 10.00, 1); -- Dinheiro (Entrou 10) -> Total Noite 30.00

-- 6. MOVIMENTAÇÃO DE ESTOQUE
INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Motivo, Quantidade, ID_Venda, Data_Movimentacao) VALUES
(48, 48, 8, 'Saída', 'Venda PDV', 2, 300, '2025-11-22 09:15:00'),
(44, 44, 8, 'Saída', 'Venda PDV', 1, 301, '2025-11-22 11:00:00'),
(50, 50, 8, 'Saída', 'Venda PDV', 1, 301, '2025-11-22 11:00:00'),
(36, 36, 8, 'Saída', 'Venda PDV', 1, 301, '2025-11-22 11:00:00'),
(9, 9, 8, 'Saída', 'Venda PDV', 1, 302, '2025-11-22 12:45:00'),
(50, 50, 9, 'Saída', 'Venda PDV', 20, 303, '2025-11-22 15:30:00'),
(49, 49, 9, 'Saída', 'Venda PDV', 8, 303, '2025-11-22 15:30:00'),
(42, 42, 9, 'Saída', 'Venda PDV', 2, 304, '2025-11-22 17:15:00'),
(1, 1, 9, 'Saída', 'Venda PDV', 1, 304, '2025-11-22 17:15:00'),
(34, 34, 9, 'Saída', 'Venda PDV', 1, 305, '2025-11-22 19:00:00'),
(33, 33, 9, 'Saída', 'Venda PDV', 1, 305, '2025-11-22 19:00:00'),
(5, 5, 5, 'Saída', 'Venda PDV', 1, 306, '2025-11-22 21:30:00'),
(33, 33, 5, 'Saída', 'Venda PDV', 2, 307, '2025-11-22 22:45:00'),
(8, 8, 5, 'Saída', 'Venda PDV', 1, 307, '2025-11-22 22:45:00'),
(9, 9, 5, 'Saída', 'Venda PDV', 1, 308, '2025-11-22 23:40:00');

-- =================================================================
-- HISTÓRICO DIA 4: 23/11/2025 (DOMINGO)
-- Plantão Solo: Lucas
-- IDs iniciam em 400
-- =================================================================

-- 1. ABERTURA E FECHAMENTO DE CAIXA
-- Turno 1 (Manhã/Almoço) adaptado para domingo
INSERT INTO CAIXAS_ABERTOS (ID_CaixaAberto, ID_Caixa, ID_Funcionario, ID_Turno, Data_Abertura, Saldo_Inicial, Data_Fechamento, Saldo_Final) VALUES
(400, 2, 9, 1, '2025-11-23 09:00:00', 150.00, '2025-11-23 14:00:00', 207.00);

-- 2. MOVIMENTAÇÕES DE CAIXA (Suprimento e Sangria Final)
INSERT INTO MOVIMENTACOES_CAIXA (ID_Caixa, ID_Funcionario, Tipo, Valor, Descricao, Data_Movimentacao) VALUES
(2, 9, 'Entrada', 150.00, 'Suprimento - Abertura Domingo', '2025-11-23 09:00:00'),
(2, 9, 'Saída', 207.00, 'Sangria - Fechamento Domingo', '2025-11-23 14:00:00');

-- 3. VENDAS (IDs 400 a 403)
INSERT INTO VENDAS (ID_Venda, ID_Funcionario, ID_CaixaAberto, ID_Cliente, DataHora_Venda, Valor_Total, Desconto) VALUES
(400, 9, 400, NULL, '2025-11-23 09:45:00', 49.00, 0.00),   -- Kit Ressaca
(401, 9, 400, 10, '2025-11-23 11:20:00', 97.40, 0.00),    -- Felipe Lima (Protetor Solar p/ Churrasco)
(402, 9, 400, 2, '2025-11-23 12:30:00', 55.00, 0.00),     -- João Pedro (Fraldas acabaram)
(403, 9, 400, NULL, '2025-11-23 13:50:00', 8.00, 0.00);   -- Soro (Último cliente)

-- 4. ITENS DA VENDA
INSERT INTO ITENS_VENDA (ID_Venda, ID_Produto, Quantidade, Valor_Total, Desconto) VALUES
-- Venda 400 (49.00) -> Neosaldina (35.00) + Simeticona (14.00)
(400, 6, 1, 35.00, 0.00),
(400, 35, 1, 14.00, 0.00),

-- Venda 401 (97.40) -> Protetor Solar La Roche (89.90) + Band-Aid (7.50)
(401, 44, 1, 89.90, 0.00),
(401, 48, 1, 7.50, 0.00),

-- Venda 402 (55.00) -> Fralda Pampers
(402, 41, 1, 55.00, 0.00),

-- Venda 403 (8.00) -> Soro Fisiológico
(403, 50, 1, 8.00, 0.00);

-- 5. PAGAMENTOS
-- Total em dinheiro esperado: 49 + 8 = 57.00
INSERT INTO VENDA_PAGAMENTOS (ID_Venda, ID_Forma_Pag, Valor, Troco, Quant_Vezes) VALUES
(400, 1, 50.00, 1.00, 1),  -- Dinheiro (Entrou 49)
(401, 3, 97.40, 0.00, 1),  -- Débito
(402, 2, 55.00, 0.00, 1),  -- Crédito
(403, 1, 10.00, 2.00, 1);  -- Dinheiro (Entrou 8)

-- 6. MOVIMENTAÇÃO DE ESTOQUE
INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Motivo, Quantidade, ID_Venda, Data_Movimentacao) VALUES
(6, 6, 9, 'Saída', 'Venda PDV', 1, 400, '2025-11-23 09:45:00'),
(35, 35, 9, 'Saída', 'Venda PDV', 1, 400, '2025-11-23 09:45:00'),
(44, 44, 9, 'Saída', 'Venda PDV', 1, 401, '2025-11-23 11:20:00'),
(48, 48, 9, 'Saída', 'Venda PDV', 1, 401, '2025-11-23 11:20:00'),
(41, 41, 9, 'Saída', 'Venda PDV', 1, 402, '2025-11-23 12:30:00'),
(50, 50, 9, 'Saída', 'Venda PDV', 1, 403, '2025-11-23 13:50:00');

-- =================================================================
-- HISTÓRICO DIA 4: 24/11/2025 (SEGUNDA)
-- =================================================================

-- 2. ABRIR CAIXAS (Simulando abertura de turno)
-- Caixa 1 aberto pela manhã por Patricia (Func 8) com R$ 200 de troco
INSERT INTO CAIXAS_ABERTOS (ID_CaixaAberto, ID_Caixa, ID_Funcionario, ID_Turno, Data_Abertura, Saldo_Inicial) VALUES
(1, 1, 8, 1, CONCAT(CURDATE(), ' 07:50:00'), 200.00);
-- Caixa 2 aberto à tarde por Lucas (Func 9) com R$ 150 de troco
INSERT INTO CAIXAS_ABERTOS (ID_CaixaAberto, ID_Caixa, ID_Funcionario, ID_Turno, Data_Abertura, Saldo_Inicial) VALUES
(2, 2, 9, 2, CONCAT(CURDATE(), ' 13:50:00'), 150.00);

-- 3. MOVIMENTAÇÃO DO CAIXA 
INSERT INTO MOVIMENTACOES_CAIXA (ID_Caixa, ID_Funcionario, Tipo, Valor, Descricao, Data_Movimentacao) VALUES
(1, 8, 'Entrada', 200.00, 'Suprimento - Abertura de Caixa', '2025-11-24 07:05:00'),
(1, 8, 'Saída', 291.00, 'Sangria - Finalização de Caixa', '2025-11-24 14:55:00'),
(2, 9, 'Entrada', 150.00, 'Suprimento - Abertura de Caixa', '2025-11-24 14:05:00');

INSERT INTO VENDAS (ID_Venda, ID_Funcionario, ID_CaixaAberto, ID_Cliente, DataHora_Venda, Valor_Total, Desconto) VALUES
-- Manhã (Caixa 1 - Func 8)
(1, 8, 1, 1, CONCAT(CURDATE(), ' 08:15:00'), 25.00, 0.00),   -- Venda Simples (Dipirona + Paracetamol)
(2, 8, 1, NULL, CONCAT(CURDATE(), ' 08:30:00'), 45.80, 0.00), -- Consumidor Final (Shampoo + Condicionador)
(3, 8, 1, 2, CONCAT(CURDATE(), ' 09:10:00'), 150.00, 0.00),   -- Venda Valor Alto (Xarelto)
(4, 8, 1, 5, CONCAT(CURDATE(), ' 09:45:00'), 50.00, 5.00),    -- Venda com Desconto
(5, 8, 1, 10, CONCAT(CURDATE(), ' 10:20:00'), 18.00, 0.00),   -- Genéricos
(6, 8, 1, NULL, CONCAT(CURDATE(), ' 11:00:00'), 89.90, 0.00), -- Dermocosmético (La Roche)
(7, 8, 1, 3, CONCAT(CURDATE(), ' 11:30:00'), 200.00, 0.00),   -- PJ (PetLove) comprando insumos
(8, 8, 1, 12, CONCAT(CURDATE(), ' 12:15:00'), 15.00, 0.00),   -- Pequena
-- Tarde (Caixa 2 - Func 9)
(9, 9, 2, 15, CONCAT(CURDATE(), ' 14:10:00'), 60.00, 0.00),   -- Kit Primeiros Socorros
(10, 9, 2, 20, CONCAT(CURDATE(), ' 15:30:00'), 339.80, 0.00), -- Suplementos (Neymar comprando Whey)
(11, 9, 2, NULL, CONCAT(CURDATE(), ' 16:00:00'), 25.00, 0.00),-- Rivotril (Controlado)
(12, 9, 2, 25, CONCAT(CURDATE(), ' 16:45:00'), 42.00, 0.00),  -- Ana Maria Braga (Dorflex + Tylenol)
(13, 9, 2, NULL, CONCAT(CURDATE(), ' 17:20:00'), 55.00, 0.00),-- Fraldas
(14, 9, 2, 8, CONCAT(CURDATE(), ' 18:00:00'), 120.00, 10.00), -- Medicamentos uso contínuo
(15, 9, 2, 30, CONCAT(CURDATE(), ' 19:30:00'), 8.00, 0.00);   -- Venda Rápida (Soro)

-- =================================================================
-- ITENS DA VENDA (Detalhes)
-- Preços baseados na tabela LOTES do Bloco 3
-- =================================================================
INSERT INTO ITENS_VENDA (ID_Venda, ID_Produto, Quantidade, Valor_Total, Desconto) VALUES
-- Venda 1 (Total 25.00) -> 2 Novalgina (25.00) Ops, Novalgina é caro. Vamos ajustar.
-- Novalgina (ID 2) é R$ 25.00. Vamos vender 1.
(1, 2, 1, 25.00, 0.00),
-- Venda 2 (Total 45.80) -> Shampoo (22.90) + Condicionador (22.90)
(2, 36, 1, 22.90, 0.00), -- Dove Shampoo
(2, 37, 1, 22.90, 0.00), -- Dove Condicionador
-- Venda 3 (Total 150.00) -> Xarelto (150.00)
(3, 21, 1, 150.00, 0.00),
-- Venda 4 (Total 50.00 com 5 desc -> Eram 55.00)
-- 1 Amoxicilina+Clav (55.00)
(4, 12, 1, 55.00, 5.00),
-- Venda 5 (Total 18.00) -> 2 Paracetamol (9.00 cada = 18.00)
(5, 4, 2, 18.00, 0.00),
-- Venda 6 (Total 89.90) -> Protetor Solar (89.90)
(6, 44, 1, 89.90, 0.00),
-- Venda 7 (Total 200.00) -> Insumos PJ
-- 10 Soro Fisiológico (8.00 * 10 = 80.00) + 24 Algodão (5.00 * 24 = 120.00)
(7, 50, 10, 80.00, 0.00),
(7, 49, 24, 120.00, 0.00),
-- Venda 8 (Total 15.00) -> Band-aid (7.50) + Soro (7.50 ops soro é 8). 
-- Vamos vender 2 Band-aid (7.50 * 2 = 15.00)
(8, 48, 2, 15.00, 0.00),
-- Venda 9 (Total 60.00) -> 2 cx Tylenol (28.00 * 2 = 56) + Sobra 4.00? Não bate.
-- Vamos fazer: 3 cx de Tylenol com desconto. 3 * 28 = 84. Não.
-- Vamos fazer: 2 Neosaldina (35 * 2 = 70).
-- Vamos ajustar o Item 9 para bater 60.00: 5 caixas de Dipirona (8.00 * 5 = 40.00) + 1 Cefalexina (18.00) = 58.00. + 1 Losartana (2.00 não, losartana é 5).
-- Simplificando: 3x Dipirona (24.00) + 2x Cefalexina (36.00) = 60.00
(9, 1, 3, 24.00, 0.00), -- Dipirona
(9, 14, 2, 36.00, 0.00), -- Cefalexina
-- Venda 10 (Total 339.80) -> 2 Whey Protein (169.90 * 2)
(10, 46, 2, 339.80, 0.00),
-- Venda 11 (Total 25.00) -> Rivotril (25.00)
(11, 22, 1, 25.00, 0.00),
-- Venda 12 (Total 42.00) -> Dorflex (22.00) + Omeprazol (20.00)
(12, 5, 1, 22.00, 0.00),
(12, 32, 1, 20.00, 0.00),
-- Venda 13 (Total 55.00) -> Fralda Pampers (55.00)
(13, 41, 1, 55.00, 0.00),
-- Venda 14 (Total 120.00 - era 130 com 10 desc)
-- 2x Yasmin (85.00 * 2 = 170... muito).
-- 2x Allegra (65.00 * 2 = 130.00). Desconto 10.00. Total 120.
(14, 29, 2, 130.00, 10.00),
-- Venda 15 (Total 8.00) -> 1 Soro
(15, 50, 1, 8.00, 0.00);

-- =================================================================
-- PAGAMENTOS
-- Formas: 1-Din, 2-Cred, 3-Deb, 4-Pix
-- =================================================================
INSERT INTO VENDA_PAGAMENTOS (ID_Venda, ID_Forma_Pag, Valor, Troco, Quant_Vezes) VALUES
(1, 1, 50.00, 25.00, 1),  -- Pagou com 50, Venda 25, Troco 25
(2, 3, 45.80, 0.00, 1),   -- Débito
(3, 2, 150.00, 0.00, 3),  -- Crédito 3x
(4, 4, 50.00, 0.00, 1),   -- Pix
(5, 1, 20.00, 2.00, 1),   -- Dinheiro
(6, 2, 89.90, 0.00, 1),   -- Crédito
(7, 4, 200.00, 0.00, 1),  -- Pix PJ
(8, 1, 15.00, 0.00, 1),   -- Dinheiro trocado
(9, 3, 60.00, 0.00, 1),   -- Débito
(10, 2, 339.80, 0.00, 2), -- Crédito 2x
(11, 1, 30.00, 5.00, 1),  -- Dinheiro
(12, 4, 42.00, 0.00, 1),  -- Pix
(13, 2, 55.00, 0.00, 1),  -- Crédito
(14, 2, 120.00, 0.00, 3), -- Crédito 3x
(15, 1, 10.00, 2.00, 1);  -- Dinheiro

-- =================================================================
-- MOVIMENTAÇÃO DE ESTOQUE (Baixa dos itens vendidos)
-- IDs correspondem aos itens inseridos acima
-- ID_Estoque 1 = Produto 1 (Dipirona), etc.
-- =================================================================
INSERT INTO MOVIMENTACAO_ESTOQUE (ID_Estoque, ID_Produto, ID_Funcionario, Tipo, Motivo, Quantidade, ID_Venda) VALUES
(2, 2, 8, 'Saída', 'Venda PDV', 1, 1),    -- Venda 1
(36, 36, 8, 'Saída', 'Venda PDV', 1, 2),  -- Venda 2
(37, 37, 8, 'Saída', 'Venda PDV', 1, 2),
(21, 21, 8, 'Saída', 'Venda PDV', 1, 3),  -- Venda 3
(12, 12, 8, 'Saída', 'Venda PDV', 1, 4),  -- Venda 4
(4, 4, 8, 'Saída', 'Venda PDV', 2, 5),    -- Venda 5
(44, 44, 8, 'Saída', 'Venda PDV', 1, 6),  -- Venda 6
(50, 50, 8, 'Saída', 'Venda PDV', 10, 7), -- Venda 7
(49, 49, 8, 'Saída', 'Venda PDV', 24, 7),
(48, 48, 8, 'Saída', 'Venda PDV', 2, 8),  -- Venda 8
(1, 1, 9, 'Saída', 'Venda PDV', 3, 9),    -- Venda 9 (Tarde - Func 9)
(14, 14, 9, 'Saída', 'Venda PDV', 2, 9),
(46, 46, 9, 'Saída', 'Venda PDV', 2, 10), -- Venda 10
(22, 22, 9, 'Saída', 'Venda PDV', 1, 11), -- Venda 11
(5, 5, 9, 'Saída', 'Venda PDV', 1, 12),   -- Venda 12
(32, 32, 9, 'Saída', 'Venda PDV', 1, 12),
(41, 41, 9, 'Saída', 'Venda PDV', 1, 13), -- Venda 13
(29, 29, 9, 'Saída', 'Venda PDV', 2, 14), -- Venda 14
(50, 50, 9, 'Saída', 'Venda PDV', 1, 15); -- Venda 15