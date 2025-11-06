<?php 
try {
    // 1. Host (quase sempre 'localhost' na Hostinger)
    $db_host = "xxxxxxxxxxxxxxxx"; 

    // 2. O nome do banco que você criou (da sua imagem)
    $db_name = "xxxxxxxxxxxxxxxxxx"; 

    // 3. O usuário que você criou para esse banco
    $db_user = "xxxxxxx"; // (Complete com o usuário que você criou)

    // 4. A senha que você definiu para esse usuário
    $db_pass = "xxxxxxxxx"; 

    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Bom para depuração

} catch (PDOException $e) {
    echo "Erro ao conectar com o banco de dados: " . $e->getMessage();
    exit();
} catch (Exception $e) {
    echo "Erro genérico: " . $e->getMessage();
    exit();
}
?>