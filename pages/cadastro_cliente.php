<?php
include 'includes/conexao.php';
include 'funcoes_log.php';

if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: pages/nao_autorizado.php');
    exit();
}

$usuarioLogado = $_SESSION['usuario'];
$showModal = false;
$errorModal = false;
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $cpf = $_POST["cpf"];
    $telefone = $_POST["telefone"];
    $email = $_POST["email"];
    $cep = $_POST["cep"];

    // Remove caracteres especiais do CPF para armazenamento
    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);

    // Validação de e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorModal = true;
        $errorMessage = 'Email inválido!';
    } else {
        // Verifica se o CPF ou e-mail já está cadastrado
        $sqlSelect = "SELECT * FROM clientes WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpfLimpo OR email = :email";
        $stmtSelect = $conn->prepare($sqlSelect);
        $stmtSelect->bindParam(':cpfLimpo', $cpfLimpo);
        $stmtSelect->bindParam(':email', $email);
        $stmtSelect->execute();
        $clienteAntigo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        if ($clienteAntigo) {
            $errorModal = true;
            $errorMessage = 'O CPF ou email informado já está cadastrado!';
        } else {
            // Consome a API para buscar o endereço a partir do CEP
            $url = "https://viacep.com.br/ws/$cep/json/";
            $response = @file_get_contents($url);
            $dadosEndereco = json_decode($response, true);

            if (!$response || isset($dadosEndereco['erro'])) {
                $errorModal = true;
                $errorMessage = 'CEP inválido ou não encontrado!';
            } else {
                $endereco = $dadosEndereco['logradouro'] . ', ' . $dadosEndereco['bairro'] . ', ' . $dadosEndereco['localidade'] . ' - ' . $dadosEndereco['uf'];

                $sql = "INSERT INTO clientes (nome, cpf, telefone, email, endereco) VALUES (:nome, :cpf, :telefone, :email, :endereco)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':cpf', $cpfLimpo); // Armazena o CPF limpo
                $stmt->bindParam(':telefone', $telefone);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':endereco', $endereco);

                try {
                    $stmt->execute();

                    $comandoSqlCompleto = "INSERT INTO clientes (nome, cpf, telefone, email, endereco) VALUES ('" . addslashes($nome) . "', '" . addslashes($cpfLimpo) . "', '" . addslashes($telefone) . "', '" . addslashes($email) . "', '" . addslashes($endereco) . "')";

                    $descricaoMudancas = "Novo cliente cadastrado: Nome: $nome, CPF: $cpf, Telefone: $telefone, Email: $email, Endereço: $endereco.";

                    // Registra a ação no log
                    registrarLog($conn, $usuarioLogado, 'Inserção', 'clientes', $comandoSqlCompleto, '', $descricaoMudancas);
                    $showModal = true;
                } catch (PDOException $e) {
                    $errorModal = true;
                    $errorMessage = "Erro ao cadastrar o cliente: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Cadastro de Cliente</title>
    <link rel="stylesheet" href="estilos/estilos.css">
    <style>
        .modal {
            display: none;
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

        <label for="cep">CEP:</label>
        <input type="text" id="cep" name="cep" placeholder="Digite o CEP" required>
        <p id="enderecoResultado"></p>

        <label for="endereco">Endereço:</label>
        <input type="text" id="endereco" name="endereco" placeholder="Endereço completo" readonly>

        <input type="submit" value="Cadastrar">
        <input type="submit" value="Voltar" onclick="window.location.href='painel.php'">
    </form>

    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p>Cliente cadastrado com sucesso!</p>
        </div>
    </div>

    <div id="errorModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p>Erro: <?php echo $errorMessage; ?></p>
        </div>
    </div>

    <script>
        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
            document.getElementById('errorModal').style.display = 'none';
            window.location.href = 'painel.php?page=cadastro_cliente';
        }

        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($showModal): ?>
                document.getElementById('successModal').style.display = 'flex';
            <?php endif; ?>

            <?php if ($errorModal): ?>
                document.getElementById('errorModal').style.display = 'flex';
            <?php endif; ?>
        });

        // Função de validação de CPF
        function validarCPF(cpf) {
            cpf = cpf.replace(/[^\d]+/g, '');
            if (cpf.length !== 11) return false;
            if (/^(\d)\1+$/.test(cpf)) return false;
            let soma = 0;
            for (let i = 0; i < 9; i++) soma += parseInt(cpf.charAt(i)) * (10 - i);
            let resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.charAt(9))) return false;
            soma = 0;
            for (let i = 0; i < 10; i++) soma += parseInt(cpf.charAt(i)) * (11 - i);
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            return resto === parseInt(cpf.charAt(10));
        }

        function buscarEndereco(cep) {
            if (cep.length === 8 && !isNaN(cep)) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.erro) {
                            document.getElementById('endereco').value = '';
                            document.getElementById('enderecoResultado').innerText = 'CEP inválido!';
                        } else {
                            const endereco = `${data.logradouro}, ${data.bairro}, ${data.localidade} - ${data.uf}`;
                            document.getElementById('endereco').value = endereco;
                            document.getElementById('enderecoResultado').innerText = '';
                        }
                    })
                    .catch(error => {
                        document.getElementById('endereco').value = '';
                        document.getElementById('enderecoResultado').innerText = 'Erro ao buscar o endereço.';
                    });
            } else {
                document.getElementById('endereco').value = '';
                document.getElementById('enderecoResultado').innerText = '';
            }
        }

        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/[^\d]/g, ''); // Remove caracteres não numéricos
            buscarEndereco(cep);
        });

        document.querySelector('form').addEventListener('submit', function(event) {
            const cpf = document.getElementById('cpf').value;
            if (!validarCPF(cpf)) {
                alert('CPF inválido. Por favor, verifique o CPF informado.');
                event.preventDefault();
            }
        });
    </script>
</body>

</html>