<?php
include 'includes/conexao.php'; // Inclua sua conexão com o banco de dados
include 'funcoes_log.php'; // Inclua a função registrarLog()

if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

$usuarioLogado = $_SESSION['usuario'];
$usuario_id = $_SESSION['usuario_id'];

// Verificar se a venda foi cadastrada com sucesso e exibir o modal
$showModal = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $produto_id = $_POST['produto_id'];
    $cliente_id = $_POST['cliente_id'];
    $quantidade = $_POST['quantidade'];
    $preco_total = $_POST['preco_total'];
    $data_venda = $_POST['data_venda'];

    try {
        // Verificar se a quantidade em estoque é suficiente
        $stmt = $conn->prepare("SELECT quantidade FROM produtos WHERE id = :produto_id");
        $stmt->bindParam(':produto_id', $produto_id);
        $stmt->execute();
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($produto && $produto['quantidade'] >= $quantidade) {
            // Subtrair a quantidade vendida do estoque
            $novaQuantidade = $produto['quantidade'] - $quantidade;
            $stmt = $conn->prepare("UPDATE produtos SET quantidade = :novaQuantidade WHERE id = :produto_id");
            $stmt->bindParam(':novaQuantidade', $novaQuantidade);
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->execute();

            // Inserir a venda no banco de dados
            $stmt = $conn->prepare("INSERT INTO vendas (produto_id, cliente_id, quantidade, preco_total, data_venda, usuario_id) 
                    VALUES (:produto_id, :cliente_id, :quantidade, :preco_total, :data_venda, :usuario_id)");
            $stmt->bindParam(':produto_id', $produto_id);
            $stmt->bindParam(':cliente_id', $cliente_id);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':preco_total', $preco_total);
            $stmt->bindParam(':data_venda', $data_venda);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->execute();

            // Dados novos para o log
            $descricaoMudancas = "Venda registrada: Produto ID $produto_id, Cliente ID $cliente_id, Quantidade $quantidade, Preço Total R$ $preco_total, Data da Venda $data_venda, Usuário ID $usuario_id.";

            // Registra a ação no log
            $comandoSqlCompleto = "INSERT INTO vendas (produto_id, cliente_id, quantidade, preco_total, data_venda, usuario_id) 
                                   VALUES ('$produto_id', '$cliente_id', '$quantidade', '$preco_total', '$data_venda', '$usuario_id')";
            registrarLog($conn, $usuarioLogado, 'Inserção', 'vendas', $comandoSqlCompleto, '', $descricaoMudancas);

            $showModal = true;
        } else {
            echo "<script>alert('Erro: Quantidade em estoque insuficiente!');</script>";
        }
    } catch (PDOException $e) {
        echo 'Erro: ' . $e->getMessage();
    }
}

// Buscar produtos e clientes ATIVOS
try {
    $stmt = $conn->query("SELECT id, nome, preco FROM produtos WHERE situacao = 'ativo'");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT id, nome FROM clientes WHERE situacao = 'ativo'");
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
            background-color: #2c3e50;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #6f6a6a;
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

    <body>
        <h1>Cadastrar Vendas</h1>
        <form method="post" enctype="multipart/form-data">
            <label for="produto_id">Produto:</label>
            <select name="produto_id" id="produto_id" required>
                <option value="">Selecione um produto</option>
                <?php foreach ($produtos as $produto): ?>
                    <option value="<?php echo $produto['id']; ?>" data-preco="<?php echo $produto['preco']; ?>">
                        <?php echo htmlspecialchars($produto['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="cliente_id">Cliente:</label>
            <select name="cliente_id" id="cliente_id" required>
                <option value="">Selecione um cliente</option>
                <?php foreach ($clientes as $cliente): ?>
                    <option value="<?php echo $cliente['id']; ?>"><?php echo $cliente['nome']; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="quantidade">Quantidade:</label>
            <input type="number" name="quantidade" id="quantidade" placeholder="Quantidade" required min="1">

            <label for="preco_total">Preço Total:</label>
            <input type="number" name="preco_total" id="preco_total" placeholder="Preço Total" required readonly>

            <label for="data_venda">Data da Venda:</label>
            <input type="date" name="data_venda" id="data_venda" required>

            <button class="btn" type="submit">Cadastrar Venda</button>
        </form>

        <!-- Modal de sucesso -->
        <div id="successModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">×</span>
                <p>Venda cadastrada com sucesso!</p>
            </div>
        </div>

        <script>
            let precoProduto = 0;
            let quantidadeEstoque = 0;

            function atualizarPrecoTotal() {
                const produtoSelect = document.getElementById('produto_id');
                const precoTotalInput = document.getElementById('preco_total');
                const quantidadeInput = document.getElementById('quantidade');
                const selectedOption = produtoSelect.options[produtoSelect.selectedIndex];
                precoProduto = parseFloat(selectedOption.dataset.preco);
                quantidadeEstoque = parseInt(selectedOption.dataset.quantidade, 10);

                // Define o máximo da quantidade como a quantidade em estoque
                quantidadeInput.setAttribute('max', quantidadeEstoque);

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

                // Verifica se a quantidade excede o estoque
                if (quantidade > quantidadeEstoque) {
                    alert(`A quantidade em estoque é de ${quantidadeEstoque} unidades.`);
                    quantidadeInput.value = quantidadeEstoque; // Define a quantidade máxima permitida
                }

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

            document.addEventListener('DOMContentLoaded', function() {
                definirDataAtual();

                document.getElementById('produto_id').addEventListener('change', function() {
                    atualizarPrecoTotal();
                    buscarInformacoesProduto();
                });

                document.getElementById('quantidade').addEventListener('input', function() {
                    calcularPrecoTotal();
                });

                <?php if ($showModal): ?>
                    document.getElementById('successModal').style.display = 'flex';
                <?php endif; ?>
            });

            function closeModal() {
                document.getElementById('successModal').style.display = 'none';
                window.location.href = 'painel.php?page=cadastro_vendas';
            }
        </script>
    </body>

</html>