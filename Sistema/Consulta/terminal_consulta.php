<?php 
include "../../Dev/Exec/config.php";
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Consulta de Preços</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/consulta.css">
    </head>
    <body>

        <div class="container-terminal">
            <div class="card card-consulta">
                <div id="tela-inicial" class="screen active">
                    <i class="bi bi-upc-scan" style="font-size: 6rem; color: #0d6efd;"></i>
                    <h1>Ei, precisa de ajuda com o preço?</h1>
                    <p>Aproxime o código de barras do leitor ou digite o código abaixo.</p>
                    <div class="mt-4">
                        <input type="text" id="ean-input" class="form-control form-control-lg text-center" placeholder="Digite o código de barras aqui" autofocus>
                    </div>
                </div>

                <div id="tela-resultado" class="screen">
                    <div class="row align-items-center">
                        <div class="col-md-5 text-center">
                            <img id="resultado-img" src="" class="img-fluid rounded" alt="Imagem do Produto">
                        </div>
                        <div class="col-md-7">
                            <h2 id="resultado-nome" class="display-6">Nome do Produto</h2>
                            <p id="resultado-desc" class="lead text-muted">Descrição do produto aqui.</p>
                            <p id="resultado-preco" class="my-3">R$ 0,00</p>
                            
                            <div id="resultado-promocao" class="box-promocao" style="display: none;">
                                <h5><i class="bi bi-tag-fill"></i> OFERTA ESPECIAL!</h5>
                                <p id="promocao-desc" class="mb-0">Descrição da promoção.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    <script>
        const eanInput = document.getElementById('ean-input');
        const telaInicial = document.getElementById('tela-inicial');
        const telaResultado = document.getElementById('tela-resultado');
        let idleTimer = null; // Variável para controlar o timer de inatividade

        eanInput.addEventListener('change', async function() {
            const ean = this.value.trim();
            if (ean === '') return;
            
            // Limpa o timer anterior para não resetar a tela no meio de uma consulta
            clearTimeout(idleTimer);

            try {
                const response = await fetch(`../../Dev/Exec/busca_produto_consulta.php?ean=${ean}`);
                const data = await response.json();

                if (data.sucesso) 
                    exibirResultado(data.produto);
                else 
                    exibirErro('Produto não encontrado. Tente novamente ou peça ajuda a um colaborador.');
            } 
            catch (error) {
                console.error('Erro na busca:', error);
                exibirErro('Ocorreu um erro na comunicação. Tente novamente.');
            } 
            finally {
                this.value = ''; // Limpa o campo para a próxima consulta
            }
        });

        function exibirResultado(produto) {
            document.getElementById('resultado-img').src = produto.Foto;
            document.getElementById('resultado-nome').textContent = produto.Nome;
            document.getElementById('resultado-desc').textContent = produto.Descricao || '';
            document.getElementById('resultado-preco').textContent = `R$ ${parseFloat(produto.Preco_Venda).toFixed(2).replace('.', ',')}`;

            const promoBox = document.getElementById('resultado-promocao');
            if (produto.Promocao_Descricao) {
                document.getElementById('promocao-desc').textContent = produto.Promocao_Descricao;
                promoBox.style.display = 'block';
            } 
            else 
                promoBox.style.display = 'none';

            // Aciona a transição
            telaInicial.classList.remove('active');
            telaResultado.classList.add('active');

            iniciarTimerReset();
        }
        
        function exibirErro(mensagem) {
            // Exibe o erro na tela inicial mesmo
            telaInicial.querySelector('p').textContent = mensagem;
            telaInicial.querySelector('p').style.color = 'red';
            
            iniciarTimerReset();
        }

        function resetarTela() {
            // Aciona a transição de volta para a tela inicial
            telaResultado.classList.remove('active');
            telaInicial.classList.add('active');

            // Limpa a mensagem de erro e devolve o foco
            setTimeout(() => { // Espera a transição terminar para mudar o texto
                telaInicial.querySelector('p').textContent = 'Aproxime o código de barras do leitor ou digite o código abaixo.';
                telaInicial.querySelector('p').style.color = '#6c757d';
                eanInput.focus();
            }, 500); // 500ms = mesma duração da transição do CSS
        }

        function iniciarTimerReset() {
            clearTimeout(idleTimer); // Limpa qualquer timer anterior
            idleTimer = setTimeout(resetarTela, 10000); // 10000 ms = 10 segundos
        }
    </script>
    </body>
</html>