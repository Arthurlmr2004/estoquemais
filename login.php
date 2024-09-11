<?php
include 'includes/conexao.php';
session_start();

if (isset($_SESSION['usuario'])) {
    header('Location: painel.php');
    exit();
}

$mensagemErro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];

    // Busca o usuário no banco de dados
    $sql = "SELECT * FROM usuarios WHERE usuario = :usuario";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);


    // Verifica se o usuário foi encontrado e se a senha está correta
    if ($user && $senha === $user['senha']) {
        $_SESSION['usuario_id'] = $user['id']; // Armazena o ID do usuário logado
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['perfil'] = $user['perfil'];  // Salva o perfil na sessão!
        header('Location: painel.php');
        exit();
    } else {
        $mensagemErro = "Usuário ou senha inválidos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="estilos/estilos.css">
    <style>
        .erro {
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
            margin: 0;
        }

        .login-box {
            width: 400px;
            /* Aumente a largura para deixar a caixa maior */
            padding: 30px;
            /* Aumente o padding para um espaço interno maior */
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .login-box h2 {
            margin: 0 0 20px 0;
            text-align: center;
        }

        .login-box form {
            display: flex;
            flex-direction: column;
        }

        .login-box label {
            margin-bottom: 5px;
        }

        .login-box input[type="text"],
        .login-box input[type="password"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .login-box input[type="submit"] {
            padding: 10px;
            background-color: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .login-box input[type="submit"]:hover {
            background-color: #6F6A6A;
        }

        .content-button {
            display: flex;
            flex-direction: column;
            margin-top: 20px;
        }

        .cliente {
            margin-top: 10px;
            text-align: center;
        }

        .cliente a {
            color: #3498db;
            text-decoration: none;
        }

        .cliente a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Login</h2>
            <form action="login.php" method="post">
                <label for="usuario">Usuário:</label>
                <input type="text" id="usuario" name="usuario" placeholder="Digite o usuário" required>
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" placeholder="Digite a senha" required>
                <?php if ($mensagemErro): ?>
                    <p class="erro"><?php echo $mensagemErro; ?></p>
                <?php endif; ?>
                <input type="submit" value="Entrar">
            </form>
        </div>
    </div>
</body>

</html>