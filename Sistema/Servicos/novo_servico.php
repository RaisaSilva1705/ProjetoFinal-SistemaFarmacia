<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'SERVICOS_GERENCIAR');
include DEV_PATH . "Exec/validar_acesso.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Serviços Farmacêuticos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

       <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Novo Serviço Farmacêutico</h3>
                </div>
            
                <div class="container p-5">
                    <div class="card card-body mb-4">
                        <h4 class="card-title">Registrar Novo Serviço</h4>
                        <form action="processa_servico.php" method="POST">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="busca_cliente_cpf" class="form-label">Buscar Cliente por CPF/CNPJ</label>
                                    <input type="text" id="busca_cliente_cpf" name="busca_cliente_cpf" class="form-control" required>
                                    <input type="hidden" id="id_cliente" name="id_cliente">
                                    <input type="hidden" id="cpf_paciente" name="cpf_paciente">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nome_paciente" class="form-label">Nome do Paciente</label>
                                    <input type="text" id="nome_paciente" name="nome_paciente" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="nascimento_paciente" class="form-label">Data de Nascimento</label>
                                    <input type="date" id="nascimento_paciente" name="nascimento_paciente" class="form-control">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Sexo</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="sexo_paciente" id="sexo_masc" value="Masculino">
                                            <label class="form-check-label" for="sexo_masc">Masculino</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="sexo_paciente" id="sexo_fem" value="Feminino">
                                            <label class="form-check-label" for="sexo_fem">Feminino</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="id_servico" class="form-label">Selecionar o Serviço</label>
                                    <select name="id_servico" id="id_servico" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <?php
                                        $servicos_result = $conn->query("SELECT ID_Servico, Nome_Servico FROM SERVICOS_FARMACEUTICOS WHERE Status = 'Ativo' ORDER BY Nome_Servico");
                                        while ($serv = $servicos_result->fetch_assoc()) {
                                            echo "<option value='{$serv['ID_Servico']}' data-id-servico='{$serv['ID_Servico']}'>{$serv['Nome_Servico']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3 d-none" id="nome_responsavel_container">
                                    <label for="nome_responsavel" class="form-label text-danger">Nome do Responsável Legal:</label>
                                    <input type="text" id="nome_responsavel" name="dados_servico[nome_responsavel]" class="form-control">
                                </div>
                                <div class="col-md-3 mb-3 d-none" id="doc_responsavel_container">
                                    <label for="doc_responsavel" class="form-label text-danger">Documento do Responsável:</label>
                                    <input type="text" id="doc_responsavel" name="dados_servico[doc_responsavel]" class="form-control">
                                </div>
                            </div>

                            <div id="campos_dinamicos_container" class="row mt-3">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="autoriza_uso_dados" class="form-label">Autoriza o uso das informações para acompanhamento do histórico?</label>
                                    <select name="dados_servico[autoriza_uso_dados]" id="autoriza_uso_dados" class="form-select">
                                        <option value="Sim" selected>Sim</option>
                                        <option value="Nao">Não</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="encaminhado_medico" class="form-label">Paciente foi encaminhado ao médico?</label>
                                    <select name="dados_servico[encaminhado_medico]" id="encaminhado_medico" class="form-select">
                                        <option value="Nao" selected>Não</option>
                                        <option value="Sim">Sim</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="obs" class="form-label">Observações</label>
                                    <textarea name="obs" id="obs" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">Salvar Registro</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>

        <?php include_once DEV_PATH . 'Views/toast.php'?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const campoBuscaCliente = document.getElementById('busca_cliente_cpf');
                const campoNomeCliente = document.getElementById('nome_paciente');
                const campoIdCliente = document.getElementById('id_cliente');
                const campoCPFPaciente = document.getElementById('cpf_paciente');
                const campoDataNascimento = document.getElementById('nascimento_paciente');
                const responsavelNomeContainer = document.getElementById('nome_responsavel_container');
                const responsavelDocContainer = document.getElementById('doc_responsavel_container');
                const responsavelNomeInput = document.getElementById('nome_responsavel');
                const responsavelDocInput = document.getElementById('doc_responsavel');
                const radiosSexo = document.querySelectorAll('input[name="sexo_paciente"]');
                const selectServico = document.getElementById('id_servico');
                const buscaMedicamento = document.getElementById('busca_medicamento');
                const idProdutoInjetavel = document.getElementById('injetavel_id_produto');
                const selectLoteInjetavel = document.getElementById('injetavel_id_lote');

                campoBuscaCliente.addEventListener('change', function() { 
                    const documento = this.value.trim();

                    if (documento === '') {
                        campoIdCliente.value = '';
                        campoNomeCliente.value = '';
                        campoNomeCliente.readOnly = false;
                        campoDataNascimento.value = '';
                        campoDataNascimento.readOnly = false;
                        radiosSexo.forEach(radio => radio.checked = false);
                        radiosSexo.forEach(radio => radio.disabled = false);
                        return;
                    }

                    // BUSCA DE CLIENTE POR CPF/CNPJ
                    fetch(`../../Dev/Exec/busca_cliente.php?documento=${encodeURIComponent(documento)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.sucesso) {
                                campoIdCliente.value = data.id_cliente;
                                campoCPFPaciente.value = documento;
                                campoNomeCliente.value = data.nome_cliente;
                                //campoNomeCliente.readOnly = true;
                                campoDataNascimento.value = data.data_nascimento;
                                //campoDataNascimento.readOnly = true;
                                checkAgeAndToggleResponsibleField(data.data_nascimento);
                                if (data.sexo) 
                                    document.querySelector(`input[name="sexo_paciente"][value="${data.sexo}"]`).checked = true;
                                //radiosSexo.forEach(radio => radio.disabled = true);
                                mostrarToast('Cliente encontrado e selecionado!', 'success');
                            }
                            else {
                                campoIdCliente.value = 0;
                                campoNomeCliente.value = '';
                                campoNomeCliente.readOnly = false;
                                campoCPFPaciente.value = documento;
                                checkAgeAndToggleResponsibleField(null);
                                mostrarToast('Cliente não cadastrado. Preencha o nome manualmente.', 'info');
                            }
                        })
                        .catch(err => {
                            console.error('Erro ao buscar cliente:', err);
                            mostrarToast('Erro de comunicação ao buscar cliente.', 'danger');
                        });
                });

                selectServico.addEventListener('change', function() {
                    const container = document.getElementById('campos_dinamicos_container');
                    const selectedOption = this.options[this.selectedIndex];
                    const servicoId = selectedOption.value;

                    container.innerHTML = '<div class="col-12"><p class="text-muted">Selecione um serviço para ver os campos.</p></div>';

                    if (!servicoId) 
                        return; 

                    container.innerHTML = '<div class="col-12"><p class="text-info">Carregando campos...</p></div>';

                    fetch(`../../Dev/Exec/busca_campos_servico.php?id_servico=${servicoId}`)
                        .then(response => response.json())
                        .then(campos => {
                            container.innerHTML = '';

                            if (campos.erro) {
                                container.innerHTML = `<div class="col-12"><p class="text-danger">${campos.erro}</p></div>`;
                                return;
                            }

                            if (campos.length === 0) {
                                container.innerHTML = '<div class="col-12"><p class="text-muted">Este serviço não possui campos personalizados.</p></div>';
                                return;
                            }

                            campos.forEach(campo => {
                                let inputHtml = '';

                                if (campo.Tipo_Campo === 'boolean') {
                                    inputHtml = `
                                        <select id="${campo.Name_Campo}" name="dados_servico[${campo.Name_Campo}]" class="form-select" required>
                                            <option value="Sim">Sim</option>
                                            <option value="Nao">Não</option>
                                        </select>
                                    `;
                                } 
                                else if (campo.Tipo_Campo === 'date') {
                                    inputHtml = `
                                        <input type="date" id="${campo.Name_Campo}" name="dados_servico[${campo.Name_Campo}]" class="form-control">
                                    `;
                                }
                                else if (campo.Tipo_Campo === 'ean') {
                                    inputHtml = `
                                        <div class="input-group">
                                            <input type="text" id="${campo.Name_Campo}" name="dados_servico[${campo.Name_Campo}]" class="form-control ean-input" required>
                                        </div>
                                        <div class="form-text" id="feedback_${campo.Name_Campo}"></div>
                                    `;
                                }
                                else if (campo.Tipo_Campo === 'number') {
                                    inputHtml = `
                                        <div class="input-group">
                                            <input type="${campo.Tipo_Campo}" id="${campo.Name_Campo}" name="dados_servico[${campo.Name_Campo}]" class="form-control" step="0.01" required>
                                            ${campo.Unidade_Medida ? `<span class="input-group-text">${campo.Unidade_Medida}</span>` : ''}
                                        </div>
                                    `;
                                }
                                else {
                                    inputHtml = `
                                        <div class="input-group">
                                            <input type="${campo.Tipo_Campo}" id="${campo.Name_Campo}" name="dados_servico[${campo.Name_Campo}]" class="form-control" step="0.01" required>
                                            ${campo.Unidade_Medida ? `<span class="input-group-text">${campo.Unidade_Medida}</span>` : ''}
                                        </div>
                                    `;
                                }

                                let campoHtml = `
                                    <div class="col-md-4 mb-3">
                                        <label for="${campo.Name_Campo}" class="form-label">${campo.Label_Campo}</label>
                                        ${inputHtml}
                                    </div>
                                `;
                                container.insertAdjacentHTML('beforeend', campoHtml);
                            });
                        })
                        .catch(error => {
                            console.error('Erro ao buscar campos:', error);
                            container.innerHTML = '<div class="col-12"><p class="text-danger">Não foi possível carregar os campos do serviço. Tente novamente.</p></div>';
                        });
                });

                const containerDinamico = document.getElementById('campos_dinamicos_container');
                containerDinamico.addEventListener('change', function(event) {
                    if (event.target.classList.contains('ean-input')) {
                        const eanInput = event.target;
                        const ean = eanInput.value.trim();
                        const feedbackDiv = document.getElementById(`feedback_${eanInput.id}`);

                        if (ean.length > 0) {
                            fetch(`../../Dev/Exec/busca_produto.php?codigo=${ean}`)
                                .then(response => response.json())
                                .then(data => {
                                    if(data.success) {
                                        feedbackDiv.className = 'form-text text-success';
                                        feedbackDiv.textContent = `Produto encontrado: ${data.nome}`;
                                        eanInput.classList.remove('is-invalid');
                                    } 
                                    else {
                                        mostrarToast('Produto não encontrado ou inativo.', 'warning');
                                        feedbackDiv.className = 'form-text text-danger';
                                        feedbackDiv.textContent = 'Produto não encontrado!';
                                        eanInput.classList.add('is-invalid');
                                    }
                                })
                                .catch(error => {
                                    console.error('Erro ao buscar produto por EAN:', error);
                                    mostrarToast('Erro de comunicação ao buscar produto.', 'danger');
                                });
                        } 
                        else {
                            feedbackDiv.textContent = '';
                            eanInput.classList.remove('is-invalid');
                        }
                    }
                });

                function checkAgeAndToggleResponsibleField(dataNascimentoStr) {
                    if (!dataNascimentoStr) {
                        responsavelNomeContainer.classList.add('d-none');
                        responsavelDocContainer.classList.add('d-none');
                        responsavelNomeInput.required = false;
                        responsavelDocInput.required = false;
                        return;
                    }

                    const dataNascimento = new Date(dataNascimentoStr);
                    const hoje = new Date();
                    let idade = hoje.getFullYear() - dataNascimento.getFullYear();
                    const m = hoje.getMonth() - dataNascimento.getMonth();

                    if (m < 0 || (m === 0 && hoje.getDate() < dataNascimento.getDate())) 
                        idade--;

                    if (idade < 18) {
                        responsavelNomeContainer.classList.remove('d-none'); // Mostra o campo
                        responsavelDocContainer.classList.remove('d-none');
                        responsavelNomeInput.required = true;
                        responsavelDocInput.required = true;
                    } 
                    else {
                        responsavelNomeContainer.classList.add('d-none'); // Oculta o campo
                        responsavelDocContainer.classList.add('d-none');
                        responsavelNomeInput.required = false;
                        responsavelNomeInput.value = '';
                        responsavelDocInput.required = false;
                        responsavelDocInput.value = ''; 
                    }
                }

                campoDataNascimento.addEventListener('change', function() {
                    checkAgeAndToggleResponsibleField(this.value);
                });
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