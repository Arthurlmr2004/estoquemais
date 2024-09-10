<?php
include 'includes/conexao.php'; // Certifique-se de que o caminho para o arquivo de conexão está correto

// Verifica se o usuário está autenticado
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

// Definir o número de itens por página
$clientesPorPagina = 5;

// Determinar a página atual
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($paginaAtual - 1) * $clientesPorPagina;

// Obtém a situação dos clientes (filtro)
$situacaoFiltro = isset($_GET['situacao']) ? $_GET['situacao'] : 'todos';

// Consulta SQL para contar o total de clientes
$sqlTotal = "SELECT COUNT(*) as total FROM clientes";
if ($situacaoFiltro !== 'todos') {
    $sqlTotal .= " WHERE situacao = :situacao";
}
$stmtTotal = $conn->prepare($sqlTotal);
if ($situacaoFiltro !== 'todos') {
    $stmtTotal->bindParam(':situacao', $situacaoFiltro);
}
$stmtTotal->execute();
$totalClientes = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

// Calcular o número total de páginas
$totalPaginas = ceil($totalClientes / $clientesPorPagina);

// Consulta SQL para buscar clientes com paginação
$sql = "SELECT * FROM clientes";
if ($situacaoFiltro !== 'todos') {
    $sql .= " WHERE situacao = :situacao";
}
$sql .= " LIMIT :inicio, :clientesPorPagina";

$stmt = $conn->prepare($sql);
if ($situacaoFiltro !== 'todos') {
    $stmt->bindParam(':situacao', $situacaoFiltro);
}
$stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
$stmt->bindParam(':clientesPorPagina', $clientesPorPagina, PDO::PARAM_INT);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Mostrar Clientes</title>
    <script>
        function alterarStatusCliente(id, situacaoAtual) {
            if (!confirm(`Você tem certeza que deseja ${situacaoAtual === 'ativo' ? 'desativar' : 'ativar'} este cliente?`)) {
                return; // Cancela a ação se o usuário clicar em "Cancelar"
            }

            var xhr = new XMLHttpRequest();
            var url = "pages/alterar_status_cliente.php?id=" + id + "&situacao=" + ((situacaoAtual === 'ativo') ? 'inativo' : 'ativo');

            xhr.open("GET", url, true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Remove a linha da tabela atual
                    var linha = document.getElementById('cliente-' + id);
                    linha.remove();

                    // Adiciona a linha na tabela correta (ativos ou inativos)
                    var tabelaDestino = (situacaoAtual === 'ativo') ? document.getElementById('tabela-inativos') : document.getElementById('tabela-ativos');
                    var novaLinha = tabelaDestino.insertRow();
                    novaLinha.id = 'cliente-' + id; // Define o ID da nova linha
                }
            };

            xhr.send();
        }

        function abrirModal(cliente) {
            console.log(cliente);

            // Preenche os campos do modal com os dados do cliente
            document.getElementById('cliente-id').value = cliente.id;
            document.getElementById('nome').value = cliente.nome;
            document.getElementById('cpf').value = cliente.cpf;
            document.getElementById('telefone').value = cliente.telefone;
            document.getElementById('email').value = cliente.email;
            document.getElementById('endereco').value = cliente.endereco;

            // Mostra o modal
            document.getElementById('modal-editar').style.display = 'flex';
        }

        function fecharModal() {
            // Esconde o modal
            document.getElementById('modal-editar').style.display = 'none';
        }

        function salvarEdicao() {
            var id = document.getElementById('cliente-id').value;
            var nome = document.getElementById('nome').value;
            var cpf = document.getElementById('cpf').value;
            var telefone = document.getElementById('telefone').value;
            var email = document.getElementById('email').value;
            var endereco = document.getElementById('endereco').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'pages/atualizar_cliente.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        // Atualiza a linha da tabela com as novas informações
                        var linha = document.getElementById('cliente-' + id);
                        linha.cells[1].textContent = nome;
                        linha.cells[2].textContent = cpf;
                        linha.cells[3].textContent = telefone;
                        linha.cells[4].textContent = email;
                        linha.cells[5].textContent = endereco;

                        // Fechar o modal
                        fecharModal();
                    } else {
                        alert('Erro ao salvar as alterações.');
                    }
                }
            };

            // Enviar os dados
            var params = 'id=' + encodeURIComponent(id) +
                '&nome=' + encodeURIComponent(nome) +
                '&cpf=' + encodeURIComponent(cpf) +
                '&telefone=' + encodeURIComponent(telefone) +
                '&email=' + encodeURIComponent(email) +
                '&endereco=' + encodeURIComponent(endereco);

            xhr.send(params);
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
    </style>
</head>

<body>
    <h1>Clientes</h1>

    <!-- Filtro -->
    <div class="filtro-container">
        <label for="situacao">Filtro:</label>
        <select id="situacao" onchange="window.location.href='?page=mostrar_clientes&situacao=' + this.value;">
            <option value="todos" <?php echo ($situacaoFiltro === 'todos') ? 'selected' : ''; ?>>Todos</option>
            <option value="ativo" <?php echo ($situacaoFiltro === 'ativo') ? 'selected' : ''; ?>>Ativos</option>
            <option value="inativo" <?php echo ($situacaoFiltro === 'inativo') ? 'selected' : ''; ?>>Inativos</option>
        </select>
    </div>

    <!-- Tabela de clientes -->
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Telefone</th>
                <th>Email</th>
                <th>Endereço</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr id="cliente-<?php echo $cliente['id']; ?>">
                    <td><?php echo $cliente['id']; ?></td>
                    <td><?php echo $cliente['nome']; ?></td>
                    <td><?php echo $cliente['cpf']; ?></td>
                    <td><?php echo $cliente['telefone']; ?></td>
                    <td><?php echo $cliente['email']; ?></td>
                    <td><?php echo $cliente['endereco']; ?></td>
                    <td>
                        <?php if ($cliente['situacao'] === 'ativo'): ?>
                            <a href="#" onclick="alterarStatusCliente(<?php echo $cliente['id']; ?>, 'ativo'); return false;" class="btn btn-danger">Desativar</a>
                        <?php else: ?>
                            <a href="#" onclick="alterarStatusCliente(<?php echo $cliente['id']; ?>, 'inativo'); return false;" class="btn btn-success">Ativar</a>
                        <?php endif; ?>
                        <!-- Botão de editar -->
                        <a href="#" onclick="abrirModal(<?php echo htmlspecialchars(json_encode($cliente)); ?>); return false;" class="btn btn-warning">Editar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- Controles de Paginação -->
    <div class="paginacao">
        <?php if ($totalPaginas > 1): ?>
            <?php
            // Definir o número máximo de links de página a serem exibidos
            $maxLinks = 5;
            $startPage = max(1, $paginaAtual - intval($maxLinks / 2));
            $endPage = min($totalPaginas, $startPage + $maxLinks - 1);

            // Ajustar o startPage se não houver páginas suficientes no final
            if ($endPage - $startPage < $maxLinks - 1) {
                $startPage = max(1, $endPage - $maxLinks + 1);
            }
            ?>

            <!-- Link para a primeira página -->
            <?php if ($paginaAtual > 1): ?>
                <a href="?page=mostrar_clientes&situacao=<?php echo $situacaoFiltro; ?>&pagina=1">&laquo; Primeira</a>
            <?php endif; ?>

            <!-- Link para a página anterior -->
            <?php if ($paginaAtual > 1): ?>
                <a href="?page=mostrar_clientes&situacao=<?php echo $situacaoFiltro; ?>&pagina=<?php echo $paginaAtual - 1; ?>">&lt; Anterior</a>
            <?php endif; ?>

            <!-- Links das páginas -->
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $paginaAtual): ?>
                    <strong><?php echo $i; ?></strong>
                <?php else: ?>
                    <a href="?page=mostrar_clientes&situacao=<?php echo $situacaoFiltro; ?>&pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Link para a próxima página -->
            <?php if ($paginaAtual < $totalPaginas): ?>
                <a href="?page=mostrar_clientes&situacao=<?php echo $situacaoFiltro; ?>&pagina=<?php echo $paginaAtual + 1; ?>">Próxima &gt;</a>
            <?php endif; ?>

            <!-- Link para a última página -->
            <?php if ($paginaAtual < $totalPaginas): ?>
                <a href="?page=mostrar_clientes&situacao=<?php echo $situacaoFiltro; ?>&pagina=<?php echo $totalPaginas; ?>">Última &raquo;</a>
            <?php endif; ?>
        <?php endif; ?>
        <!-- Modal para edição de cliente -->
        <div id="modal-editar" class="modal">
            <div class="modal-content">
                <span class="close" onclick="fecharModal()">&times;</span>
                <h2>Editar Cliente</h2>
                <form action="pages/atualizar_cliente.php" method="POST">
                    <input type="hidden" id="cliente-id">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required><br><br>

                    <label for="cpf">CPF:</label>
                    <input type="text" id="cpf" name="cpf" required><br><br>

                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" required><br><br>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required><br><br>

                    <label for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco" required><br><br>

                    <button type="button" onclick="salvarEdicao()">Salvar</button>
                </form>
            </div>
        </div>


</body>

</html>