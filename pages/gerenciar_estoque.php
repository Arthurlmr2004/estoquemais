<?php
include 'conexao.php';

// Função para paginação
function paginarResultados($totalRegistros, $itensPorPagina, $paginaAtual = 1, $paginaBaseUrl = '')
{
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    $paginacaoHTML = '<div class="paginacao">';

    // Botão "Anterior"
    if ($paginaAtual > 1) {
        $paginaAnterior = $paginaAtual - 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaAnterior'>&laquo; Anterior</a>";
    }

    // Links para as páginas
    for ($i = 1; $i <= $totalPaginas; $i++) {
        if ($i == $paginaAtual) {
            $paginacaoHTML .= "<span class='pagina-atual'>$i</span>";
        } else {
            $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$i'>$i</a>";
        }
    }

    // Botão "Próximo"
    if ($paginaAtual < $totalPaginas) {
        $paginaProxima = $paginaAtual + 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaProxima'>Próximo &raquo;</a>";
    }

    $paginacaoHTML .= '</div>';
    return $paginacaoHTML;
}


// Parâmetros da paginação
$itensPorPagina = 3; // Quantidade de itens por página
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Consulta SQL para buscar produtos com paginação
$sql = "SELECT id, nome, descricao, preco, quantidade, estoque_minimo, estoque_maximo 
        FROM produtos 
        LIMIT :itensPorPagina OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':itensPorPagina', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para contar o total de produtos (para paginação)
$sqlTotal = "SELECT COUNT(*) FROM produtos";
$totalProdutos = $conn->query($sqlTotal)->fetchColumn();

// Gerar a URL base para a paginação
$paginaBaseUrl = 'painel.php?page=gerenciar_estoque';

// Gerar o HTML da paginação
$paginacaoHTML = paginarResultados($totalProdutos, $itensPorPagina, $paginaAtual, $paginaBaseUrl);

// Atualização de quantidade (permanece o mesmo)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar'])) {
    $produto_id = $_POST['produto_id'];
    $nova_quantidade = $_POST['quantidade'];

    // Preparar e executar a consulta SQL para atualizar a quantidade
    $sqlUpdate = "UPDATE produtos SET quantidade = :quantidade WHERE id = :produto_id";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':quantidade', $nova_quantidade);
    $stmtUpdate->bindParam(':produto_id', $produto_id);

    try {
        $stmtUpdate->execute();
        echo "<p>Quantidade atualizada com sucesso!</p>";
    } catch (PDOException $e) {
        echo "Erro ao atualizar a quantidade: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Estoque</title>
    <link rel="stylesheet" href="estilos/estilos.css">
    <style>
        .paginacao {
            text-align: center;
            margin: 20px 0;
        }

        .paginacao a,
        .paginacao span {
            display: inline-block;
            padding: 10px 15px;
            margin: 0 5px;
            border-radius: 5px;
            text-decoration: none;
            color: #007bff;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .paginacao a:hover {
            background-color: #007bff;
            color: #fff;
        }

        .paginacao .pagina-atual {
            font-weight: bold;
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }

        .botao-voltar {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .botao-voltar:hover {
            background-color: #0056b3;
        }

        .navegacao {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn{
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover{
            background-color: #0056b3;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <h2>Gerenciar Estoque</h2>

    <!-- Botão Voltar e Navegação de Paginação acima da tabela -->
    <div class="navegacao">
        <?php echo $paginacaoHTML; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nome do Produto</th>
                <th>Descrição</th>
                <th>Preço</th>
                <th>Quantidade Atual</th>
                <th>Estoque Mínimo</th>
                <th>Estoque Máximo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                    <td><?php echo htmlspecialchars($produto['descricao']); ?></td>
                    <td><?php echo htmlspecialchars($produto['preco']); ?></td>
                    <td><?php echo htmlspecialchars($produto['quantidade']); ?></td>
                    <td><?php echo htmlspecialchars($produto['estoque_minimo']); ?></td>
                    <td><?php echo htmlspecialchars($produto['estoque_maximo']); ?></td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                            <input type="number" placeholder="Quantidade" name="quantidade" min="0" required>
                            <button type="submit" class="btn" name="atualizar">Atualizar</button>
                        </form>
                        <?php if ($produto['quantidade'] < $produto['estoque_minimo']): ?>
                            <p style="color: red;">Estoque abaixo do mínimo!</p>
                        <?php endif; ?>
                        <?php if ($produto['quantidade'] > $produto['estoque_maximo']): ?>
                            <p style="color: orange;">Estoque acima do máximo!</p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php include 'includes/footer.php'; ?>

</body>

</html>
