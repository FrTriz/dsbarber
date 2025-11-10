<?php

require_once '../conexao.php'; 

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha_post = $_POST['senha']; 
    $tipo = 'cliente'; // Definir o tipo como cliente por padrão

    // Validação básica (opcional, mas recomendado)
    if (empty($nome) || empty($email) || empty($senha_post)) {

        header('Location: ../../signup.php?erro=campos_vazios');
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
        
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha_hash);
        $stmt->bindParam(':tipo', $tipo);
        
        $stmt->execute();
     
        header('Location: ../../html/index.php?sucesso=cadastro');
        exit();

    } catch (PDOException $e) {
       
        if ($e->getCode() == '23000') {
            header('Location: ../html/signup.php?erro=email_duplicado');
        } else {
            header('Location: ../signup.php?erro=geral');
        }
        exit();
    }
}
?>