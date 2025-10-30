<?php
require_once '../php/session-manager.php';
require_once '../php/conexao.php'; 
require_once '../php/Classes/UsuarioClass.php'; 

// 1. Segurança
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
    $_SESSION['erro_login'] = "Acesso não autorizado.";
    header('Location: login.php');
    exit();
}

// 2. Obter dados do usuário logado
$usuarioLogadoId = $_SESSION['usuario_id'];
$usuarioLogadoNome = $_SESSION['usuario_nome'];
$usuarioLogadoTipo = $_SESSION['usuario_tipo']; // Pega o TIPO (admin ou barbeiro)

// 3. Buscar Lista de Barbeiros (Para o Filtro)
$usuarioObj = new Usuario($pdo);
$listaBarbeiros = $usuarioObj->listarPorTipo('barbeiro'); 

// 4. Buscar Agendamentos Iniciais (Mês Atual) - COM A LÓGICA CORRETA
$mesAtual = date('Y-m');
try {
    // Começa a query base
    $sqlBase = "SELECT 
                id_agendamento, 
                DATE(data_hora_inicio) as dia, 
                TIME(data_hora_inicio) as hora_inicio_fmt, 
                nome_cliente, 
                servicos_agendados, 
                status_agendamento,
                id_barbeiro 
            FROM vw_agendamentos_completos 
            WHERE DATE_FORMAT(data_hora_inicio, '%Y-%m') = :mes_ano";
            
    $params = [':mes_ano' => $mesAtual];

    // **** A CORREÇÃO ESTÁ AQUI ****
    // Se o usuário logado for um 'barbeiro', adiciona o filtro.
    // Se for 'admin', o filtro NÃO é adicionado (e ele vê tudo).
    if ($usuarioLogadoTipo === 'barbeiro') {
        $sqlBase .= " AND id_barbeiro = :id_barbeiro";
        $params[':id_barbeiro'] = $usuarioLogadoId;
    }
    
    $sqlBase .= " ORDER BY data_hora_inicio ASC";
            
    $stmt = $pdo->prepare($sqlBase);
    $stmt->execute($params);
    $agendamentosDoMes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao buscar agendamentos: " . $e->getMessage());
    $agendamentosDoMes = []; 
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ds Barber - Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-panel">
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo"><img src="../Ds_Barber_Logo.png" width="130px" height="120px"></a>
                <button id="close-sidebar-btn" class="close-sidebar-btn"><i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin.php" class="active"><i class="fas fa-calendar-alt"></i> Agendamentos</a></li>
                    <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Serviços</a></li>
                    <li><a href="barbers.php"><i class="fas fa-cut"></i> Barbeiros</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-line"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <img src="https://i.pravatar.cc/150?u=johnwick" alt="User Avatar">
                <div class="user-details">
                    <span class="user-name">John Wick</span>
                    <span class="user-email">john@dsbarber.com</span>
                </div>
                <a href="#" class="logout-icon"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </aside>
        <div id="overlay" class="overlay"></div>

        <main class="main-content">
            <header class="main-header">
                <button id="hamburger-btn" class="hamburger-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Agendamentos</h1>
                <div class="header-actions">
                    <div class="filter-dropdown">
                        <button class="filter-btn" data-dropdown="barber-dropdown">Barbeiro <i class="fas fa-chevron-down"></i></button>
                       <div id="barber-dropdown" class="dropdown-menu">
                            <?php if ($usuarioLogadoTipo === 'admin'): ?>
                                <a href="#" data-value="all">Todos os Barbeiros</a>
                                <?php foreach ($listaBarbeiros as $barbeiro): ?>
                                    <a href="#" data-value="<?php echo htmlspecialchars($barbeiro['id_usuario']); ?>">
                                        <?php echo htmlspecialchars($barbeiro['nome']); ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <a href="#" data-value="<?php echo htmlspecialchars($usuarioLogadoId); ?>"><?php echo htmlspecialchars($usuarioLogadoNome); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="filter-dropdown">
                        <button class="filter-btn" data-dropdown="status-dropdown">Status <i class="fas fa-chevron-down"></i></button>
                        <div id="status-dropdown" class="dropdown-menu">
                            <a href="#" data-value="all">Todos os Status</a>
                            <a href="#" data-value="confirmed">Confirmado</a>
                            <a href="#" data-value="pending">Pendente</a>
                            <a href="#" data-value="canceled">Cancelado</a>
                        </div>
                    </div>
                    <button id="new-appointment-btn" class="new-appointment-btn"><i class="fas fa-plus"></i> Novo Agendamento</button>
                </div>
            </header>

            <section class="calendar-section">
                <!-- Calendar content remains the same -->
                <div class="calendar-header">
                    <div class="calendar-nav">
                        <h2 id="month-year">Outubro 2024</h2>
                        <div class="nav-buttons">
                            <button id="prev-month"><i class="fas fa-chevron-left"></i></button>
                            <button id="next-month"><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="status-legend">
                        Status: 
                        <span class="status-item confirmed">Confirmado</span>,
                        <span class="status-item pending">Pendente</span>,
                        <span class="status-item canceled">Cancelado</span>
                    </div>
                </div>
                <div class="calendar-grid">
                    <!-- Calendar days will be dynamically generated by JS -->
                </div>
            </section>
        </main>
    </div>

    <!-- Modal for New Appointment -->
    <div id="appointment-modal" class="modal">
        <div class="modal-content">
            <header class="modal-header">
                <h2>Novo Agendamento</h2>
                <button id="close-modal-btn" class="close-modal-btn"><i class="fas fa-times"></i></button>
            </header>
            <form id="appointment-form">
                <div class="form-group">
                    <label for="client-name">Nome do Cliente</label>
                    <input type="text" id="client-name" required>
                </div>
                <div class="form-group">
                    <label for="appointment-date">Data</label>
                    <input type="date" id="appointment-date" required>
                </div>
                <div class="form-group">
                    <label for="appointment-service">Serviço</label>
                    <input type="text" id="appointment-service" required>
                </div>
                <div class="form-group">
                    <label for="appointment-barber">Barbeiro</label>
                    <select id="appointment-barber" required>
                        <option value="John Wick">John Wick</option>
                        <option value="Mike Ross">Mike Ross</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Salvar Agendamento</button>
            </form>
        </div>
    </div>

    <script>
        // Passa os agendamentos do mês atual para o JS
        const agendamentosIniciais = <?php echo json_encode($agendamentosDoMes); ?>;
        // Passa o ID do usuário logado para o JS (útil para futuras requisições)
        const idUsuarioLogado = <?php echo json_encode($usuarioLogadoId); ?>; 
    </script>
    
    <script src="../js/admin.js?v=1.1"></script>
</body>
</html>