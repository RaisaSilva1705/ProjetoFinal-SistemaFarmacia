<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'SERVICOS_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";

$id_servico = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_servico) {
    $_SESSION['msg'] = ['texto' => 'ID do serviço não fornecido.', 'tipo' => 'warning'];
    header("Location: servicos.php"); 
    exit();
}

$stmt = $conn->prepare("SELECT * FROM SERVICOS_FARMACEUTICOS WHERE ID_Servico = ?");
$stmt->bind_param("i", $id_servico);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) 
    $servico = $result->fetch_assoc();
else {
    $_SESSION['msg'] = ['texto' => 'Serviço não encontrado.', 'tipo' => 'danger'];
    header("Location: servicos.php"); 
    exit();
}

$campos_servicos = []; 
$stmtCampos = $conn->prepare("SELECT * FROM SERVICO_CAMPOS WHERE ID_Servico = ? ORDER BY Ordem, ID_Campo");
$stmtCampos->bind_param("i", $id_servico);
$stmtCampos->execute();
$resultCampos = $stmtCampos->get_result();

while ($campo = $resultCampos->fetch_assoc()) {
    $id_campo = $campo['ID_Campo'];
    $campo['referencias'] = []; 

    $stmtRefs = $conn->prepare("SELECT * FROM SERVICO_CAMPOS_REFERENCIAS WHERE ID_Campo = ? ORDER BY ID_Referencia");
    $stmtRefs->bind_param("i", $id_campo);
    $stmtRefs->execute();
    $resultRefs = $stmtRefs->get_result();
    while ($ref = $resultRefs->fetch_assoc())
        $campo['referencias'][] = $ref;
    
    $campos_servicos[] = $campo;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF--8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edição de Serviço</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

       <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Edição de Serviço</h3>
                </div>
            
                <div class="container p-5">
                    <form action="processa_edicao_servico.php" method="POST">
                        <input type="hidden" name="id_servico" value="<?= $id_servico ?>">
                        <div class="card card-body mb-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nome do Serviço</label>
                                    <input type="text" name="nome_servico" class="form-control" value="<?= htmlspecialchars($servico['Nome_Servico']) ?>" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Valor (R$)</label>
                                    <input type="text" id="valor_formatado" class="form-control" value="<?= number_format($servico['Valor'], 2, ',', '.') ?>" required>
                                    <input type="hidden" name="valor" id="valor_real" value="<?= $servico['Valor'] ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="Ativo" <?= $servico['Status'] == 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="Inativo" <?= $servico['Status'] == 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Descrição</label>
                                    <textarea name="descricao" class="form-control" rows="2"><?= htmlspecialchars($servico['Descricao']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h4>Campos Personalizados do Formulário</h4>
                                <small id="json-size-counter" class="text-muted fw-bold"></small>
                            </div>
                            <button type="button" id="btnAddCampo" class="btn btn-success"><i class="bi bi-plus-circle"></i> Adicionar Campo</button>
                        </div>

                        <div id="container-campos">
                            </div>
                        
                        <div id="container-exclusao"></div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            <a href="servicos.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <strong class="me-auto" id="toastTitulo">Notificação</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="toastCorpo"></div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            const camposExistentes = <?= json_encode($campos_servicos) ?>;
            
            document.addEventListener('DOMContentLoaded', function() {
                const LIMITE_JSON = 4000; // limite de segurança
                const jsonSizeCounter = document.getElementById('json-size-counter');
                const btnAddCampo = document.getElementById('btnAddCampo');
                const containerCampos = document.getElementById('container-campos');
                const containerExclusao = document.getElementById('container-exclusao');
                let contadorCampos = 0; 

                function adicionarCampo(campoData = null) {
                    const isNew = campoData === null;
                    const campoIndex = isNew ? `new_${contadorCampos++}` : campoData.ID_Campo;

                    const campoHtml = `
                        <div class="card card-body mb-3 campo-personalizado" data-id-campo="${campoData?.ID_Campo || ''}">
                            <input type="hidden" name="campos[${campoIndex}][id_campo]" value="${campoData?.ID_Campo || ''}">
                            <div class="text-end">
                                <button type="button" class="btn-close" onclick="removerCampo(this, ${campoData?.ID_Campo || null})"></button>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Título do Campo</label>
                                    <input type="text" name="campos[${campoIndex}][label]" class="form-control" value="${campoData?.Label_Campo || ''}" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Tipo de Campo</label>
                                    <select name="campos[${campoIndex}][tipo]" class="form-select">
                                        <option value="text" ${campoData?.Tipo_Campo === 'text' ? 'selected' : ''}>Texto</option>
                                        <option value="number" ${campoData?.Tipo_Campo === 'number' ? 'selected' : ''}>Número</option>
                                        <option value="boolean" ${campoData?.Tipo_Campo === 'boolean' ? 'selected' : ''}>Sim/Não</option>
                                        <option value="date" ${campoData?.Tipo_Campo === 'date' ? 'selected' : ''}>Data</option>
                                        <option value="ean" ${campoData?.Tipo_Campo === 'ean' ? 'selected' : ''}>Código/EAN do Produto</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Unidade de Medida (Opcional)</label>
                                    <input type="text" name="campos[${campoIndex}][unidade]" class="form-control" value="${campoData?.Unidade_Medida || ''}" placeholder="Ex: mmHg, BPM, mg/dL">
                                </div>
                            </div>
                            <hr>
                            <h6>Valores de Referência (Opcional)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead><tr><th>Descrição</th><th>Ref. Feminino</th><th>Ref. Masculino</th><th></th></tr></thead>
                                    <tbody class="tabela-referencias">
                                        ${(campoData?.referencias || []).map(ref => adicionarLinhaReferencia(campoIndex, ref, true)).join('')}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4">
                                                <button type="button" class="btn btn-sm btn-outline-success w-100 btn-add-referencia"><i class="bi bi-plus-circle"></i> Adicionar Linha de Referência</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>`;
                    containerCampos.insertAdjacentHTML('beforeend', campoHtml);
                }
                
                function adicionarLinhaReferencia(campoIndex, refData = null, returnHtml = true) {
                    const isNew = refData === null;
                    const refIndex = isNew ? `new_${Date.now()}` : refData.ID_Referencia;
                    
                    const linhaHtml = `
                        <tr data-id-ref="${refData?.ID_Referencia || ''}">
                            <input type="hidden" name="campos[${campoIndex}][referencias][${refIndex}][id_referencia]" value="${refData?.ID_Referencia || ''}">
                            <td><input type="text" name="campos[${campoIndex}][referencias][${refIndex}][descricao]" class="form-control form-control-sm" value="${refData?.Descricao_Referencia || ''}" placeholder="Ex: Adulto (18-59 anos)"></td>
                            <td><input type="text" name="campos[${campoIndex}][referencias][${refIndex}][fem]" class="form-control form-control-sm" value="${refData?.Valor_Feminino || ''}"></td>
                            <td><input type="text" name="campos[${campoIndex}][referencias][${refIndex}][masc]" class="form-control form-control-sm" value="${refData?.Valor_Masculino || ''}"></td>
                            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-danger" onclick="removerReferencia(this, ${refData?.ID_Referencia || null})"><i class="bi bi-dash-circle"></i></button></td>
                        </tr>`;

                    if (returnHtml)
                       return linhaHtml;
                    
                    const tbody = document.querySelector(`input[name="campos[${campoIndex}][id_campo]"]`).closest('.campo-personalizado').querySelector('.tabela-referencias');
                    tbody.insertAdjacentHTML('beforeend', linhaHtml);
                }

                if (camposExistentes) 
                    camposExistentes.forEach(campo => adicionarCampo(campo));

                document.getElementById('btnAddCampo').addEventListener('click', function() {
                    adicionarCampo();
                    atualizarContadorJSON();
                });

                containerCampos.addEventListener('click', function(event) {
                    if (event.target && event.target.matches('.btn-add-referencia')) {
                        const campoPersonalizado = event.target.closest('.campo-personalizado');
                        const campoInput = campoPersonalizado.querySelector('input[name^="campos["]');
                        const campoIndex = campoInput.name.match(/\[(.*?)\]/)[1];
                        adicionarLinhaReferencia(campoIndex, null, false);
                    }

                    if (event.target.closest('.btn-close') || event.target.closest('.btn-danger'))
                        setTimeout(atualizarContadorJSON, 100);
                });

                window.removerCampo = function(element, idCampo) {
                    if (idCampo) {
                        const inputExcluido = `<input type="hidden" name="delete_campos[]" value="${idCampo}">`;
                        containerExclusao.insertAdjacentHTML('beforeend', inputExcluido);
                    }
                    element.closest('.campo-personalizado').remove();
                }

                window.removerReferencia = function(element, idReferencia) {
                    if (idReferencia) {
                        const inputExcluido = `<input type="hidden" name="delete_referencias[]" value="${idReferencia}">`;
                        containerExclusao.insertAdjacentHTML('beforeend', inputExcluido);
                    }
                    element.closest('tr').remove();
                }

                const form = document.querySelector('form');
                const valorFormatadoInput = document.getElementById('valor_formatado');
                const valorRealInput = document.getElementById('valor_real');

                function formatarMoeda(value) {
                    let apenasDigitos = value.replace(/\D/g, '');

                    if (apenasDigitos === '') 
                        return '';

                    apenasDigitos = apenasDigitos.padStart(3, '0');

                    let centavos = apenasDigitos.slice(-2);
                    let inteiros = apenasDigitos.slice(0, -2);
                    
                    inteiros = parseInt(inteiros, 10).toString();

                    inteiros = inteiros.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                    return inteiros + ',' + centavos;
                }

                valorFormatadoInput.addEventListener('input', function(e) {
                    e.target.value = formatarMoeda(e.target.value);
                });

                form.addEventListener('submit', function() {
                    let valorFormatado = valorFormatadoInput.value;
                    
                    if (valorFormatado) {
                        let valorParaBanco = valorFormatado.replace(/\./g, '').replace(',', '.');
                        valorRealInput.value = valorParaBanco;
                    }
                });

                function atualizarContadorJSON() {
                    let formObject = [];
                    const campos = document.querySelectorAll('.campo-personalizado');

                    campos.forEach(campoCard => {
                        const labelInput = campoCard.querySelector('input[name*="[label]"]');
                        const tipoSelect = campoCard.querySelector('select[name*="[tipo]"]');
                        const unidadeInput = campoCard.querySelector('input[name*="[unidade]"]');
                        
                        let campoObj = {
                            label: labelInput ? labelInput.value : '',
                            tipo: tipoSelect ? tipoSelect.value : '',
                            unidade: unidadeInput ? unidadeInput.value : '',
                            referencias: []
                        };
                        
                        const refs = campoCard.querySelectorAll('.tabela-referencias tr');
                        refs.forEach(refRow => {
                            const descInput = refRow.querySelector('input[name*="[descricao]"]');
                            const femInput = refRow.querySelector('input[name*="[fem]"]');
                            const mascInput = refRow.querySelector('input[name*="[masc]"]');
                            campoObj.referencias.push({
                                descricao: descInput ? descInput.value : '',
                                fem: femInput ? femInput.value : '',
                                masc: mascInput ? mascInput.value : ''
                            });
                        });
                        formObject.push(campoObj);
                    });

                    const jsonString = JSON.stringify(formObject);
                    const currentSize = jsonString.length;

                    jsonSizeCounter.textContent = `Tamanho Estimado: ${currentSize} / ${LIMITE_JSON} caracteres`;

                    if (currentSize > LIMITE_JSON) {
                        jsonSizeCounter.className = 'text-danger fw-bold';
                        btnAddCampo.disabled = true; 
                    } 
                    else if (currentSize > LIMITE_JSON * 0.8) { // Acima de 80% do limite
                        jsonSizeCounter.className = 'text-warning fw-bold';
                        btnAddCampo.disabled = false;
                    } 
                    else {
                        jsonSizeCounter.className = 'text-muted fw-bold';
                        btnAddCampo.disabled = false;
                    }
                }

                containerCampos.addEventListener('input', function(event) {
                    if (event.target.tagName === 'INPUT')
                        atualizarContadorJSON();
                });

                atualizarContadorJSON();
            });

            <?php
            if (isset($_SESSION['msg']) && is_array($_SESSION['msg'])) {
                $texto = addslashes($_SESSION['msg']['texto']);
                $tipo = $_SESSION['msg']['tipo'];
                echo "mostrarToast('{$texto}', '{$tipo}');";
                unset($_SESSION['msg']);
            }
            ?>
        </script>
    </body>
</html>