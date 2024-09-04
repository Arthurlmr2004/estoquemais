<?php
include('conexao.php'); // Certifique-se de que o caminho para o arquivo de conexão está correto

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Obtém a situação dos clientes que devem ser exibidos
$situacao = isset($_GET['situacao']) ? $_GET['situacao'] : 'ativo';

$sql = "SELECT * FROM clientes";
if ($situacao === 'ativo') {
    $sql .= " WHERE situacao = 'ativo'";
} else {
    $sql .= " WHERE situacao = 'inativo'";
}
$stmt = $conn->prepare($sql);
$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mostrar Clientes</title>
    <script>
        function confirmAction(url, action) {
            if (confirm(`Você tem certeza que deseja ${action} este cliente?`)) {
                window.location.href = url;
            }
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
            color: #fff; /* Cor padrão do texto do botão */
        }

        .btn-danger {
            background-color: red; /* Vermelho */
            border-color: #d43f3a;
        }

        .btn-success {
            background-color: #5cb85c; /* Verde */
            border-color: #4cae4c;
        }
    </style>
</head>
<body>
    <h1>Clientes - <?php echo ucfirst($situacao); ?></h1>
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
                <tr>
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
                            <a href="javascript:void(0);" onclick="confirmAction('pages/alterar_status_cliente.php?id=<?php echo $cliente['id']; ?>&situacao=inativo&situacao_anterior=<?php echo $situacao; ?>', 'desativar'); return false;" class="btn btn-danger">Desativar</a>
                        <?php else: ?>
                            <a href="javascript:void(0);" onclick="confirmAction('pages/alterar_status_cliente.php?id=<?php echo $cliente['id']; ?>&situacao=ativo&situacao_anterior=<?php echo $situacao; ?>', 'ativar'); return false;" class="btn btn-success">Ativar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
