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
$itensPorPagina = isset($_POST['botao']) ? (int)$_POST['botao'] : (isset($_GET['itensPorPagina']) ? (int)$_GET['itensPorPagina'] : 5); // Pega o valor do botão ou define 5 como padrão
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Determinar qual aba exibir com base na URL
$aba = isset($_GET['aba']) ? $_GET['aba'] : 'estoque';

// Consulta SQL base para gerar os relatórios
$baseQuery = [
    'estoque' => "SELECT p.nome AS nome, p.descricao, p.quantidade, p.preco AS preco, f.nome AS fornecedor
                  FROM produtos p
                  JOIN fornecedores f ON p.fornecedor_id = f.id
                  LIMIT :itensPorPagina OFFSET :offset",
    'saidas' => "SELECT s.id AS id, s.data_saida, p.nome AS nome, s.quantidade, p.preco AS preco
                 FROM saidas s
                 JOIN produtos p ON s.produto_id = p.id
                 LIMIT :itensPorPagina OFFSET :offset",
    'entradas' => "SELECT e.id AS id, e.data_entrada, p.nome AS nome, e.quantidade, p.preco AS preco
                   FROM entradas e
                   JOIN produtos p ON e.produto_id = p.id
                   LIMIT :itensPorPagina OFFSET :offset"
];

// Contagem total de registros por aba
$totalQuery = [
    'estoque' => "SELECT COUNT(*) FROM produtos",
    'saidas' => "SELECT COUNT(*) FROM saidas",
    'entradas' => "SELECT COUNT(*) FROM entradas"
];

// Preparar e executar a consulta apropriada com base na aba ativa
try {
    $stmt = $conn->prepare($baseQuery[$aba]);
    $stmt->bindParam(':itensPorPagina', $itensPorPagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalRegistros = $conn->query($totalQuery[$aba])->fetchColumn();

    // Gerar a URL base para a paginação
    $paginaBaseUrl = "painel.php?page=relatorios&aba=$aba&itensPorPagina=$itensPorPagina";

    // Gerar o HTML da paginação
    $paginacaoHTML = paginarResultados($totalRegistros, $itensPorPagina, $paginaAtual, $paginaBaseUrl);
} catch (PDOException $e) {
    echo 'Erro na consulta: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Relatórios</title>
    <link rel="stylesheet" href="../estilos/estilos.css">
    <style>
        .paginacao {
            text-align: center;
            margin: 20px 0;
        }

        .form_itenspagina {
            background: transparent;
            box-shadow: 0 0 0 rgba(0, 0, 0, 0);
        }

        .paginacao a,
        .paginacao span {
            display: inline-block;
            padding: 10px 15px;
            margin: 0 5px;
            border-radius: 5px;
            text-decoration: none;
            color: #007bff;
            background-color: white;
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
            float: right;
        }

        .botao-voltar:hover {
            background-color: #0056b3;
        }

        .nav-tabs {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: center;
        }

        .nav-tabs li {
            display: inline;
        }

        .nav-tabs a {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 5px;
            text-decoration: none;
            color: #007bff;
            background-color: red;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .nav-tabs a.active,
        .nav-tabs a:hover {
            background-color: #007bff;
            color: #fff;
        }
    </style>
</head>

<body>
    <h2>Relatórios</h2>

    <ul class="nav-tabs">
        <li <?php if ($aba === 'estoque') echo 'class="active"'; ?>><a href="painel.php?page=relatorios&aba=estoque&itensPorPagina=<?php echo $itensPorPagina; ?>">Estoque</a></li>
        <li <?php if ($aba === 'saidas') echo 'class="active"'; ?>><a href="painel.php?page=relatorios&aba=saidas&itensPorPagina=<?php echo $itensPorPagina; ?>">Saídas</a></li>
        <li <?php if ($aba === 'entradas') echo 'class="active"'; ?>><a href="painel.php?page=relatorios&aba=entradas&itensPorPagina=<?php echo $itensPorPagina; ?>">Entradas</a></li>
    </ul>

    <!-- Form para enviar valor a variável $itensPorPagina e definir quantos registros vão aparecer por página -->
    <form class="form_itenspagina" method="post" action="painel.php?page=relatorios&aba=<?php echo $aba; ?>">
        <button class="itens_pagina" type="submit" name="botao" value="5">5</button>
        <button class="itens_pagina" type="submit" name="botao" value="10">10</button>
    </form>

    <div class="navegacao">
        <?php echo $paginacaoHTML; ?>
    </div>

    <div class="tab-content">
        <?php if ($aba === 'estoque'): ?>
            <h3>Relatório de Estoque</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nome do Produto</th>
                        <th>Descrição</th>
                        <th>Quantidade em Estoque</th>
                        <th>Preço</th>
                        <th>Fornecedor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantidade']); ?></td>
                            <td><?php echo htmlspecialchars($row['preco']); ?></td>
                            <td><?php echo htmlspecialchars($row['fornecedor']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($aba === 'saidas'): ?>
            <h3>Relatório de Saídas</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data de Saída</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['data_saida']); ?></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantidade']); ?></td>
                            <td><?php echo htmlspecialchars($row['preco']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($aba === 'entradas'): ?>
            <h3>Relatório de Entradas</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data de Entrada</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['data_entrada']); ?></td>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantidade']); ?></td>
                            <td><?php echo htmlspecialchars($row['preco']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <a href="painel.php"><button class="botao-voltar">Voltar</button></a>
</body>

</html>
