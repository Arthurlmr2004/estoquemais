<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Não Autorizado</title>
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8d7da;
            /* Cor de fundo - Vermelho claro */
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            color: #721c24;
            /* Cor do título - Vermelho escuro */
            margin-bottom: 20px;
        }

        p {
            font-size: 1.2rem;
        }

        a {
            color: #007bff;
            /* Cor do link - Azul */
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Não Autorizado</h1>
        <p>Você não tem permissão para acessar esta página.</p>
        <p><a href="javascript:history.back()">Voltar para a página anterior</a></p>
    </div>
</body>

</html>