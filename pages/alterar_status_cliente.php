<?php
include('conexao.php'); // Certifique-se de que o caminho para o arquivo de conexão está correto
session_start(); // Inicie a sessão

// Verifica se o usuário está autenticado e tem permissão
if (!isset($_SESSION['usuario']) || $_SESSION['permissao'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Obtém o ID do cliente e a situação desejada a partir da URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$situacao = isset($_GET['situacao']) && $_GET['situacao'] === 'ativo' ? 'ativo' : 'inativo';
$situacao_anterior = isset($_GET['situacao_anterior']) ? $_GET['situacao_anterior'] : 'ativo';

// Atualiza a situação do cliente
if ($id > 0) {
    $sql = "UPDATE clientes SET situacao = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute([$situacao, $id])) {
        // Redireciona de volta para a mesma página com a situação atualizada
        header('Location: ../painel.php?page=mostrar_clientes&situacao=' . $situacao_anterior);
    } else {
        // Em caso de erro, redireciona para a mesma página com uma mensagem de erro
        header('Location: ../painel.php?page=mostrar_clientes&situacao=' . $situacao_anterior . '&erro=1');
    }
    exit();
}
?>
