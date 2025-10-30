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
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/schedule.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <main class="schedule-container">
        <div class="schedule-header">
            <h1>Agende seu Horário</h1>
            <p>Siga os passos para reservar sua próxima visita conosco.</p>
        </div>

        <!-- Progress Bar -->
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
                <!-- Step 1: Choose Barber -->
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

                <!-- Step 2: Choose Service -->
               <div class="step-content" id="step-2">
                    <h2>Escolha seu(s) Serviço(s)</h2>
                    <p class="step-subtitle">Selecione um ou mais serviços. [cite: 124-125] Você pode selecionar múltiplos.</p>
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

                <!-- Step 3: Select Date & Time -->
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
                            <div class="days" id="calendar-days">
                                <!-- Os dias serão gerados por JS -->
                            </div>
                        </div>
                       <div class="time-slots">
                            <h3>Horários Disponíveis</h3>
                            
                            <div class="slots" id="dynamic-slots-container">
                                <p class="no-slots">Por favor, selecione um dia no calendário.</p>
                            </div>
                        </div>
                    </div>
                </div>

                 <!-- Step 4: Confirmation -->
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
                                <p>Pague com PIX <span>Código expira em: <strong>09:59</strong></span></p>
                                <p class="pix-instructions">Escaneie o QR code com o aplicativo do seu banco ou copie o código abaixo para completar o pagamento.</p>
                                <div class="pix-details">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=00020126580014br.gov.bcb.pix0136123e4567-e89b-12d3-a456-4266141740005204000053039865802BR5913Alex%20Costa6009SAO%20PAULO62070503***6304E5F4" alt="QR Code PIX">
                                    <div class="instructions">
                                        <p><strong>Instruções de Pagamento</strong></p>
                                        <ol>
                                            <li>Abra o aplicativo do seu banco e selecione a opção PIX.</li>
                                            <li>Escaneie o QR Code ou use a opção PIX Copia & Cola.</li>
                                            <li>Confirme os detalhes e complete o pagamento.</li>
                                            <li>Clique no botão "Já Paguei" para confirmar sua reserva.</li>
                                        </ol>
                                    </div>
                                </div>
                                <div class="pix-code">
                                    <span>00020126580014br.gov.bcb.pix...</span>
                                    <button>Copiar Código</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Appointment Summary -->
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

                <!-- Dynamic elements for Step 4 -->
                <div id="summary-step-4" style="display: none;">
                    <div class="summary-item pending-notice">
                        <p><strong>Agendamento Pendente</strong></p>
                        <p>Seu horário está reservado por 10 minutos. Será confirmado assim que o pagamento for processado. Você receberá um e-mail de confirmação.</p>
                    </div>
                    <button class="confirm-payment-btn">
                        <div class="checkbox-icon"></div>
                        Já Paguei, Confirmar Agendamento
                    </button>
                </div>
            </aside>
        </div>

        <!-- Navigation Buttons -->
        <div class="navigation-buttons">
            <button id="back-btn" class="secondary-btn" disabled>Voltar</button>
            <button id="next-btn" class="primary-btn">Próximo Passo</button>
        </div>
    </main>

    <script>
    const servicosVindosDoBanco = <?php echo json_encode($listaServicos); ?>;
</script>

    <script src="../js/schedule.js?v=1.2"></script>
</body>
</html>