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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ds Barber - Admin | Barbeiros</title>
    <!-- Shared Admin Styles -->
    <link rel="stylesheet" href="../css/admin.css">
    <!-- Page-specific Styles -->
    <link rel="stylesheet" href="../css/barbers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                    <li><a href="admin.php" class="active"><i class="fas fa-calendar-alt"></i> Agendamentos</a></li>
                    <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Serviços</a></li>
                    <li><a href="barbers.php"><i class="fas fa-cut"></i> Barbeiros</a></li>
                    
                    <li><a href="horarios.php"><i class="fas fa-clock"></i> Horários</a></li>

                    <li><a href="reports.php"><i class="fas fa-chart-line"></i> Relatórios</a></li>
                </ul>
            </nav>
            <div class="user-info">
                <img src="https://i.pravatar.cc/150?u=<?php echo htmlspecialchars($usuarioLogadoId); ?>" alt="User Avatar">
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($usuarioLogadoNome); ?></span>
                </div>
                <a href="../php/Funcoes/logout-login.php" class="logout-icon" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </aside>
        <div id="overlay" class="overlay"></div>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header barbers-header">
                <div class="header-title-container">
                    <button id="hamburger-btn" class="hamburger-btn"><i class="fas fa-bars"></i></button>
                    <div>
                        <h1>Gerenciamento de Barbeiros</h1>
                        <p class="subtitle">Adicione, edite ou remova barbeiros da sua equipe.</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button id="add-barber-btn" class="add-barber-btn"><i class="fas fa-plus"></i> Adicionar Novo Barbeiro</button>
                </div>
            </header>

            <!-- Barbers Cards Section -->
            <section class="barbers-cards-section">
                <!-- Barber cards will be dynamically populated by JS -->
            </section>
        </main>
    </div>

    <!-- Modal for Add/Edit Barber -->
    <div id="barber-modal" class="modal">
        <div class="modal-content">
            <header class="modal-header">
                <h2 id="modal-title">Adicionar Novo Barbeiro</h2>
                <button id="close-modal-btn" class="close-modal-btn"><i class="fas fa-times"></i></button>
            </header>
            <form id="barber-form">
                <input type="hidden" id="barber-id">
                <div class="form-group">
                    <label for="barber-name">Nome do Barbeiro</label>
                    <input type="text" id="barber-name" required>
                </div>
                <div class="form-group">
                    <label for="barber-specialties">Especialidades (separadas por vírgula)</label>
                    <input type="text" id="barber-specialties" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="barber-status">Status</label>
                        <select id="barber-status" required>
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="barber-photo">URL da Foto</label>
                        <input type="text" id="barber-photo">
                    </div>
                </div>
                <button type="submit" class="btn-submit">Salvar Barbeiro</button>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal for Delete -->
    <div id="delete-confirm-modal" class="modal">
        <div class="modal-content">
            <header class="modal-header">
                <h2>Confirmar Exclusão</h2>
                <button id="close-confirm-modal-btn" class="close-modal-btn"><i class="fas fa-times"></i></button>
            </header>
            <div class="modal-body">
                <p>Tem certeza de que deseja excluir o barbeiro <strong id="barber-name-to-delete"></strong>? Esta ação não pode ser desfeita.</p>
            </div>
            <footer class="modal-footer">
                <button id="cancel-delete-btn" class="btn btn-secondary">Cancelar</button>
                <button id="confirm-delete-btn" class="btn btn-danger">Confirmar Exclusão</button>
            </footer>
        </div>
    </div>
    
    <script src="../js/barbers.js"></script>
</body>
</html>
