<?php
include 'includes/conexao.php';
include 'funcoes_log.php'; // Inclua o arquivo com a função registrarLog()

// Verifica se o usuário está autenticado como admin ou vendedor
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

// Obtém o nome do usuário logado (assumindo que você armazena isso na sessão)
$usuarioLogado = $_SESSION['usuario'];

// Inicialmente, não exibe o modal
$showModal = false;
$showErrorModal = false;
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obter dados do formulário
    $nome = $_POST["nome"];
    $endereco = $_POST["endereco"];
    $telefone = $_POST["telefone"];
    $email = $_POST["email"];
    $cnpj = $_POST["cnpj"];
    $situacao = 'ativo';

    try {
        // Verificar se o CNPJ já existe
        $sqlCheck = "SELECT COUNT(*) FROM fornecedores WHERE cnpj = :cnpj";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bindParam(':cnpj', $cnpj);
        $stmtCheck->execute();
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            // CNPJ já existe
            $errorMessage = "Erro: Um fornecedor com este CNPJ já está cadastrado.";
            $showErrorModal = true;
        } else {
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
                // Para um log mais legível, descrever as mudanças
                $mudancas = [
                    "Nome: " . $nome,
                    "Endereço: " . $endereco,
                    "Telefone: " . $telefone,
                    "Email: " . $email,
                    "CNPJ: " . $cnpj,
                    "Situação: " . $situacao
                ];

                // Converter as mudanças para uma string
                $descricaoMudancas = implode("; ", $mudancas);

                // Cria o comando SQL completo para o log
                $comandoSqlCompleto = "INSERT INTO fornecedores (nome, endereco, telefone, email, cnpj, situacao) VALUES ('" . addslashes($nome) . "', '" . addslashes($endereco) . "', '" . addslashes($telefone) . "', '" . addslashes($email) . "', '" . addslashes($cnpj) . "', '" . addslashes($situacao) . "')";

                // Registra a ação no log, incluindo a descrição das mudanças e os dados novos
                registrarLog($conn, $usuarioLogado, 'Inserção de Fornecedor', 'fornecedores', $comandoSqlCompleto, '', $descricaoMudancas);

                $showModal = true;
                $showErrorModal = false;
            } else {
                $errorMessage = "Erro ao cadastrar o fornecedor.";
                $showErrorModal = true;
                $showModal = false;
            }
        }
    } catch (PDOException $e) {
        $errorMessage = "Erro ao cadastrar o fornecedor: " . $e->getMessage();
        $showErrorModal = true;
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

        h2 {

            color: black;
        }
    </style>
</head>

<body>
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

    <?php if ($showModal): ?>
        <div id="successModal" class="modal" style="display:block;">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">×</span>
                <p>Fornecedor cadastrado com sucesso!</p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($showErrorModal): ?> 
        <div id="errorModal" class="modal" style="display:block;">
            <div class="modal-content">
                <span class="close" onclick="closeErrorModal()">×</span>
                <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        </div>
    <?php endif; ?> 

    <script>
        // Script para exibir a janela modal
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        function closeErrorModal() {
            document.getElementById('errorModal').style.display = 'none';
        }

        // Mostrar a janela modal se a variável PHP $showModal for true
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($showModal): ?>
                document.getElementById('successModal').style.display = 'flex';
            <?php elseif ($showErrorModal): ?>
                document.getElementById('errorModal').style.display = 'flex';
            <?php endif; ?>
        });
    </script>
</body>

</html>