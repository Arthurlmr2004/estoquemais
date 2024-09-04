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

// Obtém a quantidade de clientes ativos e inativos
$queryClientesAtivos = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE situacao = 'ativo'");
$totalClientesAtivos = $queryClientesAtivos->fetch(PDO::FETCH_ASSOC)['total'];

$queryClientesInativos = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE situacao = 'inativo'");
$totalClientesInativos = $queryClientesInativos->fetch(PDO::FETCH_ASSOC)['total'];

// Obtém a quantidade de fornecedores ativos e inativos
$queryFornecedoresAtivos = $conn->query("SELECT COUNT(*) as total FROM fornecedores WHERE situacao = 'ativo'");
$totalFornecedoresAtivos = $queryFornecedoresAtivos->fetch(PDO::FETCH_ASSOC)['total'];

$queryFornecedoresInativos = $conn->query("SELECT COUNT(*) as total FROM fornecedores WHERE situacao = 'inativo'");
$totalFornecedoresInativos = $queryFornecedoresInativos->fetch(PDO::FETCH_ASSOC)['total'];

// Obtém a quantidade de produtos ativos e inativos
$queryProdutosAtivos = $conn->query("SELECT COUNT(*) as total FROM produtos WHERE situacao = 'ativo'");
$totalProdutosAtivos = $queryProdutosAtivos->fetch(PDO::FETCH_ASSOC)['total'];

$queryProdutosInativos = $conn->query("SELECT COUNT(*) as total FROM produtos WHERE situacao = 'inativo'");
$totalProdutosInativos = $queryProdutosInativos->fetch(PDO::FETCH_ASSOC)['total'];
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
        }

        h1 {
            text-align: center;
        }

        .container {
            display: flex;
            min-height: 100vh;
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
            justify-content: space-around;
            margin-top: 20px;
        }

        .stats .stat-box {
            background-color: #f4f4f4;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            flex: 1;
            margin: 0 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
            }

            .stats .stat-box {
                margin-bottom: 20px;
                width: 80%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <h2><?php echo ($permissao === 'admin') ? 'Administração' : 'Painel do Usuário'; ?></h2>
            <img src="images/Logo.png" alt="">

            <?php if ($permissao === 'admin'): ?>
                <div>
                    <a href="javascript:void(0);" onclick="toggleMenu('clientes-menu')">Cadastro de Clientes <span class="arrow down"></span></a>
                    <div id="clientes-menu" class="submenu">
                        <a href="?page=cadastro_cliente">Inserir</a>
                        <a href="?page=mostrar_clientes&situacao=ativo">Mostrar Clientes Ativos</a>
                        <a href="?page=mostrar_clientes&situacao=inativo">Mostrar Clientes Inativos</a>
                    </div>
                </div>
                <div>
                    <a href="javascript:void(0);" onclick="toggleMenu('fornecedores-menu')">Cadastro de Fornecedores <span class="arrow down"></span></a>
                    <div id="fornecedores-menu" class="submenu">
                        <a href="?page=cadastro_fornecedor">Inserir</a>
                        <a href="?page=mostrar_fornecedores&situacao=ativo">Mostrar Fornecedores Ativos</a>
                        <a href="?page=mostrar_fornecedores&situacao=inativo">Mostrar Fornecedores Inativos</a>
                    </div>
                </div>
                <div>
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
            <?php
            $page = isset($_GET['page']) ? $_GET['page'] : 'home';
            $pageFile = 'pages/' . $page . '.php';

            if (file_exists($pageFile)) {
                include $pageFile;
            } else {
                echo '<h1>Navegue pelo nosso menu!</h1>';
            }
            ?>
            
            <?php
            $page = isset($_GET['page']) ? $_GET['page'] : 'home';
            if ($page === 'home') {
                echo '<div class="stats">
                    <div class="stat-box">
                        <h3>Total de Clientes Ativos</h3>
                        <p>' . $totalClientesAtivos . '</p>
                    </div>
                    <div class="stat-box">
                        <h3>Total de Clientes Inativos</h3>
                        <p>' . $totalClientesInativos . '</p>
                    </div>
                    <div class="stat-box">
                        <h3>Total de Fornecedores Ativos</h3>
                        <p>' . $totalFornecedoresAtivos . '</p>
                    </div>
                    <div class="stat-box">
                        <h3>Total de Fornecedores Inativos</h3>
                        <p>' . $totalFornecedoresInativos . '</p>
                    </div>
                    <div class="stat-box">
                        <h3>Total de Produtos Ativos</h3>
                        <p>' . $totalProdutosAtivos . '</p>
                    </div>
                    <div class="stat-box">
                        <h3>Total de Produtos Inativos</h3>
                        <p>' . $totalProdutosInativos . '</p>
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>

    <script>
        function toggleMenu(id) {
            var menu = document.getElementById(id);
            var arrow = menu.previousElementSibling.querySelector('.arrow');
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
    </script>
</body>

</html>