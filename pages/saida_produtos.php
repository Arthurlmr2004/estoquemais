<?php
include 'conexao.php';

// Inicialize as variáveis para controlar a exibição dos modais
$mostrarModalErro = false;
$mostrarModalSucesso = false;
$mensagemErro = "";
$mensagemSucesso = "";

// Processamento do formulário de saída de produtos
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obter dados do formulário
    $produto_id = $_POST["produto_id"];
    $quantidade = $_POST["quantidade"];
    $data_saida = date("Y-m-d");

    try {
        // Iniciar transação
        $conn->beginTransaction();

        // Verificar se há quantidade suficiente em estoque
        $sqlEstoque = "SELECT quantidade FROM produtos WHERE id = :produto_id";
        $stmtEstoque = $conn->prepare($sqlEstoque);
        $stmtEstoque->bindParam(':produto_id', $produto_id);
        $stmtEstoque->execute();
        $estoque = $stmtEstoque->fetchColumn();

        if ($estoque >= $quantidade) {
            // Inserir registro na tabela de saídas
            $sqlSaida = "INSERT INTO saidas (produto_id, quantidade, data_saida) VALUES (:produto_id, :quantidade, :data_saida)";
            $stmtSaida = $conn->prepare($sqlSaida);
            $stmtSaida->bindParam(':produto_id', $produto_id);
            $stmtSaida->bindParam(':quantidade', $quantidade);
            $stmtSaida->bindParam(':data_saida', $data_saida);
            $stmtSaida->execute();

            // Atualizar a quantidade em estoque do produto
            $sqlUpdate = "UPDATE produtos SET quantidade = quantidade - :quantidade WHERE id = :produto_id";
            $stmtUpdate = $conn->prepare($sqlUpdate);
            $stmtUpdate->bindParam(':quantidade', $quantidade);
            $stmtUpdate->bindParam(':produto_id', $produto_id);
            $stmtUpdate->execute();

            // Commit da transação
            $conn->commit();

            // Atualize as variáveis para mostrar o modal de sucesso
            $mostrarModalSucesso = true;
            $mensagemSucesso = "Saída de produtos registrada com sucesso!";
        } else {
            // Atualize as variáveis para mostrar o modal de erro
            $mostrarModalErro = true;
            $mensagemErro = "Erro: Quantidade em estoque insuficiente para este produto.";
        }
    } catch (PDOException $e) {
        // Rollback da transação em caso de erro
        $conn->rollback();
        $mensagemErro = "Erro ao registrar a saída de produtos: " . $e->getMessage();
        $mostrarModalErro = true;
    }
}

// Consulta SQL para buscar produtos
$sqlProdutos = "SELECT id, nome FROM produtos";
$stmtProdutos = $conn->query($sqlProdutos);
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>

<head>
    <title>Saída de Produtos</title>
    <link rel="stylesheet" href="estilos/estilos.css">
    <style>
        /* Estilo para o modal */
        .modal {
            display: none; /* Inicialmente oculto */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Fundo semitransparente */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%;
            text-align: center;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
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

    <h2>Registrar Saída de Produtos</h2>

    <form method="post">
        <label for="produto_id">Produto:</label>
        
        <select id="produto_id" name="produto_id" required>
            <option value="">Selecione um produto</option>
            <?php foreach ($produtos as $produto): ?>
                <option value="<?php echo $produto['id']; ?>"><?php echo $produto['nome']; ?></option>
            <?php endforeach; ?>
        </select>

        <label for="quantidade">Quantidade:</label>
        <input type="number" id="quantidade" name="quantidade" placeholder="Quantidade" min="1" required>

        <input type="submit" value="Registrar Saída">
        <input type="submit" value="Voltar" onclick="window.location.href='painel.php'">
    </form>

    <!-- Modal de Erro -->
    <div id="modalErro" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalErro').style.display='none'">&times;</span>
            <p><?php echo $mensagemErro; ?></p>
        </div>
    </div>

    <!-- Modal de Sucesso -->
    <div id="modalSucesso" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modalSucesso').style.display='none'">&times;</span>
            <p><?php echo $mensagemSucesso; ?></p>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Mostrar o modal de erro ou sucesso se houver mensagens
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($mostrarModalErro): ?>
                document.getElementById('modalErro').style.display = 'block';
            <?php endif; ?>

            <?php if ($mostrarModalSucesso): ?>
                document.getElementById('modalSucesso').style.display = 'block';
            <?php endif; ?>
        });
    </script>
</body>

</html>
