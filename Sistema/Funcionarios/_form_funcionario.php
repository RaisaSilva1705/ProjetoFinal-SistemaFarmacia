<?php
$nome = htmlspecialchars($funcionario['Nome'] ?? '');
$documento = htmlspecialchars($funcionario['Documento'] ?? '');
$telefone = htmlspecialchars($funcionario['Telefone'] ?? '');
$email = htmlspecialchars($funcionario['Email'] ?? '');
$id_cargo = $funcionario['ID_Cargo'] ?? '';
$salario = htmlspecialchars($funcionario['Salario'] ?? '');
$data_admissao = $funcionario['Data_Admissao'] ?? '';
$status = $funcionario['Status'] ?? 'Ativo';
$obs = htmlspecialchars($funcionario['OBS'] ?? '');
$usuario_login = htmlspecialchars($usuario['Usuario'] ?? '');
?>

<form action="processa_funcionario.php" method="POST">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id_funcionario" value="<?= $id_funcionario ?>">
    <?php endif; ?>
    <h5 class="mt-4">Dados Pessoais e Contrato</h5>
    <div class="row">
        <div class="col-md-8 mb-3">
            <label for="nome" class="form-label">Nome Completo</label>
            <input type="text" id="nome" name="nome" class="form-control" required value="<?= $nome ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="documento" class="form-label">CPF</label>
            <input type="text" id="documento" name="documento" class="form-control" value="<?= $documento ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="telefone" class="form-label">Telefone</label>
            <input type="text" id="telefone" name="telefone" class="form-control" value="<?= $telefone ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" required value="<?= $email ?>">
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="id_cargo" class="form-label">Cargo</label>
            <select name="id_cargo" id="id_cargo" class="form-select" required>
                <option value="">Selecione...</option>
                <?php
                $cargos_result = $conn->query("SELECT ID_Cargo, Cargo FROM CARGOS WHERE Status = 'Ativo' ORDER BY Cargo");
                while ($cargo = $cargos_result->fetch_assoc()) {
                    $selected = ($id_cargo == $cargo['ID_Cargo']) ? 'selected' : '';
                    echo "<option value='{$cargo['ID_Cargo']}' {$selected}>{$cargo['Cargo']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="salario" class="form-label">Salário</label>
            <input type="text" id="salario" name="salario" class="form-control" value="<?= $salario ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="data_admissao" class="form-label">Data de Admissão</label>
            <input type="date" id="data_admissao" name="data_admissao" class="form-control" value="<?= $data_admissao ?>">
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
    <h5 class="mt-4">Acesso ao Sistema</h5>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="usuario" class="form-label">Usuário (Login)</label>
            <input type="text" id="usuario" name="usuario" class="form-control" required value="<?= $usuario_login ?>" <?= $is_edit ? 'readonly' : '' ?>>
             <?php if ($is_edit): ?>
                <small class="form-text text-muted">O nome de usuário não pode ser alterado após a criação.</small>
            <?php endif; ?>
        </div>
        <div class="col-md-6 mb-3">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" id="senha" name="senha" class="form-control" <?= !$is_edit ? 'required' : '' ?>>
            <?php if ($is_edit): ?>
                <small class="form-text text-muted">Deixe em branco para não alterar a senha.</small>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Salvar Alterações' : 'Cadastrar Funcionário' ?></button>
        <a href="funcionarios.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>