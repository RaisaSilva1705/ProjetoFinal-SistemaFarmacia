<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
  .sidebar {
    width: 210px; 
    height: 100vh;
    background-color: #343a40;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
  }
  .sidebar-nav {
    flex-grow: 1; 
    overflow-y: auto; 
  }
  .sidebar a, .sidebar .dropdown-toggle {
    color: white;
    padding: 0.85rem 1.25rem;
    display: block;
    text-decoration: none;
    font-size: 0.95rem;
  }
  .sidebar-header {
    padding: 0.8rem 1rem;
    font-size: 1.5rem;
    font-weight: bold;
    color: #fff;
  }
  .sidebar a:hover, .sidebar .dropdown-toggle:hover {
    background-color: #495057;
  }
  .sidebar .dropdown-menu {
    background-color: #212529;
    border: none;
    padding: 0;
    margin: 0;
  }
  .sidebar .dropdown-item {
    padding-left: 2.5rem; 
    color: #adb5bd;
  }
  .sidebar .dropdown-item:hover {
    color: #fff;
    background-color: #495057;
  }
  .sidebar .dropdown-toggle::after {
    float: right;
    margin-top: 0.5rem;
  }
  .sidebar .nav-item i {
    margin-right: 0.75rem; 
  }
  .sidebar-footer {
    border-top: 1px solid #495057;
  }
</style>

<div class="sidebar">
  <div class="sidebar-header">
    <a href="<?php echo SISTEMA_URL ?>dashboard.php" class="text-white text-decoration-none"><?php echo NOME ?></a>
  </div>

  <div class="sidebar-nav">
    <a class="nav-item" href="<?php echo SISTEMA_URL ?>dashboard.php"><i class="bi bi-grid-fill"></i>Painel</a>

    <div class="nav-item dropdown">
      <a class="dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-shop-window"></i>Frente de Loja</a>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>PDV/pdv.php">Nova Venda (PDV)</a></li>
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Caixas/caixas.php">Situação do Caixa</a></li>
      </ul>
    </div>

    <div class="nav-item dropdown">
      <a class="dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-box-seam-fill"></i>Gestão</a>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Produtos/produtos.php">Produtos</a></li>
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Estoque/estoque.php">Estoque</a></li>
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Fornecedores/fornecedores.php">Fornecedores</a></li>
        <hr class="dropdown-divider" style="border-color: #495057;">
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Cadastros/Caixas/caixas.php">Caixas</a></li>
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Cadastros/Cargos/cargos.php">Cargos</a></li>
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Cadastros/Unidades/unidades.php">Unidades</a></li>
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Cadastros/Pagamentos/formas_pagamentos.php">Formas de Pagamento</a></li>
      </ul>
    </div>

    <div class="nav-item dropdown">
      <a class="dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-people-fill"></i>Pessoas</a>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Clientes/clientes.php">Clientes</a></li>
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Funcionarios/funcionarios.php">Funcionários</a></li>
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Usuarios/usuarios.php">Usuários e Permissões</a></li>
      </ul>
    </div>

    <div class="nav-item dropdown">
      <a class="dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-bar-chart-line-fill"></i>Relatórios</a>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>PDV/relatorio_pdv.php">Relatório de Vendas</a></li>
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Estoque/relatorio_estoque.php">Relatório de Estoque</a></li>
      </ul>
    </div>

    <div class="nav-item dropdown">
      <a class="dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-gear-fill"></i>Configurações</a>
      <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="<?php echo SISTEMA_URL ?>Configuracoes/empresa.php">Dados da Empresa</a></li>
      </ul>
    </div>
  </div>

  <div class="sidebar-footer">
    <div class="nav-item dropdown">
      <a class="dropdown-toggle" href="#" data-bs-toggle="dropdown">
        <i class="bi bi-person-circle"></i><?php echo htmlspecialchars($_SESSION['Nome']); ?>
      </a>
      <ul class="dropdown-menu dropdown-menu-dark">
        <li><a class="dropdown-item" href="#">Minha Conta</a></li>
        <li><hr class="dropdown-divider" style="border-color: #495057;"></li>
        <li><a class="dropdown-item" href="<?php echo DEV_URL ?>Exec/logout.php"><i class="bi bi-box-arrow-right"></i>Sair</a></li>
      </ul>
    </div>
  </div>
</div>