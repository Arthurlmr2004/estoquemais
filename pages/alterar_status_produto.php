<?php
include '../includes/conexao.php';
include('funcoes_log.php'); // Certifique-se de que o caminho está correto
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Obtém o nome de usuário da sessão
$usuarioLogado = $_SESSION['usuario'];

// Consulta o banco de dados para verificar as permissões do usuário
$sql = "SELECT permissao FROM usuarios WHERE usuario = :usuario";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':usuario', $usuarioLogado);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verifica se o usuário tem permissão 'admin' ou 'gerente'
if (!$usuario || ($usuario['permissao'] !== 'admin' && $usuario['permissao'] !== 'gerente')) {
    header('Location: ../login.php');
    exit();
}

// Obtém o ID do produto e a nova situação da URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$situacao = isset($_GET['situacao']) && $_GET['situacao'] === 'ativo' ? 'ativo' : 'inativo';

if ($id > 0) {
    // Consulta para obter os dados atuais do produto antes da atualização
    $sqlSelect = "SELECT * FROM produtos WHERE id = ?";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->execute([$id]);
    $produtoAntigo = $stmtSelect->fetch(PDO::FETCH_ASSOC); // Armazena os dados antigos

    // Atualiza a situação do produto
    $sql = "UPDATE produtos SET situacao = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$situacao, $id])) {
        // Consulta novamente para obter os dados atualizados do produto
        $stmtSelect->execute([$id]);
        $produtoNovo = $stmtSelect->fetch(PDO::FETCH_ASSOC); // Armazena os dados novos

        // Comparar os dados e registrar as mudanças
        $mudancas = [];
        foreach ($produtoNovo as $campo => $valor) {
            if ($produtoAntigo[$campo] != $valor) {
                $mudancas[] = "Campo '$campo' alterado de '" . $produtoAntigo[$campo] . "' para '" . $valor . "'";
            }
        }
        $descricaoMudancas = implode("; ", $mudancas);

        // Cria o comando SQL completo com os valores
        $comandoSqlCompleto = "UPDATE produtos SET situacao = '" . addslashes($situacao) . "' WHERE id = " . $id;

        // Registra o log da ação com o comando SQL completo e as mudanças
        registrarLog($conn, $usuarioLogado, 'Atualização de Status', 'produtos', $comandoSqlCompleto, $descricaoMudancas, json_encode($produtoNovo));

        // Retorna uma mensagem de sucesso para o AJAX
        echo "Status do produto atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o status do produto.";
    }
}
?>
