INSERT INTO CONFIGURACOES (Nome_RazaoSocial, Nome_Fantasia, Slogan, Documento, Loja, CEP, Endereco, End_Numero, Bairro, Cidade, Estado, Valor_Min_Parcelas, Quant_Max_Parcelas) VALUES
('Farmácia LavenderPharma', 'LavenderPharma', 'Cuidando de Você!', 'XX.XXX.XXX/0001-99', '01', 
'01000000', 'Rua das Flores', '123', 'Centro', 'São Paulo', 'SP', 120.00, 3);

INSERT INTO CARGOS (Cargo, Descricao) VALUES 
('Administrador', 'Acesso irrestrito ao sistema.'),
('Gerente', 'Acesso irrestrito ao sistema.'),
('Farmacêutico', 'Acesso especial ao sistema.');

INSERT INTO MODULOS (Modulo) VALUES 
-- Sem grupo
('Home'),
('Caixa PDV'),
('Minhas Comissões'),
('Configurações'),
-- Pessoas
('Clientes'),
('Usuários'),
('Funcionários'),
('Fornecedores'),
-- Cadastros
('Cargos'),
('Caixas'),
('Forma Pgto'),
-- Produtos
('Categorias'),
('Produtos'),
('Entradas'),
('Saídas'),
('Estoque'),
('Trocas'),
-- Financeiro
('Contas à Receber'),
('Despesas'),
('Compras'),
('Vendas'),
('Fluxo de Caixa'),
('Comissões'),
('Contas Vencidas'),
-- Relatórios
('Relatório de Vendas'),
('Relatório de Clientes'),
('Relatório de Recebimentos'),
('Relatório de Despesas'),
('Relatório de Lucro'),
('Relatório de Produtos'),
('Relatório de Estoque'),
('Relatório de Entrada/Saída'),
('Relatório de Caixas'),
('Relatório de Comissões'),
('Relatório de Trocas'),
('Relatório de Vendas Produtos'),
-- Vendas
('Orçamentos'),
('Contas Pendentes'),
('Todas as Vendas'),
('Atualizar Vendas');

INSERT INTO CARGOS_MODULOS (ID_Cargo, ID_Modulo, Acesso_Permitido)
SELECT 1, ID_Modulo, TRUE FROM MODULOS;

INSERT INTO FUNCIONARIOS (ID_Funcionario, Nome, Email, ID_Cargo)
VALUES (1, 'Administrador Geral', 'admin@admin.com', 1);

INSERT INTO USUARIOS (ID_Funcionario, Usuario, Senha, Data_Cadastro)
VALUES (1, 'admin', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', CURDATE());

INSERT INTO TARJAS_MEDICAMENTOS (Tarja) VALUES
('Medicamento Isento de Prescrição'),
('Amarela'),
('Amarela e Vermelha s/ Retenção de Prescrição'),
('Amarela e Vermelha c/ Retenção de Prescrição'),
('Amarela e Preta'),
('Vermelha s/ Retenção de Prescrição'),
('Vermelha c/ Retenção de Prescrição'),
('Preta');

INSERT INTO CATEGORIAS (Categoria) VALUES
('Medicamento'),
('Cosmético'),
('Higiene Pessoal'),
('Suplemento Alimentar'),
('Dispositivo Médico'),
('Alimento Funcional'),
('Materiais para Curativo'),
('Infantil'),
('Dermocosmético'),
('Equipamento Médico');

INSERT INTO CATEGORIAS_MEDICAMENTOS (Categoria_Med) VALUES
('Antibiótico'),
('Analgésico'),
('Anti-inflamatório'),
('Antialérgico'),
('Antifúngico'),
('Antiviral'),
('Ansiolítico'),
('Antipirético'),
('Anti-hipertensivo');

INSERT INTO UNIDADES (Unidade, Abreviacao, Tipo) VALUES
('Caixa', 'cx', 'Contagem'),
('Comprimido', 'cp', 'Contagem'),
('Frasco', 'fr', 'Volume');

INSERT INTO FORNECEDORES (Nome_Fantasia, Nome, CNPJ, Tel, Email, CEP, Endereco, End_Numero, Complemento, Bairro, Cidade, Estado, Status) VALUES 
('MedCenter Distribuidora', 'MedCenter Distribuidora de Medicamentos Ltda.', '12.345.678/0001-90', '(11) 4004-1234', 'contato@medcenterdist.com.br', '01311000', 'Avenida Paulista', '1000', 'Conjunto 501', 'Bela Vista', 'São Paulo', 'SP', 'Ativo'),
('FarmaLog Logística', 'FarmaLog Logistica e Transporte Farmacêutico S.A.', '98.765.432/0001-10', '(21) 3003-5678', 'vendas@farmalog.com.br', '20040030', 'Avenida Rio Branco', '156', 'Torre B, Andar 20', 'Centro', 'Rio de Janeiro', 'RJ', 'Ativo');

INSERT INTO PRODUTOS (ID_Categoria, Nome, ID_Unidade, NCM, EAN_GTIN, Foto) VALUES
(1, 'Paracetamol 750mg 20cp', 2, '30049099', '7896422500080', 'paracetamol750mg20cp.webp'),
(9, 'Creme Hidratante Neutrogena 200ml', 3, '33049990', '7891010246124', 'cremehidratanteneutrogena200ml.webp');

INSERT INTO MEDICAMENTOS (ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, Prin_Ativo) VALUES
(1, 2, 1, 'Genérico', 'Paracetamol');

INSERT INTO LOTES (Nome_Lote, ID_Produto, Preco_Custo, Preco_Venda, Data_Validade) VALUES
('L202505A', 1, 2.50, 4.50, '2025-12-31'),
('L202506B', 2, 0.00, 0.00, '2026-01-15');
/* 22.90 */

INSERT INTO ESTOQUE (ID_Lote, Quantidade, Data_Entrada, Data_Atualizacao) VALUES
(1, 100, NOW(), NOW()),
(2, 0, NOW(), NOW());

INSERT INTO TURNOS (Turno) VALUES
('Manhã'),
('Tarde'),
('Noite');

INSERT INTO CAIXAS (Caixa) VALUES
('Caixa01'),
('Caixa02');

INSERT INTO FORMAS_PAGAMENTO (Tipo) VALUES
('Dinheiro'), ('Cartão de Crédito'), ('Cartão de Débito'), ('PIX');