<?php
include('includes/conexao.php');

// Verifica o perfil do usuário
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

// Função para a paginação
function paginarLogs($totalRegistros, $itensPorPagina, $paginaAtual = 1, $paginaBaseUrl = '')
{
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    $paginacaoHTML = '<div class="paginacao">';

    // Botão "Anterior"
    if ($paginaAtual > 1) {
        $paginaAnterior = $paginaAtual - 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaAnterior&itensPorPagina=$itensPorPagina'><i class='fas fa-chevron-left'></i></a>";
    }

    // Exibir links de páginas
    $inicio = max(1, $paginaAtual - 1);
    $fim = min($totalPaginas, $paginaAtual + 1);

    for ($i = $inicio; $i <= $fim; $i++) {
        if ($i == $paginaAtual) {
            $paginacaoHTML .= "<span class='pagina-atual'>$i</span>";
        } else {
            $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$i&itensPorPagina=$itensPorPagina'>$i</a>";
        }
    }

    // Botão "Próximo"
    if ($paginaAtual < $totalPaginas) {
        $paginaProxima = $paginaAtual + 1;
        $paginacaoHTML .= "<a href='{$paginaBaseUrl}&pagina=$paginaProxima&itensPorPagina=$itensPorPagina'><i class='fas fa-chevron-right'></i></a>";
    }

    $paginacaoHTML .= '</div>';
    return $paginacaoHTML;
}

// Parâmetros de paginação
$itensPorPagina = isset($_GET['itensPorPagina']) ? (int)$_GET['itensPorPagina'] : 5;
$paginaAtual = isset($_GET['pagina']) && is_numeric($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Consulta para contar o número total de logs
$sqlContagem = "SELECT COUNT(*) as total FROM logs";
$stmtContagem = $conn->prepare($sqlContagem);
$stmtContagem->execute();
$totalLogs = $stmtContagem->fetch(PDO::FETCH_ASSOC)['total'];

// Consulta para exibir os logs com paginação
$sql = "SELECT * FROM logs ORDER BY data_hora DESC LIMIT :limite OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':limite', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// URL base para paginação
$paginaBaseUrl = "painel.php?page=mostrar_logs&itensPorPagina=$itensPorPagina";

// Gera o HTML da paginação
$paginacaoHTML = paginarLogs($totalLogs, $itensPorPagina, $paginaAtual, $paginaBaseUrl);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Atividades</title>
    <link rel="stylesheet" href="styles/style.css">

    <style>
        h1 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .paginacao {
            text-align: center;
            margin: 20px 0;
        }

        .paginacao a,
        .paginacao span {
            /* Use strong para a página atual */
            display: inline-block;
            padding: 10px 15px;
            margin: 0 5px;
            border-radius: 5px;
            text-decoration: none;
            background-color: white;
            border: 1px solid #ddd;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-weight: bold;
        }

        .paginacao a {
            color: #000;
        }

        .paginacao a:hover {
            background-color: #6f6a6a;
            color: #fff;
        }

        .paginacao .pagina-atual {
            /* Estilo para a página atual */
            background-color: #6f6a6a;
            /* Cor de fundo azul */
            color: #fff;
        }

        td {
            word-wrap: break-word;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
            /* Limita o tamanho das células */
        }

        td.dados {
            max-width: 250px;
            /* Tamanho máximo para os dados */
            word-wrap: break-word;
            /* Quebra de linha automática */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            /* Adiciona reticências para textos longos */
        }

        td.dados:hover {
            white-space: normal;
            /* Exibe o texto completo ao passar o mouse */
            overflow: visible;
            text-overflow: unset;
        }

        /* Estilos para linhas pares e ímpares */
        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
            /* Cor mais clara para linhas pares */
        }

        table tbody tr:nth-child(odd) {
            background-color: #ffffff;
            /* Branco para linhas ímpares */
        }
    </style>
</head>

<body>
    <h1>Logs de Atividades</h1>
    <table>
        <thead>
            <tr>
                <th>Usuário</th>
                <th>Ação</th>
                <th>Tabela</th>
                <th>Data e Hora</th>
                <th>Dados Antigos</th>
                <th>Dados Novos</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['usuario']); ?></td>
                    <td><?php echo htmlspecialchars($log['acao']); ?></td>
                    <td><?php echo htmlspecialchars($log['tabela']); ?></td>
                    <td><?php echo htmlspecialchars($log['data_hora']); ?></td>
                    <td><?php echo htmlspecialchars($log['dados_antigos']); ?></td>
                    <td><?php echo htmlspecialchars($log['dados_novos']); ?></td>
                    <td><?php echo htmlspecialchars($log['ip']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Exibir paginação -->
    <?php echo $paginacaoHTML; ?>

</body>

</html>