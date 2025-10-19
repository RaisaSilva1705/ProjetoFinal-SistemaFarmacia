-- ---------------------------------------------------------------------
-- Exemplo 1: DORFLEX
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES (1, 'Dorflex 10cpr', 'Sanofi', 1, 'Analgésico e relaxante muscular', 2, '30049039', '7891058021832', 'Ativo', 'dorflex-c-10-comp.jpg');
SET @id_produto_dorflex = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES (@id_produto_dorflex, 1, 1, 'Referência', 'Dipirona Monoidratada + Citrato de Orfenadrina + Cafeína Anidra', '1832603540065', 'Não');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES (@id_produto_dorflex, 'LOTE-DORF-001', 5.50, 10.99, '2026-12-31');
SET @id_lote_dorflex = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_dorflex, 150, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 2: DIPIRONA MONOIDRATADA (Genérico)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES (1, 'Dipirona Monoidratada 500mg 10cpr', 'Medley', 2, 'Analgésico e antitérmico genérico', 2, '30049039', '7896422516314', 'Ativo', 'dipironamonoidratada_500mg_10cpr_medley.webp');
SET @id_produto_dipirona_medley = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES (@id_produto_dipirona_medley, 1, 2, 'Genérico', 'Dipirona Monoidratada', '1832603090018', 'Não');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES (@id_produto_dipirona_medley, 'LOTE-DIP-MED-001', 2.50, 5.49, '2027-05-31');
SET @id_lote_dipirona_medley = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_dipirona_medley, 300, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 3: NEOSALDINA (Similar)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES (1, 'Neosaldina 4 drágeas', 'Takeda', 1, 'Analgésico para dores de cabeça', 2, '30049039', '7896094911365', 'Ativo', 'neosaldina-30mg-300mg-30mg-blister-com-4-drageas.webp');
SET @id_produto_neosaldina = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES (@id_produto_neosaldina, 1, 1, 'Similar', 'Dipirona + Mucato de Isometepteno + Cafeína', '1063902310021', 'Não');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES (@id_produto_neosaldina, 'LOTE-NEO-001', 1.80, 4.29, '2025-10-31');
SET @id_lote_neosaldina = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_neosaldina, 250, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 4: AMOXICILINA (Genérico)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES (1, 'Amoxicilina 500mg 21cpr', 'EMS', 2, 'Antibiótico genérico', 2, '30041011', '7896004706132', 'Ativo', 'Amoxicilina_500MG_21cps_EMS.webp');
SET @id_produto_amoxi = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES (@id_produto_amoxi, 2, 2, 'Genérico', 'Amoxicilina Tri-hidratada', '1023505200138', 'Não');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES (@id_produto_amoxi, 'LOTE-AMX-001', 15.00, 29.90, '2026-08-31');
SET @id_lote_amoxi = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_amoxi, 80, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 5: TORSILAX (Referência)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES 
(1, 'Torsilax 12cpr', 'Neo Química', 1, 'Anti-inflamatório, analgésico e relaxante muscular', 2, '30049039', '7896714216839', 'Ativo', 'torxilax_12cp_neoquimica.webp');
SET @id_produto_torsi = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES 
(@id_produto_torsi, 3, 2, 'Referência', 'Diclofenaco Sódico + Carisoprodol + Paracetamol + Cafeína', '1781700220021', 'Não');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES 
(@id_produto_torsi, 'LOTE-TOR-001', 8.00, 15.99, '2027-01-31');
SET @id_lote_torsi = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES 
(@id_lote_torsi, 120, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 6: LOSARTANA POTÁSSICA (Genérico)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES 
(1, 'Losartana Potássica 50mg 30cpr', 'CIMED', 2, 'Anti-hipertensivo genérico', 2, '30049069', '7897947613567', 'Ativo', 'losartana-potassica-50mg-c-30-cp.webp');
SET @id_produto_losartana = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES 
(@id_produto_losartana, 4, 2, 'Genérico', 'Losartana Potássica', '1438101560029', 'Não');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES 
(@id_produto_losartana, 'LOTE-LOS-001', 3.50, 7.90, '2027-11-30');
SET @id_lote_losartana = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES 
(@id_lote_losartana, 400, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 7: RIVOTRIL (Referência - Controlado)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES 
(1, 'Rivotril 2,5mg/ml Gotas 20ml', 'Roche', 1, 'Ansiolítico', 1, '30049079', '7896226503525', 'Ativo', 'rivotrilgotas.webp');
SET @id_produto_rivotril = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES 
(@id_produto_rivotril, 5, 3, 'Referência', 'Clonazepam', '1010001260124', 'Sim');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES 
(@id_produto_rivotril, 'LOTE-RIV-001', 9.00, 18.50, '2026-09-30');
SET @id_lote_rivotril = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_rivotril, 50, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 8: CLONAZEPAM (Genérico - Controlado)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES 
(1, 'Clonazepam 2,5mg/ml Gotas 20ml', 'Geolab', 2, 'Ansiolítico genérico', 1, '30049079', '7898222384261', 'Ativo', 'Clonazepam_25mgml_gotas_20ml_geolab.png');
SET @id_produto_clona = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES 
(@id_produto_clona, 5, 3, 'Genérico', 'Clonazepam', '1542301130030', 'Sim');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES 
(@id_produto_clona, 'LOTE-CLO-001', 4.50, 9.99, '2027-02-28');
SET @id_lote_clona = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_clona, 90, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 9: PARACETAMOL (Genérico)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES 
(1, 'Paracetamol 750mg 20cpr', 'EMS', 2, 'Analgésico e antitérmico genérico', 2, '30049029', '7896004708341', 'Ativo', 'paracetamol-750mg-generico-ems-20-comprimidos.webp');
SET @id_produto_para = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES 
(@id_produto_para, 1, 1, 'Genérico', 'Paracetamol', '1023506690189', 'Não');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES 
(@id_produto_para, 'LOTE-PAR-001', 7.00, 14.20, '2028-01-31');
SET @id_lote_para = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_para, 200, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 10: LORATADINA (Genérico)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES 
(1, 'Loratadina 10mg 12cpr', 'CIMED', 1, 'Antialérgico genérico', 2, '30049069', '7897947600109', 'Ativo', 'loratatina_10mg_12cpr_cimed.webp');
SET @id_produto_lora = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES 
(@id_produto_lora, 6, 1, 'Genérico', 'Loratadina', '1438101290022', 'Não');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES 
(@id_produto_lora, 'LOTE-LOR-001', 5.00, 11.50, '2027-07-31');
SET @id_lote_lora = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_lora, 180, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 11: RITALINA (Referência - Controlado)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES 
(1, 'Ritalina 10mg 20cpr', 'Novartis', 1, 'Tratamento de TDAH', 2, '30049079', '7896261006023', 'Ativo', 'ritalina-10mg-20cpr.webp');
SET @id_produto_rita = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES 
(@id_produto_rita, 7, 3, 'Referência', 'Cloridrato de Metilfenidato', '1006800800051', 'Sim');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES 
(@id_produto_rita, 'LOTE-RIT-001', 22.00, 45.00, '2026-05-31');
SET @id_lote_rita = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_rita, 40, CURDATE());

-- ---------------------------------------------------------------------
-- Exemplo 12: PACO (Similar - Controlado)
-- ---------------------------------------------------------------------
INSERT INTO PRODUTOS (ID_Categoria, Nome, Marca, ID_Fornecedor, Descricao, ID_Unidade, NCM, EAN_GTIN, Status, Foto) VALUES 
(1, 'Paco 30mg/500mg 12cpr', 'Eurofarma', 2, 'Analgésico opioide', 2, '30049039', '7891317496290', 'Ativo', 'paco_30mg_500mg_12cpr_eurofarma.webp');
SET @id_produto_paco = LAST_INSERT_ID();
INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo, MS, Controlado) VALUES 
(@id_produto_paco, 1, 2, 'Similar', 'Fosfato de Codeína + Paracetamol', '1004310340019', 'Sim');
INSERT INTO LOTES (ID_Produto, Nome_Lote, Preco_Custo, Preco_Venda, Data_Validade) VALUES 
(@id_produto_paco, 'LOTE-PAC-001', 18.00, 35.50, '2027-08-31');
SET @id_lote_paco = LAST_INSERT_ID();
INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada) VALUES (@id_lote_paco, 75, CURDATE());