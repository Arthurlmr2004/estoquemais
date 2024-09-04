<?php
include 'pages/conexao.php';
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
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['permissao'] = $user['permissao'];
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
        .erro{
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }


    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>Login</h2>
            <form action="login.php" method="post">
                <label for="usuario">Usuário:</label>
                <input type="text" id="usuario" name="usuario" required>
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
                <?php if ($mensagemErro): ?>
                    <p class="erro"><?php echo $mensagemErro; ?></p>
                <?php endif; ?>
                <input type="submit" value="Entrar">
            </form>
        </div>
    </div>
</body>
</html>
