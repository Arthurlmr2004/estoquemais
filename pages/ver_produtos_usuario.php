<?php
include 'conexao.php';

// Defina o número de produtos por página
$produtos_por_pagina = 10;

// Verifique a página atual a partir da URL
$page = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($page - 1) * $produtos_por_pagina;

// Garanta que o offset nunca seja negativo
if ($offset < 0) {
    $offset = 0;
}

// Busque o total de produtos
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
            color: #333;
            /* Cor cinza mais clara */
            background-color: #f2f2f2;
            /* Cor de fundo clara */
            border: 1px solid #ddd;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .pagination a:hover {
            background-color: #ddd;
            /* Cinza mais claro no hover */
            color: #000;
        }

        .pagination .active {
            font-weight: bold;
            color: #fff;
            background-color: #666;
            /* Cor ativa cinza escura */
            border-color: #666;
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
    </style>
</head>

<body>
    <h1>Lista de Produtos</h1>
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
                            <img src="<?php echo $produto['imagem']; ?>" alt="Imagem do Produto">
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Navegação de Paginação -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="painel.php?page=ver_produtos_usuario&pagina=<?php echo $page - 1; ?>">&laquo; Anterior</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a class="<?php echo $i == $page ? 'active' : ''; ?>" href="painel.php?page=ver_produtos_usuario&pagina=<?php echo $i; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_paginas): ?>
            <a href="painel.php?page=ver_produtos_usuario&pagina=<?php echo $page + 1; ?>">Próxima &raquo;</a>
        <?php endif; ?>
    </div>
</body>

</html>