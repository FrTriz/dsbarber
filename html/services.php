<?php
require_once '../php/conexao.php';
require_once '../php/Classes/ServicosClass.php';
require_once '../php/session-manager.php';
require_once '../php/Classes/UsuarioClass.php'; 

$servicos = new Servicos($pdo);
$listaServicos = $servicos->listarServicos(); // <-- Ótimo

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
    <title>Ds Barber - Admin | Serviços</title>
    <!-- Shared Admin Styles -->
    <link rel="stylesheet" href="../css/admin.css">
    <!-- Page-specific Styles -->
    <link rel="stylesheet" href="../css/services.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-panel">
        <!-- Sidebar remains the same -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo"><img src="/Ds_Barber_Logo.png" width="130px" height="120px"></a>
                <button id="close-sidebar-btn" class="close-sidebar-btn"><i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin.php"><i class="fas fa-calendar-alt"></i> Agendamentos</a></li>
                    <li><a href="services.php" class="active"><i class="fas fa-concierge-bell"></i> Serviços</a></li>
                    <li><a href="barbers.php"><i class="fas fa-cut"></i> Barbeiros</a></li>
                    <li><a href="reports.php"><i class="fas fa-chart-line"></i> Relatórios</a></li>
                </ul>
            </nav>
          <div class="user-info">
                <img src="https://i.pravatar.cc/150?u=<?php echo htmlspecialchars($usuarioLogadoId); ?>" alt="User Avatar">
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($usuarioLogadoNome); ?></span>
                </div>
                <a href="../php/Funcoes/logout.php" class="logout-icon" title="Sair"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </aside>
        <div id="overlay" class="overlay"></div>

        <main class="main-content">
            <header class="main-header">
                <button id="hamburger-btn" class="hamburger-btn"><i class="fas fa-bars"></i></button>
                <h1>Gerenciamento de Serviços</h1>
                <button id="add-service-btn" class="add-service-btn"><i class="fas fa-plus"></i> Adicionar Novo Serviço</button>
            </header>

            <!-- Cards View for All Screen Sizes -->
            <section class="services-cards-section">
                <!-- Service cards will be dynamically managed by JS later -->
            </section>
        </main>
    </div>

    <!-- Modal for Add/Edit Service -->
    <div id="service-modal" class="modal">
        <div class="modal-content">
            <header class="modal-header">
                <h2 id="modal-title">Adicionar Novo Serviço</h2>
                <button id="close-modal-btn" class="close-modal-btn"><i class="fas fa-times"></i></button>
            </header>
            <form action="../php/Funcoes/add-servico.php" id="service-form" method="POST" class="modal-body">
                <input type="hidden" id="service-id" name="id_servico">
                <div class="form-group">
                    <label for="service-name">Nome do Serviço</label>
                    <input type="text" id="service-name" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="service-description">Descrição</label>
                    <textarea id="service-description" name="descricao" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="service-price">Preço (R$)</label>
                        <input type="number" id="service-price" name="preco" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="service-duration">Duração (min)</label>
                        <input type="number" id="service-duration" name="duracao_minutos"  required>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Salvar Serviço</button>
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
                <p>Tem certeza de que deseja excluir o serviço <strong id="item-name-to-delete"></strong>? Esta ação não pode ser desfeita.</p>
            </div>
            <footer class="modal-footer">
                <button id="cancel-delete-btn" class="btn btn-secondary">Cancelar</button>
                <button id="confirm-delete-btn" class="btn btn-danger">Confirmar Exclusão</button>
            </footer>
        </div>
    </div>
    <script>const servicosVindosDoBanco = <?php echo json_encode($listaServicos); ?>;</script>
    <script src="../js/services.js"></script>
</body>
</html>