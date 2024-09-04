<?php
include 'conexao.php';

// Processamento do formulário de entrada de produtos
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obter dados do formulário
    $produto_id = $_POST["produto_id"];
    $quantidade = $_POST["quantidade"];
    $data_entrada = date("Y-m-d");

    try {
        // Iniciar transação
        $conn->beginTransaction();

        // Inserir registro na tabela de entradas
        $sqlEntrada = "INSERT INTO entradas (produto_id, quantidade, data_entrada) VALUES (:produto_id, :quantidade, :data_entrada)";
        $stmtEntrada = $conn->prepare($sqlEntrada);
        $stmtEntrada->bindParam(':produto_id', $produto_id);
        $stmtEntrada->bindParam(':quantidade', $quantidade);
        $stmtEntrada->bindParam(':data_entrada', $data_entrada);
        $stmtEntrada->execute();

        // Atualizar a quantidade em estoque do produto
        $sqlUpdate = "UPDATE produtos SET quantidade = quantidade + :quantidade WHERE id = :produto_id";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':quantidade', $quantidade);
        $stmtUpdate->bindParam(':produto_id', $produto_id);
        $stmtUpdate->execute();

        // Commit da transação
        $conn->commit();

        echo "<p>Entrada de produtos registrada com sucesso!</p>";
    } catch (PDOException $e) {
        // Rollback da transação em caso de erro
        $conn->rollback();
        echo "Erro ao registrar a entrada de produtos: " . $e->getMessage();
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
    <title>Entrada de Produtos</title>
    <link rel="stylesheet" href="estilos/estilos.css">
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <h2>Registrar Entrada de Produtos</h2>

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

        <input type="submit" value="Registrar Entrada">
        <input type="submit" value="Voltar" onclick="window.location.href='painel.php'"></input>
    </form>

    <?php include 'includes/footer.php'; ?>
</body>

</html>