<?php
include 'includes/conexao.php';
include 'funcoes_log.php';

// Função para obter produtos e clientes
function obterProdutos($conn)
{
    $stmt = $conn->query("SELECT id, nome, preco FROM produtos WHERE situacao = 'ativo'");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obterClientes($conn)
{
    $stmt = $conn->query("SELECT id, nome FROM clientes WHERE situacao = 'ativo'");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Verifica se o usuário está autenticado
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

// Obtém o nome do usuário logado (assumindo que você armazena isso na sessão)
$usuarioLogado = $_SESSION['usuario'];
$usuario_id = $_SESSION['usuario_id'];

// Variável para controlar exibição do modal
$showModal = false;

// Lógica de submissão do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produto_id = $_POST['produto_id'];
    $cliente_id = $_POST['cliente_id'];
    $quantidade = $_POST['quantidade'];
    $preco_total = $_POST['preco_total'];
    $data_venda = $_POST['data_venda'];

    // Verificando se a data inserida está no ano atual
    $data_venda_timestamp = strtotime($data_venda);
    $ano_atual = date('Y');
    $data_minima = strtotime("$ano_atual-01-01");
    $data_maxima = strtotime("$ano_atual-12-31");

    if ($data_venda_timestamp < $data_minima || $data_venda_timestamp > $data_maxima) {
        echo "<script>alert('Erro: A data da venda deve estar dentro do ano atual.');</script>";
    } else {
        try {
            // Inserindo a venda no banco de dados
            $sql = "INSERT INTO vendas (produto_id, cliente_id, quantidade, preco_total, data_venda, usuario_id) 
                    VALUES (:produto_id, :cliente_id, :quantidade, :preco_total, :data_venda, :usuario_id)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':preco_total', $preco_total);
            $stmt->bindParam(':data_venda', $data_venda);
            $stmt->bindParam(':usuario_id', $usuario_id);

            if ($stmt->execute()) {
                // Cria uma descrição detalhada das mudanças para o log
                $mudancas = [
                    "Produto ID: $produto_id",
                    "Cliente ID: $cliente_id",
                    "Quantidade: $quantidade",
                    "Preço Total: R$ $preco_total",
                    "Data da Venda: $data_venda",
                ];

                // Converter as mudanças para uma string
                $descricaoMudancas = implode("; ", $mudancas);

                // Cria o comando SQL completo para o log
                $comandoSqlCompleto = "INSERT INTO vendas (produto_id, cliente_id, quantidade, preco_total, data_venda, usuario_id) VALUES ($produto_id, $cliente_id, $quantidade, $preco_total, '$data_venda', $usuario_id)";

                // Registra a ação no log, incluindo a descrição das mudanças
                registrarLog($conn, $usuarioLogado, 'Inserção', 'vendas', $comandoSqlCompleto, '', $descricaoMudancas);

                $showModal = true; // Mostrar modal ao cadastrar com sucesso
                
            } else {
                echo "Erro ao cadastrar venda.";
            }
           
        } catch (PDOException $e) {
            echo "Erro ao cadastrar venda: " . $e->getMessage();
        }
    }
}

$produtos = obterProdutos($conn);
$clientes = obterClientes($conn);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Venda</title>
    <link rel="stylesheet" href="../estilos/estilos.css">
    <style>
        .btn {
            display: block;
            margin: auto;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* Estilo do campo de data */
        input[type="date"] {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input[type="date"]:focus {
            border-color: #4CAF50;
            outline: none;
        }

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
            text-align: center;
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
    <h1>Cadastrar Vendas</h1>
    <form method="post" enctype="multipart/form-data">
        <label for="produto_id">Produto:</label>
        <select name="produto_id" id="produto_id" required>
            <?php foreach ($produtos as $produto): ?>
                <option value="<?php echo $produto['id']; ?>" data-preco="<?php echo $produto['preco']; ?>">
                    <?php echo $produto['nome']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="cliente_id">Cliente:</label>
        <select name="cliente_id" id="cliente_id" required>
            <?php foreach ($clientes as $cliente): ?>
                <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nome']; ?></option>
            <?php endforeach; ?>
        </select>

        <label for="quantidade">Quantidade:</label>
        <input type="number" name="quantidade" id="quantidade" placeholder="Quantidade" required>

        <label for="preco_produto" style="display: none;">Preço do Produto:</label>
        <input type="text" id="preco_produto" style="display: none;">

        <label for="preco_total">Preço Total:</label>
        <input type="number" name="preco_total" id="preco_total" placeholder="Preço Total" required readonly>

        <label for="data_venda">Data da Venda:</label>
        <input type="date" name="data_venda" id="data_venda" required>

        <button class="btn" type="submit">Cadastrar Venda</button>
    </form>

    <!-- Modal de sucesso -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p>Venda cadastrada com sucesso!</p>
        </div>
    </div>

    <script>
        function calcularPrecoTotal() {
            const precoInput = document.getElementById('preco_produto');
            const quantidadeInput = document.getElementById('quantidade');
            const precoTotalInput = document.getElementById('preco_total');

            const preco = parseFloat(precoInput.value);
            const quantidade = parseInt(quantidadeInput.value, 10);

            if (!isNaN(preco) && !isNaN(quantidade)) {
                precoTotalInput.value = (preco * quantidade).toFixed(2);
            } else {
                precoTotalInput.value = '0.00';
            }
        }

        function atualizarPreco() {
            const produtoSelect = document.getElementById('produto_id');
            const precoInput = document.getElementById('preco_produto');

            const selectedOption = produtoSelect.options[produtoSelect.selectedIndex];
            precoInput.value = selectedOption.dataset.preco;

            calcularPrecoTotal();
        }

        function validarData() {
            const dataVendaInput = document.getElementById('data_venda');
            const dataVenda = new Date(dataVendaInput.value);
            const hoje = new Date();

            if (dataVenda > hoje) {
                alert('A data da venda não pode ser futura.');
                return false;
            }
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('produto_id').addEventListener('change', atualizarPreco);
            document.getElementById('quantidade').addEventListener('input', calcularPrecoTotal);
            document.querySelector('form').addEventListener('submit', function(event) {
                if (!validarData()) {
                    event.preventDefault(); // Evita o envio do formulário se a data não for válida
                }
            });

            // Mostrar o modal se necessário
            <?php if ($showModal): ?>
                document.getElementById('successModal').style.display = 'flex';
            <?php endif; ?>
        });

        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        function validarData() {
            const dataVendaInput = document.getElementById('data_venda');
            const dataVenda = new Date(dataVendaInput.value);
            const anoAtual = new Date().getFullYear();

            // Verifica se a data está no ano atual
            if (dataVenda.getFullYear() !== anoAtual) {
                alert('A data da venda deve estar dentro do ano atual.');
                return false;
            }

            return true;
        }
        
    </script>
</body>

</html>