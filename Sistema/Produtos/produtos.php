<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";

include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$sql = "SELECT
            P.ID_Produto,
            P.Nome,
            P.ID_Fornecedor,
            E.Preco_Atual,
            C.Categoria,
            E.Quantidade
        FROM PRODUTOS P 
        LEFT JOIN CATEGORIAS C
            ON C.ID_Categoria = P.ID_Categoria
        LEFT JOIN ESTOQUE E
            ON E.ID_Produto = P.ID_Produto";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Listagem de Produtos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body>
        <!-- Sidebar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Listagem de Produtos</h3>
                    <?php
                        // Verifica se $_SESSION["msg"] não é nulo e imprime a mensagem
                        if(isset($_SESSION["msg"]) && $_SESSION["msg"] != null){
                            echo $_SESSION["msg"];
                            // Limpa a mensagem para evitar que seja exibida novamente
                            $_SESSION["msg"] = null;
                        }
                    ?>
                </div>
    
                <div class="container mt-3 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Lista de Produto</h2>
                        <a href="cadastrar_produto.php" class="btn btn-primary">Cadastrar Novo Produto</a>
                    </div>
    
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Produto</th>
                                <th scope="col">Fornecedor</th>
                                <th scope="col">Categoria</th>
                                <th scope="col">Estoque</th>
                                <th scope="col">Preço</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) { // quebra de página após 20 resultados
                                        $preco = ($row['Preco_Atual'] == null) ?  0.00 : $row['Preco_Atual']; 
                                        $fornecedor = ($row['ID_Fornecedor'] == null) ?  '' : $row['ID_Fornecedor'];
                                        echo '<tr>';
                                            echo '<td>' . $row["ID_Produto"] . '</td>';
                                            echo '<td>' . $row["Nome"] . '</td>';
                                            echo '<td>' . $fornecedor . '</td>';
                                            echo '<td>' . $row["Categoria"] . '</td>';
                                            echo '<td>' . $row["Quantidade"] . '</td>';
                                            echo '<td> R$ ' . $preco . '</td>';
                                            echo '<td>
                                                    <a href="editar_produto.php?codigo=' . $row["ID_Produto"] . '" class="btn btn-info btn-sm">Editar</a>
                                                </td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="10" class="text-center">Nenhum produto cadastrado.</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
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

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function mostrarToast(texto, tipo = 'success', titulo = 'Notificação') {
                const toastLiveExample = document.getElementById('liveToast');
                const toastHeader = toastLiveExample.querySelector('.toast-header');
                
                // Define o título padrão baseado no tipo, se não for fornecido
                if (titulo === 'Notificação') 
                    titulo = ucfirst(tipo === 'danger' ? 'Erro' : (tipo === 'warning' ? 'Atenção' : 'Sucesso'));
                
                const headerClass = `text-bg-${tipo}`;

                document.getElementById('toastTitulo').innerText = titulo;
                document.getElementById('toastCorpo').innerText = texto;
                
                // Remove classes de cor antigas e adiciona a nova
                toastHeader.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning', 'text-bg-info');
                toastHeader.classList.add(headerClass);

                const toast = new bootstrap.Toast(toastLiveExample);
                toast.show();
            }

            // Função auxiliar para deixar a primeira letra maiúscula (o PHP faz isso, o JS não)
            function ucfirst(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }

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
