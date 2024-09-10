<?php
include '../includes/conexao.php';
include 'funcoes_log.php';

session_start();

// Verificação de Autenticação e Permissão
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    echo json_encode(['error' => 'Você não tem permissão para realizar esta ação.']);
    exit();
}

// Obtém o nome do usuário logado
$usuarioLogado = $_SESSION['usuario'];

// Verifica se o método de requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação e Sanitização
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING);
    $preco = filter_input(INPUT_POST, 'preco', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_SANITIZE_NUMBER_INT);

    if (empty($id) || empty($nome) || empty($descricao) || empty($preco) || empty($quantidade)) {
        echo json_encode(['error' => 'Todos os campos são obrigatórios.']);
        exit;
    }

    // Buscar os dados antigos do produto
    $sqlSelect = "SELECT * FROM produtos WHERE id = :id";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtSelect->execute();
    $produtoAntigo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    try {
        // Consulta SQL usando Prepared Statement
        $sql = "UPDATE produtos SET
                nome = :nome,
                descricao = :descricao,
                preco = :preco,
                quantidade = :quantidade
                WHERE id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindParam(':descricao', $descricao, PDO::PARAM_STR);
        $stmt->bindParam(':preco', $preco); // Tipo de dado do preço
        $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            // Buscar os dados novos do produto (após a atualização)
            $stmtSelect->closeCursor(); // Reinicia o cursor do prepared statement
            $stmtSelect->execute();
            $produtoNovo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

            // Comparar os dados antigos e novos e identificar as mudanças
            $mudancas = [];
            foreach ($produtoNovo as $campo => $valor) {
                if ($produtoAntigo[$campo] != $valor) {
                    $mudancas[] = "Campo '$campo' alterado de '" . $produtoAntigo[$campo] . "' para '" . $valor . "'";
                }
            }

            // Converter as mudanças para uma string
            $descricaoMudancas = implode("; ", $mudancas);

            // Converter os dados para JSON para facilitar o armazenamento
            $dadosAntigos = json_encode($produtoAntigo);
            $dadosNovos = json_encode($produtoNovo);

            // Cria o comando SQL completo para o log
            $comandoSqlCompleto = "UPDATE produtos SET nome = '$nome', descricao = '$descricao', preco = $preco, quantidade = $quantidade WHERE id = $id";

            // Registra a ação no log, incluindo a descrição das mudanças e os dados novos
            registrarLog($conn, $usuarioLogado, 'Atualização de Produto', 'produtos', $comandoSqlCompleto, $descricaoMudancas, $dadosNovos);

            echo json_encode(['success' => 'Produto atualizado com sucesso!']);
        } else {
            echo json_encode(['error' => 'Erro ao atualizar o produto.']);
        }

    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro na consulta: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Método de requisição inválido.']);
}
?>
