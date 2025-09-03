<form action="" method="POST" enctype="multipart/form-data">
    <h5 class="mt-4">Informações do Produto</h5>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nome" class="form-label">Nome do Produto</label>
            <input type="text" id="nome" name="nome" class="form-control" required value="<?= htmlspecialchars($produto['Nome'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="id_fornecedor" class="form-label">Fornecedor</label>
            <input type="text" id="id_fornecedor" name="id_fornecedor" class="form-control" value="<?= htmlspecialchars($produto['ID_Fornecedor'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="id_categoria" class="form-label">Categoria</label>
            <select class="form-select" name="id_categoria" id="id_categoria" required>
                <option value="">Selecione</option>
                <?php
                $categorias->data_seek(0);
                while($cat = $categorias->fetch_assoc()):
                    $selected = (($produto['ID_Categoria'] ?? '') == $cat['ID_Categoria']) ? 'selected' : '';
                    echo "<option value='{$cat['ID_Categoria']}' data-nome-categoria='" . strtolower($cat['Categoria']) . "' {$selected}>{$cat['Categoria']}</option>";
                endwhile;
                ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="descricao" class="form-label">Descrição</label>
            <input type="text" name="descricao" id="descricao" class="form-control" value="<?= htmlspecialchars($produto['Descricao'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="id_unidade" class="form-label">Unidade</label>
            <select class="form-select" name="id_unidade" id="id_unidade" required>
                <option value="">Selecione</option>
                <?php
                $unidades->data_seek(0);
                while($uni = $unidades->fetch_assoc()):
                    $selected = (($produto['ID_Unidade'] ?? '') == $uni['ID_Unidade']) ? 'selected' : '';
                    echo "<option value='{$uni['ID_Unidade']}' {$selected}>{$uni['Unidade']}</option>";
                endwhile;
                ?>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label for="quant_minima" class="form-label">Quantidade Mínima</label>
            <input type="number" name="quant_minima" id="quant_minima" class="form-control" value="<?= htmlspecialchars($produto['Quant_Minima'] ?? '10') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="obs" class="form-label">Observações</label>
            <textarea class="form-control" name="obs" id="obs" rows="1"><?= htmlspecialchars($produto['OBS'] ?? '') ?></textarea>
        </div>
        <div class="col-md-3 mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="">Selecione</option>
                <option value="Ativo" <?= (($produto['Status'] ?? '') == 'Ativo') ? 'selected' : '' ?>>Ativo</option>
                <option value="Inativo" <?= (($produto['Status'] ?? '') == 'Inativo') ? 'selected' : '' ?>>Inativo</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label for="foto" class="form-label">Foto do Produto</label>
            <input type="file" name="foto" class="form-control">
            <?php if (!empty($produto['Foto'])): ?>
                <small class="form-text text-muted">Atual: <?= htmlspecialchars($produto['Foto']) ?></small>
            <?php endif; ?>
        </div>

        <!-- CAMPOS DE MEDICAMENTOS -->
        <div id="campos_medicamento" style="display: none;">
            <hr>
            <h5 class="mt-4">Informações do Medicamento</h5>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="id_categoria_med" class="form-label">Categoria Medicamento</label>
                    <select class="form-select" name="id_categoria_med" id="id_categoria_med">
                        <option value="">Selecione</option>
                        <?php
                        $categoriasMed->data_seek(0);
                        while($catMed = $categoriasMed->fetch_assoc()):
                            $selected = (($medicamento['ID_CategoriaMed'] ?? '') == $catMed['ID_CategoriaMed']) ? 'selected' : '';
                            echo "<option value='{$catMed['ID_CategoriaMed']}' {$selected}>{$catMed['Categoria_Med']}</option>";
                        endwhile;
                        ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="tipo_med" class="form-label">Tipo</label>
                    <select name="tipo_med" class="form-select" id="tipo_med">
                        <option value="">Selecione</option>
                        <option value="Genérico" <?= (($medicamento['Tipo'] ?? '') == 'Genérico') ? 'selected' : '' ?>>Genérico</option>
                        <option value="Similar" <?= (($medicamento['Tipo'] ?? '') == 'Similar') ? 'selected' : '' ?>>Similar</option>
                        <option value="Referência" <?= (($medicamento['Tipo'] ?? '') == 'Referência') ? 'selected' : '' ?>>Referência</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="id_tarja_med" class="form-label">Tarja</label>
                    <select class="form-select" name="id_tarja_med" id="id_tarja_med">
                        <option value="">Selecione</option>
                        <?php
                        $tarjasMed->data_seek(0);
                        while($tjMed = $tarjasMed->fetch_assoc()):
                            $selected = (($medicamento['ID_Tarja'] ?? '') == $tjMed['ID_Tarja']) ? 'selected' : '';
                            echo "<option value='{$tjMed['ID_Tarja']}' {$selected}>{$tjMed['Tarja']}</option>";
                        endwhile;
                        ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="prin_ativo" class="form-label">Princípio Ativo</label>
                    <input type="text" class="form-control" name="prin_ativo" id="prin_ativo" value="<?= htmlspecialchars($medicamento['Prin_Ativo'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <hr>

    <h5 class="mt-4">Informações Fiscais</h5>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label for="ncm" class="form-label">NCM</label>
            <input type="text" name="ncm" class="form-control" maxlength="8" required value="<?= htmlspecialchars($produto['NCM'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="ean_gtin" class="form-label">EAN/GTIN</label>
            <input type="text" name="ean_gtin" class="form-control" maxlength="14" value="<?= htmlspecialchars($produto['EAN_GTIN'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="cbenef" class="form-label">CBENEF</label>
            <input type="text" name="cbenef" class="form-control" value="<?= htmlspecialchars($produto['CBENEF'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="cest" class="form-label">CEST</label>
            <input type="text" name="cest" class="form-control" value="<?= htmlspecialchars($produto['CEST'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="extipi" class="form-label">EXTIPI</label>
            <input type="text" name="extipi" class="form-control" value="<?= htmlspecialchars($produto['EXTIPI'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="cfop" class="form-label">CFOP</label>
            <input type="number" name="cfop" class="form-control" value="<?= htmlspecialchars($produto['CFOP'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="mva" class="form-label">MVA</label>
            <input type="text" name="mva" class="form-control" value="<?= htmlspecialchars($produto['MVA'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label for="nfci" class="form-label">NFCI</label>
            <input type="text" name="nfci" class="form-control" value="<?= htmlspecialchars($produto['NFCI'] ?? '') ?>">
        </div>
    </div>

    <button type="submit" class="btn btn-primary mt-4"><?= $is_edit ? 'Salvar Alterações' : 'Cadastrar Produto' ?></button>
    <a href="produtos.php" class="btn btn-secondary mt-4 ms-2">Cancelar</a>
</form>