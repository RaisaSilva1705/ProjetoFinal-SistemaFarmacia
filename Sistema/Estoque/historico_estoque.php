<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";

include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

// Construir a consulta SQL
$sql = "SELECT
            M.ID_Produto,
            M.ID_Funcionario,
            M.Tipo,
            M.Quantidade,
            M.Valor,
            M.Data_Movimentacao,
            P.Nome AS Nome_Prod,
            F.Nome AS Nome_Func
        FROM MOVIMENTACAO_ESTOQUE M
        LEFT JOIN PRODUTOS P ON M.ID_Produto = P.ID_Produto
        LEFT JOIN FUNCIONARIOS F ON M.ID_Funcionario = F.ID_Funcionario
        ORDER BY M.Data_Movimentacao DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>LavanderPharma</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
            }
            .content {
                flex: 1;
            }
            footer {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background-color: #f8f9fa;
            }
            .navbu {
                margin: 2px;
            }
        </style>
    </head>
    <body>
        <!-- Navbar -->
        <nav class='navbar navbar-expand-lg navbar-dark bg-dark'>
            <div class='container-fluid'>
                <a class='navbar-brand' href='#'>LavanderPharma</a>
                <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarNav' aria-controls='navbarNav' aria-expanded='false' aria-label='Toggle navigation'>
                    <span class='navbar-toggler-icon'></span>
                </button>
                <div class='collapse navbar-collapse' id='navbarNav'>
                    <ul class='navbar-nav ms-auto'>
                        <a href="movimentacao.php?mov=<?php echo "E" ?>" class="navbu">
                            <button class="btn btn-secondary" type="button">Entrada Avulsa</button>
                        </a>
                        <a href="movimentacao.php?mov=<?php echo "S" ?>" class="navbu">
                            <button class="btn btn-secondary" type="button">Saída Avulsa</button>
                        </a>
                        <a href="hist_movimentacoes.php" class="navbu">
                            <button class="btn btn-secondary" type="button">Histório</button>
                        </a>
                        <a href="index.php" class="navbu">
                            <button class="btn btn-secondary" type="button">Estoque</button>
                        </a>
                        <li class='nav-item'><a class='nav-link active' href='../../index2.php'>Menu Principal</a></li>
                        <li class='nav-item'><a class='nav-link' href="../../dev/Exec/sair.php">Sair</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Banner -->
        <div class="container-fluid bg-secondary text-white text-center p-4">
            <h3>Histório de Movimentações</h3>
            <?php
                // Verifica se $_SESSION["msg"] não é nulo e imprime a mensagem
                if(isset($_SESSION["msg"]) && $_SESSION["msg"] != null){
                    echo $_SESSION["msg"];
                    // Limpa a mensagem para evitar que seja exibida novamente
                    $_SESSION["msg"] = null;
                }
            ?>
        </div>

        <!-- Filtros e Ordenação -->
        <div class="container my-5">
            <!-- Tabela de Clientes -->
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">Produto</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Quantidade</th>
                        <th scope="col">Valor</th>
                        <th scope="col">Data</th>
                        <th scope="col">Funcionário</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) { // quebra de página após 20 resultados
                            $dataMovi = new DateTime($row["Data_Movimentacao"]);
                            echo '<tr>';
                            echo '<td>' . $row["Nome_Prod"] . '</td>';
                            echo '<td>' . $row["Tipo"] . '</td>';
                            echo '<td>' . $row["Quantidade"] . '</td>';
                            echo '<td>R$ ' . $row["Valor"] . '</td>';
                            echo '<td>' . $dataMovi->format('d/m/Y H:i:s')  . '</td>';
                            echo '<td>' . $row["Nome_Func"] . '</td>';
                            echo '</tr>';
                        }
                    } 
                    else {
                        echo '<tr><td colspan="10" class="text-center">Nenhuma movimentação cadastrada.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <footer class="bg-light text-center text-lg-start">
            <div class="text-center p-3 bg-dark text-white">
                <p>© 2024 LavanderPharma - Todos os direitos reservados.</p>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>