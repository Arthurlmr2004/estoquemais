<?php
include '../includes/conexao.php';
include('funcoes_log.php'); // Inclua o arquivo com a função registrarLog()

session_start(); // Inicie a sessão

// Verifica se o usuário está autenticado e tem permissão
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

// Obtém o nome do usuário logado (assumindo que você armazena isso na sessão)
$usuarioLogado = $_SESSION['usuario'];

// Obtém o ID do fornecedor e a nova situação da URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$situacao = isset($_GET['situacao']) && $_GET['situacao'] === 'ativo' ? 'ativo' : 'inativo';

if ($id > 0) {
    // Consulta para obter os dados atuais do fornecedor antes da atualização
    $sqlSelect = "SELECT * FROM fornecedores WHERE id = ?";
    $stmtSelect = $conn->prepare($sqlSelect);
    $stmtSelect->execute([$id]);
    $fornecedorAntigo = $stmtSelect->fetch(PDO::FETCH_ASSOC); // Armazena os dados antigos

    // Atualiza a situação do fornecedor
    $sql = "UPDATE fornecedores SET situacao = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt->execute([$situacao, $id])) {
        // Consulta novamente para obter os dados atualizados do fornecedor
        $stmtSelect->execute([$id]);
        $fornecedorNovo = $stmtSelect->fetch(PDO::FETCH_ASSOC); // Armazena os dados novos

        // Comparar os dados e registrar as mudanças
        $mudancas = [];
        foreach ($fornecedorNovo as $campo => $valor) {
            if ($fornecedorAntigo[$campo] != $valor) {
                $mudancas[] = "Campo '$campo' alterado de '" . $fornecedorAntigo[$campo] . "' para '" . $valor . "'";
            }
        }
        $descricaoMudancas = implode("; ", $mudancas);

        // Cria o comando SQL completo com os valores
        $comandoSqlCompleto = "UPDATE fornecedores SET situacao = '" . addslashes($situacao) . "' WHERE id = " . $id;

        // Registra o log da ação com o comando SQL completo e as mudanças
        registrarLog($conn, $usuarioLogado, 'Atualização de Status', 'fornecedores', $comandoSqlCompleto, $descricaoMudancas, json_encode($fornecedorNovo));

        // Redireciona ou retorna uma resposta de sucesso
        echo "Status do fornecedor atualizado com sucesso!";
        exit();
    } else {
        echo "Erro ao atualizar o status do fornecedor.";
    }
}
