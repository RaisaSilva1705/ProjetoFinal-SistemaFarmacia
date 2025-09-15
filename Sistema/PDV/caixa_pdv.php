<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../Dev/Exec/config.php";
include DEV_PATH . 'Exec/conexao.php';
include DEV_PATH . 'Exec/logs.php';
include DEV_PATH . "Exec/validar_sessao.php";
include DEV_PATH . "Exec/validar_acesso.php";

$sqlCaixas = "SELECT ID_Caixa,
                     Caixa,
                     Status
              FROM CAIXAS
              WHERE StatusCadastrado = 'Ativo'";
$caixas = $conn->query($sqlCaixas);

$sqlTurnos = "SELECT ID_Turno,
                     Turno
              FROM TURNOS";
$turnos = $conn->query($sqlTurnos);

// Pega a hora atual 
$horaAtual = date('H');

$idTurnoAutomatico = null;
if ($horaAtual >= 6 && $horaAtual < 12)
    $idTurnoAutomatico = 1; // Manhã
elseif ($horaAtual >= 12 && $horaAtual < 18)
    $idTurnoAutomatico = 2; // Tarde
else
    $idTurnoAutomatico = 3; // Noite

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    $id_caixa = $_POST['id_caixa'];
    $id_funcionario = $_SESSION['ID_Funcionario'];
    $saldoInicial = $_POST['saldo_inicial'];
    $turno = $_POST['id_turno'];

    // Buscar o status real do caixa informado
    $sqlStatus = "SELECT 
                    Status 
                  FROM CAIXAS 
                  WHERE ID_Caixa = ? AND StatusCadastrado = 'Ativo'";
    $stmtStatus = $conn->prepare($sqlStatus);
    $stmtStatus->bind_param("i", $id_caixa);
    $stmtStatus->execute();
    $resultStatus = $stmtStatus->get_result();
    $caixa = $resultStatus->fetch_assoc();

    if($caixa['Status'] == 'Fechado'){

        $sql ="UPDATE CAIXAS SET
                Status = 'Aberto'
               WHERE ID_Caixa = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_caixa);
        
        if ($stmt->execute()){
            $sql ="INSERT INTO CAIXAS_ABERTOS 
                    (ID_Caixa, ID_Funcionario, ID_Turno,
                    Data_Abertura, Saldo_Inicial)
                   VALUES (?, ?, ?, NOW(), ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiid", $id_caixa, $id_funcionario, $turno, $saldoInicial);
            $stmt->execute();

            $_SESSION['ID_CaixaAberto'] = $stmt->insert_id;
            $_SESSION['ID_Caixa'] = $id_caixa;
            $_SESSION['Saldo_Inicial'] = $saldoInicial;
            
            registrar_log($conn, $_SESSION['ID_Usuario'], "Abriu o caixa {$_SESSION['ID_CaixaAberto']} com R$ {$saldoInicial}. (ID Caixa: {$id_caixa})");
            header("Location: pdv.php");
            exit();
        }
        else {
            $_SESSION["msg"] = ['texto' => 'Erro ao abrir o caixa', 'tipo' => 'danger'];
            header("Location: caixa_pdv.php"); 
            exit();
        }
    }
    else {
        $_SESSION["msg"] = ['texto' => 'Caixa já está aberto', 'tipo' => 'warning'];
        header("Location: caixa_pdv.php"); 
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Seleção de Caixa</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?php echo DEV_URL ?>CSS/global.css">
        <style>
            select > option:first-child {
                display: none;
            }
        </style>
    </head>
    <body class="bg-light">
        <!-- Navbar -->
        <?php include_once DEV_PATH . 'Views/sidebar.php'?>

        <div class="content d-flex flex-column min-vh-100">
            <div class="content flex-grow-1">
                <!-- Banner -->
                <div class="container-fluid bg-secondary text-white text-center p-4">
                    <h3>Seleção de Caixa</h3>
                </div>

                <div class="container mt-3 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2>Selecione para Continuar</h2>
                        <div>
                            <a href="../Relatorios/relatorio_caixas.php" class="btn btn-outline-secondary">Ver Relatório</a>
                        </div>
                    </div>
                    
                    <div class="card card-body mb-4">
                        <form action="#" method="POST">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="id_caixa" class="form-label">Selecione o Caixa</label>
                                    <select class="form-select" name="id_caixa" id="id_caixa" required>
                                        <option value="">Selecione</option>
                                        <?php while($caixa = $caixas->fetch_assoc()): ?>
                                            <option value="<?= $caixa['ID_Caixa'] ?>"><?= $caixa['Caixa'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="id_turno" class="form-label">Turno</label>
                                    <select class="form-select" name="id_turno" id="id_turno" required disabled>
                                        <?php 
                                        $turnos->data_seek(0);
                                        while($turno = $turnos->fetch_assoc()): 
                                            $selected = ($turno['ID_Turno'] == $idTurnoAutomatico ? 'selected' : '')
                                        ?>
                                            <option value="<?= $turno['ID_Turno'] ?>" <?= $selected ?>><?= $turno['Turno'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <input type="hidden" name="id_turno" value="<?= $idTurnoAutomatico ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label" for="saldo_inicial">Saldo Inicial</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input class="form-control" type="number" name="saldo_inicial" id="saldo_inicial" required placeholder="0,00">
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3 text-center">
                                    <button type="submit" class="btn btn-primary mt-4 px-5">Abrir Caixa</button>
                                </div>
                            </div>
                        </form>
                    </div>
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