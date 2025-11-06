<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ds Barber - Cadastro</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="right-panel">
            <div class="login-form">
                <h2>Crie uma conta</h2>
                <p>Faça o cadastro e marque seu próximo agendamento!</p>
                <form action="../php/Funcoes/add-usuario.php" method="POST" id="signup-form">
                    <label for="fullname">Nome Completo</label>
                    <div class="input-group">
                        <input type="text" id="fullname" name="nome" placeholder="John Doe">
                    </div>
                    <div class="error-message" data-error-for="fullname"></div>

                    <label for="email">E-mail</label>
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="you@example.com">
                    </div>
                    <div class="error-message" data-error-for="email"></div>

                    <label for="password">Senha</label>
                    <div class="input-group">
                        <input type="password" id="password" name="senha" placeholder="Digite sua senha">
                    </div>
                    <div class="error-message" data-error-for="password"></div>

                    <label for="confirm-password">Confirme sua senha</label>
                    <div class="input-group">
                        <input type="password" id="confirm-password" placeholder="Confirme sua senha">
                    </div>
                    <div class="error-message" data-error-for="confirm-password"></div>

                    <button type="submit" class="login-btn">Cadastrar</button>
                </form>
                <p class="signup-link">Já tem uma conta? <a href="login.php">Entre aqui</a></p>
            </div>
        </div>
        <div class="left-panel">
            <h1>Ds Barber</h1>
            <p>Seu corte perfeito está há alguns cliques de distância.</p>
        </div>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>