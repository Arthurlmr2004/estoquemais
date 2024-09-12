<?php
include 'includes/conexao.php';

// Defina o número de produtos por página
$produtos_por_pagina = isset($_GET['itensPorPagina']) ? (int)$_GET['itensPorPagina'] : 5; // 10 por padrão

// Verifique a página atual a partir da URL
$page = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($page - 1) * $produtos_por_pagina;

// Garanta que o offset nunca seja negativo
if ($offset < 0) {
    $offset = 0;
}

function paginarResultados($totalRegistros, $itensPorPagina, $paginaAtual = 1, $paginaBaseUrl = '')
{
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    $paginacaoHTML = '<div class="pagination">';

    // Botão "Anterior"
    if ($paginaAtual > 1) {
        $paginaAnterior = $paginaAtual - 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaAnterior&itensPorPagina=$itensPorPagina'>Anterior</a>";
    }

    // Lógica para mostrar 3 links (adaptar se necessário)
    $inicio = max(1, $paginaAtual - 1);
    $fim = min($totalPaginas, $paginaAtual + 1);

    for ($i = $inicio; $i <= $fim; $i++) {
        if ($i == $paginaAtual) {
            $paginacaoHTML .= "<span class='active'>$i</span>";
        } else {
            $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$i&itensPorPagina=$itensPorPagina'>$i</a>";
        }
    }

    // Botão "Próximo"
    if ($paginaAtual < $totalPaginas) {
        $paginaProxima = $paginaAtual + 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaProxima&itensPorPagina=$itensPorPagina'>Próximo</a>";
    }

    $paginacaoHTML .= '</div>';
    return $paginacaoHTML;
}

/// Busque o total de produtos
$sql_total = "SELECT COUNT(*) FROM produtos";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->execute();
$total_produtos = $stmt_total->fetchColumn();

// Calcule o número total de páginas
$total_paginas = ceil($total_produtos / $produtos_por_pagina);

// Busque os produtos para a página atual
$sql = "SELECT * FROM produtos LIMIT :offset, :produtos_por_pagina";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':produtos_por_pagina', $produtos_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Ver Produtos</title>
    <style>
        /* Estilização da tabela */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            /* Manter a cor de fundo clara para os cabeçalhos */
        }

        img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
        }

        /* Estilização da paginação */
        .pagination {
            text-align: center;
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 10px 15px;
            margin: 0 5px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            color: black;
            /* Cor cinza mais clara */
            background-color: white;
            /* Cor de fundo clara */
            border: 1px solid #ddd;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .paginacao a {
            color: #000;
        }

        .pagination a:hover {
            background-color: #6f6a6a;
            color: #fff;
        }

        .pagination .active {

            color: #fff;
            background-color: #6f6a6a;

        }

        /* Botão Voltar */
        .botao-voltar {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #666;
            /* Cor cinza escura */
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            float: right;
        }

        .botao-voltar:hover {
            background-color: #444;
            /* Cinza mais escuro no hover */
        }

        /* Navegação em abas */
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
            color: #333;
            /* Cor cinza mais clara */
            background-color: #f2f2f2;
            /* Fundo claro */
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .nav-tabs a.active,
        .nav-tabs a:hover {
            background-color: #666;
            /* Cor ativa e de hover cinza escura */
            color: #fff;
        }

        .filtro-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .filtro-container label {
            margin-right: 10px;
        }

        .filtro-container select {
            padding: 8px 12px;
            font-size: 16px;
            border: 1px solid #ddd;
            /* Borda cinza claro */
            border-radius: 5px;
            /* Cantos arredondados */

            /* Remove a seta padrão do select */
            background-color: #fff;
            /* Fundo branco */

            /* Adiciona uma seta customizada */
            background-repeat: no-repeat;
            background-position: right 10px center;
            /* Posiciona a seta à direita */
            background-size: 16px 12px;
            /* Define o tamanho da seta */
        }

        .filtro-container select:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
            /* Sombra azul ao focar */
        }
    </style>
</head>

<body>
    <h1>Lista de Produtos</h1>

    <div class="filtro-container">
        <label for="itensPorPagina">Itens por Página:</label>
        <select id="itensPorPagina" onchange="window.location.href='painel.php?page=ver_produtos_usuario&itensPorPagina=' + this.value + '&pagina=1';">
            <option value="5" <?php echo ($produtos_por_pagina == 5) ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo ($produtos_por_pagina == 10) ? 'selected' : ''; ?>>10</option>
            <!-- Adicione mais opções conforme necessário -->
        </select>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Preço</th>
                <th>Quantidade</th>
                <th>Imagem</th> 
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><?php echo $produto['id']; ?></td>
                    <td><?php echo $produto['nome']; ?></td>
                    <td><?php echo $produto['descricao']; ?></td>
                    <td><?php echo $produto['preco']; ?></td>
                    <td><?php echo $produto['quantidade']; ?></td>
                    <td>
                        <?php if (!empty($produto['imagem'])): ?>
                            <img src="imagens/<?php echo $produto['imagem']; ?>" alt="Imagem do Produto" height="50"> 
                        <?php else: ?>
                            Sem Imagem
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Navegação de Paginação -->
    <div class="pagination">
        <?php
        $paginaBaseUrl = "painel.php?page=ver_produtos_usuario&itensPorPagina=$produtos_por_pagina";
        echo paginarResultados($total_produtos, $produtos_por_pagina, $page, $paginaBaseUrl);
        ?>
    </div>
</body>

</html>