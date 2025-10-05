<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_servico = $_POST['nome_servico'];
    $valor = $_POST['valor'];
    $descricao = $_POST['descricao'];
    $dados_referencia = $_POST['dados_referencia'];
    $status = $_POST['status'];

    $sql = "INSERT INTO SERVICOS_FARMACEUTICOS (Nome_Servico, Valor, Descricao, Dados_Referencia, Status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsss", $nome_servico, $valor, $descricao, $dados_referencia, $status);

    if ($stmt->execute()) {
        $id_servico = $conn->insert_id;
        registrar_log($conn, $_SESSION['ID_Usuario'], "Cadastrou o serviço '{$nome_servico}' (ID: {$id_servico})");
        $_SESSION['msg'] = ['texto' => 'Serviço cadastrado com sucesso!', 'tipo' => 'success'];
        header("Location: servicos_lista.php");
        exit();
    } 
    else 
        $_SESSION['msg'] = ['texto' => 'Erro ao cadastrar serviço: ' . $stmt->error, 'tipo' => 'danger'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cadastro de Serviço</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

       <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Cadastro de Serviço</h3>
                </div>
            
                <div class="container p-5">
                    <form action="processa_servico_definicao.php" method="POST">
                        <div class="card card-body mb-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nome do Serviço</label>
                                    <input type="text" name="nome_servico" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Valor (R$)</label>
                                    <input type="text" id="valor_formatado" class="form-control" placeholder="0,00" required>
                                    <input type="hidden" name="valor" id="valor_real">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="Ativo">Ativo</option>
                                        <option value="Inativo">Inativo</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Descrição</label>
                                    <textarea name="descricao" class="form-control" rows="2"></textarea>
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

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">Salvar Definições do Serviço</button>
                            <a href="servicos.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <!-- Toast -->
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                <strong class="me-auto" id="toastTitulo">Notificação</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="toastCorpo">
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const LIMITE_JSON = 4000; // limite de segurança
                const jsonSizeCounter = document.getElementById('json-size-counter');
                const btnAddCampo = document.getElementById('btnAddCampo');
                const containerCampos = document.getElementById('container-campos');
                let contadorCampos = 0;

                btnAddCampo.addEventListener('click', function() {
                    contadorCampos++;
                    const novoCampoHtml = `
                        <div class="card card-body mb-3 campo-personalizado">
                            <div class="text-end">
                                <button type="button" class="btn-close" onclick="this.closest('.campo-personalizado').remove()"></button>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Título do Campo</label>
                                    <input type="text" name="campos[${contadorCampos}][label]" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Tipo de Campo</label>
                                    <select name="campos[${contadorCampos}][tipo]" class="form-select">
                                        <option value="text">Texto</option>
                                        <option value="number">Número</option>
                                        <option value="boolean">Sim/Não</option>
                                        <option value="date">Data</option>
                                        <option value="ean">Código/EAN do Produto</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Unidade de Medida (Opcional)</label>
                                    <input type="text" name="campos[${contadorCampos}][unidade]" class="form-control" placeholder="Ex: mmHg, BPM, mg/dL">
                                </div>
                            </div>
                            <hr>
                            <h6>Valores de Referência (Opcional)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead><tr><th>Descrição</th><th>Ref. Feminino</th><th>Ref. Masculino</th></tr></thead>
                                    <tbody class="tabela-referencias">
                                        <tr>
                                            <td><input type="text" name="campos[${contadorCampos}][referencias][0][descricao]" class="form-control form-control-sm" placeholder="Ex: Adulto (18-59 anos)"></td>
                                            <td><input type="text" name="campos[${contadorCampos}][referencias][0][fem]" class="form-control form-control-sm"></td>
                                            <td><input type="text" name="campos[${contadorCampos}][referencias][0][masc]" class="form-control form-control-sm"></td>
                                            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="bi bi-dash-circle"></i></button></td>
                                        </tr>
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
                        </div>
                    `;
                    containerCampos.insertAdjacentHTML('beforeend', novoCampoHtml);
                    atualizarContadorJSON();
                });

                containerCampos.addEventListener('click', function(event) {
                    if (event.target && event.target.classList.contains('btn-add-referencia')) {
                        const tbody = event.target.closest('table').querySelector('.tabela-referencias');
                        const cardPai = event.target.closest('.campo-personalizado');
                        
                        const primeiroInputName = cardPai.querySelector('input[name^="campos["]').name;
                        const campoIndex = primeiroInputName.match(/\[(\d+)\]/)[1];
                        
                        const novaLinhaIndex = tbody.rows.length;

                        const novaLinhaHtml = `
                            <tr>
                                <td><input type="text" name="campos[${campoIndex}][referencias][${novaLinhaIndex}][descricao]" class="form-control form-control-sm"></td>
                                <td><input type="text" name="campos[${campoIndex}][referencias][${novaLinhaIndex}][fem]" class="form-control form-control-sm"></td>
                                <td><input type="text" name="campos[${campoIndex}][referencias][${novaLinhaIndex}][masc]" class="form-control form-control-sm"></td>
                                <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()"><i class="bi bi-dash-circle"></i></button></td>
                            </tr>
                        `;
                        tbody.insertAdjacentHTML('beforeend', novaLinhaHtml);
                    }

                    if (event.target.closest('.btn-close') || event.target.closest('.btn-danger')) 
                        setTimeout(atualizarContadorJSON, 100);
                });

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