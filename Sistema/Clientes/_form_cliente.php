<?php
$nome = htmlspecialchars($cliente['Nome'] ?? '');
$tipo_pessoa = $cliente['Tipo'] ?? 'PF';
$data_nascimento = $cliente['Data_Nascimento'] ?? '';
$sexo = htmlspecialchars($cliente['Sexo'] ?? '');
$genero = htmlspecialchars($cliente['Genero'] ?? '');
$tel = htmlspecialchars($cliente['Tel'] ?? '');
$email = htmlspecialchars($cliente['Email'] ?? '');
$status = $cliente['Status'] ?? 'Ativo';
$obs = htmlspecialchars($cliente['OBS'] ?? '');
$documentos_json = json_encode($documentos_cliente ?? []);
?>
<form action="processa_cliente.php" method="POST">
    <?php if ($is_edit): ?>
        <input type="hidden" name="id_cliente" value="<?= $cliente['ID_Cliente'] ?>">
    <?php endif; ?>
    <h5>Dados</h5>
    <div class="row">
        <div class="col-md-8 mb-3"><label for="nome" class="form-label">Nome Completo</label><input type="text" id="nome" name="nome" class="form-control" required value="<?= $nome ?>"></div>
        <div class="col-md-4 mb-3"><label for="tipo_pessoa" class="form-label">Tipo de Pessoa</label><select name="tipo_pessoa" id="tipo_pessoa" class="form-select" required><option value="PF" <?= $tipo_pessoa == 'PF' ? 'selected' : '' ?>>Pessoa Física (PF)</option><option value="PJ" <?= $tipo_pessoa == 'PJ' ? 'selected' : '' ?>>Pessoa Jurídica (PJ)</option></select></div>
        <div class="col-md-4 mb-3"><label for="data_nascimento" class="form-label">Data de Nascimento</label><input type="date" id="data_nascimento" name="data_nascimento" class="form-control" required value="<?= $data_nascimento ?>"></div>
        <div class="col-md-4 mb-3"><label for="sexo" class="form-label">Sexo Biológico</label><select name="sexo" id="sexo" class="form-select" required><option value="">Selecione...</option><option value="Feminino" <?= $sexo == 'Feminino' ? 'selected' : '' ?>>Feminino</option><option value="Masculino" <?= $sexo == 'Masculino' ? 'selected' : '' ?>>Masculino</option></select></div>
        <div class="col-md-4 mb-3">
            <label for="genero" class="form-label">Gênero</label>
            <select name="genero" id="genero" class="form-select" required>
                <option value="">Selecione...</option>
                <option value="Mulher Cis" <?= $genero == 'Mulher Cis' ? 'selected' : '' ?>>Mulher Cis</option>
                <option value="Homem Cis" <?= $genero == 'Homem Cis' ? 'selected' : '' ?>>Homem Cis</option>
                <option value="Mulher Trans" <?= $genero == 'Mulher Trans' ? 'selected' : '' ?>>Mulher Trans</option>
                <option value="Homem Trans" <?= $genero == 'Homem Trans' ? 'selected' : '' ?>>Homem Trans</option>
                <option value="Não Binário" <?= $genero == 'Não Binário' ? 'selected' : '' ?>>Não Binário</option>
            </select>
        </div>
    </div>

    <hr>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="m-0">Documentos</h5>
        <button type="button" id="btn-add-documento" class="btn btn-sm btn-success"><i class="bi bi-plus-circle"></i> Adicionar Documento</button>
    </div>
    <div id="documentos-container">
    </div>
    <div id="documentos-excluidos-container">
    </div>

    <hr>
    <h5>Contato</h5>
    <div class="row mt-3">
        <div class="col-md-6 mb-3"><label for="tel" class="form-label">Telefone</label><input type="text" id="tel" name="tel" class="form-control" required value="<?= $tel ?>"></div>
        <div class="col-md-6 mb-3"><label for="email" class="form-label">Email</label><input type="email" id="email" name="email" class="form-control" required value="<?= $email ?>"></div>
    </div>
    
    <hr>
    <div class="row">
        <div class="col-md-9 mb-3"><label for="obs" class="form-label">Observações</label><textarea name="obs" id="obs" class="form-control" rows="1"><?= $obs ?></textarea></div>
        <div class="col-md-3 mb-3"><label for="status" class="form-label">Status</label><select name="status" id="status" class="form-select" required><option value="Ativo" <?= $status == 'Ativo' ? 'selected' : '' ?>>Ativo</option><option value="Inativo" <?= $status == 'Inativo' ? 'selected' : '' ?>>Inativo</option></select></div>
    </div>
    
    <hr>
    <h5 class="mt-4">Segurança</h5>
     <div class="row">
        <div class="col-md-6 mb-3">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" id="senha" name="senha" class="form-control" <?= !$is_edit ? 'required' : '' ?>>
            <?php if ($is_edit): ?><small class="form-text text-muted">Deixe em branco para não alterar a senha.</small><?php endif; ?>
        </div>
    </div>

    <div class="mt-4 text-end">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> <?= $is_edit ? 'Salvar Alterações' : 'Cadastrar Cliente' ?></button>
        <a href="clientes.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Cancelar</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentosContainer = document.getElementById('documentos-container');
    const btnAddDocumento = document.getElementById('btn-add-documento');
    const documentosExcluidosContainer = document.getElementById('documentos-excluidos-container');
    let docCounter = 0;
    
    // Pega os documentos existentes (para a tela de edição)
    const documentosIniciais = <?= $documentos_json ?>;

    // Função para criar uma nova linha de documento
    function adicionarDocumento(docData = null) {
        const isNew = docData === null;
        const docIndex = isNew ? `new_${docCounter++}` : docData.ID_Documento;

        const newRow = document.createElement('div');
        newRow.classList.add('row', 'align-items-center', 'mb-2', 'documento-item');
        newRow.setAttribute('data-doc-id', docData?.ID_Documento || '');

        newRow.innerHTML = `
            <input type="hidden" name="documentos[${docIndex}][id]" value="${docData?.ID_Documento || ''}">
            <div class="col-md-4">
                <select name="documentos[${docIndex}][tipo]" class="form-select" required>
                    <option value="CPF" ${docData?.Tipo === 'CPF' ? 'selected' : ''}>CPF</option>
                    <option value="CNPJ" ${docData?.Tipo === 'CNPJ' ? 'selected' : ''}>CNPJ</option>
                    <option value="RG" ${docData?.Tipo === 'RG' ? 'selected' : ''}>RG</option>
                    <option value="CNH" ${docData?.Tipo === 'CNH' ? 'selected' : ''}>CNH</option>
                </select>
            </div>
            <div class="col-md-7">
                <input type="text" name="documentos[${docIndex}][numero]" class="form-control" required value="${docData?.Numero || ''}" placeholder="Número do Documento">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm w-100 btn-remover-doc"><i class="bi bi-trash"></i></button>
            </div>
        `;
        documentosContainer.appendChild(newRow);
    }

    // Carrega os documentos iniciais na edição
    documentosIniciais.forEach(doc => adicionarDocumento(doc));

    // Se for um novo cadastro, adiciona um campo de CPF por padrão
    if (documentosIniciais.length === 0 && !<?= json_encode($is_edit) ?>) {
        adicionarDocumento();
    }

    // Evento para o botão de adicionar
    btnAddDocumento.addEventListener('click', () => adicionarDocumento());

    // Evento para remover (usando delegação)
    documentosContainer.addEventListener('click', function(e) {
        if (e.target && e.target.closest('.btn-remover-doc')) {
            const row = e.target.closest('.documento-item');
            const docId = row.getAttribute('data-doc-id');

            // Se for um documento que já existe no banco, marca para exclusão
            if (docId) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'delete_documentos[]';
                hiddenInput.value = docId;
                documentosExcluidosContainer.appendChild(hiddenInput);
            }
            row.remove();
        }
    });
});
</script>