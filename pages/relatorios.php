<?php
include 'includes/conexao.php';

if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

// Função para paginação (modificada para exibir 3 links por vez)
function paginarResultados($totalRegistros, $itensPorPagina, $paginaAtual = 1, $paginaBaseUrl = '')
{
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    $paginacaoHTML = '<div class="paginacao">';

    // Botão "Anterior"
    if ($paginaAtual > 1) {
        $paginaAnterior = $paginaAtual - 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaAnterior&itensPorPagina=$itensPorPagina'><i class='fas fa-chevron-left'></i></a>";
    }

    // Calcula o intervalo de páginas a serem exibidas
    $inicio = max(1, $paginaAtual - 1);
    $fim = min($totalPaginas, $paginaAtual + 1);

    // Links para as páginas
    for ($i = $inicio; $i <= $fim; $i++) {
        if ($i == $paginaAtual) {
            $paginacaoHTML .= "<span class='pagina-atual'>$i</span>";
        } else {
            $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$i'>$i</a>";
        }
    }

    // Botão "Próximo"
    if ($paginaAtual < $totalPaginas) {
        $paginaProxima = $paginaAtual + 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaProxima&itensPorPagina=$itensPorPagina'><i class='fas fa-chevron-right'></i></a>";
    }

    $paginacaoHTML .= '</div>';
    return $paginacaoHTML;
}

// Parâmetros da paginação
$itensPorPagina = isset($_GET['itensPorPagina']) ? (int)$_GET['itensPorPagina'] : 5; // Define 5 como padrão
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Determinar qual aba exibir com base na URL
$aba = isset($_GET['aba']) ? $_GET['aba'] : 'estoque';

// Consulta SQL base para gerar os relatórios (modificada - removida a aba 'produtos')
$baseQuery = [
    'estoque' => "SELECT p.nome AS nome, p.descricao, p.quantidade, p.preco AS preco, f.nome AS fornecedor
                  FROM produtos p
                  JOIN fornecedores f ON p.fornecedor_id = f.id
                  LIMIT :itensPorPagina OFFSET :offset",
    'vendas' => "SELECT v.id AS id, v.data_venda, c.nome AS cliente_nome, p.nome AS produto_nome, v.quantidade, v.preco_total, u.usuario AS vendedor
                 FROM vendas v
                 JOIN produtos p ON v.produto_id = p.id
                 JOIN clientes c ON v.cliente_id = c.id
                 JOIN usuarios u ON v.usuario_id = u.id
                 LIMIT :itensPorPagina OFFSET :offset",
];

// Contagem total de registros por aba (modificada - removida a chave 'produtos')
$totalQuery = [
    'estoque' => "SELECT COUNT(*) FROM produtos",
    'vendas' => "SELECT COUNT(*) FROM vendas",
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
        h2 {
            text-align: center;
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 20px;
        }

        .paginacao {
            text-align: center;
            margin: 20px 0;
        }

        .paginacao a,
        .paginacao span {
            /* Use strong para a página atual */
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
        }

        .paginacao a:hover {
            background-color: #6f6a6a;
            color: #fff;
        }

        .paginacao .pagina-atual {
            /* Estilo para a página atual */
            background-color: #6f6a6a;
            /* Cor de fundo azul */
            color: #fff;
        }

        /* Estilos para posicionar a paginação e os botões dentro da tabela */

        .nav-tabs {
            display: flex;
            justify-content: flex-start;
            /* Alinha as abas à esquerda */
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0 0 10px 0;
        }

        .nav-tabs li {
            display: inline-block;
        }

        .form_itenspagina {
            background: none;
            box-shadow: none;
            display: inline-block;
            /* Permite que o form fique ao lado dos links */
            margin-left: 20px;
            /* Espaçamento entre os links e os botões */
        }

        .nav-tabs a {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 5px;
            text-decoration: none;
            color: white;
            background-color: #2c3e50;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        nav-tabs a.active,
        .nav-tabs a:hover {
            background-color: #6f6a6a;
            color: #fff;
        }

        .itens-por-pagina {
            display: flex;
            align-items: center;
            /* Alinha verticalmente ao centro */
            margin-bottom: 20px;
        }

        .itens-por-pagina label {
            margin-right: 10px;
            /* Espaço entre o label e o select */
            font-weight: bold;
        }

        .itens-por-pagina select {
            padding: 6px 10px;
            font-size: 16px;
            border: 1px solid black;
            border-radius: 4px;
        }

        .itens-por-pagina select:focus {
            outline: none;
            /* Remove a borda do focus */
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
            /* Sombra azul suave no focus */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
            border: none;
        }

        table th {
            background-color: #f2f2f2;
            color: white;
            font-weight: bold;
            background-color: #2c3e50;
        }

        /* Estilos para linhas pares e ímpares */
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
            /* Cor mais clara para linhas pares */
        }

        table tbody tr:nth-child(odd) {
            background-color: #ffffff;
            /* Branco para linhas ímpares */
        }

        table tbody tr:hover {
            background-color: #f1f1f1;
        }

        /* Classe para o container dos botões */
        .botoes-paginacao {
            display: flex;
            align-items: center;
        }

        /* Adiciona uma margem à esquerda do segundo botão */
        .botoes-paginacao {
            margin-left: 10px;
        }

        .botao-voltar {
            display: inline-block;
            margin: 20px 0;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-align: center;
        }

        .botao-voltar:hover {
            background-color: #0056b3;
        }

        .form_itenspagina {
            text-align: center;
            margin: 20px 0;
        }

        .tab-content {
            border: 0 solid #ddd !important;
        }

        .tab-content h3 {
            font-size: 1.5rem;
            margin-top: 20px;
            color: #333;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="itens-por-pagina">
        <label for="itensPorPagina">Itens por Página:</label>
        <select id="itensPorPagina" onchange="window.location.href='?page=relatorios&aba=<?php echo $aba; ?>&itensPorPagina=' + this.value;">
            <option value="5" <?php if ($itensPorPagina == 5) echo 'selected'; ?>>5</option>
            <option value="10" <?php if ($itensPorPagina == 10) echo 'selected'; ?>>10</option>
        </select>
    </div>
    <div class="tab-content">
        <?php if ($aba === 'estoque'): ?>
            <h3>Relatório de Estoque</h3>
            <ul class="nav-tabs">
                <li <?php if ($aba === 'estoque') echo 'class="active"'; ?>><a href="painel.php?page=relatorios&aba=estoque&itensPorPagina=<?php echo $itensPorPagina; ?>">Estoque</a></li>
                <li <?php if ($aba === 'vendas') echo 'class="active"'; ?>><a href="painel.php?page=relatorios&aba=vendas&itensPorPagina=<?php echo $itensPorPagina; ?>">Vendas</a></li>
            </ul>
            <table id="tabelaRelatorio">
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
                <tfoot>
                    <tr>
                        <td colspan="5">
                            <?php echo $paginacaoHTML; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        <?php elseif ($aba === 'vendas'): ?>
            <h3>Relatório de Vendas</h3>
            <ul class="nav-tabs">
                <li <?php if ($aba === 'estoque') echo 'class="active"'; ?>><a href="painel.php?page=relatorios&aba=estoque&itensPorPagina=<?php echo $itensPorPagina; ?>">Estoque</a></li>
                <li <?php if ($aba === 'vendas') echo 'class="active"'; ?>><a href="painel.php?page=relatorios&aba=vendas&itensPorPagina=<?php echo $itensPorPagina; ?>">Vendas</a></li>
            </ul>
            <table id="tabelaRelatorio">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data da Venda</th>
                        <th>Cliente</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Total</th>
                        <th>Vendedor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['data_venda']); ?></td>
                            <td><?php echo htmlspecialchars($row['cliente_nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['produto_nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantidade']); ?></td>
                            <td><?php echo htmlspecialchars($row['preco_total']); ?></td>
                            <td><?php echo htmlspecialchars($row['vendedor']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6">
                            <?php echo $paginacaoHTML; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>

</body>

</html>