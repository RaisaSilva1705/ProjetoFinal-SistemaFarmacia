<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'config.php';
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($conn->connect_error) {
        die("Erro na conexão com o banco de dados: " . $conn->connect_error);
    }

    $user = $_POST["user"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT U.ID_Usuario,
                                   U.ID_Funcionario,
                                   U.Senha,
                                   U.Status,
                                   F.ID_Cargo,
                                   F.Nome
                            FROM USUARIOS U LEFT JOIN FUNCIONARIOS F
                            ON U.ID_Funcionario = F.ID_Funcionario
                            WHERE Usuario = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $dados = $result->fetch_assoc();
        if ($dados['Status'] == 'Ativo'){
            $passHash = $dados['Senha'];

            if (password_verify($password, $passHash)) {
                $_SESSION['ID_Usuario'] = $dados['ID_Usuario'];
                $_SESSION['ID_Funcionario'] = $dados['ID_Funcionario'];
                $_SESSION['Nome'] = $dados['Nome'];
                $_SESSION['ID_Cargo'] = $dados['ID_Cargo'];
                $_SESSION['expire'] = strtotime('+60 minutes', strtotime('now'));
                $_SESSION["msg"] = ['texto' => "Olá " . $_SESSION['Nome'] . ". Login efetuado com sucesso!", 'tipo' => 'success'];
                mysqli_close($conn);                    
                header('Location:' . SISTEMA_URL .'dashboard.php');
                exit();
            }
            else {
                $_SESSION["msg"] = ['texto' => 'Usuário ou senha estão incorretos. Por favor, verifique suas credenciais', 'tipo' => 'danger'];
                mysqli_close($conn);
                header('Location' . SISTEMA_URL .'index.php');
                exit;
            }
        }
        else {
            $_SESSION["msg"] = ['texto' => 'Usuário não está ativo', 'tipo' => 'danger'];
            mysqli_close($conn);
            header('Location' . SISTEMA_URL .'index.php');
            exit;
        }
        
    }
    else {
        $_SESSION["msg"] = ['texto' => 'Usuário ou senha estão incorretos. Por favor, verifique suas credenciais', 'tipo' => 'danger'];
        mysqli_close($conn);
        header('Location' . SISTEMA_URL .'index.php');
        exit;
    }
}
?>