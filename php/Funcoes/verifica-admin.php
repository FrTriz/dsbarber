<?php
// Garante que uma sessão esteja ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica duas condições:
// 1. Se NÃO existe uma sessão 'usuario_logado'.
// 2. Ou se o 'usuario_tipo' NÃO é 'administrador'.
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_tipo'] !== 'administrador') {
    
    // Se qualquer uma das condições for verdadeira, o acesso é negado.
    $_SESSION['erro_login'] = "Acesso restrito. Por favor, faça login como administrador.";
    
    // Redireciona o usuário para a página de login
    header('Location: /login.php');
    
    // Encerra o script para garantir que o resto da página não seja carregado
    exit();
}
?>