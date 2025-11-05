<?php
require_once '../php/session-manager.php';
require_once '../php/conexao.php'; 
require_once '../php/Classes/UsuarioClass.php'; 

// 1. Segurança (igual às suas outras páginas de admin)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
    $_SESSION['erro_login'] = "Acesso não autorizado.";
    header('Location: login.php');
    exit();
}

// 2. Obter dados do usuário logado
$usuarioLogadoId = $_SESSION['usuario_id'];
$usuarioLogadoNome = $_SESSION['usuario_nome'];
$usuarioLogadoTipo = $_SESSION['usuario_tipo'];

// 3. Buscar Lista de Barbeiros (Para o Filtro)
$usuarioObj = new Usuario($pdo);
// Se for admin, lista todos. Se for barbeiro, lista apenas a si mesmo.
if ($usuarioLogadoTipo === 'admin') {
    $listaBarbeiros = $usuarioObj->listarPorTipo('barbeiro');
} else {
    $listaBarbeiros = [$usuarioObj->buscarPorId($usuarioLogadoId)];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ds Barber - Admin | Horários</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/horarios.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-panel">
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="logo"><img src="/Ds_Barber_Logo.png" width="130px" height="120px"></a>
                <button id="close-sidebar-btn" class="close-sidebar-btn"><i class="fas fa-times"></i></button>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin.php"><i class="fas fa-calendar-alt"></i> Agendamentos</a></li>
                    <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Serviços</a></li>
                    <li><a href="barbers.php"><i class="fas fa-cut"></i> Barbeiros</a></li>
                    <li><a href="horarios.php" class="active"><i class="fas fa-clock"></i> Horários</a></li>
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

        <main class="main-content">
            <header class="main-header">
                <button id="hamburger-btn" class="hamburger-btn"><i class="fas fa-bars"></i></button>
                <h1>Gerenciamento de Horários</h1>
            </header>

            <section class="horarios-container">
                <div class="horarios-header">
                    <div class="form-group">
                        <label for="barbeiro-select">Selecione o Barbeiro</label>
                        <select id="barbeiro-select">
                            <option value="">-- Escolha um profissional --</option>
                            <?php foreach ($listaBarbeiros as $barbeiro): ?>
                                <option value="<?php echo htmlspecialchars($barbeiro['id_usuario']); ?>">
                                    <?php echo htmlspecialchars($barbeiro['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <form id="horarios-form" class="horarios-form" style="display: none;">
                    <div id="horarios-loading" class="loading-message" style="display: none;">Carregando horários...</div>
                    <div id="horarios-body">
                        </div>
                    <button type="submit" class="btn-submit">Salvar Horários</button>
                </form>
            </section>
        </main>
    </div>
    
    <script>
        const usuarioLogadoTipo = <?php echo json_encode($usuarioLogadoTipo); ?>;
    </script>
    <script src="../js/horarios.js"></script>
</body>
</html>