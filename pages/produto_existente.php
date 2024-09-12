<?php
include 'includes/conexao.php';

// Verifica se o ID do produto foi passado
if (!isset($_GET['produto_id'])) {
    echo "Produto não encontrado!";
    exit();
}

$produto_id = $_GET['produto_id'];

// Buscar dados do produto
$sqlProduto = "SELECT * FROM produtos WHERE id = :produto_id";
$stmtProduto = $conn->prepare($sqlProduto);
$stmtProduto->bindParam(':produto_id', $produto_id);
$stmtProduto->execute();
$produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "Produto não encontrado!";
    exit();
}

// Buscar fornecedores ativos
$sqlFornecedores = "SELECT id, nome FROM fornecedores WHERE situacao = 'ativo'";
$stmtFornecedores = $conn->query($sqlFornecedores);
$fornecedores = $stmtFornecedores->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $preco = $_POST["preco"];
    $quantidade = $_POST["quantidade"];
    $fornecedor_id = $_POST["fornecedor_id"];

    // Atualiza o produto existente
    $sqlAtualiza = "UPDATE produtos SET preco = :preco, quantidade = :quantidade, fornecedor_id = :fornecedor_id WHERE id = :produto_id";
    $stmtAtualiza = $conn->prepare($sqlAtualiza);
    $stmtAtualiza->bindParam(':preco', $preco);
    $stmtAtualiza->bindParam(':quantidade', $quantidade);
    $stmtAtualiza->bindParam(':fornecedor_id', $fornecedor_id);
    $stmtAtualiza->bindParam(':produto_id', $produto_id);

    if ($stmtAtualiza->execute()) {
        echo "Produto atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o produto!";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Editar Produto</title>
    <link rel="stylesheet" href="estilos/estilos.css">
</head>

<body>
    <h2>Editar Produto</h2>

    <form method="post">
        <label for="produto">Produto:</label>
        <select id="produto" name="produto_id" disabled>
            <option value="<?php echo $produto['id']; ?>"><?php echo $produto['nome']; ?></option>
        </select>

        <label for="preco">Preço:</label>
        <input type="number" id="preco" name="preco" value="<?php echo $produto['preco']; ?>" step="0.01" required>

        <label for="quantidade">Quantidade:</label>
        <input type="number" id="quantidade" name="quantidade" value="<?php echo $produto['quantidade']; ?>" required>

        <label for="fornecedor_id">Fornecedor:</label>
        <select id="fornecedor_id" name="fornecedor_id" required>
            <?php foreach ($fornecedores as $fornecedor) { ?>
                <option value="<?php echo $fornecedor['id']; ?>" <?php echo ($fornecedor['id'] == $produto['fornecedor_id']) ? 'selected' : ''; ?>>
                    <?php echo $fornecedor['nome']; ?>
                </option>
            <?php } ?>
        </select>

        <input type="submit" value="Salvar">
    </form>
</body>

</html>