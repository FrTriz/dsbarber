<?php

require_once '../conexao.php'; 

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -----------------------------------------------------------
// (PROTEÇÃO XSS - SANITIZAÇÃO DE ENTRADA)
    // -----------------------------------------------------------
    
    // Remove todas as tags HTML e PHP do nome.
$nome = strip_tags($_POST['nome']); 
    
    // Remove todos os caracteres exceto letras, números e !#$%&'*+-/=?^_`{|}~@.[]
    // Esta é a sanitização correta para e-mails.
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // A senha não precisa de sanitização pois será hasheada
$senha_post = $_POST['senha']; 
 $tipo = 'cliente';
    // -----------------------------------------------------------


// Validação básica (agora usando as variáveis sanitizadas)
if (empty($nome) || empty($email) || empty($senha_post)) {
 header('Location: ../../html/signup.php?erro=campos_vazios');
 exit();
 }

    // (RECOMENDADO) Adicionar validação de formato de e-mail após sanitizar
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../../html/signup.php?erro=email_invalido');
 exit();
    }

try {
 // Criptografar a senha
$senha_hash = password_hash($senha_post, PASSWORD_DEFAULT);

// Preparar a consulta SQL
 $sql = "INSERT INTO usuarios (nome, email, senha, tipo) 
VALUES (:nome, :email, :senha, :tipo)";
 
 // Preparar e Executar
$stmt = $pdo->prepare($sql);

        // Agora estamos vinculando os dados sanitizados e seguros
$stmt->bindParam(':nome', $nome);
 $stmt->bindParam(':email', $email);
 $stmt->bindParam(':senha', $senha_hash);
$stmt->bindParam(':tipo', $tipo);

$stmt->execute();

header('Location: ../../html/login.php?sucesso=cadastro'); // Redireciona para login
 exit();

} catch (PDOException $e) {

 if ($e->getCode() == '23000') {
 header('Location: ../../html/signup.php?erro=email_duplicado');
} else {
 header('Location: ../../html/signup.php?erro=geral');
}
 exit();
}
}
?>