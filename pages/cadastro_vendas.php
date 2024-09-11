<?php
include 'includes/conexao.php'; // Inclua sua conexão com o banco de dados

// Verificar se a venda foi cadastrada com sucesso e exibir o modal
$showModal = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Adicionar lógica para processar o formulário e salvar no banco de dados
    $produto_id = $_POST['produto_id'];
    $cliente_id = $_POST['cliente_id'];
    $quantidade = $_POST['quantidade'];
    $preco_total = $_POST['preco_total'];
    $data_venda = $_POST['data_venda'];

    // Verifique se os dados estão corretos e insira no banco de dados
    try {
        $stmt = $conn->prepare("INSERT INTO vendas (produto_id, cliente_id, quantidade, preco_total, data_venda) VALUES (:produto_id, :cliente_id, :quantidade, :preco_total, :data_venda)");
        $stmt->bindParam(':produto_id', $produto_id);
        $stmt->bindParam(':cliente_id', $cliente_id);
        $stmt->bindParam(':quantidade', $quantidade);
        $stmt->bindParam(':preco_total', $preco_total);
        $stmt->bindParam(':data_venda', $data_venda);
        $stmt->execute();
        $showModal = true;
    } catch (PDOException $e) {
        // Gerenciar erro
        echo 'Erro: ' . $e->getMessage();
    }
}

// Buscar produtos e clientes
try {
    $stmt = $conn->query("SELECT id, nome, preco FROM produtos");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT id, nome FROM clientes");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Erro: ' . $e->getMessage();
}
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

        .info-produto {
            margin-top: 20px;
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

        <label for="preco_total">Preço Total:</label>
        <input type="number" name="preco_total" id="preco_total" placeholder="Preço Total" required readonly>

        <label for="data_venda">Data da Venda:</label>
        <input type="date" name="data_venda" id="data_venda" required>

        <button class="btn" type="submit">Cadastrar Venda</button>
    </form>

    <label for="info_produto">Informações do Produto:</label>
    <div id="info_produto" class="info-produto"></div>

    <!-- Modal de sucesso -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <p>Venda cadastrada com sucesso!</p>
        </div>
    </div>

    <script>
        let precoProduto = 0;

        function atualizarPrecoTotal() {
            const produtoSelect = document.getElementById('produto_id');
            const precoTotalInput = document.getElementById('preco_total');
            const selectedOption = produtoSelect.options[produtoSelect.selectedIndex];
            precoProduto = parseFloat(selectedOption.dataset.preco);
            if (!isNaN(precoProduto)) {
                precoTotalInput.value = precoProduto.toFixed(2);
            } else {
                precoTotalInput.value = '';
            }
        }

        function calcularPrecoTotal() {
            const precoTotalInput = document.getElementById('preco_total');
            const quantidadeInput = document.getElementById('quantidade');
            const quantidade = parseInt(quantidadeInput.value, 10);

            if (!isNaN(quantidade) && quantidade > 0) {
                precoTotalInput.value = (precoProduto * quantidade).toFixed(2);
            } else {
                precoTotalInput.value = precoProduto.toFixed(2);
            }
        }

        function definirDataAtual() {
            const dataVendaInput = document.getElementById('data_venda');
            const hoje = new Date();

            const dia = String(hoje.getDate()).padStart(2, '0');
            const mes = String(hoje.getMonth() + 1).padStart(2, '0');
            const ano = hoje.getFullYear();

            dataVendaInput.value = `${ano}-${mes}-${dia}`;
        }

        function buscarInformacoesProduto() {
            const produtoSelect = document.getElementById('produto_id');
            const infoProdutoDiv = document.getElementById('info_produto');
            const produtoId = produtoSelect.value;

            if (produtoId) {
                fetch(`?acao=info_produto&id=${produtoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            infoProdutoDiv.innerHTML = `<p>${data.error}</p>`;
                        } else {
                            infoProdutoDiv.innerHTML = `
                                <p><strong>Nome:</strong> ${data.nome}</p>
                                <p><strong>Preço:</strong> R$ ${data.preco}</p>
                                <p><strong>Descrição:</strong> ${data.descricao}</p>
                            `;
                        }
                    })
                    .catch(error => {
                        infoProdutoDiv.innerHTML = `<p>Erro ao buscar informações do produto.</p>`;
                    });
            } else {
                infoProdutoDiv.innerHTML = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            definirDataAtual();

            document.getElementById('produto_id').addEventListener('change', function () {
                atualizarPrecoTotal();
                buscarInformacoesProduto();
            });

            document.getElementById('quantidade').addEventListener('input', function () {
                calcularPrecoTotal();
            });

            <?php if ($showModal): ?>
                document.getElementById('successModal').style.display = 'block';
            <?php endif; ?>
        });

        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }
    </script>
</body>

</html>

<?php
// Verificar se é uma requisição AJAX para retornar as informações do produto
if (isset($_GET['acao']) && $_GET['acao'] == 'info_produto' && isset($_GET['id'])) {
    $produtoId = $_GET['id'];

    try {
        $stmt = $conn->prepare("SELECT nome, preco, descricao FROM produtos WHERE id = :id");
        $stmt->bindParam(':id', $produtoId);
        $stmt->execute();
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produto) {
            echo json_encode($produto);
        } else {
            echo json_encode(['error' => 'Produto não encontrado.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao buscar informações do produto.']);
    }

    exit;
}
?>
