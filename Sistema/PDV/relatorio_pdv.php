<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "../../Dev/Exec/config.php";

// Incluir o arquivo de conexão
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . "Exec/validar_sessao.php";

// Filtros
$data_inicio = $_POST['data_inicio'] ?? '';
$data_fim = $_POST['data_fim'] ?? '';
$id_caixa = $_POST['id_caixa'] ?? '';
$id_turno = $_POST['id_turno'] ?? '';

// 3. Busca os pagamentos das vendas
$sqlVendas = "SELECT V.ID_Venda,
                     V.DataHora_Venda,
                     V.Valor_Total,
                     F.Nome AS 'Nome_Funcionario',
                     C.Nome AS 'Nome_Cliente',
                     FP.Tipo AS 'Forma_Pag',
                     VP.Valor AS 'Valor_Pag',
                     VP.Troco,
                     CX.Caixa,
                     T.Turno
            FROM VENDA_PAGAMENTOS VP INNER JOIN VENDAS V
                ON VP.ID_Venda = V.ID_Venda
            LEFT JOIN FUNCIONARIOS F
                ON V.ID_Funcionario = F.ID_Funcionario
            LEFT JOIN CLIENTES C
                ON V.ID_Cliente = C.ID_Cliente
            LEFT JOIN FORMAS_PAGAMENTO FP
                ON VP.ID_Forma_Pag = FP.ID_Forma_Pag
            LEFT JOIN CAIXAS_ABERTOS CA
                ON V.ID_CaixaAberto = CA.ID_CaixaAberto
            LEFT JOIN CAIXAS CX
                ON CA.ID_Caixa = CX.ID_Caixa
            LEFT JOIN TURNOS T 
                ON CA.ID_Turno = T.ID_Turno
            WHERE 1=1";

// Array para parâmetros
$tipos = '';
$parametros = [];

// Filtro por data
if (!empty($data_inicio) && !empty($data_fim)) {
    $sqlVendas .= " AND V.DataHora_Venda BETWEEN ? AND ?";
    $tipos .= 'ss';
    $parametros[] = $data_inicio . " 00:00:00";
    $parametros[] = $data_fim . " 23:59:59";
}

// Filtro por caixa
if (!empty($id_caixa)) {
    $sqlVendas .= " AND CX.ID_Caixa = ?";
    $tipos .= 'i';
    $parametros[] = intval($id_caixa);
}

// Filtro por turno
if (!empty($id_turno)) {
    $sqlVendas .= " AND T.ID_Turno = ?";
    $tipos .= 'i';
    $parametros[] = intval($id_turno);
}

$sqlVendas .= " ORDER BY V.ID_Venda ASC, VP.ID_VendaPagamento ASC";

$stmtVendas = $conn->prepare($sqlVendas);

// Associa parâmetros dinamicamente
if (!empty($parametros)) {
    $stmtVendas->bind_param($tipos, ...$parametros);
}

$stmtVendas->execute();
$resultVendas = $stmtVendas->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Relatório Vendas</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
    </head>
    <body class="bg-light">
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Relatório Vendas</h3>
                </div>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Consultar Vendas</h1>
    
                    <!-- Filtros -->
                    <form method="POST" class="row g-3 mb-4">
                        <div class="col-md-2">
                            <label for="data_inicio" class="form-label">Data Início</label>
                            <input type="date" name="data_inicio" id="data_inicio" class="form-control" value="<?php echo $data_inicio; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="data_fim" class="form-label">Data Fim</label>
                            <input type="date" name="data_fim" id="data_fim" class="form-control" value="<?php echo $data_fim; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="id_caixa" class="form-label">Caixa</label>
                            <select name="id_caixa" id="id_caixa" class="form-control">
                                <option value="">Todos</option>
                                <?php
                                $caixas = $conn->query("SELECT ID_Caixa, Caixa FROM CAIXAS");
                                while ($caixa = $caixas->fetch_assoc()) {
                                    $selected = ($caixa['ID_Caixa'] == $id_caixa) ? 'selected' : '';
                                    echo "<option value='{$caixa['ID_Caixa']}' $selected>{$caixa['Caixa']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="id_turno" class="form-label">Turno</label>
                            <select name="id_turno" id="id_turno" class="form-control">
                                <option value="">Todos</option>
                                <?php
                                $turnos = $conn->query("SELECT ID_Turno, Turno FROM TURNOS");
                                while ($turno = $turnos->fetch_assoc()) {
                                    $selected = ($turno['ID_Turno'] == $id_turno) ? 'selected' : '';
                                    echo "<option value='{$turno['ID_Turno']}' $selected>{$turno['Turno']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
    
                    <!-- Tabela -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-cash-register me-1"></i>
                            Lista de Vendas Realizadas
                        </div>
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">Nº Venda</th>
                                    <th scope="col">Caixa</th>
                                    <th scope="col">Turno</th>
                                    <th scope="col">Funcionario</th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Data e Hora</th>
                                    <th scope="col">Formas Pag</th>
                                    <th scope="col">R$ Pag</th>
                                    <th scope="col">R$ Troco</th>
                                    <th scope="col">R$ Total</th>
                                    <th scope="col">Cupom Fiscal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    if ($resultVendas->num_rows > 0) {
                                        while ($row = $resultVendas->fetch_assoc()) { // quebra de página após 20 resultados
                                            $nomeCli = ($row['Nome_Cliente']) ? $row['Nome_Cliente'] : '--';
                                            echo '<tr>';
                                                echo '<td>' . $row["ID_Venda"] . '</td>';
                                                echo '<td>' . $row["Caixa"] . '</td>';
                                                echo '<td>' . $row["Turno"] . '</td>';
                                                echo '<td>' . $row["Nome_Funcionario"] . '</td>';
                                                echo '<td>' . $nomeCli . '</td>';
                                                echo '<td>' . $row["DataHora_Venda"] . '</td>';
                                                echo '<td>' . $row["Forma_Pag"] . '</td>';
                                                echo '<td>' . number_format($row["Valor_Pag"], 2, ',', '.') . '</td>';
                                                echo '<td>' . number_format($row["Troco"], 2, ',', '.') . '</td>';
                                                echo '<td>' . number_format($row["Valor_Total"], 2, ',', '.') . '</td>';
                                                echo '<td>
                                                        <a href="cupomNfiscal.php?ID_Venda=' . $row["ID_Venda"] . '" class="btn btn-info btn-sm">Ver</a>
                                                    </td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="11" class="text-center">Nenhuma venda realizada.</td></tr>';
                                    }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <?php include_once DEV_PATH . 'Views/footer.php'?>
        </div>
    </body>
</html>
