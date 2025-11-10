<?php

require_once '../php/session-manager.php';

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    
    $_SESSION['erro_login'] = "Você precisa estar logado para agendar um horário.";
    
    header('Location: login.php');
    exit(); // Para a execução do script imediatamente
}
require_once '../php/conexao.php'; 
require_once '../php/Classes/ServicosClass.php'; 
require_once '../php/Classes/UsuarioClass.php';

// Buscar os Serviços
$servicosObj = new Servicos($pdo);
$listaServicos = $servicosObj->listarServicos();

$usuarioObj = new Usuario($pdo);
$listaBarbeiros = $usuarioObj->listarPorTipo('barbeiro'); // <-- ADICIONADO
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ds Barber - Agendamento</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime('../css/style.css'); ?>"> 
    
    <link rel="stylesheet" href="../css/schedule.css?v=<?php echo filemtime('../css/schedule.css'); ?>">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="manifest" href="../manifest.json?v=<?php echo filemtime('../manifest.json'); ?>">
    <link rel="apple-touch-icon" href="../logo-tela-inicial.png">
    <link rel="icon" href="../favicon.ico?v=<?php echo filemtime('../favicon.ico'); ?>" type="image/x-icon">
</head>
<body>
    <main class="schedule-container">
        <div class="schedule-header">
            <h1>Agende seu Horário</h1>
            <p>Siga os passos para reservar sua próxima visita conosco.</p>
        </div>

        <div class="progress-bar">
            <div class="step active" id="progress-step-1">
                <div class="step-number">1</div>
                <span>Escolha o Barbeiro</span>
            </div>
            <div class="progress-line"></div>
            <div class="step" id="progress-step-2">
                <div class="step-number">2</div>
                <span>Escolha o Serviço</span>
            </div>
            <div class="progress-line"></div>
            <div class="step" id="progress-step-3">
                <div class="step-number">3</div>
                <span>Data e Hora</span>
            </div>
            <div class="progress-line"></div>
            <div class="step" id="progress-step-4">
                <div class="step-number">4</div>
                <span>Confirmação</span>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="steps-content">
                <div class="step-content active" id="step-1">
                    <h2>Escolha seu Barbeiro</h2>
                    <p class="step-subtitle">Selecione seu profissional de preferência.</p> 
                    <div class="barber-selection">
                        <?php foreach ($listaBarbeiros as $barbeiro): ?>
                            <div class="barber-card" 
                                data-barbeiro-id="<?php echo htmlspecialchars($barbeiro['id_usuario']); ?>"
                                data-barbeiro-nome="<?php echo htmlspecialchars($barbeiro['nome']); ?>">
                                <img src="https://i.pravatar.cc/150?u=<?php echo htmlspecialchars($barbeiro['id_usuario']); ?>" alt="Foto de <?php echo htmlspecialchars($barbeiro['nome']); ?>">
                                <div class="barber-info">
                                    <h3><?php echo htmlspecialchars($barbeiro['nome']); ?></h3>
                                    <h4>Barbeiro</h4> <p>Profissional qualificado da equipe DsBarber.</p> </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="step-content" id="step-2">
                    <h2>Escolha seu(s) Serviço(s)</h2>
                    <p class="step-subtitle">Selecione um ou mais serviços. Você pode selecionar múltiplos.</p>
                    <div class="service-selection">
                        <?php foreach ($listaServicos as $servico): ?>
                            <div class="service-card" 
                                data-service-id="<?php echo htmlspecialchars($servico['id_servico']); ?>"
                                data-service-nome="<?php echo htmlspecialchars($servico['nome']); ?>"
                                data-price="<?php echo htmlspecialchars($servico['preco']); ?>"
                                data-duration="<?php echo htmlspecialchars($servico['duracao_minutos']); ?>">
                                <div class="service-info">
                                    <h3><?php echo htmlspecialchars($servico['nome']); ?></h3>
                                    <p><?php echo htmlspecialchars($servico['descricao']); ?></p>
                                </div>
                                <div class="service-price">R$<?php echo number_format($servico['preco'], 2, ',', '.'); ?></div>
                                <div class="service-checkbox"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="step-content" id="step-3">
                    <h2>Selecione Data e Hora</h2>
                    <p class="step-subtitle">Escolha uma data e hora que funcione para você.</p>
                    <div class="date-time-selection">
                        <div class="calendar">
                            <div class="month-header">
                                <button id="prev-month">&lt;</button>
                                <h3 id="month-name">Outubro 2024</h3>
                                <button id="next-month">&gt;</button>
                            </div>
                            <div class="weekdays">
                                <div>Dom</div><div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div>
                            </div>
                            <div class="days" id="calendar-days"></div>
                        </div>
                       <div class="time-slots">
                            <h3>Horários Disponíveis</h3>
                            <div class="slots" id="dynamic-slots-container">
                                <p class="no-slots">Por favor, selecione um dia no calendário.</p>
                            </div>
                        </div>
                    </div>
                </div>

              <div class="step-content" id="step-4">

                    <h2>Complete seu Pagamento</h2>

                    <div class="payment-container">

                        <div class="payment-options">

                            <h4>Escolha uma opção de pagamento</h4>

                            <div class="tabs">

                                <button class="tab-btn active">Pagamento Total<br>R$<span id="payment-total-full">50.00</span></button>

                                <button class="tab-btn">Pagar Metade<br>R$<span id="payment-total-half">25.00</span></button>

                            </div>

                            

                            <div class="pix-info">

                                <div id="pix-loading" style="text-align: center; padding: 40px 0; display: none;">

                                    <p class="loading">Gerando seu PIX, aguarde...</p>

                                </div>



                                <div id="pix-container" style="display: none;">

                                    <p class="pix-instructions">Escaneie o QR code com o aplicativo do seu banco ou copie o código abaixo para completar o pagamento.</p>
                                    
                                    <div class="pix-expiration-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>Você deve efetuar o pagamento em <strong>30 minutos</strong>, ou seu agendamento será cancelado automaticamente.</span>
                                    </div>
                                    
                                    <div class="pix-details">

                                        <img src="" alt="QR Code PIX" id="pix-qr-code-img">

                                        <div class="instructions">

                                            <p><strong>Instruções de Pagamento</strong></p>

                                            <ol>

                                                <li>Abra o aplicativo do seu banco e selecione a opção PIX.</li>

                                                <li>Escaneie o QR Code ou use a opção PIX Copia & Cola.</li>

                                                <li>Confirme os detalhes e complete o pagamento.</li>

                                                <li>Seu agendamento será confirmado automaticamente.</li>

                                            </ol>

                                        </div>

                                    </div>

                                    <div class="pix-code">

                                        <span id="pix-copia-cola-texto"></span>

                                        <button type="button" id="btn-copiar-pix">Copiar Código</button>

                                    </div>

                                </div>

                            </div>

                            </div>

                    </div>

                </div>



            </div>

            <aside class="appointment-summary">
                <h2>Seu Agendamento</h2>
                <div class="summary-item">
                    <span>Barbeiro:</span>
                    <strong id="summary-barber">Jameson</strong>
                </div>
                <div class="summary-item">
                    <span>Serviço:</span>
                    <strong id="summary-services">Não selecionado</strong>
                </div>
                <div class="summary-item">
                    <span>Data e Hora:</span>
                    <strong id="summary-datetime">Não selecionado</strong>
                </div>
                <hr>
                <div class="summary-total">
                    <span>Preço Total:</span>
                    <strong id="summary-total">R$0.00</strong>
                </div>
                </aside>
        </div>
        

        <div class="navigation-buttons">
            <button id="back-btn" class="secondary-btn" disabled>Voltar</button>
            <button id="next-btn" class="primary-btn">Próximo Passo</button>
        </div>
    </main>

    <div id="confirmation-modal" class="modal-pix">
        <div class="modal-pix-content">
            <header class="modal-pix-header">
                <h2>Pagamento Confirmado!</h2>
            </header>
            
            <div class="modal-body">
                <i class="fas fa-check-circle"></i>
                <p>Seu agendamento foi confirmado com sucesso.</p>
                <p>Você será redirecionado para "Meus Agendamentos"...</p>
            </div>
        </div>
    </div>
    
    <div id="expiration-modal" class="modal-pix">
        <div class="modal-pix-content">
            <header class="modal-pix-header">
                <h2>Tempo Esgotado</h2>
            </header>
            
            <div class="modal-body">
                <i class="fas fa-times-circle icon-expired"></i>
                <p>O tempo para pagamento (30 minutos) expirou.</p>
                <p>Seu agendamento foi cancelado para liberar o horário. Por favor, tente novamente.</p>
                <button id="btn-reload-page" class="primary-btn" style="width: 100%; margin-top: 15px;">Novo Agendamento</button>
            </div>
        </div>
    </div>
    
    <script>
    const servicosVindosDoBanco = <?php echo json_encode($listaServicos); ?>;
    </script>

    <script src="../js/schedule.js?v=<?php echo filemtime('../js/schedule.js'); ?>"></script>
</body>
</html>