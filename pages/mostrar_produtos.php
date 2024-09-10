<?php
include 'includes/conexao.php';

// Verifica autenticação (se necessário)
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: pages/nao_autorizado.php');
    exit();
}

// Função para atualizar dados do produto
function atualizarProduto($conn, $id, $nome, $descricao, $preco, $quantidade)
{
    $sql = "UPDATE produtos SET nome = :nome, descricao = :descricao, 
            preco = :preco, quantidade = :quantidade WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
    $stmt->bindParam(':preco', $preco, PDO::PARAM_STR);
    $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
}

// Processa edição se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_produto'])) {
    // Proteja contra SQL Injection (use prepared statements)
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    // Remova caracteres não numéricos do preço antes de salvar
    $preco = preg_replace("/[^0-9,.]/", "", $_POST['preco']);
    $quantidade = $_POST['quantidade'];

    if (atualizarProduto($conn, $id, $nome, $descricao, $preco, $quantidade)) {
        echo "<script>alert('Produto atualizado com sucesso!');</script>";
        // Redireciona para evitar reenvio do formulário ao atualizar a página
        echo "<script>window.location.href='?page=mostrar_produtos';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar produto.');</script>";
    }
}

// Obtém a situação dos produtos (filtro)
$situacaoFiltro = isset($_GET['situacao']) ? $_GET['situacao'] : 'todos';

// Consulta SQL para buscar produtos, com filtro opcional
$sql = "SELECT * FROM produtos";
if ($situacaoFiltro !== 'todos') {
    $sql .= " WHERE situacao = :situacao";
}

$stmt = $conn->prepare($sql);
if ($situacaoFiltro !== 'todos') {
    $stmt->bindParam(':situacao', $situacaoFiltro);
}
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Mostrar Produtos</title>
    <script>
        function alterarStatusProduto(id, situacaoAtual) {
            if (!confirm(`Você tem certeza que deseja ${situacaoAtual === 'ativo' ? 'desativar' : 'ativar'} este produto?`)) {
                return;
            }

            var xhr = new XMLHttpRequest();
            var url = "pages/alterar_status_produto.php?id=" + id + "&situacao=" + ((situacaoAtual === 'ativo') ? 'inativo' : 'ativo') + "&situacao_anterior=<?php echo $situacaoFiltro; ?>";

            xhr.open("GET", url, true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Redireciona para a página com a situação correta
                    window.location.href = "?page=mostrar_produtos&situacao=" + ((situacaoAtual === 'ativo') ? 'ativo' : 'inativo');
                }
            };

            xhr.send();
        }

        function editarProduto(id) {
            // Obter a linha da tabela
            var linha = document.getElementById('produto-' + id);

            // Obter os valores atuais da linha
            var nome = linha.cells[1].textContent;
            var descricao = linha.cells[2].textContent;
            var preco = linha.cells[3].textContent;
            var quantidade = linha.cells[4].textContent;

            // Criar campos de entrada editáveis
            linha.cells[1].innerHTML = '<input type="text" name="nome" value="' + nome + '">';
            linha.cells[2].innerHTML = '<input type="text" name="descricao" value="' + descricao + '">';
            linha.cells[3].innerHTML = '<input type="text" name="preco" value="' + preco + '">';
            linha.cells[4].innerHTML = '<input type="number" name="quantidade" value="' + quantidade + '">';

            // Substituir botões de ação
            linha.cells[5].innerHTML = `
                <button type="submit" class="btn btn-primary btn-sm" onclick="salvarProduto(${id})">Salvar</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cancelarEdicao(${id})">Cancelar</button>
            `;
        }

        function salvarProduto(id) {
            var linha = document.getElementById('produto-' + id);

            // Obter os valores dos campos de entrada
            var nome = linha.querySelector('input[name="nome"]').value;
            var descricao = linha.querySelector('input[name="descricao"]').value;
            var preco = linha.querySelector('input[name="preco"]').value;
            var quantidade = linha.querySelector('input[name="quantidade"]').value;

            // Enviar os dados para o servidor para salvar no banco de dados
            var xhr = new XMLHttpRequest();
            var url = "pages/atualizar_produto.php";

            var params = "id=" + id + "&nome=" + encodeURIComponent(nome) + "&descricao=" + encodeURIComponent(descricao) + "&preco=" + encodeURIComponent(preco) + "&quantidade=" + encodeURIComponent(quantidade);

            xhr.open("POST", url, true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Verificar a resposta do servidor
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('Produto atualizado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro: ' + response.error);
                    }
                }
            };

            xhr.send(params);
        }

        function cancelarEdicao(id) {
            // Recarregar a página para cancelar a edição
            location.reload();
        }
    </script>
    <style>
         /* Estilos para inputs dentro da tabela */
         table input[type="text"],
        table input[type="email"],
        table input[type="number"] {
            width: 100%;
            /* Ocupa toda a largura da célula */
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            /* Inclui padding e border no cálculo da largura total */
        }

        table input:focus {
            outline: none;
            /* Remove a borda de foco padrão */
            border-color: #337ab7;
            /* Define a cor da borda quando o input está em foco */
            box-shadow: 0 0 5px rgba(51, 122, 183, 0.5);
            /* Adiciona uma sombra suave quando em foco */
        }
        /* Estilos para o botão */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            color: #fff;
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-success {
            background-color: #28a745;
        }

        .btn-danger:hover,
        .btn-danger:focus {
            background-color: #c82333;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.5);
        }

        .btn-success:hover,
        .btn-success:focus {
            background-color: #218838;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.5);
        }

        td.situacao-ativo {
            background-color: #d4edda;
            font-weight: bold;
        }

        td.situacao-inativo {
            background-color: #f8d7da;
            font-weight: bold;
        }

        /* Estilos para o filtro */
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
            border: 1px solid #ced4da;
            border-radius: 5px;
            background-color: #fff;
            background-size: 16px 12px;
        }

        .filtro-container select:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .filtro-container select option {
            background-color: #fff;
            color: #343a40;
        }

        /* Centraliza o conteúdo da célula da tabela */
        td {
            text-align: center;
        }
    </style>
</head>

<body>
    <h1>Produtos</h1>

    <!-- Filtro (manter o código existente) -->
    <div class="filtro-container">
        <label for="situacao">Filtro:</label>
        <select id="situacao" onchange="window.location.href='?page=mostrar_produtos&situacao=' + this.value;">
            <option value="todos" <?php echo ($situacaoFiltro === 'todos') ? 'selected' : ''; ?>>Todos</option>
            <option value="ativo" <?php echo ($situacaoFiltro === 'ativo') ? 'selected' : ''; ?>>Ativos</option>
            <option value="inativo" <?php echo ($situacaoFiltro === 'inativo') ? 'selected' : ''; ?>>Inativos</option>
        </select>
    </div>

    <!-- Tabela de produtos -->


    <!-- Tabela de produtos -->
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Preço</th>
                <th>Quantidade</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr id="produto-<?php echo $produto['id']; ?>">
                    <td><?php echo $produto['id']; ?></td>
                    <td><?php echo $produto['nome']; ?></td>
                    <td><?php echo $produto['descricao']; ?></td>
                    <td><?php echo number_format($produto['preco'], 2, ',', '.'); ?></td>
                    <td><?php echo $produto['quantidade']; ?></td>
                    <td>
                        <?php if ($produto['situacao'] === 'ativo'): ?>
                            <a href="#" onclick="alterarStatusProduto(<?php echo $produto['id']; ?>, 'ativo'); return false;" class="btn btn-danger btn-sm">Desativar</a>
                        <?php else: ?>
                            <a href="#" onclick="alterarStatusProduto(<?php echo $produto['id']; ?>, 'inativo'); return false;" class="btn btn-success btn-sm">Ativar</a>
                        <?php endif; ?>

                        <!-- Botão de Editar CORRIGIDO: -->
                        <button class="btn btn-primary btn-sm" onclick="editarProduto(<?php echo $produto['id']; ?>)">Editar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ... (resto do código) -->
</body>

</html>