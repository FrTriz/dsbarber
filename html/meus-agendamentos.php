<?php
require_once '../php/session-manager.php';

// 1. Segurança: Redireciona se não for um cliente logado
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    $_SESSION['erro_login'] = "Você precisa estar logado como cliente para ver seus agendamentos.";
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ds Barber - Meus Agendamentos</title>
    
    <link rel="stylesheet" href="../css/style.css"> 
    <link rel="stylesheet" href="../css/meus-agendamentos.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo"><img src="../Ds_Barber_Logo.png" width="130px" height="120px"></a>
            <div class="auth-buttons">
                <?php if (isset($_SESSION['usuario_nome'])) : ?>
                    <span class="welcome-message">
                        Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!
                    </span>
                    <a href="../html/meus-agendamentos.php" class="btn-meus-agendamentos">Meus Agendamentos</a>
                    
                    <a href="../php/Funcoes/logout-login.php" class="btn-logout">Sair</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <div class="container-meus-agendamentos">
            <h1>Meus Agendamentos</h1>
            
            <div id="lista-agendamentos">
                <p class="loading-message">Carregando seus agendamentos...</p>
            </div>
        </div>
    </main>
    
    <div id="pix-modal" class="modal-pix">
        <div class="modal-pix-content">
            <header class="modal-pix-header">
                <h2>Efetuar Pagamento</h2>
                <button id="close-pix-modal-btn" class="close-pix-modal-btn"><i class="fas fa-times"></i></button>
            </header>
            
            <div class="pix-info">
                <div id="pix-loading-modal" style="text-align: center; padding: 40px 0;">
                    <p class="loading">Gerando seu PIX, aguarde...</p>
                </div>

                <div id="pix-container-modal" style="display: none;">
                    <p class="pix-instructions">Escaneie o QR code com o aplicativo do seu banco ou copie o código abaixo para completar o pagamento.</p>
                    <div class="pix-details">
                        <img src="" alt="QR Code PIX" id="pix-qr-code-img-modal">
                    </div>
                    <div class="pix-code">
                        <span id="pix-copia-cola-texto-modal"></span>
                        <button type"button" id="btn-copiar-pix-modal">Copiar Código</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/meus-agendamentos.js"></script>
</body>
</html>