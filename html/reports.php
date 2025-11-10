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

$paginaAtiva = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ds Barber - Admin | Relatórios</title>
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo filemtime('../css/admin.css'); ?>"> 
    <link rel="stylesheet" href="../css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="manifest" href="../manifest.json?v=<?php echo filemtime('../manifest.json'); ?>">
    <link rel="apple-touch-icon" href="../logo-tela-inicial.png">
    <link rel="icon" href="../favicon.ico?v=<?php echo filemtime('../favicon.ico'); ?>" type="image/x-icon">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-panel">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo"><img src="/Ds_Barber_Logo.png" width="130px" height="120px"></a>
                <button id="close-sidebar-btn" class="close-sidebar-btn"><i class="fas fa-times"></i>
                </button>
            </div>
            
          <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin.php" class="<?php echo ($paginaAtiva == 'admin.php') ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Agendamentos
                    </a></li>
                    
                    <li><a href="services.php" class="<?php echo ($paginaAtiva == 'services.php') ? 'active' : ''; ?>">
                        <i class="fas fa-concierge-bell"></i> Serviços
                    </a></li>
                    
                    <li><a href="horarios.php" class="<?php echo ($paginaAtiva == 'horarios.php') ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Horários
                    </a></li>
                    
                    <li><a href="barbers.php" class="<?php echo ($paginaAtiva == 'barbers.php') ? 'active' : ''; ?>">
                        <i class="fas fa-cut"></i> Barbeiros
                    </a></li>
                
                    <li><a href="reports.php" class="<?php echo ($paginaAtiva == 'reports.php') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Relatórios
                    </a></li>
                </ul>
            </nav>
           <div class="user-info">
                <div class="user-avatar-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($usuarioLogadoNome); ?></span>
            </div>
            <a href="../php/Funcoes/logout-login.php" class="logout-icon" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
        </div>
        </aside>
        <div id="overlay" class="overlay"></div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <button id="hamburger-btn" class="hamburger-btn"><i class="fas fa-bars"></i></button>
                <h1>Relatórios</h1>
            </header>

            <!-- Filters Section -->
            <section class="filters-section">
                <div class="filter-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Período: Últimos 30 dias</span>
                </div>
                <div class="filter-item">
                    <i class="fas fa-cut"></i>
                    <span>Barbeiro: Todos</span>
                </div>
                <div class="filter-item">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Serviço: Todos</span>
                </div>
                <div class="filter-actions">
                    <button class="btn-reset"><i class="fas fa-undo"></i> Resetar</button>
                    <button class="btn-apply">Aplicar Filtros</button>
                </div>
            </section>

            <!-- KPIs Section -->
            <section class="kpi-section">
                <div class="kpi-card">
                    <div class="kpi-title">Total Revenue</div>
                    <div class="kpi-value">R$12.500</div>
                    <div class="kpi-change positive"><i class="fas fa-arrow-up"></i> 12.5%</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Total Appointments</div>
                    <div class="kpi-value">820</div>
                    <div class="kpi-change positive"><i class="fas fa-arrow-up"></i> 8.2%</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">New Clients</div>
                    <div class="kpi-value">45</div>
                    <div class="kpi-change negative"><i class="fas fa-arrow-down"></i> 3.1%</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Avg. Rating</div>
                    <div class="kpi-value">4.9</div>
                    <div class="kpi-change positive"><i class="fas fa-arrow-up"></i> 0.1</div>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="charts-section">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Appointments Over Time</h3>
                        <span>Last 4 Weeks</span>
                    </div>
                    <canvas id="appointments-chart"></canvas>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Revenue per Barber</h3>
                        <span>Last 30 Days</span>
                    </div>
                    <canvas id="revenue-chart"></canvas>
                </div>
            </section>
        </main>
    </div>
    <script src="../js/reports.js"></script>
</body>
</html>