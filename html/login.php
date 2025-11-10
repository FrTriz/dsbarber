<?php
// Garante que a sessão está iniciada (coloque seu 'require_once' aqui)
require_once '../php/session-manager.php'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ds Barber - Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="manifest" href="../manifest.json?v=<?php echo filemtime('../manifest.json'); ?>">
    <link rel="apple-touch-icon" href="../logo-tela-inicial.png">
    <link rel="icon" href="../favicon.ico?v=<?php echo filemtime('../favicon.ico'); ?>" type="image/x-icon">
</head>
<body>
               <script>
        <?php
        // 1. Verifica se o PHP enviou uma mensagem de erro na sessão
        if (isset($_SESSION['erro_login']) && !empty($_SESSION['erro_login'])) {
            
            // 2. Imprime o JavaScript para mostrar o alerta
            // (Usamos json_encode para garantir que o texto não quebre o JS)
            echo "alert(" . json_encode($_SESSION['erro_login']) . ");";
            
            // 3. Limpa o erro da sessão
            // (Isso impede que o pop-up apareça de novo se o usuário der F5)
            unset($_SESSION['erro_login']);
        }
        ?>
    </script>
    <div class="container">
        <div class="left-panel">
            <h1>Ds Barber</h1>
            <p>Seu corte perfeito está há alguns cliques de distância.</p>
        </div>
        <div class="right-panel">
            <div class="login-form">
                <h2>Entrar</h2>
                <p>Bem-vindo de volta! Insira seu E-mail e Senha</p>
                <form action="../php/Funcoes/processamento-login.php" method="POST">
                    <label for="email">Email</label>
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder="demo@email.com">
                    </div>
                    <div class="error-message" data-error-for="email"></div>

                    <label for="password">Senha</label>
                    <div class="input-group">
                        <input type="password" id="password" name="senha" placeholder="Digite sua senha">
                    </div>
                    <div class="error-message" data-error-for="password"></div>

                    <div class="options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember">
                            <label for="remember">Lembre de mim</label>
                        </div>
                        <a href="#">Esqueceu a senha?</a>
                    </div>
                    <button type="submit" class="login-btn">Login</button>
                </form>
                <p class="signup-link">Não tem uma conta? <a href="signup.php">Cadastre-se</a></p>
            </div>
        </div>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>