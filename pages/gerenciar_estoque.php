<?php
include 'includes/conexao.php';
include 'funcoes_log.php';

// Verifica se o usuário está autenticado como admin ou vendedor
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

$usuarioLogado = $_SESSION['usuario'];

// Inicialmente, não exibe o modal
$showModal = false;
$mensagemErro = "";

// Buscar fornecedores
$sqlFornecedores = "SELECT id, nome FROM fornecedores";
$stmtFornecedores = $conn->query($sqlFornecedores);
$fornecedores = $stmtFornecedores->fetchAll(PDO::FETCH_ASSOC);

// Função para paginação
function paginarResultados($totalRegistros, $itensPorPagina, $paginaAtual = 1, $paginaBaseUrl = '')
{
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    $paginacaoHTML = '<div class="paginacao">';

    // Botão "Anterior"
    if ($paginaAtual > 1) {
        $paginaAnterior = $paginaAtual - 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaAnterior&itensPorPagina=$itensPorPagina'> Anterior</a>"; // Adiciona itensPorPagina
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
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaProxima&itensPorPagina=$itensPorPagina'>Próximo</a>"; // Adiciona itensPorPagina
    }

    $paginacaoHTML .= '</div>';
    return $paginacaoHTML;
}

// Parâmetros da paginação
$itensPorPagina = isset($_GET['itensPorPagina']) ? (int)$_GET['itensPorPagina'] : 5; // Define 5 como padrão
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

// Atualização de quantidade 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['atualizar'])) {
    $produto_id = $_POST['id'];
    $nova_quantidade = $_POST['estoque_minimo'];

    // Buscar o valor antigo de estoque_minimo
    $sqlSelect = "SELECT estoque_minimo FROM produtos WHERE id = :id";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bindParam(':id', $produto_id, PDO::PARAM_INT);
    $stmtSelect->execute();
    $estoque_minimo_antigo = $stmtSelect->fetchColumn();

    // Preparar e executar a consulta SQL para atualizar a estoque_minimo
    $sqlUpdate = "UPDATE produtos SET estoque_minimo = :estoque_minimo WHERE id = :id";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':estoque_minimo', $nova_quantidade);
    $stmtUpdate->bindParam(':id', $produto_id);

    try {
        if ($stmtUpdate->execute()) {
            // Crie o comando SQL completo para o log
            $comandoSqlCompleto = "UPDATE produtos SET estoque_minimo = $nova_quantidade WHERE id = $produto_id";

            // Registra a ação no log
            registrarLog($conn, $usuarioLogado, 'Atualização de Estoque', 'produtos', $comandoSqlCompleto, "Estoque Mínimo antigo: $estoque_minimo_antigo", "Estoque Mínimo novo: $nova_quantidade");
        } else {
            echo "Erro ao atualizar a quantidade: " . $e->getMessage();
        }
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
    <link rel="stylesheet" href="../estilos/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        h2 {
            text-align: center;
            font-size: 1.5rem;
            color: #333;
            /* Cor do título - Azul */
            margin-bottom: 20px;
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
            color: #007bff;
            /* Cor do link - Azul */
            background-color: white;
            border: 1px solid #ddd;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-weight: bold;
        }

        .paginacao a {
            color: #000;
            /* Cor do link - Preto */
        }

        .paginacao a:hover {
            background-color: #6f6a6a;
            /* Cor de fundo no hover - Cinza escuro */
            color: #fff;
            /* Cor do texto no hover - Branco */
        }

        .paginacao .pagina-atual {
            font-weight: bold;
            color: #fff;
            /* Cor do texto - Branco */
            background-color: #6f6a6a;
            /* Cor de fundo - Cinza escuro */
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
            color: white;
            font-weight: bold;
            background-color: #2c3e50;
            /* Cor de fundo do cabeçalho - Azul escuro */
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
            /* Cor de fundo no hover - Cinza muito claro */
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

        .btn:hover {
            background-color: #0056b3;
            /* Cor de fundo no hover - Azul mais escuro */
        }

        /* Modal CSS */
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
            color: black;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            position: relative;
            text-align: center;
        }

        .modal-success-message {
            color: #28a745;
            font-size: 1.5rem;
            text-align: center;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            cursor: pointer;
        }

        .modal-sucesso {
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
            text-align: center;
        }

        .modal-sucesso .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
        }

        /* Estilos para o select de quantidade de itens por página */
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

        .btn-warning {
            background-color: #ffc107;
            /* Amarelo do Bootstrap */
            border-color: #ffc107;
            color: #212529;
            font-weight: bold;
            /* Cor do texto - Preto */
        }

        .btn-warning:hover {
            background-color: #e0a800;
            /* Amarelo mais escuro no hover */
            border-color: #e0a800;
        }

        .btn:hover,
        .btn:focus {
            opacity: 0.8;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.5);
        }
    </style>
</head>

<body>
    <h2>Gerenciar Estoque</h2>

    <!-- Itens por Página -->
    <div class="itens-por-pagina">
        <label for="itensPorPagina">Itens por Página:</label>
        <select id="itensPorPagina" onchange="window.location.href='?page=gerenciar_estoque&itensPorPagina=' + this.value;">
            <option value="5" <?php if ($itensPorPagina == 5) echo 'selected'; ?>>5</option>
            <option value="10" <?php if ($itensPorPagina == 10) echo 'selected'; ?>>10</option>
        </select>
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
            <?php foreach ($produtos as $produto) : ?>
                <tr data-id="<?php echo $produto['id']; ?>">
                    <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                    <td><?php echo htmlspecialchars($produto['descricao']); ?></td>
                    <td><?php echo htmlspecialchars($produto['preco']); ?></td>
                    <td><?php echo htmlspecialchars($produto['quantidade']); ?></td>
                    <td class="estoque-minimo"><?php echo htmlspecialchars($produto['estoque_minimo']); ?></td>
                    <td class="estoque-maximo"><?php echo htmlspecialchars($produto['estoque_maximo']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="abrirModal(<?php echo htmlspecialchars(json_encode($produto)); ?>)">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Paginação -->
    <div class="paginacao">
        <?php echo $paginacaoHTML; ?>
    </div>

    <div id="modal-editar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">×</span>
            <h2>Atualizar Estoque</h2>
            <form id="form-editar-estoque">
                <input type="hidden" id="produto-id" name="id">
                <label for="estoque-minimo">Estoque Mínimo:</label>
                <input type="number" id="estoque-minimo" name="estoque_minimo" required><br><br>

                <label for="estoque-maximo">Estoque Máximo:</label>
                <input type="number" id="estoque-maximo" name="estoque_maximo" required><br><br>

                <button type="button" onclick="salvarEdicao()">Salvar</button>
            </form>
        </div>
    </div>

    <div id="modal-sucesso" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModalSucesso()">×</span>
            <h2 class="modal-success-message">Estoque atualizado com sucesso!</h2>
        </div>
    </div>

    <script>
        function abrirModal(produto) {
            document.getElementById('produto-id').value = produto.id;
            document.getElementById('estoque-minimo').value = produto.estoque_minimo;
            document.getElementById('estoque-maximo').value = produto.estoque_maximo;
            document.getElementById('modal-editar').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modal-editar').style.display = 'none';
        }

        function mostrarModalSucesso() {
            document.getElementById('modal-sucesso').style.display = 'flex';
        }

        function fecharModalSucesso() {
            document.getElementById('modal-sucesso').style.display = 'none';
        }

        function salvarEdicao() {
            var id = document.getElementById('produto-id').value;
            var estoqueMinimo = document.getElementById('estoque-minimo').value;
            var estoqueMaximo = document.getElementById('estoque-maximo').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'pages/atualizar_estoque.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        console.log(xhr.responseText); // Adiciona um console.log para ver a resposta
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.status === 'success') {
                                // Atualiza os valores na tabela HTML
                                var row = document.querySelector('tr[data-id="' + id + '"]');
                                row.querySelector('.estoque-minimo').textContent = estoqueMinimo;
                                row.querySelector('.estoque-maximo').textContent = estoqueMaximo;
                                fecharModal();
                                mostrarMensagemSucesso(); // Adiciona a chamada da função para mostrar a mensagem de sucesso
                            } else {
                                alert('Erro ao salvar as alterações: ' + (response.message || 'Erro desconhecido.'));
                            }
                        } catch (error) {
                            alert('Erro ao processar a resposta do servidor.');
                        }
                    } else {
                        alert('Erro na solicitação: ' + xhr.status);
                    }
                }
            };

            var params = 'id=' + encodeURIComponent(id) +
                '&estoque_minimo=' + encodeURIComponent(estoqueMinimo) +
                '&estoque_maximo=' + encodeURIComponent(estoqueMaximo);
            xhr.send(params);
        }

        function mostrarMensagemSucesso() {
            var sucessoModal = document.getElementById('modal-sucesso');
            sucessoModal.style.display = 'flex';
            setTimeout(function() {
                sucessoModal.style.display = 'none';
            }, 10000); // Fecha o modal após 3 segundos
        }
    </script>

</body>

</html>