<?php
session_start();

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/logs.php";
include DEV_PATH . "Exec/validar_sessao.php";
define('MODULO_SOLICITADO', 'CONTROLADOS_GERENCIAR'); 
include DEV_PATH . "Exec/validar_acesso.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dispensação de Medicamento Controlado</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <?php include_once DEV_PATH . 'Views/sidebar.php'; ?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Dispensação de Medicamento Controlado</h3>
                </div>
                
                <div class="container p-5">
                    <form id="formDispensacao" action="processa_dispensacao.php" method="POST">
                    
                        <div class="card card-body mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                            <h5>1. Paciente e Comprador</h5>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="comprador_eh_paciente" name="comprador_eh_paciente" checked>
                                <label class="form-check-label" for="comprador_eh_paciente">Paciente é o Comprador</label>
                            </div>
                            </div>
                            <hr>
                            <div id="dados_paciente_container">
                                <h6>Dados do Paciente</h6>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="busca_cliente_cpf" class="form-label">Buscar Paciente por CPF</label>
                                        <input type="text" id="busca_cliente_cpf" name="busca_cliente_cpf" class="form-control" required>
                                        <input type="hidden" id="id_cliente_paciente" name="id_cliente_paciente">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nome do Paciente</label>
                                        <input type="text" id="nome_paciente" name="nome_paciente" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="tel_paciente" class="form-label">Telefone</label>
                                        <input type="text" id="tel_paciente" name="tel_paciente" class="form-control">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="cep_paciente" class="form-label">CEP</label>
                                        <input type="text" id="cep_paciente" name="cep_paciente" class="form-control cep-input" required>
                                    </div>
                                    <div class="col-md-7 mb-3">
                                        <label for="endereco_paciente" class="form-label">Endereço</label>
                                        <input type="text" id="endereco_paciente" name="endereco_paciente" class="form-control" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="numero_paciente" class="form-label">Número</label>
                                        <input type="text" id="numero_paciente" name="numero_paciente" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="bairro_paciente" class="form-label">Bairro</label>
                                        <input type="text" id="bairro_paciente" name="bairro_paciente" class="form-control" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="cidade_paciente" class="form-label">Cidade</label>
                                        <input type="text" id="cidade_paciente" name="cidade_paciente" class="form-control" required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="estado_paciente" class="form-label">UF</label>
                                        <input type="text" id="estado_paciente" name="estado_paciente" class="form-control" required maxlength="2">
                                    </div>
                                </div>
                            </div>
                            <div id="dados_comprador_container" class="d-none mt-4">
                                <hr>
                                <h6>Dados do Comprador</h6>
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="busca_comprador_cpf" class="form-label">Buscar Comprador por CPF</label>
                                        <input type="text" id="busca_comprador_cpf" name="busca_comprador_cpf" class="form-control">
                                        <input type="hidden" id="id_cliente_comprador" name="id_cliente_comprador">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nome_comprador" class="form-label">Nome Completo</label>
                                        <input type="text" id="nome_comprador" name="nome_comprador" class="form-control">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="tel_comprador" class="form-label">Telefone</label>
                                        <input type="text" id="tel_comprador" name="tel_comprador" class="form-control">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="cep_comprador" class="form-label">CEP</label>
                                        <input type="text" id="cep_comprador" name="cep_comprador" class="form-control cep-input">
                                    </div>
                                    <div class="col-md-7 mb-3">
                                        <label for="endereco_comprador" class="form-label">Endereço</label>
                                        <input type="text" id="endereco_comprador" name="endereco_comprador" class="form-control">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="numero_comprador" class="form-label">Número</label>
                                        <input type="text" id="numero_comprador" name="numero_comprador" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="bairro_comprador" class="form-label">Bairro</label>
                                        <input type="text" id="bairro_comprador" name="bairro_comprador" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="cidade_comprador" class="form-label">Cidade</label>
                                        <input type="text" id="cidade_comprador" name="cidade_comprador" class="form-control">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="estado_comprador" class="form-label">UF</label>
                                        <input type="text" id="estado_comprador" name="estado_comprador" class="form-control" maxlength="2">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-body mb-4">
                            <h5>2. Dados da Prescrição</h5>
                            <hr>
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="paciente_nome_receita" class="form-label">Nome do Paciente</label>
                                    <input type="text" id="paciente_nome_receita" name="paciente_nome_receita" class="form-control" readonly tabindex="-1">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="paciente_dn_receita" class="form-label">Data de Nasc.</label>
                                    <input type="date" id="paciente_dn_receita" name="paciente_dn_receita" class="form-control">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="paciente_sexo_receita" class="form-label">Sexo</label>
                                    <select id="paciente_sexo_receita" name="paciente_sexo_receita" class="form-select">
                                        <option value="">Selecione...</option> 
                                        <option value="Masculino">Masculino</option>
                                        <option value="Feminino">Feminino</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="num_receita" class="form-label">Numeração da Receita</label>
                                    <input type="text" id="num_receita" name="num_receita" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="tipo_receita" class="form-label">Tipo Receita</label>
                                    <select id="tipo_receita" name="tipo_receita" class="form-select" required>
                                        <option value="">Selecione...</option>
                                        <option value="A1/A2/A3">Notificação "A" (Amarela)</option>
                                        <option value="B1/B2">Notificação "B" (Azul)</option>
                                        <option value="C2">Notificação "C2" (Branca)</option>
                                        <option value="Especial">Receita Especial (Controle C1/C5)</option>
                                        <option value="Antimicrobiano">Receita Antimicrobiana (2 vias)</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="data_receita" class="form-label">Data de Emissão da Receita</label>
                                    <input type="date" id="data_receita" name="data_receita" class="form-control" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label for="nome_profissional" class="form-label">Nome do Profissional Prescritor</label>
                                    <input type="text" id="nome_profissional" name="nome_profissional" class="form-control" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="conselho" class="form-label">Conselho</label>
                                    <input type="text" id="conselho" name="conselho" class="form-control" required>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="num_conselho" class="form-label">Nº do Registro</label>
                                    <input type="text" id="num_conselho" name="num_conselho" class="form-control" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="uf_conselho" class="form-label">UF</label>
                                    <input type="text" id="uf_conselho" name="uf_conselho" class="form-control" maxlength="2" required>
                                </div>
                            </div>
                            <div class="row d-flex align-items-center">
                                <div class="col-md-2 mb-3 d-flex justify-content-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="receita_digital" name="receita_digital" value="0">
                                        <label class="form-check-label" for="receita_digital">Receita Digital</label>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="dispensador" class="form-label">Dispensador</label>
                                    <input type="text" id="dispensador" name="dispensador" class="form-control" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="card card-body">
                            <h5>3. Medicamentos da Receita</h5>
                            <div id="search-results-container" class="mt-2"></div>
                            <hr>
                            <div class="row align-items-center">
                                <div class="col-md-4 mb-3">
                                    <label for="busca_medicamento_controlado" class="form-label">Buscar Medicamento Controlado (Nome ou EAN)</label>
                                    <input type="text" id="busca_medicamento_controlado" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="lote_medicamento" class="form-label">Lote</label>
                                    <select id="lote_medicamento" name="lote_medicamento" class="form-select" required>
                                        <option value="">Selecione...</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="quant_estoque" class="form-label">Quant. Estoque</label>
                                    <input type="text" id="quant_estoque" name="quant_estoque" class="form-control" value="" readonly>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="validade_medicamento" class="form-label">Validade</label>
                                    <input type="date" id="validade_medicamento" name="validade_medicamento" class="form-control" readonly>
                                </div>
                                <div class="col-md-3 mb-3 d-flex justify-content-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="antibiotico_continuo" name="antibiotico_continuo" value="0">
                                        <label class="form-check-label" for="antibiotico_continuo">Antibiótico de Uso Contínuo</label>
                                    </div>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="ms_medicamento" class="form-label">MS</label>
                                    <input type="text" id="ms_medicamento" name="ms_medicamento" class="form-control" value="" readonly>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="quant_medicamento" class="form-label">Quant.</label>
                                    <div class="input-group">
                                        <button class="btn btn-outline-secondary" type="button" id="btn_diminuir_quant" disabled><i class="bi bi-dash-lg"></i></button>
                                        <input type="text" id="quant_medicamento" name="quant_medicamento" class="form-control" value="1" required>
                                        <button class="btn btn-outline-secondary" type="button" id="btn_aumentar_quant"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-1"></div>
                                <div class="col-md-2 mt-3">
                                    <button type="button" id="btn_adicionar_medicamento" class="btn btn-success w-100">Adicionar</button>
                                </div>
                            </div>
                            <hr>
                            <h5>Medicamentos a serem Dispensados:</h5>
                            <div id="itens_dispensacao_container">
                                <p class="text-muted">Nenhum medicamento adicionado.</p>
                            </div>
                        </div>
                        
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-shield-check"></i> Salvar Prescrição e Gerar Pré-Venda</button>
                        </div>
                    </form>
                </div>
                <?php include_once DEV_PATH . 'Views/footer.php'; ?>
            </div>
        </div>

        <div id="manual-content-container" style="display: none;">
            <h4><i class="bi bi-shield-check"></i> Dispensação de Medicamento Controlado</h4>
            <hr>
            <p>Esta é uma das telas mais importantes e reguladas do sistema. Ela foi projetada para garantir um registro seguro e completo de toda a venda (dispensação) de medicamentos de controle especial, seguindo as normas da vigilância sanitária.</p>
            <p>O processo é dividido em 3 etapas e, ao final, gera uma <strong>Pré-Venda</strong> que deve ser levada ao caixa para o pagamento.</p>

            <h6><i class="bi bi-people-fill"></i> Passo 1: Paciente e Comprador</h6>
            <p>Nesta seção, você identifica as pessoas envolvidas na retirada do medicamento.</p>
            <ul>
                <li><strong>Buscar Paciente por CPF:</strong> Se o paciente já for um cliente cadastrado, digite seu CPF para preencher automaticamente seus dados. Se não for, preencha manualmente.</li>
                <li><strong>Paciente é o Comprador:</strong> Marque esta opção se a pessoa que está comprando é o próprio paciente. Se for um terceiro (cuidador, familiar), desmarque a opção para habilitar e preencher os dados do comprador.</li>
            </ul>

            <h6><i class="bi bi-file-earmark-medical-fill"></i> Passo 2: Dados da Prescrição</h6>
            <p>Transcreva cuidadosamente todas as informações da receita médica para o sistema:</p>
            <ul>
                <li><strong>Dados do Paciente na Receita:</strong> Confirme o nome, data de nascimento e sexo conforme escritos na receita.</li>
                <li><strong>Dados da Receita:</strong> Preencha o número da receita, o tipo (Notificação Amarela, Azul, Receita Especial, etc.) e a data em que ela foi emitida pelo médico.</li>
                <li><strong>Dados do Prescritor:</strong> Insira o nome completo do profissional de saúde, seu conselho (CRM, CRO, etc.), o número do registro e a UF.</li>
            </ul>
            <p class="alert alert-danger"><strong>Validade da Receita:</strong> O sistema calcula automaticamente a validade da receita com base no tipo e na data de emissão. Se a receita estiver vencida, o sistema emitirá um alerta e não permitirá prosseguir.</p>

            <h6><i class="bi bi-capsule-pill"></i> Passo 3: Medicamentos da Receita</h6>
            <ol>
                <li>Use o campo <strong>"Buscar Medicamento Controlado"</strong> para encontrar o item prescrito.</li>
                <li>Selecione o <strong>Lote</strong> que será dispensado. O sistema mostrará o estoque e a validade de cada lote disponível.</li>
                <li>Informe a <strong>Quantidade</strong> de caixas/unidades que serão vendidas.</li>
                <li>Clique em <strong>"Adicionar"</strong> para incluir o medicamento na lista de dispensação. Repita o processo para todos os medicamentos da receita.</li>
            </ol>

            <h6><i class="bi bi-receipt"></i> Passo 4: Gerar Pré-Venda</h6>
            <p>Após adicionar todos os medicamentos, clique no botão <strong>"Salvar Prescrição e Gerar Pré-Venda"</strong>. O sistema salvará todos os dados para fins de auditoria, gerará um código de barras e te redirecionará para a tela de pré-venda, onde você poderá imprimir o cupom para o cliente levar ao caixa e finalizar o pagamento.</p>
        </div>
        
        <?php include_once DEV_PATH . 'Views/toast.php'; ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="<?= DEV_URL ?>JS/toast.js"></script>
        <script src="<?= DEV_URL ?>JS/manual_usuario.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // --- ESTADO DA APLICAÇÃO ---
                let dispensacaoItens = [];
                let produtoSelecionado = null; 

                // --- ELEMENTOS DO FORMULÁRIO ---
                const compradorCheckbox = document.getElementById('comprador_eh_paciente');
                const compradorContainer = document.getElementById('dados_comprador_container');
                const buscaClienteInput = document.getElementById('busca_cliente_cpf');
                const buscaCompradorInput = document.getElementById('busca_comprador_cpf');
                const nomePacienteInput = document.getElementById('nome_paciente');
                const nomePacienteReceitaInput = document.getElementById('paciente_nome_receita');
                const checkReceitaDigital = document.getElementById('receita_digital');
                const inputDispensadorDigital = document.getElementById('dispensador');
                const buscaMedicamentoInput = document.getElementById('busca_medicamento_controlado');
                const searchResultsContainer = document.getElementById('search-results-container');
                const selectLote = document.getElementById('lote_medicamento');
                const inputQuantEstoque = document.getElementById('quant_estoque');
                const inputValidade = document.getElementById('validade_medicamento');
                const inputMS = document.getElementById('ms_medicamento');
                const btnAdicionarMedicamento = document.getElementById('btn_adicionar_medicamento');
                const itensDispensacaoContainer = document.getElementById('itens_dispensacao_container');
                const formDispensacao = document.getElementById('formDispensacao');
                const selectTipoReceita = document.getElementById('tipo_receita');
                const inputDataReceita = document.getElementById('data_receita');
                const switchUsoContinuo = document.getElementById('antibiotico_continuo');
                const mapaValidade = {
                    "A1/A2/A3": 30,
                    "B1/B2": 30,
                    "C2": 30,
                    "Especial": 30,
                    "Antimicrobiano": 10
                };

                // --- FUNÇÕES DE UI ---

                compradorCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    compradorContainer.classList.toggle('d-none', isChecked);
                    compradorContainer.querySelectorAll('input').forEach(input => {
                        input.required = !isChecked;
                        if (isChecked) input.value = '';
                    });
                });

                checkReceitaDigital.addEventListener('change', function() {
                    const isChecked = this.checked;
                    inputDispensadorDigital.disabled = !isChecked;
                    inputDispensadorDigital.required = isChecked;
                    if (!isChecked) inputDispensadorDigital.value = '';
                });
                
                nomePacienteInput.addEventListener('blur', () => nomePacienteReceitaInput.value = nomePacienteInput.value);

                // --- LÓGICA DE ENDEREÇO (VIACEP) ---
                const preencherFormularioEndereco = (prefixo, data) => {
                    document.getElementById(`endereco_${prefixo}`).value = data.logradouro || '';
                    document.getElementById(`bairro_${prefixo}`).value = data.bairro || '';
                    document.getElementById(`cidade_${prefixo}`).value = data.localidade || '';
                    document.getElementById(`estado_${prefixo}`).value = data.uf || '';
                    if (data.logradouro) 
                        document.getElementById(`numero_${prefixo}`).focus(); // Foca no campo de número após preencher
                };

                document.querySelectorAll('.cep-input').forEach(input => {
                    input.addEventListener('blur', function() {
                        const cep = this.value.replace(/\D/g, '');
                        const prefixo = this.id.split('_')[1];
                        if (cep.length === 8) {
                            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                                .then(response => response.json())
                                .then(data => {
                                    if (!data.erro) 
                                        preencherFormularioEndereco(prefixo, data);
                                    else 
                                        mostrarToast('CEP não encontrado.', 'warning');
                                });
                        }
                    });
                });

                // --- LÓGICA PARA OS BOTÕES DE QUANTIDADE ---
                const btnAumentar = document.getElementById('btn_aumentar_quant');
                const btnDiminuir = document.getElementById('btn_diminuir_quant');
                const inputQuantidade = document.getElementById('quant_medicamento');

                btnAumentar.addEventListener('click', function() {
                    let valorAtual = parseInt(inputQuantidade.value, 10) || 0;
                    inputQuantidade.value = valorAtual + 1;
                    btnDiminuir.disabled = false;
                });

                btnDiminuir.addEventListener('click', function() {
                    let valorAtual = parseInt(inputQuantidade.value, 10) || 0;
                    if (valorAtual > 1) {
                        inputQuantidade.value = valorAtual - 1;
                        if (inputQuantidade.value == 1) 
                            btnDiminuir.disabled = true;
                    }
                });

                // --- LÓGICA DE BUSCA DE CLIENTE / COMPRADOR ---
                const setupBuscaCliente = (inputId, prefixo) => {
                    const input = document.getElementById(inputId);
                    input.addEventListener('change', function() {
                        const documento = this.value.trim().replace(/\D/g, '');
                        if (!documento) return;
                        fetch(`../../Dev/Exec/busca_cliente.php?documento=${documento}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.sucesso) {
                                    document.getElementById(`id_cliente_${prefixo}`).value = data.id_cliente;
                                    document.getElementById(`nome_${prefixo}`).value = data.nome_cliente;
                                    document.getElementById(`tel_${prefixo}`).value = data.telefone;
                                    
                                    if (data.endereco) {
                                        document.getElementById(`cep_${prefixo}`).value = data.endereco.CEP;
                                        preencherFormularioEndereco(prefixo, data.endereco);
                                        document.getElementById(`numero_${prefixo}`).value = data.endereco.End_Numero;
                                    }
                                    
                                    if(prefixo === 'paciente'){
                                        nomePacienteReceitaInput.value = data.nome_cliente;
                                        document.getElementById('paciente_dn_receita').value = data.data_nascimento;
                                        document.getElementById('paciente_sexo_receita').value = data.sexo;
                                    }
                                    
                                    mostrarToast(`Cliente ${data.nome_cliente} encontrado(a)!`, 'success');
                                } 
                                else 
                                    mostrarToast(`Cliente não encontrado(a). Preencha as informações manualmente.`, 'info');
                            });
                    });
                }
                setupBuscaCliente('busca_cliente_cpf', 'paciente');
                setupBuscaCliente('busca_comprador_cpf', 'comprador');

                // --- LÓGICA DE MEDICAMENTOS ---

                buscaMedicamentoInput.addEventListener('keyup', function() {
                    const query = this.value;
                    if (query.length < 3) {
                        searchResultsContainer.innerHTML = '';
                        return;
                    }
                    fetch(`../../Dev/Exec/busca_produto_controlado.php?nome=${query}`)
                        .then(response => response.json())
                        .then(data => {
                            searchResultsContainer.innerHTML = '';
                            data.forEach(p => {
                                const div = document.createElement('a'); 
                                div.href = '#';
                                div.className = 'list-group-item list-group-item-action';
                                div.innerHTML = `<strong>${p.Nome}</strong> <small class="text-muted">EAN: ${p.EAN_GTIN}</small>`;
                                div.onclick = (e) => {
                                    e.preventDefault();
                                    selecionarProduto(p);
                                };
                                searchResultsContainer.appendChild(div);
                            });
                        });
                });
                
                function selecionarProduto(produto) {
                    produtoSelecionado = produto; 
                    buscaMedicamentoInput.value = produto.Nome; 
                    searchResultsContainer.innerHTML = ''; 
                    inputMS.value = produto.MS || 'N/A';
                    carregarLotes(produto.ID_Produto);
                    selectLote.disabled = false;
                    selectLote.focus();
                }
                
                function carregarLotes(idProduto) {
                    selectLote.innerHTML = '<option>Carregando...</option>';
                    fetch(`../../Dev/Exec/busca_lotes_controlados.php?id_produto=${idProduto}`)
                        .then(response => response.json())
                        .then(data => {
                            selectLote.innerHTML = '<option value="">Selecione um lote...</option>';
                            if(data.sucesso && data.lotes.length > 0) {
                                data.lotes.forEach(lote => {
                                    const validade = new Date(lote.Data_Validade + 'T00:00:00').toLocaleDateString('pt-BR');
                                    selectLote.innerHTML += `<option value="${lote.ID_Lote}" data-validade="${lote.Data_Validade}" data-estoque="${lote.Quantidade}">
                                        ${lote.Nome_Lote} (Estoque: ${lote.Quantidade})
                                    </option>`;
                                });
                            } 
                            else 
                                selectLote.innerHTML = '<option value="">Nenhum lote com estoque.</option>';
                        });
                }

                selectLote.addEventListener('change', function(){
                    const selected = this.options[this.selectedIndex];
                    if(!selected.value) {
                        inputValidade.value = '';
                        inputQuantEstoque.value = '';
                        return;
                    }
                    inputValidade.value = selected.dataset.validade;
                    inputQuantEstoque.value = selected.dataset.estoque;
                });

                btnAdicionarMedicamento.addEventListener('click', function() {
                    const quantidadeDispensar = parseInt(document.getElementById('quant_medicamento').value, 10);
                    const loteSelecionado = selectLote.options[selectLote.selectedIndex];

                    if (!produtoSelecionado || !loteSelecionado.value || !quantidadeDispensar || quantidadeDispensar <= 0) {
                        mostrarToast('Selecione um produto, um lote e uma quantidade válida.', 'warning');
                        return;
                    }
                    
                    const estoqueDisponivel = parseInt(loteSelecionado.dataset.estoque, 10);
                    if (quantidadeDispensar > estoqueDisponivel) {
                        mostrarToast(`Quantidade solicitada (${quantidadeDispensar}) maior que o estoque do lote (${estoqueDisponivel}).`, 'danger');
                        return;
                    }

                    dispensacaoItens.push({
                        id_produto: produtoSelecionado.ID_Produto,
                        nome: produtoSelecionado.Nome,
                        preco: produtoSelecionado.Preco_Venda,
                        ms: inputMS.value,
                        id_lote: loteSelecionado.value,
                        nome_lote: loteSelecionado.text.split('(')[0].trim(),
                        validade: inputValidade.value,
                        quantidade: quantidadeDispensar,
                        uso_continuo: document.getElementById('antibiotico_continuo').checked
                    });
                    
                    renderItensDispensacao();
                    resetCamposMedicamento();
                });

                function renderItensDispensacao() {
                    if (dispensacaoItens.length === 0) {
                        itensDispensacaoContainer.innerHTML = '<p class="text-muted">Nenhum medicamento adicionado.</p>';
                        return;
                    }
                    itensDispensacaoContainer.innerHTML = '';
                    dispensacaoItens.forEach((item, index) => {
                        const itemHtml = `<div class="alert alert-secondary d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${item.nome}</strong><br>
                                <small>Lote: ${item.nome_lote} | Qtd: ${item.quantidade} | Val: ${new Date(item.validade + 'T00:00:00').toLocaleDateString('pt-BR')}</small>
                            </div>
                            <button type="button" class="btn-close" onclick="removerItemDispensacao(${index})"></button>
                        </div>`;
                        itensDispensacaoContainer.insertAdjacentHTML('beforeend', itemHtml);
                    });
                    validarDataReceita();
                }

                window.removerItemDispensacao = function(index) {
                    dispensacaoItens.splice(index, 1);
                    renderItensDispensacao();
                }
                
                function resetCamposMedicamento() {
                    produtoSelecionado = null;
                    buscaMedicamentoInput.value = '';
                    selectLote.innerHTML = '<option value="">Selecione...</option>';
                    selectLote.disabled = true;
                    inputQuantEstoque.value = '';
                    inputValidade.value = '';
                    inputMS.value = '';
                    document.getElementById('quant_medicamento').value = '1';
                    document.getElementById('antibiotico_continuo').checked = false;
                    buscaMedicamentoInput.focus();
                }
                
                formDispensacao.addEventListener('submit', function(e){
                    e.preventDefault()

                    if (inputDataReceita.classList.contains('is-invalid')) {
                        mostrarToast('Não é possível continuar. A receita informada está vencida.', 'danger', 'Ação Bloqueada');
                        return;
                    }

                    if(dispensacaoItens.length === 0){
                        e.preventDefault();
                        mostrarToast('Adicione pelo menos um medicamento à dispensação.', 'danger');
                        return;
                    }

                    let inputItens = this.querySelector('input[name="itens_dispensacao"]');
                    if (!inputItens) {
                        inputItens = document.createElement('input');
                        inputItens.type = 'hidden';
                        inputItens.name = 'itens_dispensacao';
                        this.appendChild(inputItens);
                    }
                    inputItens.value = JSON.stringify(dispensacaoItens);

                    const formData = new FormData(this);
                    const btnSubmit = this.querySelector('button[type="submit"]');
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';

                    fetch('processa_dispensacao.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.sucesso && data.redirectUrl) {
                            mostrarToast('Prescrição salva! Redirecionando para a pré-venda...', 'success');
                            window.open(`termo_dispensacao.php?id=${data.id_prescricao}`, '_blank');
                            setTimeout(() => {
                                window.location.href = data.redirectUrl;
                            }, 1500);
                        } 
                        else {
                            mostrarToast('Erro: ' + (data.mensagem || 'Ocorreu um problema ao salvar.'), 'danger');
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = '<i class="bi bi-shield-check"></i> Salvar Prescrição e Gerar Pré-Venda';
                        }
                    })
                    .catch(error => {
                        console.error('Erro no fetch:', error);
                        mostrarToast('Erro de comunicação com o servidor.', 'danger');
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i class="bi bi-shield-check"></i> Salvar Prescrição e Gerar Pré-Venda';
                    });
                });

                // --- LÓGICA DE DATA DE VENCIMENTO DA RECEITA ---

                function validarDataReceita() {
                    const tipo = selectTipoReceita.value;
                    const dataEmissaoStr = inputDataReceita.value;

                    if (!tipo || !dataEmissaoStr) {
                        inputDataReceita.classList.remove('is-invalid');
                        return;
                    }

                    let diasValidade = mapaValidade[tipo];
                    if (!diasValidade) return; 

                    // VERIFICA A EXCEÇÃO DE USO CONTÍNUO
                    const temUsoContinuo = dispensacaoItens.some(item => item.uso_continuo === true) || switchUsoContinuo.checked;
                    if (tipo === 'Antimicrobiano' && temUsoContinuo)
                        diasValidade = 90;

                    const dataEmissao = new Date(dataEmissaoStr + 'T00:00:00');
                    const dataValidade = new Date(dataEmissao);
                    dataValidade.setDate(dataValidade.getDate() + diasValidade);

                    const hoje = new Date();
                    hoje.setHours(0, 0, 0, 0);

                    if (hoje >= dataValidade) {
                        inputDataReceita.classList.add('is-invalid'); 
                        mostrarToast(`Atenção: A validade desta receita (${diasValidade} dias) expirou.`, 'danger', 'Receita Vencida');
                    } 
                    else 
                        inputDataReceita.classList.remove('is-invalid');
                }

                selectTipoReceita.addEventListener('change', validarDataReceita);
                inputDataReceita.addEventListener('blur', validarDataReceita);
                switchUsoContinuo.addEventListener('change', validarDataReceita);
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