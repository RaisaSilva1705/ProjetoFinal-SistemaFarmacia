<?php
include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';

$id_caixa = filter_input(INPUT_GET, 'id_caixa', FILTER_VALIDATE_INT);
if (!$id_caixa) {
    header('Location: selecao_tela_cliente.php');
    exit;
}

$stmt = $conn->prepare("SELECT Caixa FROM CAIXAS WHERE ID_Caixa = ?");
$stmt->bind_param("i", $id_caixa);
$stmt->execute();
$caixa = $stmt->get_result()->fetch_assoc();
$nome_caixa = $caixa ? $caixa['Caixa'] : 'Caixa Inválido';

?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cliente - <?= htmlspecialchars($nome_caixa) ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/telaCliente.css">
    </head>
    <body>

        <div class="container-tela">
        <div class="card card-tela">

            <div id="tela-aguardando" class="screen active tela-centro">
                <h1>Bem-vindo!</h1>
                <p>Aguardando o início da sua compra...</p>
            </div>

            <div id="tela-venda" class="screen">
                <div id="lista-itens-cliente" class="list-group list-group-flush">
                </div>
                
                <div id="total-container">
                    <span id="total-venda-label">TOTAL</span>
                    <span id="total-venda-cliente">R$ 0,00</span>
                </div>
            </div>

            <div id="tela-avaliacao" class="screen tela-centro">
                <h1>Obrigado por comprar conosco!</h1>
                <p>Como você avalia o atendimento do nosso funcionário(a)?</p>
                <div class="faces-avaliacao">
                    <i class="bi bi-emoji-angry-fill text-secondary face-pessimo" onclick="enviarAvaliacao(1)" title="Péssimo"></i>
                    <i class="bi bi-emoji-frown-fill text-secondary face-ruim" onclick="enviarAvaliacao(2)" title="Ruim"></i>
                    <i class="bi bi-emoji-neutral-fill text-secondary face-neutro" onclick="enviarAvaliacao(3)" title="Neutro"></i>
                    <i class="bi bi-emoji-smile-fill text-secondary face-bom" onclick="enviarAvaliacao(4)" title="Bom"></i>
                    <i class="bi bi-emoji-laughing-fill text-secondary face-excelente" onclick="enviarAvaliacao(5)" title="Excelente"></i>
                </div>
                <form id="form-avaliacao" style="display: none;">
                    <input type="hidden" name="id_caixa" value="<?= $id_caixa ?>">
                    <input type="hidden" name="id_venda" id="aval_id_venda">
                    <input type="hidden" name="id_funcionario" id="aval_id_funcionario">
                    <input type="hidden" name="nota" id="aval_nota">
                </form>
            </div>
            
            <div id="tela-inativo" class="screen tela-centro">
                <h1>Caixa Fechado</h1>
                <p>Esta tela será reiniciada em instantes. <br>Agradecemos a sua visita!</p>
            </div>

        </div>
    </div>

    <script>
        const ID_CAIXA = <?= $id_caixa ?>;
        const telas = {
            aguardando: document.getElementById('tela-aguardando'),
            venda: document.getElementById('tela-venda'),
            avaliacao: document.getElementById('tela-avaliacao'),
            inativo: document.getElementById('tela-inativo')
        };
        let modoAtual = 'Aguardando';
        let carrinhoAnterior = null;

        function mudarTela(novoModo) {
            if (modoAtual === novoModo) return; // Não faz nada se o modo for o mesmo
            
            Object.values(telas).forEach(tela => tela.classList.remove('active'));
            telas[novoModo.toLowerCase()].classList.add('active');
            modoAtual = novoModo;
        }

        function renderTelaVenda(status) {
            const listaItens = document.getElementById('lista-itens-cliente');
            listaItens.innerHTML = '';
            let totalGeral = 0;

            status.forEach(item => {
                const preco = parseFloat(item.preco || item.valor || 0);
                const qtd = parseInt(item.quantidade || item.qtd || 1);
                const desconto = parseFloat(item.desconto || 0);
                const subtotal = (preco * qtd) - desconto;
                totalGeral += subtotal;

                const promocaoHtml = item.desconto_promocao_desc 
                    ? `<br><small class="badge bg-success promocao-badge">${item.desconto_promocao_desc}</small>` 
                    : '';

                const itemHtml = `
                    <div class="item-lista list-group-item">
                        <div class="fw-bold">${item.nome}</div>
                        <div class="text-center">x ${qtd}</div>
                        <div class="text-end">R$ ${subtotal.toFixed(2).replace('.', ',')} ${promocaoHtml}</div>
                    </div>
                `;
                listaItens.insertAdjacentHTML('beforeend', itemHtml);
            });
            
            document.getElementById('total-venda-cliente').textContent = `R$ ${totalGeral.toFixed(2).replace('.', ',')}`;
        }
        
        function renderTelaAvaliacao(status) {
            document.getElementById('aval_id_venda').value = status.id_venda;
            document.getElementById('aval_id_funcionario').value = status.id_funcionario;
        }

        async function enviarAvaliacao(nota) {
            document.getElementById('aval_nota').value = nota;
            const form = document.getElementById('form-avaliacao');
            const formData = new FormData(form);

            // Feedback visual para o cliente
            mudarTela('Aguardando');
            telas.aguardando.querySelector('h1').textContent = 'Obrigado por avaliar!';
            telas.aguardando.querySelector('p').textContent = 'Sua opinião é muito importante para nós.';

            // Envia a avaliação para o servidor
            await fetch('../../Dev/Exec/salvar_avaliacao.php', {
                method: 'POST',
                body: formData
            });

            // Atualiza o estado local para evitar que o próximo "poll" reverta a tela
            modoAtual = 'Aguardando';
            carrinhoAnterior = JSON.stringify([]); // Define o carrinho como vazio

            // Após um tempo, volta para a tela de boas-vindas padrão
            setTimeout(() => {
                telas.aguardando.querySelector('h1').textContent = 'Seja bem-vindo!';
                telas.aguardando.querySelector('p').textContent = 'Aguardando o início da sua compra...';
            }, 4000);
        }

        async function fetchStatus() {
            try {
                const response = await fetch(`../../Dev/Exec/busca_tela_cliente_status.php?id_caixa=${ID_CAIXA}`);
                const data = await response.json();
                
                // Compara o carrinho atual com o anterior para evitar redesenhos desnecessários
                const carrinhoAtualString = JSON.stringify(data.status);

                if (modoAtual !== data.modo || carrinhoAnterior !== carrinhoAtualString) {
                    mudarTela(data.modo);

                    switch (data.modo.toLowerCase()) {
                        case 'venda':
                            renderTelaVenda(data.status);
                            break;
                        case 'avaliacao':
                            renderTelaAvaliacao(data.status);
                            break;
                        case 'inativo':
                            setTimeout(() => {
                                window.location.href = 'selecao_tela_cliente.php';
                            }, 5000);
                            break;
                    }
                    carrinhoAnterior = carrinhoAtualString;
                }
            } catch (error) {
                console.error("Erro ao buscar status:", error);
                // Em caso de falha de rede, tenta novamente
                setTimeout(fetchStatus, 5000); 
            }
        }

        // Inicia o "polling" para verificar o status a cada 2 segundos
        setInterval(fetchStatus, 2000);
        // Faz uma chamada inicial para carregar o estado assim que a página abre
        fetchStatus();

    </script>
    </body>
</html>