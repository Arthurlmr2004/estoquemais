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
$showModal = false; // Variável para o controle do modal
$mensagemErro = ""; // Variável para a mensagem de erro

// Buscar fornecedores
$sqlFornecedores = "SELECT id, nome FROM fornecedores";
$stmtFornecedores = $conn->query($sqlFornecedores);
$fornecedores = $stmtFornecedores->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $descricao = $_POST["descricao"];
    $preco = $_POST["preco"];
    $quantidade = $_POST["quantidade"];
    $estoque_minimo = $_POST["estoque_minimo"];
    $estoque_maximo = $_POST["estoque_maximo"];
    $fornecedor_id = $_POST["fornecedor_id"];
    $imagem = '';

    // Verifica se uma imagem foi enviada
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $nomeImagem = $_FILES['imagem']['name'];
        $tempImagem = $_FILES['imagem']['tmp_name'];
        $pastaDestino = 'uploads/';

        if (!is_dir($pastaDestino)) {
            mkdir($pastaDestino, 0755, true);
        }

        $caminhoImagem = $pastaDestino . uniqid() . '_' . $nomeImagem;
        if (move_uploaded_file($tempImagem, $caminhoImagem)) {
            $imagem = $caminhoImagem;
        }
    }

    try {
        // Verifica se o produto já existe, independente de maiúsculas e minúsculas
        $sqlVerificaProduto = "SELECT id FROM produtos WHERE LOWER(nome) = LOWER(:nome) AND fornecedor_id = :fornecedor_id";
        $stmtVerificaProduto = $conn->prepare($sqlVerificaProduto);
        $stmtVerificaProduto->bindParam(':nome', $nome);
        $stmtVerificaProduto->bindParam(':fornecedor_id', $fornecedor_id);
        $stmtVerificaProduto->execute();
        $produtoExistente = $stmtVerificaProduto->fetch(PDO::FETCH_ASSOC);

        if ($produtoExistente) {
            $mensagemErro = "Erro: Produto já cadastrado!";
        } else {
            // Insere o novo produto
            $sql = "INSERT INTO produtos (nome, descricao, preco, quantidade, estoque_minimo, estoque_maximo, fornecedor_id, imagem) 
                    VALUES (:nome, :descricao, :preco, :quantidade, :estoque_minimo, :estoque_maximo, :fornecedor_id, :imagem)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':preco', $preco);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':estoque_minimo', $estoque_minimo);
            $stmt->bindParam(':estoque_maximo', $estoque_maximo);
            $stmt->bindParam(':fornecedor_id', $fornecedor_id);
            $stmt->bindParam(':imagem', $imagem);

            if ($stmt->execute()) {
                $showModal = true;
                // Crie uma descrição detalhada das mudanças para o log
                $mudancas = [
                    "Nome: $nome",
                    "Descrição: $descricao",
                    "Preço: R$ $preco",
                    "Quantidade: $quantidade",
                    "Estoque Mínimo: $estoque_minimo",
                    "Estoque Máximo: $estoque_maximo",
                    "Fornecedor ID: $fornecedor_id",
                    "Imagem: " . ($imagem ? $imagem : 'Sem imagem')
                ];

                // Converter as mudanças para uma string
                $descricaoMudancas = implode("; ", $mudancas);

                // Cria o comando SQL completo para o log
                $comandoSqlCompleto = "INSERT INTO produtos (nome, descricao, preco, quantidade, estoque_minimo, estoque_maximo, fornecedor_id, imagem) 
                                       VALUES ('" . addslashes($nome) . "', '" . addslashes($descricao) . "', $preco, $quantidade, $estoque_minimo, $estoque_maximo, $fornecedor_id, '" . addslashes($imagem) . "')";

                // Registra a ação no log, incluindo a descrição das mudanças
                registrarLog($conn, $usuarioLogado, 'Inserção de Produto', 'produtos', $comandoSqlCompleto, '', $descricaoMudancas);
            } else {
                $mensagemErro = "Erro ao cadastrar o produto.";
            }
        }
    } catch (PDOException $e) {
        $mensagemErro = "Erro ao cadastrar o produto: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Cadastro de Produto</title>
    <link rel="stylesheet" href="estilos/estilos.css">
    <style>
        /* Estilos para a janela modal */
        .modal {
            display: none;
            /* Inicialmente oculta */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
            align-items: center;
            justify-content: center;
            display: flex;
            /* Centraliza o modal verticalmente e horizontalmente */
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 40%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            margin: auto;
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
    </style>
</head>

<body>

    <h2>Cadastrar Produto</h2>

    <form method="post" enctype="multipart/form-data">
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" placeholder="Nome do produto" required>

        <label for="descricao">Descrição:</label>
        <textarea id="descricao" name="descricao" placeholder="Descrição do produto"></textarea>

        <label for="preco">Preço:</label>
        <input type="number" id="preco" name="preco" step="0.01" placeholder="Preço do produto" required>

        <label for="quantidade">Quantidade:</label>
        <input type="number" id="quantidade" name="quantidade" placeholder="Quantidade em estoque" required>

        <label for="estoque_minimo">Estoque Mínimo:</label>
        <input type="number" id="estoque_minimo" name="estoque_minimo" placeholder="Estoque mínimo" required>

        <label for="estoque_maximo">Estoque Máximo:</label>
        <input type="number" id="estoque_maximo" name="estoque_maximo" placeholder="Estoque máximo" required>

        <label for="fornecedor_id">Fornecedor:</label>
        <select id="fornecedor_id" name="fornecedor_id" required>
            <option value="">Selecione um fornecedor</option>
            <?php foreach ($fornecedores as $fornecedor) { ?>
                <option value="<?php echo $fornecedor['id']; ?>"><?php echo $fornecedor['nome']; ?></option>
            <?php } ?>
        </select>

        <label for="imagem" class="custom-file-upload">
            Selecionar imagem
        </label>
        <input type="file" id="imagem" name="imagem" accept="image/*" onchange="previewImagem(event)">
        <img id="imagemPreview" alt="Pré-visualização da Imagem" style="display:none;">

        <button class="btn" type="submit">Cadastrar</button>
    </form>

    <!-- Modal para exibir a mensagem -->
    <?php if ($showModal) : ?>
        <div id="myModal" class="modal" style="display:block;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <p>Produto cadastrado com sucesso!</p>
            </div>
        </div>
    <?php elseif ($mensagemErro) : ?>
        <div id="myModal" class="modal" style="display:block;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <p><?php echo $mensagemErro; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Função para fechar o modal
        document.querySelector('.close').addEventListener('click', function() {
            document.getElementById('myModal').style.display = 'none';
        });

        // Função para mostrar a pré-visualização da imagem
        function previewImagem(event) {
            const imagemPreview = document.getElementById('imagemPreview');
            imagemPreview.src = URL.createObjectURL(event.target.files[0]);
            imagemPreview.style.display = "block";
        }
    </script>

</body>

</html>