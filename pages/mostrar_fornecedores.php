<?php
include('conexao.php'); // Certifique-se de que o caminho para o arquivo de conexão está correto

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Obtém a situação dos fornecedores (filtro)
$situacaoFiltro = isset($_GET['situacao']) ? $_GET['situacao'] : 'todos';

// Consulta SQL para buscar fornecedores, com filtro opcional
$sql = "SELECT * FROM fornecedores";
if ($situacaoFiltro !== 'todos') {
    $sql .= " WHERE situacao = :situacao";
}

$stmt = $conn->prepare($sql);
if ($situacaoFiltro !== 'todos') {
    $stmt->bindParam(':situacao', $situacaoFiltro);
}
$stmt->execute();
$fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Mostrar Fornecedores</title>
    <script>
        function alterarStatusFornecedor(id, situacaoAtual) {
            if (!confirm(`Você tem certeza que deseja ${situacaoAtual === 'ativo' ? 'desativar' : 'ativar'} este fornecedor?`)) {
                return;
            }

            var xhr = new XMLHttpRequest();
            var url = "pages/alterar_status_fornecedor.php?id=" + id + "&situacao=" + ((situacaoAtual === 'ativo') ? 'inativo' : 'ativo');

            xhr.open("GET", url, true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Move a linha para a posição correta na tabela
                    var linha = document.getElementById('fornecedor-' + id);
                    var tabela = linha.parentNode; // Obtém a tabela pai da linha
                    var novaSituacao = (situacaoAtual === 'ativo') ? 'inativo' : 'ativo';

                    // Encontra a primeira linha com a nova situação
                    var linhas = tabela.querySelectorAll('tbody tr');
                    var linhaReferencia = null;
                    for (var i = 0; i < linhas.length; i++) {
                        var situacaoLinha = linhas[i].querySelector('td:nth-child(6)').textContent;
                        if (situacaoLinha === novaSituacao) {
                            linhaReferencia = linhas[i];
                            break;
                        }
                    }

                    // Move a linha antes da linha de referência ou para o final da tabela
                    if (linhaReferencia) {
                        tabela.insertBefore(linha, linhaReferencia);
                    } else {
                        tabela.appendChild(linha);
                    }

                    // Atualiza a célula da situação e o link de ação
                    var celulaSituacao = linha.querySelector('td:nth-child(6)');
                    var celulaAcoes = linha.querySelector('td:nth-child(7)');

                    celulaSituacao.textContent = novaSituacao;

                    var novoLink = (situacaoAtual === 'ativo') ?
                        '<a href="#" onclick="alterarStatusFornecedor(' + id + ', \'inativo\'); return false;" class="btn btn-success">Ativar</a>' :
                        '<a href="#" onclick="alterarStatusFornecedor(' + id + ', \'ativo\'); return false;" class="btn btn-danger">Desativar</a>';
                    celulaAcoes.innerHTML = novoLink;

                    // Adiciona/remove a classe CSS de acordo com a situação
                    if (novaSituacao === 'ativo') {
                        celulaSituacao.classList.add('situacao-ativo');
                        celulaSituacao.classList.remove('situacao-inativo');
                    } else {
                        celulaSituacao.classList.add('situacao-inativo');
                        celulaSituacao.classList.remove('situacao-ativo');
                    }
                }
            };

            xhr.send();
        }
    </script>
    <style>
        .btn {
            display: inline-block;
            padding: 8px 16px;
            /* Ajuste o espaçamento interno */
            font-size: 16px;
            /* Ajuste o tamanho da fonte */
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: none;
            /* Remove a borda padrão */
            border-radius: 5px;
            /* Ajuste o raio da borda */
            transition: all 0.3s ease;
            /* Adiciona uma transição suave */
        }

        .btn-danger {
            background-color: #dc3545;
            /* Vermelho mais vibrante */
            color: #fff;
        }

        .btn-success {
            background-color: #28a745;
            /* Verde mais vibrante */
            color: #fff;
        }

        .btn-danger:hover,
        .btn-danger:focus {
            background-color: #c82333;
            /* Vermelho mais escuro ao passar o mouse */
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.5);
            /* Adiciona uma sombra ao focar */
        }

        .btn-success:hover,
        .btn-success:focus {
            background-color: #218838;
            /* Verde mais escuro ao passar o mouse */
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.5);
            /* Adiciona uma sombra ao focar */
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
    </style>
</head>

<body>
    <h1>Fornecedores</h1>

    <!-- Filtro -->
    <div>
        <label for="situacao">Filtro:</label>
        <select id="situacao"
            onchange="window.location.href='?page=mostrar_fornecedores&situacao=' + this.value;">
            <option value="todos" <?php echo ($situacaoFiltro === 'todos') ? 'selected' : ''; ?>>Todos</option>
            <option value="ativo" <?php echo ($situacaoFiltro === 'ativo') ? 'selected' : ''; ?>>Ativos</option>
            <option value="inativo" <?php echo ($situacaoFiltro === 'inativo') ? 'selected' : ''; ?>>Inativos</option>
        </select>
    </div>

    <!-- Tabela de fornecedores -->
    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Endereço</th>
                <th>Telefone</th>
                <th>Email</th>
                <th>Situação</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($fornecedores as $fornecedor): ?>
                <tr id="fornecedor-<?php echo $fornecedor['id']; ?>">
                    <td><?php echo $fornecedor['id']; ?></td>
                    <td><?php echo $fornecedor['nome']; ?></td>
                    <td><?php echo $fornecedor['endereco']; ?></td>
                    <td><?php echo $fornecedor['telefone']; ?></td>
                    <td><?php echo $fornecedor['email']; ?></td>
                    <td class="situacao-<?php echo $fornecedor['situacao']; ?>">
                        <?php echo $fornecedor['situacao']; ?>
                    </td>
                    <td>
                        <?php if ($fornecedor['situacao'] === 'ativo'): ?>
                            <a href="#"
                                onclick="alterarStatusFornecedor(<?php echo $fornecedor['id']; ?>, 'ativo'); return false;"
                                class="btn btn-danger">Desativar</a>
                        <?php else: ?>
                            <a href="#"
                                onclick="alterarStatusFornecedor(<?php echo $fornecedor['id']; ?>, 'inativo'); return false;"
                                class="btn btn-success">Ativar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>