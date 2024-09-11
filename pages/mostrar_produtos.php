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
            $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$i&itensPorPagina=$itensPorPagina'>$i</a>";
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

// Paginação
$itensPorPagina = isset($_GET['itensPorPagina']) ? (int)$_GET['itensPorPagina'] : 5; // Define 5 como padrão
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Obtém a situação dos produtos (filtro)
$situacaoFiltro = isset($_GET['situacao']) ? $_GET['situacao'] : 'todos';

// Consulta SQL para buscar produtos, com filtro opcional e paginação
$sql = "SELECT * FROM produtos";
if ($situacaoFiltro !== 'todos') {
    $sql .= " WHERE situacao = :situacao";
}
$sql .= " LIMIT :itensPorPagina OFFSET :offset";

$stmt = $conn->prepare($sql);
if ($situacaoFiltro !== 'todos') {
    $stmt->bindParam(':situacao', $situacaoFiltro);
}
$stmt->bindParam(':itensPorPagina', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obter o total de produtos (para paginação)
$sqlTotal = "SELECT COUNT(*) FROM produtos";
if ($situacaoFiltro !== 'todos') {
    $sqlTotal .= " WHERE situacao = :situacao";
}
$stmtTotal = $conn->prepare($sqlTotal);
if ($situacaoFiltro !== 'todos') {
    $stmtTotal->bindParam(':situacao', $situacaoFiltro);
}
$stmtTotal->execute();
$totalProdutos = $stmtTotal->fetchColumn();

// Calcula o número total de páginas
$totalPaginas = ceil($totalProdutos / $itensPorPagina);

$paginaBaseUrl = "painel.php?page=mostrar_produtos&situacao=$situacaoFiltro&itensPorPagina=$itensPorPagina";

$paginacaoHTML = paginarResultados($totalProdutos, $itensPorPagina, $paginaAtual, $paginaBaseUrl);
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

        function abrirModalEdicao(produto) {
            document.getElementById('produto-id').value = produto.id;
            document.getElementById('edit-nome').value = produto.nome;
            document.getElementById('edit-descricao').value = produto.descricao; // Adicione esta linha
            document.getElementById('edit-preco').value = produto.preco; // Adicione esta linha
            document.getElementById('edit-quantidade').value = produto.quantidade; // Adicione esta linha

            document.getElementById('modal-editar-produto').style.display = 'flex';
        }

        function fecharModalEdicao() {
            document.getElementById('modal-editar-produto').style.display = 'none';
        }

        function salvarProdutoModal() {
            var form = document.getElementById('editar-produto-form');
            var formData = new FormData(form);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "pages/atualizar_produto.php", true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert('Produto atualizado com sucesso!');
                        location.reload(); // Recarrega a página após salvar
                    } else {
                        alert('Erro: ' + response.error);
                    }
                }
            };

            xhr.send(formData);
        }

        function fecharModalEdicao() {
            // Esconde o modal
            document.getElementById('modal-editar-produto').style.display = 'none';
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

        a {
            text-decoration: none;
        }

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

        table input:focus {
            outline: none;
            /* Remove a borda de foco padrão */
            border-color: #337ab7;
            /* Define a cor da borda quando o input está em foco */
            box-shadow: 0 0 5px rgba(51, 122, 183, 0.5);
            /* Adiciona uma sombra suave quando em foco */
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin-right: 5px;
        }

        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .btn-success {
            background-color: #28a745;
            color: #fff;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn i {
            margin-right: 5px;
        }

        /* Hover e Focus para todos os botões */
        .btn:hover,
        .btn:focus {
            opacity: 0.8;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.5);
        }

        td.situacao-ativo {
            background-color: #d4edda;
            /* Verde claro para ativo */
            font-weight: bold;
        }

        td.situacao-inativo {
            background-color: #f8d7da;
            /* Vermelho claro para inativo */
            font-weight: bold;
        }

        .filtro-container {
            display: flex;
            align-items: center;
            /* Alinha os itens verticalmente */
            margin-bottom: 20px;
            /* Adiciona margem inferior */
        }

        .filtro-container label {
            margin-right: 10px;
            /* Adiciona margem direita ao label */
        }

        .filtro-container select {
            padding: 8px 12px;
            /* Espaçamento interno */
            font-size: 16px;
            /* Tamanho da fonte */
            border: 1px solid #ced4da;
            /* Borda cinza claro */
            border-radius: 5px;
            /* Cantos arredondados */
            background-color: #fff;
            /* Posição da seta */
            background-size: 16px 12px;
            /* Tamanho da seta */
        }

        .filtro-container select:focus {
            outline: none;
            /* Remove o contorno ao focar */
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            /* Sombra ao focar */
        }

        .filtro-container select option {
            background-color: #fff;
            /* Cor de fundo das opções */
            color: #343a40;
            /* Cor do texto das opções */
        }

        /* Estilos para o modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 400px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            cursor: pointer;
        }

        .modal-content {
            animation: modalFadeIn 0.5s;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .itens-por-pagina {
            display: flex;
            align-items: center;
            /* Alinha verticalmente ao centro */
            margin-bottom: 20px;
        }

        .itensPagina {
            margin-left: 5px;
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
    </style>
</head>

<body>
    <h1>Produtos</h1>

    <!-- Filtro e Itens por Página -->
    <div class="filtro-container">
        <label for="situacao">Filtro:</label>
        <select id="itensPorPagina" onchange="window.location.href='?page=mostrar_produtos&situacao=<?php echo $situacaoFiltro; ?>&itensPorPagina=' + this.value + '&pagina=1';">
            <option value="todos" <?php echo ($situacaoFiltro === 'todos') ? 'selected' : ''; ?>>Todos</option>
            <option value="ativo" <?php echo ($situacaoFiltro === 'ativo') ? 'selected' : ''; ?>>Ativos</option>
            <option value="inativo" <?php echo ($situacaoFiltro === 'inativo') ? 'selected' : ''; ?>>Inativos</option>
        </select>


        <label for="itensPorPagina" class="itensPagina">Itens por Página:</label>
        <select id="itensPorPagina" onchange="window.location.href='?page=mostrar_produtos&situacao=<?php echo $situacaoFiltro; ?>&itensPorPagina=' + this.value + '&pagina=1';">
            <option value="5" <?php echo ($itensPorPagina == 5) ? 'selected' : ''; ?>>5</option>
            <option value="10" <?php echo ($itensPorPagina == 10) ? 'selected' : ''; ?>>10</option>
        </select>
    </div>

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
                            <a href="#" onclick="alterarStatusProduto(<?php echo $produto['id']; ?>, 'ativo'); return false;" class="btn btn-danger btn-sm"><i class="fas fa-ban"></i> Desativar</a>
                        <?php else: ?>
                            <a href="#" onclick="alterarStatusProduto(<?php echo $produto['id']; ?>, 'inativo'); return false;" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Ativar</a>
                        <?php endif; ?>

                        <!-- Botão de Editar CORRIGIDO: -->
                        <button class="btn btn-warning btn-sm" onclick="abrirModalEdicao(<?php echo htmlspecialchars(json_encode($produto)); ?>)"><i class="fas fa-edit"></i> Editar</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="paginacao">
        <?php echo $paginacaoHTML; ?>
    </div>

    <div id="modal-editar-produto" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModalEdicao()">×</span>
            <h2>Editar Produto</h2>
            <form id="editar-produto-form"> <input type="hidden" id="produto-id" name="id">
                <label for="edit-nome">Nome:</label>
                <input type="text" id="edit-nome" name="nome" required><br><br>

                <label for="edit-descricao">Descrição:</label>
                <textarea id="edit-descricao" name="descricao"></textarea><br><br>

                <label for="edit-preco">Preço:</label>
                <input type="text" id="edit-preco" name="preco" required><br><br>

                <label for="edit-quantidade">Quantidade:</label>
                <input type="number" id="edit-quantidade" name="quantidade" required><br><br>

                <button type="button" onclick="salvarProdutoModal()">Salvar</button>
            </form>
        </div>
    </div>

</body>

</html>