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
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recebe os dados enviados via AJAX
    $id = $_POST['id'];
    $estoque_minimo = $_POST['estoque_minimo'];
    $estoque_maximo = $_POST['estoque_maximo'];

    // Validação básica
    if ($estoque_minimo > $estoque_maximo) {
        echo json_encode(['status' => 'error', 'message' => 'O estoque mínimo não pode ser maior que o estoque máximo.']);
        exit;
    }

    // Buscar os dados antigos do produto
    $sqlSelect = "SELECT * FROM produtos WHERE id = :id";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtSelect->execute();
    $produtoAntigo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    // Atualiza os dados no banco de dados
    $sql = "UPDATE produtos SET estoque_minimo = :estoque_minimo, estoque_maximo = :estoque_maximo WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':estoque_minimo', $estoque_minimo);
    $stmt->bindParam(':estoque_maximo', $estoque_maximo);

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
        $comandoSqlCompleto = "UPDATE produtos SET estoque_minimo = '$estoque_minimo', estoque_maximo = '$estoque_maximo' WHERE id = $id";

        // Registra a ação no log, incluindo a descrição das mudanças e os dados novos
        registrarLog($conn, $usuarioLogado, 'Atualização de Estoque', 'produtos', $comandoSqlCompleto, $descricaoMudancas, $dadosNovos);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
