<?php
include 'conexao.php';

// Inicialmente, não exibe o modal
$showModal = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obter dados do formulário
    $nome = $_POST["nome"];
    $endereco = $_POST["endereco"];
    $telefone = $_POST["telefone"];
    $email = $_POST["email"];
    $cnpj = $_POST["cnpj"];
    $situacao = 'ativo'; // Define a situacao como 'ativo' por padrão

    try {
        // Preparar e executar a consulta SQL
        $sql = "INSERT INTO fornecedores (nome, endereco, telefone, email, cnpj, situacao) VALUES (:nome, :endereco, :telefone, :email, :cnpj, :situacao)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':cnpj', $cnpj);
        $stmt->bindParam(':situacao', $situacao);

        if ($stmt->execute()) {
            // Cadastro bem-sucedido
            $showModal = true;
        } else {
            echo "Erro ao cadastrar o fornecedor.";
        }
    } catch (PDOException $e) {
        echo "Erro ao cadastrar o fornecedor: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Cadastro de Fornecedor</title>
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

    <h2>Cadastrar Fornecedor</h2>

    <form method="post" id="cadastroForm">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" placeholder="Nome do fornecedor" required>

        <label for="endereco">Endereço:</label>
        <input type="text" id="endereco" name="endereco" placeholder="Endereço completo" required>

        <label for="telefone">Telefone:</label>
        <input type="text" id="telefone" name="telefone" placeholder="Número de telefone" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="Endereço de e-mail" required>

        <label for="cnpj">CNPJ:</label>
        <input type="text" name="cnpj" id="cnpj" placeholder="Número do CNPJ" required>

        <input type="hidden" name="situacao" id="situacao" value="ativo">

        <input type="submit" value="Cadastrar">
        <input type="submit" value="Voltar" onclick="window.location.href='painel.php'">
    </form>

    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p>Fornecedor cadastrado com sucesso!</p>
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