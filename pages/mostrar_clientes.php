<?php
include('conexao.php'); // Certifique-se de que o caminho para o arquivo de conexão está correto

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Obtém a situação dos clientes (filtro)
$situacaoFiltro = isset($_GET['situacao']) ? $_GET['situacao'] : 'todos';

// Consulta SQL para buscar clientes, com filtro opcional
$sql = "SELECT * FROM clientes";
if ($situacaoFiltro !== 'todos') {
    $sql .= " WHERE situacao = :situacao";
}

$stmt = $conn->prepare($sql);
if ($situacaoFiltro !== 'todos') {
    $stmt->bindParam(':situacao', $situacaoFiltro);
}
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
    </script>
    <style>
        /* Estilos para o botão */
        .btn {
            display: inline-block;
            padding: 6px 12px;
            font-size: 14px;
            font-weight: normal;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 4px;
            text-decoration: none;
            color: #fff;
            /* Cor padrão do texto do botão */
        }

        .btn-danger {
            background-color: red;
            /* Vermelho */
            border-color: #d43f3a;
        }

        .btn-success {
            background-color: #5cb85c;
            /* Verde */
            border-color: #4cae4c;
        }
    </style>
</head>

<body>
    <h1>Clientes</h1>

    <!-- Filtro -->
    <div>
        <label for="situacao">Filtro:</label>
        <select id="situacao" onchange="window.location.href='?page=mostrar_clientes&situacao=' + this.value;">
            <option value="todos" <?php echo ($situacaoFiltro === 'todos') ? 'selected' : ''; ?>>Todos</option>
            <option value="ativo" <?php echo ($situacaoFiltro === 'ativo') ? 'selected' : ''; ?>>Ativos</option>
            <option value="inativo" <?php echo ($situacaoFiltro === 'inativo') ? 'selected' : ''; ?>>Inativos</option>
        </select>
    </div>

    <table border="1" cellpadding="10" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Telefone</th>
                <th>Email</th>
                <th>Endereço</th>
                <th>Situação</th>
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
                        <?php echo ($cliente['situacao'] === 'ativo') ? 'Ativo' : 'Inativo'; ?>
                    </td>
                    <td>
                        <?php if ($cliente['situacao'] === 'ativo'): ?>
                            <a href="#" onclick="alterarStatusCliente(<?php echo $cliente['id']; ?>, 'ativo'); return false;" class="btn btn-danger">Desativar</a>
                        <?php else: ?>
                            <a href="#" onclick="alterarStatusCliente(<?php echo $cliente['id']; ?>, 'inativo'); return false;" class="btn btn-success">Ativar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>