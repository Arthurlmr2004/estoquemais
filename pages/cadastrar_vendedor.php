<?php
// Verifica se o usuário está autenticado como admin
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: nao_autorizado.php'); // Redireciona se não for admin
    exit();
}

include 'conexao.php';

$usuario = "";
$mensagem = ""; // Variável para mensagens de erro ou sucesso

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];
    $confirmarSenha = $_POST['confirmar_senha'];

    // Validações básicas
    if (empty($usuario) || empty($senha) || empty($confirmarSenha)) {
        $mensagem = "Preencha todos os campos!";
    } elseif ($senha !== $confirmarSenha) {
        $mensagem = "As senhas não coincidem!";
    } else {
        // Verifica se o usuário já existe
        $sql = "SELECT usuario FROM usuarios WHERE usuario = :usuario";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $mensagem = "Usuário já cadastrado!";
        } else {
            // Insere o novo vendedor
            $sql = "INSERT INTO usuarios (usuario, senha, perfil) VALUES (:usuario, :senha, 'vendedor')";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':senha', $senha);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $mensagem = "Vendedor cadastrado com sucesso!";
                // Limpa os campos após o cadastro
                $usuario = "";
                $senha = "";
                $confirmarSenha = "";
            } else {
                $mensagem = "Erro ao cadastrar vendedor!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Vendedor</title>
    <link rel="stylesheet" href="../estilos/estilos.css">
    <style>
        .cadastro-box {
            width: 400px;
            padding: 30px;
            background: #fff;
            margin: auto;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .cadastro-box h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .cadastro-box form {
            display: flex;
            flex-direction: column;
        }

        .cadastro-box label {
            margin-bottom: 5px;
        }

        .cadastro-box input[type="text"],
        .cadastro-box input[type="password"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .cadastro-box input[type="submit"] {
            padding: 10px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .cadastro-box input[type="submit"]:hover {
            background-color: #2980b9;
        }

        .mensagem {
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
            color: <?php echo ($mensagem === "Vendedor cadastrado com sucesso!") ? 'green' : 'red'; ?>;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="cadastro-box">
            <h2>Cadastrar Vendedor</h2>
            <?php if (!empty($mensagem)): ?>
                <p class="mensagem"><?php echo $mensagem; ?></p>
            <?php endif; ?>
            <form action="painel.php?page=cadastrar_vendedor" method="post">
                <label for="usuario">Usuário:</label>
                <input type="text" id="usuario" name="usuario" required value="<?php echo $usuario; ?>"> <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>

                <label for="confirmar_senha">Confirmar Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>

                <input type="submit" value="Cadastrar">
            </form>
        </div>
    </div>
</body>

</html>