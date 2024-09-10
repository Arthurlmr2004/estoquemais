<?php
include '../includes/conexao.php';
include('funcoes_log.php'); // Certifique-se de que o caminho para o arquivo de conexão está correto
session_start(); // Inicie a sessão

// Verifica se o usuário está autenticado e tem permissão
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

// Obtém o nome do usuário logado (assumindo que você armazena isso na sessão)
$usuarioLogado = $_SESSION['usuario'];

// Obtém o ID do cliente e a situação desejada a partir da URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$situacao = isset($_GET['situacao']) && $_GET['situacao'] === 'ativo' ? 'ativo' : 'inativo';

// Atualiza a situação do cliente
if ($id > 0) {
    // Consulta para obter a situação atual do cliente
    $sqlSelect = "SELECT * FROM clientes WHERE id = ?";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->execute([$id]);
    $clienteAntigo = $stmtSelect->fetch(PDO::FETCH_ASSOC); 

    $sql = "UPDATE clientes SET situacao = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    $situacaoAnterior = $cliente['situacao']; // Armazena a situação anterior

    if ($stmt->execute([$situacao, $id])) {
        // Consulta para obter os dados atualizados do cliente
        $sqlSelect = "SELECT * FROM clientes WHERE id = ?"; 
        $stmtSelect = $conn->prepare($sqlSelect);
        $stmtSelect->execute([$id]);
        $clienteNovo = $stmtSelect->fetch(PDO::FETCH_ASSOC); 

        $dadosNovos = json_encode($clienteNovo); 

        // Comparar os dados e registrar as mudanças
        $mudancas = [];
        foreach ($clienteNovo as $campo => $valor) {
            if ($clienteAntigo[$campo] != $valor) {
                $mudancas[] = "Campo '$campo' alterado de '" . $clienteAntigo[$campo] . "' para '" . $valor . "'";
            }
        }
        $descricaoMudancas = implode("; ", $mudancas);

        // Cria o comando SQL completo com os valores
        $comandoSqlCompleto = "UPDATE clientes SET situacao = '" . addslashes($situacao) . "' WHERE id = " . $id;

        // Registra o log da ação com o comando SQL completo
        registrarLog($conn, $usuarioLogado, 'Atualização de Status', 'clientes', $comandoSqlCompleto, $descricaoMudancas, $dadosNovos);

        // Retorna uma mensagem de sucesso para o AJAX
        echo "Status do cliente atualizado com sucesso!";
    } else {
        echo "Erro ao atualizar o status do cliente.";
    }
}
