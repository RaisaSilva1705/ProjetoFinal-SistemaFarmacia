-- =================================================================
-- BLOCO 1: TABELAS BASE, AUXILIARES E CONFIGURAÇÃO INICIAL
-- =================================================================
INSERT INTO CONFIGURACOES (Nome_RazaoSocial, Nome_Fantasia, Slogan, Documento, CNES, Telefone, Loja, CEP, Endereco, End_Numero, Bairro, Cidade, Estado, Valor_Min_Parcelas, Quant_Max_Parcelas, Margem_Lucro_Padrao) VALUES
('Farmácia LavenderPharma', 'LavenderPharma', 'Cuidando de Você!', 'XX.XXX.XXX/0001-99', 'XXXXXXX', '(XX) 9XXXX-XXXX', '01', 
'01000000', 'Rua das Flores', '123', 'Centro', 'São Paulo', 'SP', 120.00, 3, 100.00);

INSERT INTO `MODULOS` (`Nome_Modulo`, `Chave_Acesso`) VALUES
('Acesso ao PDV', 'PDV_ACESSAR'),
('Acesso ao Financeiro', 'FINANCEIRO_VER'),
('Acesso às Promoções', 'PROMOCOES_GERENCIAR'),
('Acesso aos Relatórios', 'RELATORIOS_VER'),
('Cadastro de Produtos', 'PRODUTOS_GERENCIAR'),
('Gestão de Estoque', 'ESTOQUE_GERENCIAR'),
('Cadastro de Clientes', 'CLIENTES_GERENCIAR'),
('Cadastro de Funcionários', 'FUNCIONARIOS_GERENCIAR'),
('Cadastro de Fornecedores', 'FORNECEDORES_GERENCIAR'),
('Gestão de Controlados', 'CONTROLADOS_GERENCIAR'),
('Gestão de Serviços', 'SERVICOS_GERENCIAR'),
('Acesso às Configurações', 'CONFIGURACOES_GERENCIAR')
ON DUPLICATE KEY UPDATE Chave_Acesso=Chave_Acesso;

-- 1. CARGOS (Obrigatório + Novos Cargos)
-- ID 1 é o Admin (Solicitado). Geramos Farmacêutico, Balconista, etc.
INSERT INTO CARGOS (ID_Cargo, Cargo, Descricao, Status) VALUES 
(1, 'Administrador', 'Acesso irrestrito ao sistema.', 'Ativo'),
(2, 'Farmacêutico Responsável', 'Responsável técnico, SNGPC e Serviços.', 'Ativo'),
(3, 'Gerente de Loja', 'Gestão de equipe, estoque e relatórios.', 'Ativo'),
(4, 'Balconista', 'Atendimento ao cliente e vendas.', 'Ativo'),
(5, 'Operador de Caixa', 'Frente de caixa e recebimentos.', 'Ativo'),
(6, 'Estoquista', 'Recebimento de mercadorias e organização.', 'Ativo');

-- 2. CARGOS_MODULOS (Permissões)
-- Admin tem tudo (Cópia da tabela MODULOS para o ID 1, conforme solicitado)
INSERT INTO CARGOS_MODULOS (ID_Cargo, ID_Modulo)
SELECT 1, ID_Modulo FROM MODULOS;
-- Farmacêutico (2): PDV, Produtos, Clientes, Controlados(10), Serviços(11)
INSERT INTO CARGOS_MODULOS (ID_Cargo, ID_Modulo) VALUES
(2, 1), (2, 5), (2, 7), (2, 10), (2, 11);
-- Gerente (3): Tudo menos Configurações (12)
INSERT INTO CARGOS_MODULOS (ID_Cargo, ID_Modulo) VALUES
(3, 1), (3, 2), (3, 3), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 9), (3, 10), (3, 11);
-- Balconista (4): PDV, Produtos (Ver), Clientes, Estoque (Ver)
INSERT INTO CARGOS_MODULOS (ID_Cargo, ID_Modulo) VALUES
(4, 1), (4, 5), (4, 7);
-- Caixa (5): PDV, Financeiro (Limitado - mas o módulo é um só por enquanto)
INSERT INTO CARGOS_MODULOS (ID_Cargo, ID_Modulo) VALUES
(5, 1);
-- Estoquista (6): Produtos, Estoque, Fornecedores
INSERT INTO CARGOS_MODULOS (ID_Cargo, ID_Modulo) VALUES
(6, 5), (6, 6), (6, 9);

-- 3. FUNCIONARIO ADMIN (Obrigatório)
INSERT INTO FUNCIONARIOS (ID_Funcionario, Nome, Email, ID_Cargo, Data_Admissao, Status)
VALUES (1, 'Administrador Geral', 'admin@admin.com', 1, CURDATE(), 'Ativo');

-- 4. USUARIO ADMIN (Obrigatório)
INSERT INTO USUARIOS (ID_Funcionario, Usuario, Senha, Data_Cadastro, Status)
VALUES (1, 'admin', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', CURDATE(), 'Ativo');

-- 5. CATEGORIAS (Produtos Gerais)
INSERT INTO CATEGORIAS (ID_Categoria, Categoria, Status) VALUES
(1, 'Medicamentos', 'Ativo'),
(2, 'Perfumaria e Beleza', 'Ativo'),
(3, 'Higiene Pessoal', 'Ativo'),
(4, 'Dermocosméticos', 'Ativo'),
(5, 'Mamãe e Bebê', 'Ativo'),
(6, 'Ortopedia e Acessórios', 'Ativo'),
(7, 'Suplementos e Vitaminas', 'Ativo'),
(8, 'Primeiros Socorros', 'Ativo');

-- 6. UNIDADES (Medidas)
INSERT INTO UNIDADES (ID_Unidade, Unidade, Abreviacao, Status) VALUES
(1, 'Unidade', 'UN', 'Ativo'),
(2, 'Caixa', 'CX', 'Ativo'),
(3, 'Frasco', 'FR', 'Ativo'),
(4, 'Mililitro', 'ML', 'Ativo'),
(5, 'Grama', 'G', 'Ativo'),
(6, 'Pacote', 'PCT', 'Ativo'),
(7, 'Cartela', 'CT', 'Ativo');

-- 7. TURNOS
INSERT INTO TURNOS (ID_Turno, Turno) VALUES
(1, 'Manhã'),
(2, 'Tarde'),
(3, 'Noite');

-- 8. FORMAS_PAGAMENTO
INSERT INTO FORMAS_PAGAMENTO (ID_Forma_Pag, Tipo, Status) VALUES
(1, 'Dinheiro', 'Ativo'),
(2, 'Cartão de Crédito', 'Ativo'),
(3, 'Cartão de Débito', 'Ativo'),
(4, 'PIX', 'Ativo'),
(5, 'Convênio/Crediário', 'Inativo');

-- 9. CATEGORIAS_MEDICAMENTOS (Classificação Terapêutica)
INSERT INTO CATEGORIAS_MEDICAMENTOS (ID_CategoriaMed, Categoria_Med, Status) VALUES
(1, 'Analgésico/Antitérmico', 'Ativo'),
(2, 'Anti-inflamatório', 'Ativo'),
(3, 'Antibiótico', 'Ativo'),
(4, 'Anti-hipertensivo', 'Ativo'),
(5, 'Antidiabético', 'Ativo'),
(6, 'Antialérgico', 'Ativo'),
(7, 'Antidepressivo', 'Ativo'),
(8, 'Contraceptivo', 'Ativo');

-- 10. TARJAS_MEDICAMENTOS
INSERT INTO TARJAS_MEDICAMENTOS (ID_Tarja, Tarja, Status) VALUES
(1, 'Sem Tarja', 'Ativo'),
(2, 'Tarja Amarela', 'Ativo'),
(3, 'Tarja Vermelha', 'Ativo'),
(4, 'Tarja Vermelha', 'Ativo'),
(5, 'Tarja Preta', 'Ativo');

-- 11. SERVICOS_FARMACEUTICOS
INSERT INTO SERVICOS_FARMACEUTICOS (ID_Servico, Nome_Servico, Descricao, Valor, Status) VALUES
(1, 'Aferição de Pressão Arterial', 'Medição de pressão sistólica e diastólica.', 5.00, 'Ativo'),
(2, 'Teste de Glicemia Capilar', 'Verificação do nível de glicose no sangue.', 15.00, 'Ativo'),
(3, 'Aplicação de Injetáveis', 'Aplicação de medicamentos via intramuscular ou subcutânea.', 20.00, 'Ativo'),
(4, 'Colocação de Brincos', 'Perfuração de lóbulo auricular com pistola estéril.', 35.00, 'Ativo');

-- 12. SERVICO_CAMPOS (Campos dinâmicos para o registro clínico)
-- Campos para Pressão Arterial (ID_Servico 1)
INSERT INTO SERVICO_CAMPOS (ID_Campo, ID_Servico, Ordem, Label_Campo, Name_Campo, Tipo_Campo, Unidade_Medida) VALUES
(1, 1, 1, 'Pressão Sistólica (Máxima)', 'pressao_sistolica', 'number', 'mmHg'),
(2, 1, 2, 'Pressão Diastólica (Mínima)', 'pressao_diastolica', 'number', 'mmHg'),
(3, 1, 3, 'Pulsação', 'pulsacao', 'number', 'BPM');

-- Campos para Glicemia (ID_Servico 2)
INSERT INTO SERVICO_CAMPOS (ID_Campo, ID_Servico, Ordem, Label_Campo, Name_Campo, Tipo_Campo, Unidade_Medida) VALUES
(4, 2, 1, 'Valor da Glicemia', 'valor_glicemia', 'number', 'mg/dL'),
(5, 2, 2, 'Condição', 'condicao_jejum', 'text', NULL); -- Ex: Jejum, Pós-prandial

-- 13. SERVICO_CAMPOS_REFERENCIAS (Valores de referência para alertas)
INSERT INTO SERVICO_CAMPOS_REFERENCIAS (ID_Referencia, ID_Campo, Descricao_Referencia, Valor_Feminino, Valor_Masculino) VALUES
(1, 1, 'Normal', '< 120', '< 120'), -- Sistólica
(2, 2, 'Normal', '< 80', '< 80'), -- Diastólica
(3, 4, 'Jejum Normal', '70 a 99', '70 a 99'), -- Glicemia
(4, 4, 'Pré-Diabetes', '100 a 125', '100 a 125');

-- 14. DESPESAS_CATEGORIAS (Preparo para Bloco 5)
INSERT INTO DESPESAS_CATEGORIAS (ID_Categoria_Despesa, Nome_Categoria) VALUES
(1, 'Contas de Consumo (Água/Luz)'),
(2, 'Aluguel/Condomínio'),
(3, 'Fornecedores'),
(4, 'Materiais de Escritório'),
(5, 'Manutenção Predial'),
(6, 'Marketing e Publicidade');


-- =================================================================
-- BLOCO 2: ENTIDADES (FORNECEDORES, FUNCIONÁRIOS, USUÁRIOS, CLIENTES)
-- =================================================================

-- 1. FORNECEDORES (20 Registros de Laboratórios e Distribuidoras Reais)
INSERT INTO FORNECEDORES (ID_Fornecedor, Nome_Fantasia, Nome, CNPJ, Tel, Email, CEP, Endereco, End_Numero, Bairro, Cidade, Estado, Status) VALUES
(1, 'Eurofarma', 'Eurofarma Laboratórios S.A.', '61.190.096/0001-92', '(11) 5000-1111', 'contato@eurofarma.com.br', '04500000', 'Av. das Nações Unidas', '22532', 'Santo Amaro', 'São Paulo', 'SP', 'Ativo'),
(2, 'EMS', 'EMS S/A', '57.507.378/0001-01', '(19) 3800-1000', 'sac@ems.com.br', '13186901', 'Rod. Jornalista F. Aguirre', 'km 8', 'Chácara Assay', 'Hortolândia', 'SP', 'Ativo'),
(3, 'Medley', 'Medley Farmacêutica Ltda', '50.929.710/0001-79', '(19) 3000-2222', 'vendas@medley.com.br', '13000111', 'Rua Macedo Costa', '55', 'Distrito Industrial', 'Campinas', 'SP', 'Ativo'),
(4, 'Neo Química', 'Laboratório Neo Química', '29.785.870/0001-03', '(11) 4000-3333', 'comercial@neoquimica.com.br', '06000222', 'Via Anchieta', '400', 'Vila Mury', 'São Bernardo', 'SP', 'Ativo'),
(5, 'Santa Cruz', 'Distribuidora Santa Cruz', '45.888.999/0001-44', '(11) 2222-5555', 'pedidos@stacruz.com.br', '01000999', 'Av. Paulista', '1000', 'Bela Vista', 'São Paulo', 'SP', 'Ativo'),
(6, 'Panpharma', 'Panpharma Distribuidora', '02.333.444/0001-55', '(62) 3333-1111', 'contato@panpharma.com.br', '74000123', 'Rodovia BR 153', 'S/N', 'Jardim Goiás', 'Goiânia', 'GO', 'Ativo'),
(7, 'Profarma', 'Profarma Distribuidora', '33.111.222/0001-66', '(21) 2222-9999', 'sac@profarma.com.br', '20000555', 'Av. das Américas', '3000', 'Barra da Tijuca', 'Rio de Janeiro', 'RJ', 'Ativo'),
(8, 'Hypera Pharma', 'Hypera S.A.', '61.000.111/0001-77', '(11) 3333-7777', 'falecom@hypera.com.br', '04000888', 'Av. Magalhães de Castro', '4800', 'Cidade Jardim', 'São Paulo', 'SP', 'Ativo'),
(9, 'Aché', 'Aché Laboratórios', '60.659.463/0001-91', '(11) 2000-4444', 'vendas@ache.com.br', '07000123', 'Rodovia Dutra', 'km 222', 'Porto da Igreja', 'Guarulhos', 'SP', 'Ativo'),
(10, 'Cimed', 'Cimed Indústria de Medicamentos', '04.555.666/0001-88', '(35) 3444-2222', 'sac@cimed.com.br', '37550000', 'Av. Cel. Santa Rita', '100', 'Centro', 'Pouso Alegre', 'MG', 'Ativo'),
(11, 'Bayer', 'Bayer S.A.', '18.459.628/0001-15', '(11) 5694-5166', 'saude@bayer.com.br', '04779900', 'Rua Domingos Jorge', '1100', 'Socorro', 'São Paulo', 'SP', 'Ativo'),
(12, 'Sanofi', 'Sanofi Aventis Farmacêutica', '02.685.377/0001-57', '(11) 3759-6000', 'sac.brasil@sanofi.com', '05690000', 'Av. Major Sylvio', '5200', 'Morumbi', 'São Paulo', 'SP', 'Ativo'),
(13, 'Novartis', 'Novartis Biociências', '56.994.502/0001-30', '(11) 5532-7000', 'sic.novartis@novartis.com', '04707000', 'Av. Prof. Vicente Rao', '90', 'Brooklin', 'São Paulo', 'SP', 'Ativo'),
(14, 'Pfizer', 'Pfizer Brasil', '46.070.868/0001-69', '(11) 5000-1000', 'fale.pfizer@pfizer.com', '04717004', 'Rua Alexandre Dumas', '1711', 'Chácara Sto Antonio', 'São Paulo', 'SP', 'Ativo'),
(15, 'Johnson & Johnson', 'Johnson & Johnson do Brasil', '59.748.988/0001-14', '(11) 3030-1000', 'sac@its.jnj.com', '12240908', 'Rod. Pres. Dutra', 'km 154', 'Jardim das Indústrias', 'São José dos Campos', 'SP', 'Ativo'),
(16, 'União Química', 'União Química Farmacêutica', '60.665.981/0001-18', '(11) 5586-2000', 'sac@uniaoquimica.com.br', '04405000', 'Av. Magalhães de Castro', '4800', 'Cidade Jardim', 'São Paulo', 'SP', 'Ativo'),
(17, 'Teuto', 'Laboratório Teuto', '17.159.229/0001-76', '(62) 3310-2000', 'sac@teuto.com.br', '75132040', 'VP 7 Módulo 11', 'S/N', 'DAIA', 'Anápolis', 'GO', 'Ativo'),
(18, 'Prati-Donaduzzi', 'Prati-Donaduzzi Medicamentos', '73.856.593/0001-66', '(45) 2103-1000', 'cac@pratidonaduzzi.com.br', '85903220', 'Rua Mitsugoro Tanaka', '145', 'Centro', 'Toledo', 'PR', 'Ativo'),
(19, 'Hospira', 'Hospira Produtos Hospitalares', '08.666.888/0001-99', '(11) 4444-8888', 'contato@hospira.com.br', '06460000', 'Av. Tamboré', '100', 'Tamboré', 'Barueri', 'SP', 'Ativo'),
(20, 'Dimed', 'Dimed S/A Distribuidora', '92.665.611/0001-77', '(51) 3000-9999', 'vendas@dimed.com.br', '90000111', 'Av. Ipiranga', '2000', 'Praia de Belas', 'Porto Alegre', 'RS', 'Ativo');

-- 2. FUNCIONÁRIOS (Do ID 2 ao 11 - Admin é ID 1)
-- ID_Cargo: 2=Farmacêutico, 3=Gerente, 4=Balconista, 5=Caixa, 6=Estoquista
INSERT INTO FUNCIONARIOS (ID_Funcionario, Nome, Tipo, Documento, CRF, Telefone, ID_Cargo, Email, Salario, Data_Admissao, Status) VALUES
(2, 'Ana Clara Silva', 'PF', '111.222.333-44', 'CRF-SP 12345', '(11) 99999-1111', 2, 'ana.silva@lavender.com', 4500.00, '2023-01-15', 'Ativo'), -- Farmacêutica
(3, 'Carlos Eduardo Costa', 'PF', '222.333.444-55', 'CRF-SP 67890', '(11) 99999-2222', 2, 'carlos.costa@lavender.com', 4500.00, '2023-03-10', 'Ativo'), -- Farmacêutico
(4, 'Roberto Almeida', 'PF', '333.444.555-66', NULL, '(11) 99999-3333', 3, 'roberto.almeida@lavender.com', 5500.00, '2022-05-20', 'Ativo'), -- Gerente
(5, 'Fernanda Lima', 'PF', '444.555.666-77', NULL, '(11) 99999-4444', 4, 'fernanda.lima@lavender.com', 2200.00, '2023-06-01', 'Ativo'), -- Balconista
(6, 'Gabriel Souza', 'PF', '555.666.777-88', NULL, '(11) 99999-5555', 4, 'gabriel.souza@lavender.com', 2200.00, '2023-07-15', 'Ativo'), -- Balconista
(7, 'Juliana Mendes', 'PF', '666.777.888-99', NULL, '(11) 99999-6666', 4, 'juliana.mendes@lavender.com', 2200.00, '2023-08-01', 'Ativo'), -- Balconista
(8, 'Patricia Rocha', 'PF', '777.888.999-00', NULL, '(11) 99999-7777', 5, 'patricia.rocha@lavender.com', 1800.00, '2023-09-10', 'Ativo'), -- Caixa
(9, 'Lucas Martins', 'PF', '888.999.000-11', NULL, '(11) 99999-8888', 5, 'lucas.martins@lavender.com', 1800.00, '2023-10-05', 'Ativo'), -- Caixa
(10, 'Marcos Oliveira', 'PF', '999.000.111-22', NULL, '(11) 99999-9999', 6, 'marcos.oliveira@lavender.com', 2000.00, '2023-02-20', 'Ativo'), -- Estoquista
(11, 'Beatriz Santos', 'PF', '000.111.222-33', NULL, '(11) 98888-0000', 4, 'beatriz.santos@lavender.com', 2200.00, '2024-01-10', 'Ativo'); -- Balconista

-- 3. USUÁRIOS (Logins para os Funcionários acima)
-- Senha padrão igual a do admin ($2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO)
INSERT INTO USUARIOS (ID_Funcionario, Usuario, Senha, Status) VALUES
(2, 'ana.silva', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo'),
(3, 'carlos.costa', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo'),
(4, 'roberto.almeida', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo'),
(5, 'fernanda.lima', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo'),
(6, 'gabriel.souza', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo'),
(7, 'juliana.mendes', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo'),
(8, 'patricia.rocha', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo'),
(9, 'lucas.martins', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo'),
(10, 'marcos.oliveira', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo'),
(11, 'beatriz.santos', '$2y$10$ywTQsi8pt7ttAVku9vKPXuDhA.VuIqZkMRzLUKOgJKd.mmmD/yzUO', 'Ativo');

-- 4. CLIENTES (30 Registros - Mix de PF e PJ)
INSERT INTO CLIENTES (ID_Cliente, Nome, Tipo, Sexo, Genero, Data_Nascimento, Tel, Email, Senha, Status, Saldo_Credito) VALUES
(1, 'Maria Aparecida da Silva', 'PF', 'Feminino', 'Mulher Cis', '1985-05-10', '(11) 91111-1111', 'maria.cidinha@email.com', 'hashsenha123', 'Ativo', 50.00),
(2, 'João Pedro Oliveira', 'PF', 'Masculino', 'Homem Cis', '1990-08-20', '(11) 92222-2222', 'joao.pedro@email.com', 'hashsenha123', 'Ativo', 0.00),
(3, 'Clínica Veterinária PetLove', 'PJ', NULL, NULL, NULL, '(11) 3000-5000', 'contato@petlove.com.br', 'hashsenha123', 'Ativo', 1000.00),
(4, 'Lúcia Ferreira', 'PF', 'Feminino', 'Mulher Cis', '1975-12-05', '(11) 93333-3333', 'lucia.ferreira@email.com', 'hashsenha123', 'Ativo', 0.00),
(5, 'Rafael Souza', 'PF', 'Masculino', 'Homem Cis', '1998-02-15', '(11) 94444-4444', 'rafael.souza@email.com', 'hashsenha123', 'Ativo', 0.00),
(6, 'Empresa de Transporte Rápido', 'PJ', NULL, NULL, NULL, '(11) 3333-8888', 'financeiro@transrapido.com.br', 'hashsenha123', 'Ativo', 0.00),
(7, 'Camila Santos', 'PF', 'Feminino', 'Mulher Trans', '2000-07-22', '(11) 95555-5555', 'camila.santos@email.com', 'hashsenha123', 'Ativo', 0.00),
(8, 'Antônio Carlos', 'PF', 'Masculino', 'Homem Cis', '1960-03-30', '(11) 96666-6666', 'antonio.carlos@email.com', 'hashsenha123', 'Ativo', 15.50),
(9, 'Bruna Costa', 'PF', 'Feminino', 'Mulher Cis', '1995-11-11', '(11) 97777-7777', 'bruna.costa@email.com', 'hashsenha123', 'Ativo', 0.00),
(10, 'Felipe Lima', 'PF', 'Masculino', 'Homem Cis', '1988-06-25', '(11) 98888-8888', 'felipe.lima@email.com', 'hashsenha123', 'Ativo', 0.00),
(11, 'Escola Infantil Sol Nascente', 'PJ', NULL, NULL, NULL, '(11) 3222-1111', 'adm@solnascente.com.br', 'hashsenha123', 'Ativo', 200.00),
(12, 'Gustavo Henrique', 'PF', 'Masculino', 'Homem Cis', '1992-09-09', '(11) 99999-0000', 'gustavo.henrique@email.com', 'hashsenha123', 'Ativo', 0.00),
(13, 'Larissa Manoela', 'PF', 'Feminino', 'Mulher Cis', '2001-01-30', '(11) 90000-1111', 'larissa.manoela@email.com', 'hashsenha123', 'Ativo', 0.00),
(14, 'Alexandre Frota', 'PF', 'Masculino', 'Homem Cis', '1970-10-10', '(11) 90000-2222', 'alexandre.frota@email.com', 'hashsenha123', 'Ativo', 0.00),
(15, 'Restaurante Bom Sabor', 'PJ', NULL, NULL, NULL, '(11) 4000-5555', 'compras@bomsabor.com.br', 'hashsenha123', 'Ativo', 0.00),
(16, 'Patrícia Abravanel', 'PF', 'Feminino', 'Mulher Cis', '1982-04-14', '(11) 90000-3333', 'patricia.abravanel@email.com', 'hashsenha123', 'Ativo', 500.00),
(17, 'Roberto Carlos', 'PF', 'Masculino', 'Homem Cis', '1945-04-19', '(11) 90000-4444', 'roberto.carlos@email.com', 'hashsenha123', 'Ativo', 0.00),
(18, 'Xuxa Meneghel', 'PF', 'Feminino', 'Mulher Cis', '1963-03-27', '(11) 90000-5555', 'xuxa.meneghel@email.com', 'hashsenha123', 'Ativo', 0.00),
(19, 'Academia Fit Life', 'PJ', NULL, NULL, NULL, '(11) 3555-7777', 'contato@fitlife.com.br', 'hashsenha123', 'Ativo', 0.00),
(20, 'Neymar Junior', 'PF', 'Masculino', 'Homem Cis', '1992-02-05', '(11) 90000-6666', 'neymar.jr@email.com', 'hashsenha123', 'Ativo', 0.00),
(21, 'Anitta Machado', 'PF', 'Feminino', 'Mulher Cis', '1993-03-30', '(11) 90000-7777', 'anitta.machado@email.com', 'hashsenha123', 'Ativo', 0.00),
(22, 'Ivete Sangalo', 'PF', 'Feminino', 'Mulher Cis', '1972-05-27', '(11) 90000-8888', 'ivete.sangalo@email.com', 'hashsenha123', 'Ativo', 0.00),
(23, 'Posto de Gasolina Ipiranga', 'PJ', NULL, NULL, NULL, '(11) 3888-9999', 'gerente@postoipiranga.com.br', 'hashsenha123', 'Ativo', 0.00),
(24, 'Fausto Silva', 'PF', 'Masculino', 'Homem Cis', '1950-05-02', '(11) 90000-9999', 'fausto.silva@email.com', 'hashsenha123', 'Ativo', 0.00),
(25, 'Ana Maria Braga', 'PF', 'Feminino', 'Mulher Cis', '1949-04-01', '(11) 91111-0000', 'ana.maria@email.com', 'hashsenha123', 'Ativo', 0.00),
(26, 'Luciano Huck', 'PF', 'Masculino', 'Homem Cis', '1971-09-03', '(11) 92222-0000', 'luciano.huck@email.com', 'hashsenha123', 'Ativo', 0.00),
(27, 'Asilo Sagrado Coração', 'PJ', NULL, NULL, NULL, '(11) 2222-3333', 'doacoes@asilosagrado.com.br', 'hashsenha123', 'Ativo', 0.00),
(28, 'Tatá Werneck', 'PF', 'Feminino', 'Mulher Cis', '1983-08-11', '(11) 93333-0000', 'tata.werneck@email.com', 'hashsenha123', 'Ativo', 0.00),
(29, 'Paulo Gustavo (Homenagem)', 'PF', 'Masculino', 'Homem Cis', '1978-10-30', '(11) 94444-0000', 'paulo.gustavo@email.com', 'hashsenha123', 'Inativo', 0.00),
(30, 'Marília Mendonça (Homenagem)', 'PF', 'Feminino', 'Mulher Cis', '1995-07-22', '(11) 95555-0000', 'marilia.mendonca@email.com', 'hashsenha123', 'Inativo', 0.00);

-- 5. CLIENTES_DOCUMENTOS (CPF/RG para PF, CNPJ/IE para PJ)
INSERT INTO CLIENTES_DOCUMENTOS (ID_Cliente, Tipo, Numero) VALUES
(1, 'CPF', '111.111.111-11'), (1, 'RG', '22.222.222-2'),
(2, 'CPF', '222.222.222-22'),
(3, 'CNPJ', '33.333.333/0001-33'), (3, 'IE', '123.456.789.000'),
(4, 'CPF', '444.444.444-44'),
(5, 'CPF', '555.555.555-55'),
(6, 'CNPJ', '66.666.666/0001-66'),
(7, 'CPF', '777.777.777-77'),
(8, 'CPF', '888.888.888-88'),
(9, 'CPF', '999.999.999-99'),
(10, 'CPF', '000.000.000-00'),
(11, 'CNPJ', '11.111.111/0001-11'),
(12, 'CPF', '121.212.121-12'),
(13, 'CPF', '131.313.313-13'),
(14, 'CPF', '141.414.414-14'),
(15, 'CNPJ', '15.151.515/0001-15'),
(16, 'CPF', '161.616.616-16'),
(17, 'CPF', '171.717.717-17'),
(18, 'CPF', '181.818.818-18'),
(19, 'CNPJ', '19.191.919/0001-19'),
(20, 'CPF', '202.020.202-20'),
(21, 'CPF', '212.121.212-21'),
(22, 'CPF', '222.323.232-22'),
(23, 'CNPJ', '23.232.323/0001-23'),
(24, 'CPF', '242.424.242-24'),
(25, 'CPF', '252.525.525-25'),
(26, 'CPF', '262.626.626-26'),
(27, 'CNPJ', '27.272.727/0001-27'),
(28, 'CPF', '282.828.828-28'),
(29, 'CPF', '292.929.929-29'),
(30, 'CPF', '303.030.303-30');

-- 6. CLI_ENDERECOS (Endereços para os 30 clientes)
INSERT INTO CLI_ENDERECOS (ID_Cliente, CEP, Endereco, End_Numero, Bairro, Cidade, Estado) VALUES
(1, '01001000', 'Praça da Sé', '10', 'Sé', 'São Paulo', 'SP'),
(2, '01310100', 'Av. Paulista', '500', 'Bela Vista', 'São Paulo', 'SP'),
(3, '04543900', 'Av. Pres. Juscelino Kubitschek', '1000', 'Vila Olímpia', 'São Paulo', 'SP'),
(4, '20040002', 'Av. Rio Branco', '150', 'Centro', 'Rio de Janeiro', 'RJ'),
(5, '30130009', 'Av. Afonso Pena', '2000', 'Centro', 'Belo Horizonte', 'MG'),
(6, '80060000', 'Rua XV de Novembro', '300', 'Centro', 'Curitiba', 'PR'),
(7, '70040900', 'Esplanada dos Ministérios', 'S/N', 'Zona Cívico-Administrativa', 'Brasília', 'DF'),
(8, '40020000', 'Av. Sete de Setembro', '50', 'Vitória', 'Salvador', 'BA'),
(9, '60165120', 'Av. Beira Mar', '4000', 'Meireles', 'Fortaleza', 'CE'),
(10, '69005000', 'Av. Eduardo Ribeiro', '100', 'Centro', 'Manaus', 'AM'),
(11, '50030230', 'Rua da Aurora', '500', 'Boa Vista', 'Recife', 'PE'),
(12, '90010150', 'Rua dos Andradas', '1200', 'Centro Histórico', 'Porto Alegre', 'RS'),
(13, '66010000', 'Av. Pres. Vargas', '800', 'Campina', 'Belém', 'PA'),
(14, '74015000', 'Av. Goiás', '600', 'Centro', 'Goiânia', 'GO'),
(15, '07090000', 'Av. Paulo Faccini', '900', 'Macedo', 'Guarulhos', 'SP'),
(16, '13010000', 'Av. Francisco Glicério', '1100', 'Centro', 'Campinas', 'SP'),
(17, '24020000', 'Rua da Conceição', '55', 'Centro', 'Niterói', 'RJ'),
(18, '57020000', 'Rua do Comércio', '120', 'Centro', 'Maceió', 'AL'),
(19, '29010004', 'Av. Jerônimo Monteiro', '330', 'Centro', 'Vitória', 'ES'),
(20, '11060001', 'Av. Ana Costa', '450', 'Gonzaga', 'Santos', 'SP'),
(21, '14010000', 'Rua General Osório', '700', 'Centro', 'Ribeirão Preto', 'SP'),
(22, '12209000', 'Praça Afonso Pena', '80', 'Centro', 'São José dos Campos', 'SP'),
(23, '09710190', 'Rua Marechal Deodoro', '1500', 'Centro', 'São Bernardo do Campo', 'SP'),
(24, '09015050', 'Av. Portugal', '100', 'Centro', 'Santo André', 'SP'),
(25, '06093020', 'Av. dos Autonomistas', '3000', 'Centro', 'Osasco', 'SP'),
(26, '08710500', 'Rua Dr. Deodato Wertheimer', '200', 'Centro', 'Mogi das Cruzes', 'SP'),
(27, '18010000', 'Rua São Bento', '400', 'Centro', 'Sorocaba', 'SP'),
(28, '13400000', 'Rua Governador Pedro de Toledo', '500', 'Centro', 'Piracicaba', 'SP'),
(29, '17015000', 'Av. Rodrigues Alves', '15-50', 'Centro', 'Bauru', 'SP'),
(30, '15015100', 'Rua Bernardino de Campos', '2900', 'Centro', 'São José do Rio Preto', 'SP');


-- =================================================================
-- BLOCO 3: CATÁLOGO (PRODUTOS, MEDICAMENTOS, LOTES E ESTOQUE)
-- =================================================================

-- 1. PRODUTOS (IDs 1-35 = Medicamentos, IDs 36-50 = Gerais)
-- ID_Categoria 1 = Medicamentos
INSERT INTO PRODUTOS (ID_Produto, ID_Categoria, Nome, Marca, ID_Fornecedor, ID_Unidade, NCM, EAN_GTIN, Status) VALUES
-- Analgésicos e Antitérmicos
(1, 1, 'Dipirona Monohidratada 500mg 10cp', 'Medley', 3, 2, '30049099', '7891000000010', 'Ativo'),
(2, 1, 'Novalgina 1g 10cp', 'Sanofi', 12, 2, '30049099', '7891000000027', 'Ativo'),
(3, 1, 'Tylenol 750mg 20cp', 'Johnson', 15, 2, '30049099', '7891000000034', 'Ativo'),
(4, 1, 'Paracetamol 750mg 20cp', 'EMS', 2, 2, '30049099', '7891000000041', 'Ativo'),
(5, 1, 'Dorflex 36cp', 'Sanofi', 12, 2, '30049099', '7891000000058', 'Ativo'),
(6, 1, 'Neosaldina 30 Drágeas', 'Hypera', 8, 2, '30049099', '7891000000065', 'Ativo'),
(7, 1, 'Buscopan Composto', 'Hypera', 8, 2, '30049099', '7891000000072', 'Ativo'),
(8, 1, 'Ibuprofeno 600mg 20cp', 'Prati-Donaduzzi', 18, 2, '30049099', '7891000000089', 'Ativo'),
(9, 1, 'Nimesulida 100mg 12cp', 'Eurofarma', 1, 2, '30049099', '7891000000096', 'Ativo'),
(10, 1, 'Cataflam Pro Emulgel 60g', 'Novartis', 13, 2, '30049099', '7891000000102', 'Ativo'),
-- Antibióticos (Controle Especial)
(11, 1, 'Amoxicilina 500mg 21cp', 'Neo Química', 4, 2, '30041010', '7891000000119', 'Ativo'),
(12, 1, 'Amoxicilina + Clavulanato 875mg', 'Eurofarma', 1, 2, '30041010', '7891000000126', 'Ativo'),
(13, 1, 'Azitromicina 500mg 5cp', 'EMS', 2, 2, '30041010', '7891000000133', 'Ativo'),
(14, 1, 'Cefalexina 500mg 10cp', 'Teuto', 17, 2, '30041010', '7891000000140', 'Ativo'),
(15, 1, 'Ciprofloxacino 500mg 14cp', 'Sandoz', 13, 2, '30041010', '7891000000157', 'Ativo'),
-- Uso Contínuo (Hipertensão/Diabetes)
(16, 1, 'Losartana Potássica 50mg 30cp', 'Neo Química', 4, 2, '30049099', '7891000000164', 'Ativo'),
(17, 1, 'Enalapril 10mg 30cp', 'Medley', 3, 2, '30049099', '7891000000171', 'Ativo'),
(18, 1, 'Atenolol 25mg 30cp', 'Aché', 9, 2, '30049099', '7891000000188', 'Ativo'),
(19, 1, 'Metformina 850mg 30cp', 'Prati-Donaduzzi', 18, 2, '30049099', '7891000000195', 'Ativo'),
(20, 1, 'Glibenclamida 5mg 30cp', 'EMS', 2, 2, '30049099', '7891000000201', 'Ativo'),
(21, 1, 'Xarelto 20mg 28cp', 'Bayer', 11, 2, '30049099', '7891000000218', 'Ativo'),
-- Controlados (Tarja Preta/Vermelha Retenção)
(22, 1, 'Rivotril 2mg 30cp', 'Roche', 5, 2, '30049099', '7891000000225', 'Ativo'), -- Usando forn 5 como distribuidora
(23, 1, 'Clonazepam 2.5mg/ml Gotas', 'Medley', 3, 3, '30049099', '7891000000232', 'Ativo'),
(24, 1, 'Sertralina 50mg 30cp', 'Eurofarma', 1, 2, '30049099', '7891000000249', 'Ativo'),
(25, 1, 'Fluoxetina 20mg 30cp', 'EMS', 2, 2, '30049099', '7891000000256', 'Ativo'),
(26, 1, 'Zolpidem 10mg 20cp', 'Sandoz', 13, 2, '30049099', '7891000000263', 'Ativo'),
-- Anticoncepcionais e Outros
(27, 1, 'Ciclo 21 21cp', 'União Química', 16, 2, '30049099', '7891000000270', 'Ativo'),
(28, 1, 'Yasmin 21cp', 'Bayer', 11, 2, '30049099', '7891000000287', 'Ativo'),
(29, 1, 'Allegra 120mg 10cp', 'Sanofi', 12, 2, '30049099', '7891000000294', 'Ativo'),
(30, 1, 'Histamin 2mg 20cp', 'Neo Química', 4, 2, '30049099', '7891000000300', 'Ativo'),
(31, 1, 'Pantoprazol 40mg 28cp', 'Medley', 3, 2, '30049099', '7891000000317', 'Ativo'),
(32, 1, 'Omeprazol 20mg 28cp', 'Teuto', 17, 2, '30049099', '7891000000324', 'Ativo'),
(33, 1, 'Vitamina C 1g Efervescente', 'Cimed', 10, 2, '30045090', '7891000000331', 'Ativo'),
(34, 1, 'Addera D3 50.000UI 4cp', 'Hypera', 8, 2, '30045090', '7891000000348', 'Ativo'),
(35, 1, 'Simeticona 75mg Gotas', 'EMS', 2, 3, '30049099', '7891000000355', 'Ativo'),
-- PERFUMARIA E HIGIENE (IDs 36-50)
(36, 3, 'Shampoo Dove Reconstrução 400ml', 'Unilever', 5, 3, '33051000', '7891000000362', 'Ativo'),
(37, 3, 'Condicionador Dove Reconstrução 200ml', 'Unilever', 5, 3, '33051000', '7891000000379', 'Ativo'),
(38, 3, 'Sabonete Dove Original 90g', 'Unilever', 5, 1, '34011190', '7891000000386', 'Ativo'),
(39, 3, 'Desodorante Rexona Clinical', 'Unilever', 5, 1, '33072010', '7891000000393', 'Ativo'),
(40, 3, 'Creme Dental Colgate Total 12', 'Colgate', 5, 2, '33061000', '7891000000409', 'Ativo'),
(41, 5, 'Fralda Pampers Confort Sec M 24un', 'P&G', 5, 6, '96190000', '7891000000416', 'Ativo'),
(42, 5, 'Lenços Umedecidos Huggies 48un', 'Kimberly', 5, 6, '96190000', '7891000000423', 'Ativo'),
(43, 5, 'Pomada Bepantol Baby 30g', 'Bayer', 11, 2, '30049099', '7891000000430', 'Ativo'),
(44, 4, 'Protetor Solar La Roche FPS 60', 'Loreal', 5, 3, '33049990', '7891000000447', 'Ativo'),
(45, 4, 'Hidratante CeraVe 200ml', 'Loreal', 5, 3, '33049990', '7891000000454', 'Ativo'),
(46, 7, 'Whey Protein 900g Baunilha', 'Max Titanium', 5, 5, '21061000', '7891000000461', 'Ativo'),
(47, 7, 'Creatina Monohidratada 300g', 'Max Titanium', 5, 5, '21061000', '7891000000478', 'Ativo'),
(48, 8, 'Curativos Band-Aid 10un', 'Johnson', 15, 2, '30051090', '7891000000485', 'Ativo'),
(49, 8, 'Algodão Apolo 50g', 'Apolo', 5, 6, '52010000', '7891000000492', 'Ativo'),
(50, 8, 'Soro Fisiológico 0.9% 500ml', 'Farmax', 5, 3, '30049099', '7891000000508', 'Ativo');

-- 2. MEDICAMENTOS (Detalhes técnicos apenas para IDs 1-35)
-- CategoriasMed: 1=Analg, 2=AntiInf, 3=Antibio, 4=Hiper, 5=Diab, 6=Alerg, 7=Depress, 8=Contra
-- Tarjas: 1=MIP, 2=Gen, 3=Verm, 4=VermRet, 5=Preta
INSERT INTO MEDICAMENTOS (ID_Medicamento, ID_Produto, ID_CategoriaMed, ID_Tarja, Tipo, MS, Controlado) VALUES
-- Analgésicos (MIPs e Tarja Vermelha Simples)
(1, 1, 1, 2, 'Genérico', '1.0001.0001.001-1', 'Não'),
(2, 2, 1, 1, 'Referência', '1.0002.0002.002-1', 'Não'),
(3, 3, 1, 1, 'Referência', '1.0003.0003.003-1', 'Não'),
(4, 4, 1, 2, 'Genérico', '1.0004.0004.004-1', 'Não'),
(5, 5, 1, 1, 'Referência', '1.0005.0005.005-1', 'Não'),
(6, 6, 1, 1, 'Referência', '1.0006.0006.006-1', 'Não'),
(7, 7, 1, 1, 'Referência', '1.0007.0007.007-1', 'Não'),
(8, 8, 2, 2, 'Genérico', '1.0008.0008.008-1', 'Não'),
(9, 9, 2, 2, 'Genérico', '1.0009.0009.009-1', 'Não'),
(10, 10, 2, 1, 'Referência', '1.0010.0010.010-1', 'Não'),
-- Antibióticos (Controle Especial - Retenção de Receita)
(11, 11, 3, 4, 'Genérico', '1.0011.0011.011-1', 'Sim'),
(12, 12, 3, 4, 'Genérico', '1.0012.0012.012-1', 'Sim'),
(13, 13, 3, 4, 'Genérico', '1.0013.0013.013-1', 'Sim'),
(14, 14, 3, 4, 'Genérico', '1.0014.0014.014-1', 'Sim'),
(15, 15, 3, 4, 'Genérico', '1.0015.0015.015-1', 'Sim'),
-- Uso Contínuo (Tarja Vermelha)
(16, 16, 4, 2, 'Genérico', '1.0016.0016.016-1', 'Não'),
(17, 17, 4, 2, 'Genérico', '1.0017.0017.017-1', 'Não'),
(18, 18, 4, 2, 'Genérico', '1.0018.0018.018-1', 'Não'),
(19, 19, 5, 2, 'Genérico', '1.0019.0019.019-1', 'Não'),
(20, 20, 5, 2, 'Genérico', '1.0020.0020.020-1', 'Não'),
(21, 21, 4, 3, 'Referência', '1.0021.0021.021-1', 'Não'),
-- Controlados (Tarja Preta e Vermelha Retenção)
(22, 22, 7, 5, 'Referência', '1.0022.0022.022-1', 'Sim'), -- Rivotril
(23, 23, 7, 5, 'Genérico', '1.0023.0023.023-1', 'Sim'),
(24, 24, 7, 4, 'Genérico', '1.0024.0024.024-1', 'Sim'), -- Sertralina
(25, 25, 7, 4, 'Genérico', '1.0025.0025.025-1', 'Sim'),
(26, 26, 7, 4, 'Genérico', '1.0026.0026.026-1', 'Sim'), -- Zolpidem
-- Outros
(27, 27, 8, 3, 'Similar', '1.0027.0027.027-1', 'Não'),
(28, 28, 8, 3, 'Referência', '1.0028.0028.028-1', 'Não'),
(29, 29, 6, 1, 'Referência', '1.0029.0029.029-1', 'Não'),
(30, 30, 6, 3, 'Similar', '1.0030.0030.030-1', 'Não'),
(31, 31, 2, 2, 'Genérico', '1.0031.0031.031-1', 'Não'),
(32, 32, 2, 2, 'Genérico', '1.0032.0032.032-1', 'Não'),
(33, 33, 1, 1, 'Similar', '1.0033.0033.033-1', 'Não'),
(34, 34, 1, 1, 'Referência', '1.0034.0034.034-1', 'Não'),
(35, 35, 2, 1, 'Similar', '1.0035.0035.035-1', 'Não');

-- 3. LOTES (Um lote inicial para cada produto)
INSERT INTO LOTES (ID_Lote, Nome_Lote, ID_Produto, Preco_Custo, Preco_Venda, Data_Validade) VALUES
(1, 'LOTE001', 1, 2.50, 8.00, '2026-12-31'), -- Dipirona
(2, 'LOTE002', 2, 12.00, 25.00, '2026-10-30'), -- Novalgina
(3, 'LOTE003', 3, 15.00, 28.00, '2027-01-15'), -- Tylenol
(4, 'LOTE004', 4, 3.00, 9.90, '2026-05-20'), -- Paracetamol
(5, 'LOTE005', 5, 14.00, 22.00, '2026-08-10'), -- Dorflex
(6, 'LOTE006', 6, 18.00, 35.00, '2027-02-28'), -- Neosaldina
(7, 'LOTE007', 7, 16.00, 29.90, '2026-11-15'),
(8, 'LOTE008', 8, 5.00, 12.00, '2026-09-01'),
(9, 'LOTE009', 9, 4.00, 10.00, '2026-07-20'),
(10, 'LOTE010', 10, 25.00, 45.00, '2027-03-10'),
(11, 'LOTE011', 11, 8.00, 22.00, '2026-06-30'), -- Amoxicilina
(12, 'LOTE012', 12, 20.00, 55.00, '2026-05-15'),
(13, 'LOTE013', 13, 10.00, 28.00, '2026-12-12'),
(14, 'LOTE014', 14, 6.00, 18.00, '2026-11-30'),
(15, 'LOTE015', 15, 12.00, 35.00, '2026-10-20'),
(16, 'LOTE016', 16, 2.00, 5.00, '2027-05-01'), -- Losartana (Farmácia Popular style)
(17, 'LOTE017', 17, 3.00, 7.00, '2027-06-15'),
(18, 'LOTE018', 18, 3.50, 8.00, '2027-04-10'),
(19, 'LOTE019', 19, 2.50, 6.00, '2027-08-20'),
(20, 'LOTE020', 20, 3.00, 7.50, '2027-09-05'),
(21, 'LOTE021', 21, 80.00, 150.00, '2026-12-25'), -- Xarelto
(22, 'LOTE022', 22, 10.00, 25.00, '2026-08-30'), -- Rivotril
(23, 'LOTE023', 23, 8.00, 18.00, '2026-07-15'),
(24, 'LOTE024', 24, 15.00, 45.00, '2026-11-20'),
(25, 'LOTE025', 25, 12.00, 35.00, '2026-10-10'),
(26, 'LOTE026', 26, 20.00, 55.00, '2026-09-05'),
(27, 'LOTE027', 27, 5.00, 12.00, '2028-01-01'), -- Ciclo 21
(28, 'LOTE028', 28, 40.00, 85.00, '2028-02-15'), -- Yasmin
(29, 'LOTE029', 29, 30.00, 65.00, '2027-05-20'),
(30, 'LOTE030', 30, 8.00, 18.00, '2027-06-10'),
(31, 'LOTE031', 31, 15.00, 35.00, '2027-03-15'),
(32, 'LOTE032', 32, 8.00, 20.00, '2027-04-20'),
(33, 'LOTE033', 33, 10.00, 25.00, '2026-12-01'),
(34, 'LOTE034', 34, 35.00, 70.00, '2026-11-10'),
(35, 'LOTE035', 35, 6.00, 14.00, '2027-01-05'),
(36, 'LOTE036', 36, 12.00, 22.90, '2028-05-10'), -- Shampoo
(37, 'LOTE037', 37, 14.00, 25.90, '2028-05-10'),
(38, 'LOTE038', 38, 1.50, 4.00, '2028-06-15'),
(39, 'LOTE039', 39, 12.00, 24.90, '2028-02-20'),
(40, 'LOTE040', 40, 4.00, 9.50, '2028-08-30'),
(41, 'LOTE041', 41, 30.00, 55.00, '2029-01-01'), -- Fralda
(42, 'LOTE042', 42, 8.00, 16.90, '2028-12-10'),
(43, 'LOTE043', 43, 15.00, 32.00, '2028-03-20'),
(44, 'LOTE044', 44, 45.00, 89.90, '2027-11-15'), -- La Roche
(45, 'LOTE045', 45, 50.00, 95.00, '2027-10-10'),
(46, 'LOTE046', 46, 90.00, 169.90, '2027-01-20'), -- Whey
(47, 'LOTE047', 47, 50.00, 99.90, '2027-02-25'),
(48, 'LOTE048', 48, 3.00, 7.50, '2029-05-05'),
(49, 'LOTE049', 49, 2.00, 5.00, '2030-01-01'),
(50, 'LOTE050', 50, 3.00, 8.00, '2027-09-09');

-- 4. ESTOQUE (Saldo inicial para cada lote)
INSERT INTO ESTOQUE (ID_Estoque, ID_Lote, Quantidade, Data_Entrada) VALUES
(1, 1, 100, CURDATE()), (2, 2, 50, CURDATE()), (3, 3, 60, CURDATE()), (4, 4, 150, CURDATE()), (5, 5, 80, CURDATE()),
(6, 6, 40, CURDATE()), (7, 7, 50, CURDATE()), (8, 8, 100, CURDATE()), (9, 9, 90, CURDATE()), (10, 10, 30, CURDATE()),
(11, 11, 60, CURDATE()), (12, 12, 40, CURDATE()), (13, 13, 50, CURDATE()), (14, 14, 70, CURDATE()), (15, 15, 45, CURDATE()),
(16, 16, 200, CURDATE()), (17, 17, 150, CURDATE()), (18, 18, 120, CURDATE()), (19, 19, 180, CURDATE()), (20, 20, 100, CURDATE()),
(21, 21, 20, CURDATE()), (22, 22, 30, CURDATE()), (23, 23, 25, CURDATE()), (24, 24, 40, CURDATE()), (25, 25, 35, CURDATE()),
(26, 26, 30, CURDATE()), (27, 27, 80, CURDATE()), (28, 28, 40, CURDATE()), (29, 29, 50, CURDATE()), (30, 30, 60, CURDATE()),
(31, 31, 55, CURDATE()), (32, 32, 75, CURDATE()), (33, 33, 90, CURDATE()), (34, 34, 25, CURDATE()), (35, 35, 85, CURDATE()),
(36, 36, 30, CURDATE()), (37, 37, 30, CURDATE()), (38, 38, 100, CURDATE()), (39, 39, 40, CURDATE()), (40, 40, 80, CURDATE()),
(41, 41, 25, CURDATE()), (42, 42, 40, CURDATE()), (43, 43, 35, CURDATE()), (44, 44, 15, CURDATE()), (45, 45, 20, CURDATE()),
(46, 46, 12, CURDATE()), (47, 47, 15, CURDATE()), (48, 48, 100, CURDATE()), (49, 49, 80, CURDATE()), (50, 50, 60, CURDATE());


-- =================================================================
-- BLOCO 4: MOVIMENTAÇÃO (CAIXAS, VENDAS, ITENS, PAGAMENTOS, ESTOQUE)
-- =================================================================

-- 1. CRIAR CAIXAS
INSERT INTO CAIXAS (ID_Caixa, Caixa, Status, StatusCadastrado) VALUES
(1, 'Caixa 01 - Principal', 'Fechado', 'Ativo'),
(2, 'Caixa 02 - Balcão', 'Fechado', 'Ativo'),
(3, 'Caixa 03 - Retaguarda', 'Fechado', 'Ativo');

-- 2. Conferir arquivo insert_banca_vendas.sql


-- =================================================================
-- BLOCO 5: ESPECÍFICOS (SNGPC, SERVIÇOS, DESPESAS, DEVOLUÇÕES)
-- =================================================================

-- 1. PRESCRICOES (Justificando as vendas de controlados anteriores)
-- Venda 4 (Amoxicilina) e Venda 11 (Rivotril) precisam de receita.
INSERT INTO PRESCRICOES (ID_Prescricao, ID_Cliente, ID_Funcionario, Nome_Profissional, Conselho, Num_Conselho, UF_Conselho, Data_Receita, Dados_Adicionais) VALUES
-- Prescrição 1: Vinculada ao Cliente Rafael Souza (ID 5) - Comprou Amoxicilina
(1, 5, 2, 'Dr. Drauzio Varella', 'CRM', '11111', 'SP', '2025-11-15', 
'{
  "tipo_receita": "C1", 
  "comprador_doc": "555.555.555-55", 
  "comprador_tel": "(11) 94444-4444", 
  "comprador_nome": "Rafael Souza", 
  "numero_receita": "11111-B", 
  "receita_digital": false, 
  "dispensador_digital": "", 
  "paciente_dn_receita": "1998-02-15", 
  "paciente_na_receita": "Rafael Souza", 
  "comprador_eh_paciente": true, 
  "paciente_sexo_receita": "Masculino"
}'),

-- Prescrição 2: Venda Avulsa (Sem cadastro de cliente ID, mas dados obrigatórios no JSON) - Comprou Rivotril
(2, NULL, 2, 'Dr. House', 'CRM', '22222', 'SP', '2025-11-10', 
'{
  "tipo_receita": "B1", 
  "comprador_doc": "22.333.444-5", 
  "comprador_tel": "(21) 98888-7777", 
  "comprador_nome": "Juliana Paes", 
  "numero_receita": "22222-A", 
  "receita_digital": false, 
  "dispensador_digital": "", 
  "paciente_dn_receita": "1980-05-12", 
  "paciente_na_receita": "Juliana Paes", 
  "comprador_eh_paciente": true, 
  "paciente_sexo_receita": "Feminino"
}');

-- 2. REGISTRO DE SERVIÇOS FARMACÊUTICOS (Atenção Farmacêutica)
-- ID_Servico 1 = Pressão, 2 = Glicemia. Feitos pela Ana Clara (ID 2).
INSERT INTO REGISTRO_SERVICOS (ID_Registro_Servico, ID_Servico, ID_Cliente, Nome_Paciente, Doc_Paciente, Sexo_Paciente, Data_Nascimento_Paciente, ID_Funcionario, DataHora, Dados_Servico, OBS) VALUES
-- Aferição de Pressão no Sr. Antônio (Hipertenso)
(1, 1, 8, 'Antônio Carlos', '888.888.888-88', 'Masculino', '1960-03-30', 2, '2025-11-20 09:00:00', 
'{"pressao_sistolica": "150", "pressao_diastolica": "95", "pulsacao": "80"}', 
'Paciente relatou tontura. Orientado a procurar médico e repousar.'),
-- Teste de Glicemia na Dona Maria
(2, 2, 1, 'Maria Aparecida', '111.111.111-11', 'Feminino', '1985-05-10', 2, '2025-11-21 08:30:00', 
'{"valor_glicemia": "98", "condicao_jejum": "Sim"}', 
'Glicemia dentro dos padrões normais para jejum.'),
-- Aplicação de Injetável (Benze****) - Serviço ID 3
(3, 3, NULL, 'José da Silva', '555.444.333-22', 'Masculino', '1990-01-01', 2, '2025-11-22 10:00:00', 
'{"medicamento": "Benzetacil", "via": "Intramuscular", "lote": "LOTE_EXTERNO_09"}', 
'Aplicação realizada no glúteo, sem intercorrências.');

-- 3. DESPESAS (Contas a Pagar/Pagas)
-- Categorias: 1=Consumo, 2=Aluguel, 4=Escritório
INSERT INTO DESPESAS (ID_Despesa, ID_Categoria_Despesa, Descricao, Valor, Data_Vencimento, Data_Pagamento, Status, ID_Funcionario, Data_Registro) VALUES
(1, 2, 'Aluguel Imóvel Comercial - Nov/2025', 3500.00, '2025-11-10', '2025-11-10', 'Paga', 3, '2025-11-01 10:00:00'),
(2, 1, 'Conta de Luz (Enel)', 450.00, '2025-11-15', '2025-11-14', 'Paga', 3, '2025-11-05 14:00:00'),
(3, 1, 'Conta de Água (Sabesp)', 120.00, '2025-11-15', '2025-11-14', 'Paga', 3, '2025-11-05 14:05:00'),
(4, 1, 'Internet Fibra', 150.00, '2025-11-20', '2025-11-20', 'Paga', 3, '2025-11-05 14:10:00'),
(5, 4, 'Compra de Bobinas Térmicas', 80.00, '2025-11-25', NULL, 'Pendente', 3, '2025-11-20 16:00:00'),
(6, 6, 'Impulsionamento Instagram', 200.00, '2025-11-30', NULL, 'Pendente', 3, '2025-11-22 11:00:00');

-- 4. DEVOLUÇÕES (Logística Reversa)

-- Devolução ao Fornecedor (Produto chegou quebrado)
-- ID 1 = Eurofarma, ID 1 = Dipirona
INSERT INTO DEVOLUCOES_FORNECEDORES (ID_Devolucao_Fornecedor, ID_Fornecedor, ID_Funcionario, Data_Devolucao, Motivo_Geral, Valor_Total_Custo) VALUES
(1, 1, 6, '2025-11-10 10:00:00', 'Caixas chegaram amassadas e frascos quebrados no transporte.', 25.00);

INSERT INTO DEVOLUCOES_FORNECEDORES_ITENS (ID_Item_Dev_Fornecedor, ID_Devolucao_Fornecedor, ID_Produto, ID_Lote, Quantidade, Valor_Custo_Unitario) VALUES
(1, 1, 1, 1, 10, 2.50); -- 10 caixas de Dipirona a 2.50 custo

-- Devolução de Cliente (Comprou errado)
-- Cliente devolveu 1 Shampoo da Venda 2
INSERT INTO DEVOLUCOES_CLIENTES (ID_Devolucao_Cliente, ID_Cliente, ID_Venda, ID_Funcionario, Data_Devolucao, Tipo_Resolucao, Valor_Total_Devolvido) VALUES
(1, NULL, 2, 8, CURDATE(), 'Credito_Loja', 22.90);

INSERT INTO DEVOLUCOES_CLIENTES_ITENS (ID_Item_Dev_Cliente, ID_Devolucao_Cliente, ID_Produto, Quantidade, Valor_Unitario, Motivo) VALUES
(1, 1, 36, 1, 22.90, 'Cliente preferia a versão Anticaspa');

-- 5. ATIVAÇÃO DE UMA PROMOÇÃO (Para testar o Event Scheduler se estiver ativo)
INSERT INTO PROMOCOES (ID_Promocao, Descricao, Tipo, Data_Inicio, Data_Fim, Status) VALUES
(1, 'Black Friday Antecipada', 'DESCONTO_PROGRESSIVO', '2025-11-01', '2025-11-30', 'Ativo');

INSERT INTO PROMOCOES_ITENS (ID_Item_Promocao, ID_Promocao, Tipo_Item, ID_Produto, Quantidade, Valor_Desconto_Percentual) VALUES
(1, 1, 'Beneficio', 44, 1, 15.00); -- 15% off no La Roche


-- =================================================================
-- BLOCO EXTRA: PRÉ-VENDAS E VÍNCULO COM PRESCRIÇÕES
-- =================================================================

-- Cenário 1: Pré-Venda da "Venda 4" (Amoxicilina do Cliente Rafael/ID 5)
-- Esta venda já ocorreu no Bloco 4, então inserimos como 'Finalizada' e linkamos o ID_Venda 4.
-- Quem fez a pré-venda foi a Farmacêutica Ana Clara (ID 2).
INSERT INTO PRE_VENDAS (ID_PreVenda, ID_Cliente, ID_Venda, ID_Funcionario, ID_Prescricao, Codigo_PreVenda, Status, Data_Criacao, Data_Finalizacao) VALUES
(1, 5, 4, 2, 1, 'PRE-2025-001', 'Finalizada', CONCAT(CURDATE(), ' 09:30:00'), CONCAT(CURDATE(), ' 09:45:00'));
INSERT INTO PRE_VENDAS_ITENS (ID_PreVenda, ID_Produto, ID_Lote, Quantidade, Valor_Unitario, Desconto) VALUES
(1, 12, 12, 1, 55.00, 5.00); -- Amoxicilina + Clavulanato

-- Cenário 2: Pré-Venda da "Venda 11" (Rivotril - Venda Avulsa)
-- Esta venda já ocorreu no Bloco 4, inserimos como 'Finalizada' e linkamos o ID_Venda 11.
INSERT INTO PRE_VENDAS (ID_PreVenda, ID_Cliente, ID_Venda, ID_Funcionario, ID_Prescricao, Codigo_PreVenda, Status, Data_Criacao, Data_Finalizacao) VALUES
(2, NULL, 11, 2, 2, 'PRE-2025-002', 'Finalizada', CONCAT(CURDATE(), ' 15:50:00'), CONCAT(CURDATE(), ' 16:00:00'));
INSERT INTO PRE_VENDAS_ITENS (ID_PreVenda, ID_Produto, ID_Lote, Quantidade, Valor_Unitario, Desconto) VALUES
(2, 22, 22, 1, 25.00, 0.00); -- Rivotril