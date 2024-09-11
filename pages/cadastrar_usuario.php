<?php
include 'includes/conexao.php';
include 'funcoes_log.php';

// Verifica se o usuário está autenticado como admin
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: nao_autorizado.php');
    exit();
}

// Obtém o nome do usuário logado (assumindo que você armazena isso na sessão)
$usuarioLogado = $_SESSION['usuario'];

$usuario = "";
$senha = "";
$confirmarSenha = "";
$perfil = "vendedor"; // Valor padrão
$mensagem = ""; // Variável para mensagens de erro ou sucesso

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $senha = $_POST['senha'];
    $confirmarSenha = $_POST['confirmar_senha'];
    $perfil = $_POST['perfil'];

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
            // Insere o novo usuário
            $sql = "INSERT INTO usuarios (usuario, senha, perfil) VALUES (:usuario, :senha, :perfil)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':perfil', $perfil);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Para um log mais legível, descrever as mudanças
                $mudancas = [
                    "Usuário: " . $usuario,
                    "Perfil: " . $perfil
                ];

                // Converter as mudanças para uma string
                $descricaoMudancas = implode("; ", $mudancas);

                // Cria o comando SQL completo para o log
                $comandoSqlCompleto = "INSERT INTO usuarios (usuario, senha, perfil) VALUES ('" . addslashes($usuario) . "', '" . addslashes($senha) . "', '" . addslashes($perfil) . "')";

                // Registra a ação no log, incluindo a descrição das mudanças e os dados novos
                registrarLog($conn, $usuarioLogado, 'Inserção de Usuário', 'usuarios', $comandoSqlCompleto, '', $descricaoMudancas);

                $mensagem = "Usuário cadastrado com sucesso!";
                // Limpa os campos
                $usuario = "";
                $senha = "";
                $confirmarSenha = "";
                $perfil = "vendedor";
            } else {
                $mensagem = "Erro ao cadastrar usuário!";
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
    <title>Cadastrar Usuário</title>
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
            background-color: #2c3e50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .cadastro-box input[type="submit"]:hover {
            background-color: #6F6A6A;
        }

        .mensagem {
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
            color: <?php echo ($mensagem === "Usuário cadastrado com sucesso!") ? 'green' : 'red'; ?>;
        }

        h2 {
            background-color: white;
            color: black;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="cadastro-box">
            <h2>Cadastrar Usuário</h2>
            <?php if (!empty($mensagem)): ?>
                <p class="mensagem"><?php echo $mensagem; ?></p>
            <?php endif; ?>
            <form action="painel.php?page=cadastrar_usuario" method="post">
                <label for="usuario">Usuário:</label>
                <input type="text" id="usuario" name="usuario" placeholder="Digite o nome do usuário" required value="<?php echo htmlspecialchars($usuario); ?>">

                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" placeholder="Digite a senha" required>

                <label for="confirmar_senha">Confirmar Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme a senha" required>

                <label for="perfil">Permissão:</label>
                <select id="perfil" name="perfil">
                    <option value="vendedor" <?php echo ($perfil === 'vendedor') ? 'selected' : ''; ?>>Vendedor</option>
                    <option value="admin" <?php echo ($perfil === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                </select>

                <input type="submit" value="Cadastrar">
            </form>
        </div>
    </div>
</body>

</html>