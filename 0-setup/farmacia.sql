-- -----------------------------------------------------
-- Criando BATABASE `sistemaFarmacia`
-- -----------------------------------------------------
CREATE DATABASE IF NOT EXISTS `sistemaFarmacia` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sistemaFarmacia`;
/* drop database sistemaFarmacia; */

-- -----------------------------------------------------
-- Table `CONFIGURACOES`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CONFIGURACOES` (
    `ID_Config` INT AUTO_INCREMENT PRIMARY KEY,
    `Nome_RazaoSocial` VARCHAR(255) NOT NULL,
    `Nome_Fantasia` VARCHAR(255) NOT NULL,
    `Slogan` VARCHAR(255) NOT NULL,
    `Documento` VARCHAR(18) NOT NULL,
    `CNES` VARCHAR(7) NOT NULL,
    `Telefone` VARCHAR(20) NOT NULL,
    `Loja` VARCHAR(100) NOT NULL,
    `CEP` CHAR(8) NOT NULL,
    `Endereco` VARCHAR(255) NOT NULL,
    `End_Numero` VARCHAR(255) DEFAULT NULL,
    `Bairro` VARCHAR(255) NOT NULL,
    `Cidade` VARCHAR(255) NOT NULL,
    `Estado` CHAR(2) NOT NULL,
    `Valor_Min_Parcelas` DECIMAL(10,2) NOT NULL,
    `Quant_Max_Parcelas` INT NOT NULL,
    `Margem_Lucro_Padrao` DECIMAL(10,2) DEFAULT 100.00,
    `Data_Alteracao` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;
/* drop table CONFIGURACOES; */
/* select * from CONFIGURACOES; */

-- -----------------------------------------------------
-- Table `CLIENTES`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CLIENTES` (
    `ID_Cliente` INT AUTO_INCREMENT PRIMARY KEY,
    `Nome` VARCHAR(255) NOT NULL,
    `Tipo` ENUM('PJ', 'PF') NOT NULL,
    `Sexo` ENUM('Masculino', 'Feminino') NULL,
    `Data_Nascimento` DATE NULL,
    `Documento` VARCHAR(18) NOT NULL UNIQUE,
    `Tel` VARCHAR(20) NOT NULL,
    `Email` VARCHAR(100) NOT NULL UNIQUE,
    `Senha` VARCHAR(255) NOT NULL,
    `Status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    `OBS` TEXT DEFAULT NULL,
    `Data_Cadastro` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `Data_Alteracao` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;
/* drop table CLIENTES; */
/* select * from CLIENTES; */

-- -----------------------------------------------------
-- Table `CLI_ENDERECOS`         INTEGRAR COM API VIACEP
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CLI_ENDERECOS` (
    `ID_Endereco_Cli` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Cliente` INT NOT NULL,
    `CEP` CHAR(8) NOT NULL,
    `Endereco` VARCHAR(255) NOT NULL,
    `End_Numero` VARCHAR(255) NOT NULL,
    `Complemento` VARCHAR(255) DEFAULT NULL,
    `Bairro` VARCHAR(255) NOT NULL,
    `Cidade` VARCHAR(255) NOT NULL,
    `Estado` CHAR(2) NOT NULL,
    `OBS` TEXT DEFAULT NULL,
    FOREIGN KEY (`ID_Cliente`) REFERENCES `CLIENTES` (`ID_Cliente`) ON DELETE CASCADE
) ENGINE = InnoDB;
/* drop table CLI_ENDERECOS; */
/* select * from CLI_ENDERECOS; */

-- -----------------------------------------------------
-- Table `CARGOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CARGOS` (
    `ID_Cargo` INT AUTO_INCREMENT PRIMARY KEY,
    `Cargo` VARCHAR(255) NOT NULL UNIQUE,
    `Descricao` VARCHAR(255) DEFAULT NULL,
    `Status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo'
) ENGINE = InnoDB;
/* drop table CARGOS; */
/* select * from CARGOS; */

-- -----------------------------------------------------
-- Table `MODULOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `MODULOS` (
    `ID_Modulo` INT AUTO_INCREMENT PRIMARY KEY,
    `Modulo` VARCHAR(255) NOT NULL UNIQUE
) ENGINE = InnoDB;
/* drop table MODULOS; */
/* select * from MODULOS; */

-- -----------------------------------------------------
-- Table `CARGOS_MODULOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CARGOS_MODULOS` (
    `ID_Cargo_Modulo` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Cargo` INT NOT NULL,
    `ID_Modulo` INT NOT NULL,
    `Acesso_Permitido` BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (`ID_Cargo`) REFERENCES `CARGOS` (`ID_Cargo`),
    FOREIGN KEY (`ID_Modulo`) REFERENCES `MODULOS` (`ID_Modulo`)
) ENGINE = InnoDB;
/* drop table CARGOS_MODULOS; */
/* select * from CARGOS_MODULOS; */

-- -----------------------------------------------------
-- Table `FUNCIONARIOS`            
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `FUNCIONARIOS` (
    `ID_Funcionario` INT AUTO_INCREMENT PRIMARY KEY,
    `Nome` VARCHAR(255) NOT NULL,
    `Tipo` ENUM('PJ', 'PF') DEFAULT NULL,
    `Documento` VARCHAR(18) DEFAULT NULL UNIQUE,
    `CRF` VARCHAR(20) DEFAULT NULL UNIQUE,
    `Telefone` VARCHAR(20) DEFAULT NULL,
    `ID_Cargo` INT NOT NULL,
    `Email` VARCHAR(255) NOT NULL UNIQUE,
    `Salario` DECIMAL(10,2) DEFAULT NULL,
    `Data_Admissao` DATE DEFAULT NULL,
    `Data_Demissao` DATE DEFAULT NULL,
    `Status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    `OBS` TEXT DEFAULT NULL,
    `Data_Cadastro` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `Data_Alteracao` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`ID_Cargo`) REFERENCES `CARGOS` (`ID_Cargo`)
) ENGINE = InnoDB;
/* drop table FUNCIONARIOS; */
/* select * from FUNCIONARIOS; */

-- -----------------------------------------------------
-- Table `USUARIOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `USUARIOS` (
    `ID_Usuario` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Funcionario` INT NOT NULL,
    `Usuario` VARCHAR(50) NOT NULL UNIQUE,
    `Senha` VARCHAR(255) NOT NULL,
    `Status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    `Data_Cadastro` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ID_Funcionario`) REFERENCES `FUNCIONARIOS` (`ID_Funcionario`)
);
/* drop table USUARIOS; */
/* select * from USUARIOS; */

-- -----------------------------------------------------
-- Table `FORNECEDORES`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `FORNECEDORES` (
    `ID_Fornecedor` INT AUTO_INCREMENT PRIMARY KEY,
    `Nome_Fantasia` VARCHAR(255) NOT NULL,
    `Nome` VARCHAR(255) NOT NULL,
    `CNPJ` VARCHAR(18) NOT NULL UNIQUE,
    `Tel` VARCHAR(20) NOT NULL,
    `Email` VARCHAR(255) NOT NULL,
    `CEP` CHAR(8) NOT NULL,
    `Endereco` VARCHAR(255) NOT NULL,
    `End_Numero` VARCHAR(10) NOT NULL,
    `Complemento` VARCHAR(100) DEFAULT NULL,
    `Bairro` VARCHAR(255) NOT NULL,
    `Cidade` VARCHAR(255) NOT NULL,
    `Estado` CHAR(2) NOT NULL,
    `Status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    `OBS` TEXT DEFAULT NULL,
    `Data_Cadastro` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `Data_Alteracao` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;
/* drop table FORNECEDORES; */
/* select * from FORNECEDORES; */

-- -----------------------------------------------------
-- Table `CATEGORIAS`    
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CATEGORIAS` (
    `ID_Categoria` INT AUTO_INCREMENT PRIMARY KEY,
    `Categoria` VARCHAR(255) NOT NULL
) ENGINE = InnoDB;
/* drop table CATEGORIAS; */
/* select * from CATEGORIAS; */

-- -----------------------------------------------------
-- Table `UNIDADES`    
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `UNIDADES` (
    `ID_Unidade` INT AUTO_INCREMENT PRIMARY KEY,
    `Unidade` VARCHAR(255) NOT NULL,
    `Abreviacao` VARCHAR(10) DEFAULT NULL,
    `Status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo'
) ENGINE = InnoDB;
/* drop table UNIDADES; */
/* select * from UNIDADES; */

-- -----------------------------------------------------
-- Table `PRODUTOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `PRODUTOS` (
    `ID_Produto` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Categoria` INT NOT NULL,
    `Nome` VARCHAR(255) NOT NULL,
    `Marca` VARCHAR(255) DEFAULT NULL,
    `ID_Fornecedor` INT DEFAULT NULL,
    `Descricao` VARCHAR(255) DEFAULT NULL,
    `ID_Unidade` INT NOT NULL,
    `Quant_Minima` INT DEFAULT 10, 
    `Status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    `OBS` VARCHAR(255) DEFAULT NULL,
    `NCM` CHAR(8) NOT NULL, -- 
    `EAN_GTIN` VARCHAR(14) NOT NULL UNIQUE, -- Código de Barras
    `CBENEF` VARCHAR(20) DEFAULT NULL, --
    `CEST` VARCHAR(10) DEFAULT NULL, --
    `EXTIPI` VARCHAR(10) DEFAULT NULL, --
    `CFOP` INT(11) DEFAULT NULL, --
    `MVA` DECIMAL(10,2) DEFAULT NULL, --
    `NFCI` VARCHAR(20) DEFAULT NULL, --
    `CST_ICMS` VARCHAR(3) DEFAULT NULL, -- Define como o ICMS será tratado na venda (ex: tributado, isento, etc)
    `CST_PIS` VARCHAR(2) DEFAULT NULL, -- O mesmo, mas para PIS
    `CST_COFINS` VARCHAR(2) DEFAULT NULL, -- O mesmo, mas para COFINS.
    `Foto` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`ID_Categoria`) REFERENCES `CATEGORIAS` (`ID_Categoria`),
    FOREIGN KEY (`ID_Unidade`) REFERENCES `UNIDADES` (`ID_Unidade`),
    FOREIGN KEY (`ID_Fornecedor`) REFERENCES `FORNECEDORES` (`ID_Fornecedor`)
) ENGINE = InnoDB;
/* drop table PRODUTOS; */
/* select * from PRODUTOS; */

-- -----------------------------------------------------
-- Table `LOTES`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `LOTES` (
    `ID_Lote` INT AUTO_INCREMENT PRIMARY KEY,
    `Nome_Lote` VARCHAR(255) NOT NULL,
    `ID_Produto` INT NOT NULL,
    `Preco_Custo` DECIMAL(10,2) NOT NULL,
    `Preco_Venda` DECIMAL(10,2) NOT NULL,
    `Data_Validade` DATE NOT NULL,
    FOREIGN KEY (`ID_Produto`) REFERENCES `PRODUTOS` (`ID_Produto`)
) ENGINE = InnoDB;
/* drop table LOTES; */
/* select * from LOTES; */

-- -----------------------------------------------------
-- Table `CATEGORIAS_MEDICAMENTOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CATEGORIAS_MEDICAMENTOS` (
    `ID_CategoriaMed` INT AUTO_INCREMENT PRIMARY KEY,
    `Categoria_Med` VARCHAR(255) NOT NULL
) ENGINE = InnoDB;
/* drop table CATEGORIAS_MEDICAMENTOS; */
/* select * from CATEGORIAS_MEDICAMENTOS; */

-- -----------------------------------------------------
-- Table `TARJAS_MEDICAMENTOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `TARJAS_MEDICAMENTOS` (
    `ID_Tarja` INT AUTO_INCREMENT PRIMARY KEY,
    `Tarja` VARCHAR(255) NOT NULL
) ENGINE = InnoDB;
/* drop table TARJAS_MEDICAMENTOS; */
/* select * from TARJAS_MEDICAMENTOS; */

-- -----------------------------------------------------
-- Table `MEDICAMENTOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `MEDICAMENTOS` (
    `ID_Medicamento` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Produto` INT NOT NULL,
    `ID_CategoriaMed` INT NOT NULL,
    `ID_Tarja` INT NOT NULL,
    `Tipo` ENUM('Genérico', 'Similar', 'Referência') NOT NULL,
    `Prin_Ativo` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`ID_Produto`) REFERENCES `PRODUTOS` (`ID_Produto`) ON DELETE CASCADE,
    FOREIGN KEY (`ID_Tarja`) REFERENCES `TARJAS_MEDICAMENTOS` (`ID_Tarja`),
    FOREIGN KEY (`ID_CategoriaMed`) REFERENCES `CATEGORIAS_MEDICAMENTOS` (`ID_CategoriaMed`)
) ENGINE = InnoDB;
/* drop table MEDICAMENTOS; */
/* select * from MEDICAMENTOS; */

-- -----------------------------------------------------
-- Table `ESTOQUE`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ESTOQUE` (
    `ID_Estoque` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Lote` INT NOT NULL,
    `Quantidade` INT NOT NULL,
    `Data_Entrada` DATE NOT NULL,
    `Data_Atualizacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`ID_Lote`) REFERENCES `LOTES` (`ID_Lote`)
) ENGINE = InnoDB;
/* drop table ESTOQUE; */
/* select * from ESTOQUE; */

-- -----------------------------------------------------
-- Table `TURNOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `TURNOS` (
    `ID_Turno` INT AUTO_INCREMENT PRIMARY KEY,
    `Turno` VARCHAR(255) NOT NULL
) ENGINE = InnoDB;
/* drop table TURNOS; */
/* select * from TURNOS; */

-- -----------------------------------------------------
-- Table `CAIXAS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CAIXAS` (
    `ID_Caixa` INT AUTO_INCREMENT PRIMARY KEY,
    `Caixa` VARCHAR(255) NOT NULL,
    `Status` ENUM('Aberto', 'Fechado') NOT NULL DEFAULT 'Fechado',
    `StatusCadastrado` ENUM('Ativo', 'Inativo') NOT NULL DEFAULT 'Ativo'
) ENGINE = InnoDB;
/* drop table CAIXAS; */
/* select * from CAIXAS; */

-- -----------------------------------------------------
-- Table `CAIXAS_ABERTOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CAIXAS_ABERTOS` (
    `ID_CaixaAberto` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Caixa` INT NOT NULL,
    `ID_Funcionario` INT NOT NULL,
    `ID_Turno` INT NOT NULL,
    `Data_Abertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `Saldo_Inicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00, 
    `Data_Fechamento` DATETIME DEFAULT NULL,
    `Saldo_Final` DECIMAL(10,2) DEFAULT NULL,
    `Valor_Vendido` DECIMAL(10,2) DEFAULT NULL,
    FOREIGN KEY (`ID_Caixa`) REFERENCES `CAIXAS` (`ID_Caixa`),
    FOREIGN KEY (`ID_Funcionario`) REFERENCES `FUNCIONARIOS` (`ID_Funcionario`),
    FOREIGN KEY (`ID_Turno`) REFERENCES `TURNOS` (`ID_Turno`)
) ENGINE = InnoDB;
/* drop table CAIXAS_ABERTOS; */
/* select * from CAIXAS_ABERTOS; */

-- -----------------------------------------------------
-- Table `MOVIMENTACOES_CAIXA`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `MOVIMENTACOES_CAIXA` (
    `ID_MovimentacaoCaixa` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Caixa` INT NOT NULL,
    `ID_Funcionario` INT NOT NULL, 
    `Tipo` ENUM('Entrada', 'Saída') NOT NULL, 
    `Valor` DECIMAL(10,2) NOT NULL, 
    `Descricao` VARCHAR(255) NOT NULL, 
    `Data_Movimentacao` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ID_Caixa`) REFERENCES `CAIXAS` (`ID_Caixa`),
    FOREIGN KEY (`ID_Funcionario`) REFERENCES `FUNCIONARIOS` (`ID_Funcionario`)
) ENGINE = InnoDB;
/* drop table MOVIMENTACOES_CAIXA; */
/* select * from MOVIMENTACOES_CAIXA; */

-- -----------------------------------------------------
-- Table `FORMAS_PAGAMENTO`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `FORMAS_PAGAMENTO` (
    `ID_Forma_Pag` INT AUTO_INCREMENT PRIMARY KEY,
    `Tipo` VARCHAR(255) NOT NULL,
    `Status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo'
) ENGINE = InnoDB;
/* drop table FORMAS_PAGAMENTO; */
/* select * from FORMAS_PAGAMENTO; */

-- -----------------------------------------------------
-- Table `VENDAS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `VENDAS` (
    `ID_Venda` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Funcionario` INT NOT NULL,
    `ID_CaixaAberto` INT NOT NULL,
    `ID_Cliente` INT DEFAULT NULL,
    `DataHora_Venda` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `Valor_Total` DECIMAL(10,2) NOT NULL,
    `Desconto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (`ID_Funcionario`) REFERENCES `FUNCIONARIOS` (`ID_Funcionario`),
    FOREIGN KEY (`ID_CaixaAberto`) REFERENCES `CAIXAS_ABERTOS` (`ID_CaixaAberto`),
    FOREIGN KEY (`ID_Cliente`) REFERENCES `CLIENTES` (`ID_Cliente`)
) ENGINE = InnoDB;
/* drop table VENDAS; */
/* select * from VENDAS; */

-- -----------------------------------------------------
-- Table `MOVIMENTACAO_ESTOQUE`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `MOVIMENTACAO_ESTOQUE` (
    `ID_MovimentacaoEstoque` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Estoque` INT NOT NULL,
    `ID_Produto` INT NOT NULL,
    `ID_Funcionario` INT NOT NULL,
    `Tipo` ENUM('Entrada', 'Saída') NOT NULL,
    `Quantidade` INT NOT NULL,
    `ID_Venda` INT DEFAULT NULL,
    `Data_Movimentacao` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `OBS` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`ID_Estoque`) REFERENCES `ESTOQUE` (`ID_Estoque`),
    FOREIGN KEY (`ID_Produto`) REFERENCES `PRODUTOS` (`ID_Produto`),
    FOREIGN KEY (`ID_Venda`) REFERENCES `VENDAS` (`ID_Venda`),
    FOREIGN KEY (`ID_Funcionario`) REFERENCES `FUNCIONARIOS` (`ID_Funcionario`)
) ENGINE = InnoDB;
/* drop table MOVIMENTACAO_ESTOQUE; */
/* select * from MOVIMENTACAO_ESTOQUE; */

-- -----------------------------------------------------
-- Table `VENDA_PAGAMENTOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `VENDA_PAGAMENTOS` (
    `ID_VendaPagamento` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Venda` INT NOT NULL,
    `ID_Forma_Pag` INT NOT NULL,
    `Valor` DECIMAL(10,2) NOT NULL,
    `Troco` DECIMAL(10,2) DEFAULT 0.00,
    `Quant_Vezes` INT NOT NULL DEFAULT 1,
    `Data_Pagamento` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ID_Venda`) REFERENCES `VENDAS` (`ID_Venda`),
    FOREIGN KEY (`ID_Forma_Pag`) REFERENCES `FORMAS_PAGAMENTO` (`ID_Forma_Pag`)
) ENGINE = InnoDB;
/* drop table VENDA_PAGAMENTOS; */
/* select * from VENDA_PAGAMENTOS; */

-- -----------------------------------------------------
-- Table `ITENS_VENDA`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ITENS_VENDA` (
    `ID_Item_Venda` INT AUTO_INCREMENT PRIMARY KEY,
    `ID_Venda` INT NOT NULL,
    `ID_Produto` INT NOT NULL,
    `Quantidade` INT NOT NULL,
    `Valor_Total` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`ID_Venda`) REFERENCES `VENDAS` (`ID_Venda`),
    FOREIGN KEY (`ID_Produto`) REFERENCES `PRODUTOS` (`ID_Produto`)
) ENGINE = InnoDB;
/* drop table ITENS_VENDA; */
/* select * from ITENS_VENDA; */

-- -----------------------------------------------------
-- Table `LOGS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `LOGS` (
  `ID_Log` INT AUTO_INCREMENT PRIMARY KEY,
  `ID_Usuario` INT,
  `Acao` TEXT NOT NULL,
  `Timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`ID_Usuario`) REFERENCES `USUARIOS`(`ID_Usuario`)
) ENGINE = InnoDB;
/* drop table LOGS; */
/* select * from LOGS; */

-- -----------------------------------------------------
-- Table `SERVICOS_FARMACEUTICOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `SERVICOS_FARMACEUTICOS` (
  `ID_Servico` INT AUTO_INCREMENT PRIMARY KEY,
  `Nome_Servico` VARCHAR(255) NOT NULL,
  `Descricao` TEXT NULL,
  `Valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `Status` ENUM('Ativo', 'Inativo') DEFAULT 'Ativo'
) ENGINE = InnoDB;
/* drop table SERVICOS_FARMACEUTICOS; */
/* select * from SERVICOS_FARMACEUTICOS; */

-- -----------------------------------------------------
-- Table `REGISTRO_SERVICOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `REGISTRO_SERVICOS` (
  `ID_Registro_Servico` INT AUTO_INCREMENT PRIMARY KEY,
  `ID_Servico` INT NOT NULL,
  `ID_Cliente` INT NULL,
  `Nome_Paciente` VARCHAR(255) NOT NULL,
  `Doc_Paciente` VARCHAR(15) NOT NULL,
  `Sexo_Paciente` ENUM('Masculino', 'Feminino') NOT NULL,
  `Data_Nascimento_Paciente` DATE NOT NULL,
  `ID_Funcionario` INT NOT NULL,
  `DataHora` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `Dados_Servico` JSON NULL, 
  `OBS` TEXT NULL,
  FOREIGN KEY (`ID_Servico`) REFERENCES `SERVICOS_FARMACEUTICOS`(`ID_Servico`),
  FOREIGN KEY (`ID_Cliente`) REFERENCES `CLIENTES`(`ID_Cliente`),
  FOREIGN KEY (`ID_Funcionario`) REFERENCES `FUNCIONARIOS`(`ID_Funcionario`)
) ENGINE = InnoDB;
/* drop table REGISTRO_SERVICOS; */
/* select * from REGISTRO_SERVICOS; */

-- -----------------------------------------------------
-- Table `SERVICO_CAMPOS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `SERVICO_CAMPOS` (
  `ID_Campo` INT AUTO_INCREMENT PRIMARY KEY,
  `ID_Servico` INT NOT NULL,
  `Ordem` INT DEFAULT 0,
  `Label_Campo` VARCHAR(255) NOT NULL, -- "Pressão Sistólica"
  `Name_Campo` VARCHAR(100) NOT NULL, -- "pressao_sistolica"
  `Tipo_Campo` ENUM('number', 'text', 'boolean', 'date', 'ean') NOT NULL, -- Tipo do input
  `Unidade_Medida` VARCHAR(20) NULL, -- "mmHg", "BPM", etc.
  FOREIGN KEY (`ID_Servico`) REFERENCES `SERVICOS_FARMACEUTICOS`(`ID_Servico`) ON DELETE CASCADE
) ENGINE=InnoDB;
/* drop table SERVICO_CAMPOS; */
/* select * from SERVICO_CAMPOS; */

-- -----------------------------------------------------
-- Table `SERVICO_CAMPOS_REFERENCIAS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `SERVICO_CAMPOS_REFERENCIAS` (
  `ID_Referencia` INT AUTO_INCREMENT PRIMARY KEY,
  `ID_Campo` INT NOT NULL,
  `Descricao_Referencia` VARCHAR(255) NOT NULL, -- Ex: "Criança (0-12 anos)"
  `Valor_Feminino` VARCHAR(100) NULL,
  `Valor_Masculino` VARCHAR(100) NULL,
  FOREIGN KEY (`ID_Campo`) REFERENCES `SERVICO_CAMPOS`(`ID_Campo`) ON DELETE CASCADE
) ENGINE=InnoDB;
/* drop table SERVICO_CAMPOS_REFERENCIAS; */
/* select * from SERVICO_CAMPOS_REFERENCIAS; */

-- -----------------------------------------------------
-- Table `PRE_VENDAS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `PRE_VENDAS` (
  `ID_PreVenda` INT AUTO_INCREMENT PRIMARY KEY,
  `Codigo_PreVenda` VARCHAR(20) NOT NULL UNIQUE, -- Código único para o cliente levar ao caixa
  `ID_Cliente` INT NULL,
  `ID_Funcionario` INT NOT NULL,
  `Status` ENUM('Pendente', 'Finalizada', 'Cancelada') NOT NULL DEFAULT 'Pendente',
  `Data_Criacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Data_Finalizacao` DATETIME NULL,
  `ID_Venda` INT NULL,
  FOREIGN KEY (`ID_Cliente`) REFERENCES `CLIENTES` (`ID_Cliente`) ON DELETE SET NULL,
  FOREIGN KEY (`ID_Funcionario`) REFERENCES `FUNCIONARIOS` (`ID_Funcionario`),
  FOREIGN KEY (`ID_Venda`) REFERENCES `VENDAS` (`ID_Venda`) ON DELETE SET NULL
) ENGINE = InnoDB;
/* drop table PRE_VENDAS; */
/* select * from PRE_VENDAS; */

-- -----------------------------------------------------
-- Table `PRE_VENDAS_ITENS`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `PRE_VENDAS_ITENS` (
  `ID_Item_PreVenda` INT AUTO_INCREMENT PRIMARY KEY,
  `ID_PreVenda` INT NOT NULL,
  `ID_Produto` INT NULL,
  `ID_Servico` INT NULL,
  `Quantidade` INT NOT NULL DEFAULT 1,
  `Valor_Unitario` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`ID_PreVenda`) REFERENCES `PRE_VENDAS` (`ID_PreVenda`) ON DELETE CASCADE,
  FOREIGN KEY (`ID_Produto`) REFERENCES `PRODUTOS` (`ID_Produto`),
  FOREIGN KEY (`ID_Servico`) REFERENCES `SERVICOS_FARMACEUTICOS` (`ID_Servico`),
  -- Garante que cada linha seja ou um produto ou um serviço, mas não ambos
  CONSTRAINT chk_item_type CHECK (`ID_Produto` IS NOT NULL OR `ID_Servico` IS NOT NULL)
) ENGINE = InnoDB;
/* drop table PRE_VENDAS_ITENS; */
/* select * from PRE_VENDAS_ITENS; */