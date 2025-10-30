<?php
//Iniciar a sessão
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir a conexão
require_once '../conexao.php'; 

// Verificar se o método é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Limpar os dados de entrada
        $email = trim($_POST['email']);
        $senha_digitada = trim($_POST['senha']);

        // Selecionar id_usuario, nome, senha (hash) e tipo de uma vez.
        $sql = "SELECT id_usuario, nome, senha, tipo FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar se o usuário existe E se a senha está correta
        if ($usuario && password_verify($senha_digitada, $usuario['senha'])) {
            
            // Salvar os dados na sessão 
            $_SESSION['usuario_logado'] = true;
            $_SESSION['usuario_id'] = $usuario['id_usuario']; 
            $_SESSION['usuario_tipo'] = $usuario['tipo'];
            $_SESSION['usuario_nome'] = $usuario['nome']; 

            // Redirecionar com base no tipo correto
            if ($usuario['tipo'] === 'cliente') {
                // Caminho para o dashboard do cliente
                header('Location: ../../html/index.php'); 
                exit;

            } elseif ($usuario['tipo'] === 'admin') { 
                // Caminho para o dashboard do admin
                header('Location: ../../admin.php');
                exit;

            } elseif ($usuario['tipo'] === 'barbeiro') {
                // ADICIONADO: Lógica para o barbeiro
                header('Location: ../../html/admin.php'); 
                exit;
            
            } else {
                // Tipo de usuário desconhecido? Melhor deslogar.
                session_destroy();
                $_SESSION['erro_login'] = "Tipo de usuário inválido.";
                header('Location: ../../html/index.php');
                exit;
            }

        } else {
            // Falha no login (e-mail não encontrado ou senha errada)
            $_SESSION['erro_login'] = "E-mail ou senha incorretos.";
            header('Location: ../../html/login.php'); // Redireciona para o login
            exit;
        }

    } catch (PDOException $e) {
        // Erro geral de banco de dados
        $_SESSION['erro_login'] = "Erro no sistema. Tente novamente mais tarde.";
        // Logar o erro real 
        error_log("Erro de login (PDO): " . $e->getMessage()); 
        header('Location: ../../html/index.php');
        exit;
    }
} else {
    // Se alguém tentar acessar o script diretamente (sem POST)
    header('Location: ../../html/index.php');
    exit;
}
?>