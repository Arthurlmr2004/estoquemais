<?php
include 'conexao.php';

// Inicialmente, não exibe o modal
$showModal = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obter dados do formulário
    $nome = $_POST["nome"];
    $cpf = $_POST["cpf"];
    $telefone = $_POST["telefone"];
    $email = $_POST["email"];
    $endereco = $_POST["endereco"];

    // Preparar e executar a consulta SQL para inserir o novo cliente
    $sql = "INSERT INTO clientes (nome, cpf, telefone, email, endereco) VALUES (:nome, :cpf, :telefone, :email, :endereco)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':endereco', $endereco);

    try {
        $stmt->execute();
        // Exibir o modal somente após a inserção bem-sucedida
        $showModal = true;
    } catch (PDOException $e) {
        echo "Erro ao cadastrar o cliente: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Cadastro de Cliente</title>
    <link rel="stylesheet" href="estilos/estilos.css">
    <style>
        /* Estilos para a janela modal */
        .modal {
            display: <?php echo $showModal ? 'block' : 'none'; ?>;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 40%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <h2>Cadastrar Cliente</h2>

    <form method="post">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" placeholder="Digite o nome completo" required>

        <label for="cpf">CPF:</label>
        <input type="text" id="cpf" name="cpf" placeholder="Digite o CPF" required>

        <label for="telefone">Telefone:</label>
        <input type="text" id="telefone" name="telefone" placeholder="Digite o telefone" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="Digite o email" required>

        <label for="endereco">Endereço:</label>
        <input type="text" id="endereco" name="endereco" placeholder="Digite o endereço" required>

        <input type="submit" value="Cadastrar">
        <input type="submit" value="Voltar" onclick="window.location.href='painel.php'">
    </form>

    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p>Cliente cadastrado com sucesso!</p>
        </div>
    </div>

    <script>
        // Script para exibir a janela modal
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        // Mostrar a janela modal se a variável PHP $showModal for true
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($showModal): ?>
                document.getElementById('successModal').style.display = 'flex';
            <?php endif; ?>
        });
    </script>

    <?php include 'includes/footer.php'; ?>
</body>

</html>