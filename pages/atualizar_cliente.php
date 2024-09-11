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
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];

    // Buscar os dados antigos do cliente
    $sqlSelect = "SELECT * FROM clientes WHERE id = :id";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtSelect->execute();
    $clienteAntigo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    // Atualiza os dados no banco de dados
    $sql = "UPDATE clientes SET nome = :nome, cpf = :cpf, telefone = :telefone, email = :email, endereco = :endereco WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':endereco', $endereco);

    if ($stmt->execute()) {
        // Buscar os dados novos do cliente (após a atualização)
        $stmtSelect->closeCursor(); // Reinicia o cursor do prepared statement
        $stmtSelect->execute();
        $clienteNovo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        // Comparar os dados antigos e novos e identificar as mudanças
        $mudancas = [];
        foreach ($clienteNovo as $campo => $valor) {
            if ($clienteAntigo[$campo] != $valor) {
                $mudancas[] = "Campo '$campo' alterado de '" . $clienteAntigo[$campo] . "' para '" . $valor . "'";
            }
        }

        // Converter as mudanças para uma string
        $descricaoMudancas = implode("; ", $mudancas);

        // Converter os dados para JSON para facilitar o armazenamento
        $dadosAntigos = json_encode($clienteAntigo);
        $dadosNovos = json_encode($clienteNovo);

        // Cria o comando SQL completo para o log
        $comandoSqlCompleto = "UPDATE clientes SET nome = '$nome', cpf = '$cpf', telefone = '$telefone', email = '$email', endereco = '$endereco' WHERE id = $id";

        // Registra a ação no log, incluindo a descrição das mudanças e os dados novos
        registrarLog($conn, $usuarioLogado, 'Atualização', 'clientes', $comandoSqlCompleto, $descricaoMudancas, $dadosNovos);

        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
