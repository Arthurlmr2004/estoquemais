<?php
include 'includes/conexao.php';

if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) { 
    header('Location: nao_autorizado.php');
    exit();
}

$mensagemErro = "";
$compras = [];
$cpf = ""; 

// Função para paginação (mantive a mesma lógica, apenas estilizei o HTML)
function paginarResultados($totalRegistros, $itensPorPagina, $paginaAtual = 1, $paginaBaseUrl = '', $cpf = '') {
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    $paginacaoHTML = '<div class="pagination">';

    if ($paginaAtual > 1) {
        $paginaAnterior = $paginaAtual - 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaAnterior&cpf=$cpf' class='page-link'>«</a>";
    }

    $limite = 2; 
    $inicio = max(1, $paginaAtual - $limite);
    $fim = min($totalPaginas, $paginaAtual + $limite);

    for ($i = $inicio; $i <= $fim; $i++) {
        $activeClass = ($i == $paginaAtual) ? 'active' : '';
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$i&cpf=$cpf' class='page-link {$activeClass}'>$i</a>";
    }

    if ($paginaAtual < $totalPaginas) {
        $paginaProxima = $paginaAtual + 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaProxima&cpf=$cpf' class='page-link'>»</a>";
    }

    $paginacaoHTML .= '</div>';
    return $paginacaoHTML;
}

// Parâmetros de paginação
$itensPorPagina = 3; 
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Verifica se o CPF foi enviado 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cpf'])) {
    $cpf = $_POST['cpf'];
} elseif (isset($_GET['cpf'])) {
    $cpf = $_GET['cpf'];
}

// ... (lógica para buscar as compras no banco de dados - sem alterações) ... 

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
        $sqlCompras = "SELECT v.id AS id_compra, v.data_venda, v.preco_total, p.nome AS produto 
                       FROM vendas v
                       JOIN produtos p ON v.produto_id = p.id
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
    <title>Histórico de Compras</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Courier New', Consolas, monospace;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        .container {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto; 
        }

        h2 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .search-container {
            display: flex;
            margin-bottom: 20px;
        }

        #cpf {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px 0 0 4px;
            width: 100%; 
            box-sizing: border-box; 
        }

        .search-button {
            background-color: #007bff; 
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: background-color 0.3s; 
        }

        .search-button:hover {
            background-color: #0056b3; 
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        .results-table th, .results-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .results-table th {
            background-color: #343a40; 
            color: white;
            font-weight: bold;
        }

        .results-table tbody tr:nth-child(even) {
            background-color: #f2f2f2; 
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin-bottom: 10px; 
        }

        /* Estilização da paginação */
        .pagination {
            display: flex;
            justify-content: center; 
            margin-top: 20px;
        }

        .pagination a.page-link {
            color: #007bff;
            padding: 6px 12px;
            border: 1px solid #ddd;
            margin: 0 4px; 
            border-radius: 4px;
            text-decoration: none; 
            transition: background-color 0.3s; 
        }

        .pagination a.page-link:hover {
            background-color: #e9ecef; 
        }

        .pagination a.page-link.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff; 
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Histórico de Compras</h2>

        <div class="search-container">
            <input type="text" id="cpf" name="cpf" placeholder="Digite o CPF do cliente (apenas números)" value="<?= $cpf ?>">
            <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
        </div>

        <?php if ($mensagemErro): ?>
            <p class="error-message"><?= $mensagemErro ?></p>
        <?php endif; ?>

        <?php if (!empty($compras)): ?>
            <table class="results-table">
                <thead>
                    <tr>
                        <th>ID da Compra</th>
                        <th>Data</th>
                        <th>Produto</th>
                        <th>Valor Total</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compras as $compra): ?>
                        <tr>
                            <td><?= $compra['id_compra'] ?></td>
                            <td><?= $compra['data_venda'] ?></td>
                            <td><?= $compra['produto'] ?></td>
                            <td><?= $compra['preco_total'] ?></td> 
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (isset($paginacaoHTML)) echo $paginacaoHTML; ?>
        <?php endif; ?>
        <a href="painel.php" class="button-back">Voltar</a>
    </div>

</body>
</html>