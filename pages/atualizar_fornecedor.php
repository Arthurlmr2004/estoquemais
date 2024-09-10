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
    // Validação e Sanitização
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $endereco = $_POST['endereco'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $cnpj = $_POST['cnpj'];

    // Verifica se todos os campos estão preenchidos
    if (empty($id) || empty($nome) || empty($endereco) || empty($telefone) || empty($email) || empty($cnpj)) {
        echo json_encode(['error' => 'Todos os campos são obrigatórios.']);
        exit;
    }

    // Buscar os dados antigos do fornecedor
    $sqlSelect = "SELECT * FROM fornecedores WHERE id = :id";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtSelect->execute();
    $fornecedorAntigo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    // Atualiza o fornecedor no banco de dados
    $sql = "UPDATE fornecedores SET nome = :nome, endereco = :endereco, telefone = :telefone, email = :email, cnpj = :cnpj WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':endereco', $endereco);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':cnpj', $cnpj);

    if ($stmt->execute()) {
        // Buscar os dados novos do fornecedor (após a atualização)
        $stmtSelect->closeCursor(); // Reinicia o cursor do prepared statement
        $stmtSelect->execute();
        $fornecedorNovo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        // Comparar os dados antigos e novos e identificar as mudanças
        $mudancas = [];
        foreach ($fornecedorNovo as $campo => $valor) {
            if ($fornecedorAntigo[$campo] != $valor) {
                $mudancas[] = "Campo '$campo' alterado de '" . $fornecedorAntigo[$campo] . "' para '" . $valor . "'";
            }
        }

        // Converter as mudanças para uma string
        $descricaoMudancas = implode("; ", $mudancas);

        // Converter os dados para JSON para facilitar o armazenamento
        $dadosAntigos = json_encode($fornecedorAntigo);
        $dadosNovos = json_encode($fornecedorNovo);

        // Cria o comando SQL completo para o log
        $comandoSqlCompleto = "UPDATE fornecedores SET nome = '$nome', endereco = '$endereco', telefone = '$telefone', email = '$email', cnpj = '$cnpj' WHERE id = $id";

        // Registra a ação no log, incluindo a descrição das mudanças e os dados novos
        registrarLog($conn, $usuarioLogado, 'Atualização de Fornecedor', 'fornecedores', $comandoSqlCompleto, $descricaoMudancas, $dadosNovos);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>
