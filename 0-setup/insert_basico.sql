INSERT INTO CONFIGURACOES (Nome_RazaoSocial, Nome_Fantasia, Slogan, Documento, CNES, Telefone, Loja, CEP, Endereco, End_Numero, Bairro, Cidade, Estado, Valor_Min_Parcelas, Quant_Max_Parcelas, Margem_Lucro_Padrao) VALUES
('Farmácia LavenderPharma', 'LavenderPharma', 'Cuidando de Você!', 'XX.XXX.XXX/0001-99', 'XXXXXXX', '(XX) 9XXXX-XXXX', '01', 
'01000000', 'Rua das Flores', '123', 'Centro', 'São Paulo', 'SP', 120.00, 3, 100.00);

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

INSERT INTO UNIDADES (Unidade, Abreviacao) VALUES
('Caixa', 'cx'),
('Comprimido', 'cp'),
('Frasco', 'fr');

INSERT INTO FORNECEDORES (Nome_Fantasia, Nome, CNPJ, Tel, Email, CEP, Endereco, End_Numero, Complemento, Bairro, Cidade, Estado, Status) VALUES 
('MedCenter Distribuidora', 'MedCenter Distribuidora de Medicamentos Ltda.', '12345678000190', '(11) 4004-1234', 'contato@medcenterdist.com.br', '01311000', 'Avenida Paulista', '1000', 'Conjunto 501', 'Bela Vista', 'São Paulo', 'SP', 'Ativo'),
('FarmaLog Logística', 'FarmaLog Logistica e Transporte Farmacêutico S.A.', '98765432000110', '(21) 3003-5678', 'vendas@farmalog.com.br', '20040030', 'Avenida Rio Branco', '156', 'Torre B, Andar 20', 'Centro', 'Rio de Janeiro', 'RJ', 'Ativo');

INSERT INTO TURNOS (Turno) VALUES
('Manhã'),
('Tarde'),
('Noite');

INSERT INTO CAIXAS (Caixa) VALUES
('Caixa01'),
('Caixa02');

INSERT INTO FORMAS_PAGAMENTO (Tipo) VALUES
('Dinheiro'),
('Cartão de Crédito'), 
('Cartão de Débito'),
('PIX');