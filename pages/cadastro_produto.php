<?php
include 'includes/conexao.php';
include 'funcoes_log.php';

// Verifica se o usuário está autenticado como admin ou vendedor
if (!isset($_SESSION['perfil']) || ($_SESSION['perfil'] !== 'admin' && $_SESSION['perfil'] !== 'vendedor')) {
    header('Location: nao_autorizado.php');
    exit();
}

$usuarioLogado = $_SESSION['usuario'];

// Inicialmente, não exibe o modal
$showModal = false;
$mensagemErro = "";
$produtos = [];
$fornecedores = [];

// Inicializa a variável da imagem
$imagem_nome = NULL;

// Buscar fornecedores
$sqlFornecedores = "SELECT id, nome FROM fornecedores WHERE situacao = 'ativo'";
$stmtFornecedores = $conn->query($sqlFornecedores);
$fornecedores = $stmtFornecedores->fetchAll(PDO::FETCH_ASSOC);

// Buscar produtos para o formulário "Já Cadastrado"
$sqlProdutos = "SELECT id, nome, preco, fornecedor_id FROM produtos WHERE situacao = 'ativo'";
$stmtProdutos = $conn->query($sqlProdutos);
$produtos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["opcao"]) && $_POST["opcao"] === "cadastrar") {
        // Cadastro de novo produto
        $nome = $_POST["nome"];
        $descricao = $_POST["descricao"];
        $preco = $_POST["preco"];
        $quantidade = $_POST["quantidade"];
        $estoque_minimo = $_POST["estoque_minimo"];
        $estoque_maximo = $_POST["estoque_maximo"];
        $fornecedor_id = $_POST["fornecedor_id"];
        $imagem = $_FILES["imagem"];

        try {
            // Verifica se o diretório para imagens existe, se não, cria
            $dir_imagens = 'imagens/';
            if (!is_dir($dir_imagens)) {
                mkdir($dir_imagens, 0755, true); // 0755 é uma permissão padrão para diretórios
            }

            $sqlCadastroProduto = "INSERT INTO produtos (nome, descricao, preco, quantidade, estoque_minimo, estoque_maximo, fornecedor_id, imagem, situacao) VALUES (:nome, :descricao, :preco, :quantidade, :estoque_minimo, :estoque_maximo, :fornecedor_id, :imagem, 'ativo')";
            $stmtCadastroProduto = $conn->prepare($sqlCadastroProduto);

            $stmtCadastroProduto->bindParam(':nome', $nome);
            $stmtCadastroProduto->bindParam(':descricao', $descricao);
            $stmtCadastroProduto->bindParam(':preco', $preco);
            $stmtCadastroProduto->bindParam(':quantidade', $quantidade);
            $stmtCadastroProduto->bindParam(':estoque_minimo', $estoque_minimo);
            $stmtCadastroProduto->bindParam(':estoque_maximo', $estoque_maximo);
            $stmtCadastroProduto->bindParam(':fornecedor_id', $fornecedor_id);

            // Lida com o upload da imagem
            if ($imagem['error'] == UPLOAD_ERR_OK) {
                $imagem_nome = basename($imagem["name"]);
                $imagem_destino = $dir_imagens . $imagem_nome;

                if (move_uploaded_file($imagem["tmp_name"], $imagem_destino)) {
                    $stmtCadastroProduto->bindParam(':imagem', $imagem_nome);
                } else {
                    $mensagemErro = "Erro ao mover o arquivo para o diretório de imagens.";
                    $stmtCadastroProduto->bindValue(':imagem', NULL);
                }
            } else {
                $stmtCadastroProduto->bindValue(':imagem', NULL);
            }

            if ($stmtCadastroProduto->execute()) {
                $showModal = true;
                $mensagemModal = "Produto cadastrado com sucesso!";

                // Cria uma descrição detalhada das mudanças para o log
                $mudancas = [
                    "Nome: $nome",
                    "Descrição: $descricao",
                    "Preço: $preco",
                    "Quantidade: $quantidade",
                    "Estoque Mínimo: $estoque_minimo",
                    "Estoque Máximo: $estoque_maximo",
                    "Fornecedor ID: $fornecedor_id",
                    "Imagem: $imagem_nome"
                ];

                // Converter as mudanças para uma string
                $descricaoMudancas = implode("; ", $mudancas);

                // Cria o comando SQL completo para o log
                $comandoSqlCompleto = "INSERT INTO produtos (nome, descricao, preco, quantidade, estoque_minimo, estoque_maximo, fornecedor_id, imagem, situacao) VALUES ('$nome', '$descricao', '$preco', '$quantidade', '$estoque_minimo', '$estoque_maximo', '$fornecedor_id', '$imagem_nome', 'ativo')";

                // Registra a ação no log, incluindo a descrição das mudanças
                registrarLog($conn, $usuarioLogado, 'Cadastro de Produto', 'produtos', $comandoSqlCompleto, '', $descricaoMudancas);
            } else {
                $mensagemErro = "Erro ao cadastrar o produto.";
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao cadastrar o produto: " . $e->getMessage();
        }
    } elseif (isset($_POST["opcao"]) && $_POST["opcao"] === "atualizar") {
        // Atualizar produto existente
        $produto_id = $_POST["produto_id"];
        $quantidade = $_POST["quantidade"];
        $novo_fornecedor_id = $_POST["novo_fornecedor_id"];

        try {
            // Atualiza a quantidade e o fornecedor do produto
            $sqlAtualizaProduto = "UPDATE produtos SET quantidade = quantidade + :quantidade, fornecedor_id = :novo_fornecedor_id WHERE id = :produto_id";
            $stmtAtualizaProduto = $conn->prepare($sqlAtualizaProduto);
            $stmtAtualizaProduto->bindParam(':quantidade', $quantidade);
            $stmtAtualizaProduto->bindParam(':novo_fornecedor_id', $novo_fornecedor_id);
            $stmtAtualizaProduto->bindParam(':produto_id', $produto_id);

            if ($stmtAtualizaProduto->execute()) {
                $showModal = true;
                $mensagemModal = "Quantidade e fornecedor atualizados com sucesso!";

                // Cria uma descrição detalhada das mudanças para o log
                $mudancas = [
                    "Produto ID: $produto_id",
                    "Quantidade Adicionada: $quantidade",
                    "Novo Fornecedor ID: $novo_fornecedor_id"
                ];

                // Converter as mudanças para uma string
                $descricaoMudancas = implode("; ", $mudancas);

                // Cria o comando SQL completo para o log
                $comandoSqlCompleto = "UPDATE produtos SET quantidade = quantidade + $quantidade, fornecedor_id = $novo_fornecedor_id WHERE id = $produto_id";

                // Registra a ação no log, incluindo a descrição das mudanças
                registrarLog($conn, $usuarioLogado, 'Atualização de Quantidade de Produto', 'produtos', $comandoSqlCompleto, '', $descricaoMudancas);
            } else {
                $mensagemErro = "Erro ao atualizar a quantidade do produto.";
            }
        } catch (PDOException $e) {
            $mensagemErro = "Erro ao atualizar a quantidade do produto: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro e Atualização de Produtos</title>
    <link rel="stylesheet" href="../estilos/estilos.css">
    <style>
        .modal {
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 40%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            animation: fadeIn 0.3s ease;
            /* Adiciona uma animação suave ao aparecer */
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }


        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Estilizando o input de arquivo */
        #imagem {
            display: none;
            /* Ocultar o input file padrão */
        }

        /* Estilizar o rótulo como um botão */
        .custom-file-upload {
            display: inline-block;
            padding: 10px 20px;
            cursor: pointer;
            background-color: #2C3E50;
            color: white;
            border-radius: 5px;
            border: none;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        /* Efeito de hover para o rótulo */
        .custom-file-upload:hover {
            background-color: #6f6a6a;
        }

        .btn {
            display: block;
            margin: auto;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #2C3E50;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #6f6a6a;
        }

        /* Estilo para a visualização da imagem */
        #imagemPreview {
            max-width: 200px;
            margin: auto;
            padding-bottom: 10px;
            object-fit: contain;
            display: block;
        }

        h2 {
            color: black;
        }

        .alinhar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
    </style>
</head>

<body>

    <?php if ($showModal): ?>
        <div class="modal" id="myModal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('myModal').style.display='none'">&times;</span>
                <p><?= htmlspecialchars($mensagemModal) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensagemErro)): ?>
        <div class="modal" id="errorModal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('errorModal').style.display='none'">&times;</span>
                <p><?= htmlspecialchars($mensagemErro) ?></p>
            </div>
        </div>
    <?php endif; ?>
    <!-- Formulários -->
    <div id="formulario-cadastro" class="formulario">
        <h2>Cadastro de Novo Produto</h2>
        <div class="alinhar">
            <button type="button" onclick="mostrarFormulario('formulario-cadastro')">Cadastrar Novo Produto</button>
            <button type="button" onclick="mostrarFormulario('formulario-atualizacao')">Atualizar Quantidade</button>
        </div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="opcao" value="cadastrar">
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" placeholder="Nome" required>

            <label for="descricao">Descrição:</label>
            <textarea id="descricao" name="descricao" placeholder="Descrição" required></textarea>

            <label for="preco">Preço:</label>
            <input type="number" id="preco" name="preco" step="0.01" placeholder="Preço" required>

            <label for="quantidade">Quantidade:</label>
            <input type="number" id="quantidade" name="quantidade" placeholder="Quantidade" required>

            <label for="estoque_minimo">Estoque Mínimo:</label>
            <input type="number" id="estoque_minimo" name="estoque_minimo" placeholder="Estoque mínimo" required>

            <label for="estoque_maximo">Estoque Máximo:</label>
            <input type="number" id="estoque_maximo" name="estoque_maximo" placeholder="Estoque máximo" required>

            <label for="fornecedor_id">Fornecedor:</label>
            <select id="fornecedor_id" name="fornecedor_id" required>
                <?php foreach ($fornecedores as $fornecedor): ?>
                    <option value="<?= $fornecedor['id'] ?>"><?= $fornecedor['nome'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="imagem">Imagem (opcional):</label>
            <input type="file" id="imagem" name="imagem" accept="image/*" onchange="previewImage(event)">
            <label for="imagem" class="custom-file-upload">
                <i class="fas fa-cloud-upload-alt"></i> Escolher Imagem
            </label>

            <!-- Exibir a pré-visualização da imagem -->
            <img id="imagePreview" src="#" alt="Pré-visualização da Imagem" style="display: none;">
            <button class="btn" type="submit">Cadastrar</button>
        </form>
    </div>

    <div id="formulario-atualizacao" class="formulario" style="display:none;">
        <h2>Atualizar Quantidade de Produto</h2>
        <form method="post">
            <input type="hidden" name="opcao" value="atualizar">
            <label for="produto_id">Produto:</label>
            <select id="produto_id" name="produto_id" required>
                <?php foreach ($produtos as $produto): ?>
                    <?php
                    // Buscar o nome do fornecedor para o produto atual
                    $sqlFornecedorNome = "SELECT nome FROM fornecedores WHERE id = :fornecedor_id";
                    $stmtFornecedorNome = $conn->prepare($sqlFornecedorNome);
                    $stmtFornecedorNome->bindParam(':fornecedor_id', $produto['fornecedor_id']);
                    $stmtFornecedorNome->execute();
                    $fornecedor = $stmtFornecedorNome->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <option value="<?= $produto['id'] ?>"><?= $produto['nome'] ?> - <?= $produto['preco'] ?> - Fornecedor: <?= $fornecedor['nome'] ?></option>
                <?php endforeach; ?>
            </select>

            <label for="quantidade">Quantidade a Adicionar:</label>
            <input type="number" id="quantidade" name="quantidade" required>

            <label for="novo_fornecedor_id">Novo Fornecedor:</label>
            <select id="novo_fornecedor_id" name="novo_fornecedor_id" required>
                <?php foreach ($fornecedores as $fornecedor): ?>
                    <option value="<?= $fornecedor['id'] ?>"><?= $fornecedor['nome'] ?></option>
                <?php endforeach; ?>
            </select>

            <button class="btn" type="submit">Atualizar</button>
        </form>
    </div>

    <!-- Navegação para mostrar/ocultar formulários -->

    <script>
        function mostrarFormulario(idFormulario) {
            document.querySelectorAll('.formulario').forEach(function(formulario) {
                formulario.style.display = 'none';
            });
            document.getElementById(idFormulario).style.display = 'block';
        }

        document.querySelector('.close').addEventListener('click', function() {
            document.getElementById('myModal').style.display = 'none';
            window.location.href = 'painel.php?page=cadastro_produto';
        });

        // Função para mostrar a pré-visualização da imagem
        function previewImage(event) {
            const input = event.target;
            const file = input.files[0];
            const preview = document.getElementById('imagePreview'); // Certifique-se que o ID está correto

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };

                reader.readAsDataURL(file);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        }
    </script>
</body>

</html>