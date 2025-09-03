<?php
$nome = htmlspecialchars($cliente['Nome'] ?? '');
$tipo = $cliente['Tipo'] ?? 'PF';
$documento = htmlspecialchars($cliente['Documento'] ?? '');
$tel = htmlspecialchars($cliente['Tel'] ?? '');
$email = htmlspecialchars($cliente['Email'] ?? '');
$status = $cliente['Status'] ?? 'Ativo';
$obs = htmlspecialchars($cliente['OBS'] ?? '');
?>

<form action="" method="POST">
    <div class="row">
        <div class="col-md-8 mb-3">
            <label for="nome" class="form-label">Nome Completo</label>
            <input type="text" id="nome" name="nome" class="form-control" required value="<?= $nome ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="tipo" class="form-label">Tipo de Pessoa</label>
            <select name="tipo" id="tipo" class="form-select" required>
                <option value="PF" <?= $tipo == 'PF' ? 'selected' : '' ?>>Pessoa Física (PF)</option>
                <option value="PJ" <?= $tipo == 'PJ' ? 'selected' : '' ?>>Pessoa Jurídica (PJ)</option>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="documento" class="form-label">Documento (CPF/CNPJ)</label>
            <input type="text" id="documento" name="documento" class="form-control" required value="<?= $documento ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="tel" class="form-label">Telefone</label>
            <input type="text" id="tel" name="tel" class="form-control" required value="<?= $tel ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" required value="<?= $email ?>">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-9 mb-3">
            <label for="obs" class="form-label">Observações</label>
            <textarea name="obs" id="obs" class="form-control" rows="1"><?= $obs ?></textarea>
        </div>
        <div class="col-md-3 mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="Ativo" <?= $status == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="Inativo" <?= $status == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
            </select>
        </div>
    </div>
    
    <hr>
    
    <h5 class="mt-4">Segurança</h5>
     <div class="row">
        <div class="col-md-6 mb-3">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" id="senha" name="senha" class="form-control" <?= !$is_edit ? 'required' : '' ?>>
            <?php if ($is_edit): ?>
                <small class="form-text text-muted">Deixe em branco para não alterar a senha.</small>
            <?php endif; ?>
        </div>
    </div>


    <div class="mt-4">
        <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Salvar Alterações' : 'Cadastrar Cliente' ?></button>
        <a href="clientes.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>