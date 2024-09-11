<?php
include 'includes/conexao.php'; // Conexão com o banco

if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

$mensagemErro = "";
$compras = [];
$cpf = ""; // Inicializa o CPF

// Função para paginação
function paginarResultados($totalRegistros, $itensPorPagina, $paginaAtual = 1, $paginaBaseUrl = '', $cpf = '')
{
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    $paginacaoHTML = '<div class="paginacao">';

    // Botão "Anterior"
    if ($paginaAtual > 1) {
        $paginaAnterior = $paginaAtual - 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaAnterior&cpf=$cpf'><i class='fas fa-chevron-left'></i></a>";
    }

    // Limite de páginas visíveis
    $limite = 2; // Quantidade de páginas para mostrar ao lado da página atual

    // Determinar quais páginas exibir ao redor da página atual
    $inicio = max(1, $paginaAtual - $limite);
    $fim = min($totalPaginas, $paginaAtual + $limite);

    // Links para as páginas
    for ($i = $inicio; $i <= $fim; $i++) {
        if ($i == $paginaAtual) {
            $paginacaoHTML .= "<span class='pagina-atual'>$i</span>";
        } else {
            $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$i&cpf=$cpf'>$i</a>";
        }
    }

    // Botão "Próximo"
    if ($paginaAtual < $totalPaginas) {
        $paginaProxima = $paginaAtual + 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaProxima&cpf=$cpf'><i class='fas fa-chevron-right'></i></a>";
    }

    $paginacaoHTML .= '</div>';
    return $paginacaoHTML;
}

// Parâmetros de paginação
$itensPorPagina = 3; // Quantidade de compras por página
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Verifica se o CPF foi enviado via POST ou GET
if (isset($_POST['cpf'])) {
    $cpf = $_POST['cpf']; // CPF vindo do formulário
} elseif (isset($_GET['cpf'])) {
    $cpf = $_GET['cpf']; // CPF vindo da URL para paginação
}

if (!empty($cpf)) {
    // Busca o ID do cliente com base no CPF
    $sqlCliente = "SELECT id FROM clientes WHERE cpf = :cpf";
    $stmtCliente = $conn->prepare($sqlCliente);
    $stmtCliente->bindParam(':cpf', $cpf);
    $stmtCliente->execute();
    $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    // Verifica se o cliente existe
    if ($cliente) {
        $clienteId = $cliente['id'];

        // Contagem total de compras para paginação
        $sqlTotal = "SELECT COUNT(*) FROM vendas WHERE cliente_id = :clienteId";
        $stmtTotal = $conn->prepare($sqlTotal);
        $stmtTotal->bindParam(':clienteId', $clienteId);
        $stmtTotal->execute();
        $totalCompras = $stmtTotal->fetchColumn();

        // Busca as compras do cliente com paginação
        // Busca as compras do cliente com nome do cliente e nome do usuário
        $sqlCompras = "SELECT v.data_venda, v.preco_total, p.nome AS produto, c.nome AS cliente, u.usuario AS vendedor
        FROM vendas v
        JOIN produtos p ON v.produto_id = p.id
        JOIN clientes c ON v.cliente_id = c.id
        JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.cliente_id = :clienteId
        LIMIT :itensPorPagina OFFSET :offset";

        $stmtCompras = $conn->prepare($sqlCompras);
        $stmtCompras->bindParam(':clienteId', $clienteId);
        $stmtCompras->bindParam(':itensPorPagina', $itensPorPagina, PDO::PARAM_INT);
        $stmtCompras->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmtCompras->execute();
        $compras = $stmtCompras->fetchAll(PDO::FETCH_ASSOC);

        // Verifica se o cliente possui compras
        if (empty($compras)) {
            $mensagemErro = "Nenhuma compra encontrada para este CPF.";
        }
    } else {
        $mensagemErro = "Nenhum cliente encontrado com este CPF.";
    }
} else {
    $mensagemErro = "Por favor, insira um CPF válido.";
}

// Gerar a URL base para a paginação
$paginaBaseUrl = 'painel.php?page=ver_compras';

// Gerar o HTML da paginação
if (!empty($compras)) {
    $paginacaoHTML = paginarResultados($totalCompras, $itensPorPagina, $paginaAtual, $paginaBaseUrl, $cpf);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Compras</title>
    <link rel="stylesheet" href="../estilos/estilos.css">
    <style>
        .box {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: auto;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        h2 {
            background-color: white;

        }

        form {
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            color: #333;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;


        }

        input[type="submit"] {
            background-color: #2c3e50 !important;
            color: black;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #6f6a6a !important;
            color: #fff;
        }

        .compras-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .compras-table th,
        .compras-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .compras-table th {
            background-color: #2c3e50;
            color: white;
        }

        .compras-table td {
            background-color: #f9f9f9;
        }

        .button-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2c3e50;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }

        .button-back:hover {
            background-color: #6f6a6a;
            color: #fff;
        }

        /* Estilização da mensagem de erro */
        .erro {
            color: red;
            font-weight: bold;
        }

        /* Estilização da paginação */
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
            background-color: white;
            border: 1px solid #ddd;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-weight: bold;
        }

        .paginacao a {
            color: #000;
            /* Cor do link */
        }

        .paginacao a:hover {
            background-color: #6f6a6a;
            /* Cor de fundo no hover */
            color: #fff;
            /* Cor do texto no hover */
        }

        .paginacao .pagina-atual {
            /* Estilo para a página atual */
            font-weight: bold;
            color: #fff;
            background-color: #6f6a6a;
        }
    </style>
</head>

<body>
    <div class="box">
        <h2>Ver Compras</h2>
        <form method="POST" action="">
            <label for="cpf">Digite o CPF do Cliente:</label>
            <input type="text" name="cpf" id="cpf" value="<?php echo htmlspecialchars($cpf); ?>" required>
            <input type="submit" value="Buscar Compras">
        </form>

        <?php if ($mensagemErro): ?>
            <p class="erro"><?php echo htmlspecialchars($mensagemErro); ?></p>
        <?php endif; ?>

        <?php if (!empty($compras)): ?>
            <table class="compras-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Produto</th>
                        <th>Nome do Cliente</th>
                        <th>Nome do Vendedor</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compras as $compra): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($compra['data_venda']); ?></td>
                            <td><?php echo htmlspecialchars($compra['produto']); ?></td>
                            <td><?php echo htmlspecialchars($compra['cliente']); ?></td>
                            <td><?php echo htmlspecialchars($compra['vendedor']); ?></td>
                            <td><?php echo htmlspecialchars($compra['preco_total']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php echo $paginacaoHTML; ?>
        <?php endif; ?>
    </div>
</body>

</html>