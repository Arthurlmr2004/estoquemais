<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit();
}

// Verifica permissões
$permissao = isset($_SESSION['permissao']) ? $_SESSION['permissao'] : 'usuario';

// Inclui a conexão com o banco de dados
include 'pages/conexao.php';

// Obtém a quantidade de produtos ativos e inativos
$queryProdutosAtivos = $conn->query("SELECT COUNT(*) as total FROM produtos WHERE situacao = 'ativo'");
$totalProdutosAtivos = $queryProdutosAtivos->fetch(PDO::FETCH_ASSOC)['total'];

// Obtém a quantidade de fornecedores ativos
$queryFornecedoresAtivos = $conn->query("SELECT COUNT(*) as total FROM fornecedores WHERE situacao = 'ativo'");
$totalFornecedoresAtivos = $queryFornecedoresAtivos->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel</title>
    <link rel="stylesheet" href="estilos/estilos.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        h1 {
            text-align: center;
        }

        .container {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #fff;
            padding: 15px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            text-align: center;
            color: #FFF;
        }

        .sidebar a {
            display: block;
            color: #fff;
            padding: 10px;
            text-decoration: none;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .sidebar a:hover {
            background-color: #34495e;
        }

        .content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            position: relative;
        }

        .sidebar img {
            display: block;
            margin: 10px auto;
            max-width: 100%;
            height: auto;
        }

        .menu-item {
            margin-bottom: 10px;
        }

        .menu-item h3 {
            margin: 0;
            cursor: pointer;
        }

        .submenu {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.3s ease-out, opacity 0.3s ease-out;
            padding-left: 15px;
        }

        .submenu.open {
            max-height: 500px;
            opacity: 1;
        }

        .submenu a {
            background-color: #34495e;
            padding: 8px;
            border-radius: 4px;
        }

        .submenu a:hover {
            background-color: #2c3e50;
        }

        .arrow {
            display: inline-block;
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            cursor: pointer;
            margin-right: 10px;
            border-top: 5px solid #fff;
            transition: transform 0.3s ease;
        }

        .arrow.down {
            border-top: 5px solid #fff;
            transform: rotate(0deg);
        }

        .arrow.up {
            transform: rotate(180deg);
        }

        .stats {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
            padding: 20px;
            background-color: #f4f4f4;
            border-top: 1px solid #ddd;
        }

        .stats .stat-box {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            width: calc(50% - 20px);
            margin: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        .stats .stat-box h3 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }

        .stats .stat-box p {
            margin: 5px 0 0;
            font-size: 18px;
            color: #34495e;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
            }

            .content {
                margin-left: 0;
            }

            .stats {
                flex-direction: column;
                align-items: center;
                padding: 10px;
            }

            .stats .stat-box {
                width: 80%;
                margin-bottom: 10px;
            }
        }
    </style>
    <!-- Adicionando o Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <h2><?php echo ($permissao === 'admin') ? 'Administração' : 'Painel do Usuário'; ?></h2>
            <img src="images/Logo.png" alt="">

            <?php if ($permissao === 'admin'): ?>
                <div class="menu-item">
                    <a href="javascript:void(0);" onclick="toggleMenu('clientes-menu')">Cadastro de Clientes <span class="arrow down"></span></a>
                    <div id="clientes-menu" class="submenu">
                        <a href="?page=cadastro_cliente">Inserir</a>
                        <a href="?page=mostrar_clientes&situacao=ativo">Mostrar Clientes Ativos</a>
                        <a href="?page=mostrar_clientes&situacao=inativo">Mostrar Clientes Inativos</a>
                    </div>
                </div>
                <div class="menu-item">
                    <a href="javascript:void(0);" onclick="toggleMenu('fornecedores-menu')">Cadastro de Fornecedores <span class="arrow down"></span></a>
                    <div id="fornecedores-menu" class="submenu">
                        <a href="?page=cadastro_fornecedor">Inserir</a>
                        <a href="?page=mostrar_fornecedores&situacao=ativo">Mostrar Fornecedores Ativos</a>
                        <a href="?page=mostrar_fornecedores&situacao=inativo">Mostrar Fornecedores Inativos</a>
                    </div>
                </div>
                <div class="menu-item">
                    <a href="javascript:void(0);" onclick="toggleMenu('produtos-menu')">Cadastro de Produtos <span class="arrow down"></span></a>
                    <div id="produtos-menu" class="submenu">
                        <a href="?page=cadastro_produto">Inserir</a>
                        <a href="?page=mostrar_produtos&situacao=ativo">Mostrar Produtos Ativos</a>
                        <a href="?page=mostrar_produtos&situacao=inativo">Mostrar Produtos Inativos</a>
                    </div>
                </div>
                <a href="?page=entrada_produtos">Entrada de Produtos</a>
                <a href="?page=saida_produtos">Saída de Produtos</a>
                <a href="?page=relatorios">Relatórios</a>
                <a href="?page=gerenciar_estoque">Gerenciar Estoque</a>
            <?php else: ?>
                <a href="?page=ver_produtos_usuario">Ver Produtos</a>
            <?php endif; ?>
            <a href="logout.php">Sair</a>
        </div>

        <div class="content">
            <h1>Navegue pelo nosso menu!</h1>

            <!-- Adicionando o gráfico de pizza -->
            <div style="width: 50%; max-width: 400px; margin: 20px auto;">
                <canvas id="produtosFornecedoresPieChart"></canvas>
            </div>

            <!-- Dados abaixo do gráfico -->
            <div class="stats">
                <div class="stat-box">
                    <h3><?php echo $totalProdutosAtivos; ?></h3>
                    <p>Produtos em Estoque</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo $totalFornecedoresAtivos; ?></h3>
                    <p>Fornecedores Ativos</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu(menuId) {
            const menu = document.getElementById(menuId);
            const arrow = menu.previousElementSibling.querySelector('.arrow');

            if (menu.classList.contains('open')) {
                menu.classList.remove('open');
                arrow.classList.remove('up');
                arrow.classList.add('down');
            } else {
                menu.classList.add('open');
                arrow.classList.remove('down');
                arrow.classList.add('up');
            }
        }

        // Criação do gráfico de pizza
        const ctx = document.getElementById('produtosFornecedoresPieChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Produtos em Estoque', 'Fornecedores Ativos'],
                datasets: [{
                    label: 'Quantidade',
                    data: [<?php echo $totalProdutosAtivos; ?>, <?php echo $totalFornecedoresAtivos; ?>],
                    backgroundColor: ['#3498db', '#e74c3c'],
                    borderColor: ['#2980b9', '#c0392b'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (context.parsed !== null) {
                                    label += ': ' + context.parsed + ' unidades';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>
