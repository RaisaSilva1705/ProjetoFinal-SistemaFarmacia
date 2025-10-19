<?php
$nome_fantasia = htmlspecialchars($fornecedor['Nome_Fantasia'] ?? '');
$razao_social = htmlspecialchars($fornecedor['Nome'] ?? '');
$cnpj = htmlspecialchars($fornecedor['CNPJ'] ?? '');
$tel = htmlspecialchars($fornecedor['Tel'] ?? '');
$email = htmlspecialchars($fornecedor['Email'] ?? '');
$cep = htmlspecialchars($fornecedor['CEP'] ?? '');
$endereco = htmlspecialchars($fornecedor['Endereco'] ?? '');
$numero = htmlspecialchars($fornecedor['End_Numero'] ?? '');
$complemento = htmlspecialchars($fornecedor['Complemento'] ?? '');
$bairro = htmlspecialchars($fornecedor['Bairro'] ?? '');
$cidade = htmlspecialchars($fornecedor['Cidade'] ?? '');
$estado = htmlspecialchars($fornecedor['Estado'] ?? '');
$status = $fornecedor['Status'] ?? 'Ativo';
$obs = htmlspecialchars($fornecedor['OBS'] ?? '');
?>

<form action="processa_fornecedor.php" method="POST">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id_fornecedor" value="<?= $fornecedor['ID_Fornecedor'] ?>">
    <?php endif; ?>
    <h5 class="mt-4">Dados da Empresa</h5>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
            <input type="text" id="nome_fantasia" name="nome_fantasia" class="form-control" required value="<?= $nome_fantasia ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="razao_social" class="form-label">Razão Social</label>
            <input type="text" id="razao_social" name="razao_social" class="form-control" required value="<?= $razao_social ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="cnpj" class="form-label">CNPJ</label>
            <input type="text" id="cnpj" name="cnpj" class="form-control" required value="<?= $cnpj ?>">
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

    <hr>

    <h5 class="mt-4">Endereço</h5>
    <div class="row">
        <div class="col-md-2 mb-3">
            <label for="cep" class="form-label">CEP</label>
            <input type="text" id="cep" name="cep" class="form-control" required value="<?= $cep ?>">
        </div>
        <div class="col-md-5 mb-3">
            <label for="endereco" class="form-label">Endereço</label>
            <input type="text" id="endereco" name="endereco" class="form-control" required value="<?= $endereco ?>">
        </div>
        <div class="col-md-2 mb-3">
            <label for="numero" class="form-label">Número</label>
            <input type="text" id="numero" name="numero" class="form-control" required value="<?= $numero ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="complemento" class="form-label">Complemento</label>
            <input type="text" id="complemento" name="complemento" class="form-control" value="<?= $complemento ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="bairro" class="form-label">Bairro</label>
            <input type="text" id="bairro" name="bairro" class="form-control" required value="<?= $bairro ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="cidade" class="form-label">Cidade</label>
            <input type="text" id="cidade" name="cidade" class="form-control" required value="<?= $cidade ?>">
        </div>
        <div class="col-md-4 mb-3">
            <label for="estado" class="form-label">Estado</label>
            <input type="text" id="estado" name="estado" class="form-control" required maxlength="2" value="<?= $estado ?>">
        </div>
    </div>

    <hr>

    <h5 class="mt-4">Informações Adicionais</h5>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="Ativo" <?= $status == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="Inativo" <?= $status == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
            </select>
        </div>
        <div class="col-md-8 mb-3">
            <label for="obs" class="form-label">Observações</label>
            <textarea name="obs" id="obs" class="form-control" rows="1"><?= $obs ?></textarea>
        </div>
    </div>

    <div class="mt-4 text-end">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> <?= $is_edit ? 'Salvar Alterações' : 'Cadastrar Fornecedor' ?></button>
        <a href="fornecedores.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const campoCep = document.getElementById('cep');

        campoCep.addEventListener('input', function() {
            let cepValue = this.value.replace(/\D/g, '');

            if (cepValue.length === 8) {
                toggleAdressFields(true);
                
                fetch(`https://viacep.com.br/ws/${cepValue}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.erro) {
                            alert('CEP não encontrado.');
                            limparCamposEndereco();
                        } 
                        else {
                            document.getElementById('endereco').value = data.logradouro;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                            
                            document.getElementById('numero').focus();
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar CEP:', error);
                        mostrarToast('Não foi possível buscar o CEP.', 'warning');
                    })
                    .finally(() => {
                        toggleAdressFields(false);
                    });
            }
        });

        function toggleAdressFields(disabled) {
            document.getElementById('endereco').disabled = disabled;
            document.getElementById('bairro').disabled = disabled;
            document.getElementById('cidade').disabled = disabled;
            document.getElementById('estado').disabled = disabled;
        }

        function limparCamposEndereco() {
            document.getElementById('endereco').value = '';
            document.getElementById('bairro').value = '';
            document.getElementById('cidade').value = '';
            document.getElementById('estado').value = '';
        }
    });
</script>