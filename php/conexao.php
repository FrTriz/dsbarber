<?php 
// 1. Inclui o autoload do Composer (que também carrega o phpdotenv)
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Carrega as variáveis do arquivo .env
// O __DIR__ . '/../' aponta para a pasta raiz do seu projeto
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    // 3. Usa as variáveis carregadas do .env
    $db_host = $_ENV['DB_HOST']; 
    $db_name = $_ENV['DB_NAME']; 
    $db_user = $_ENV['DB_USER']; 
    $db_pass = $_ENV['DB_PASS']; 

    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 

} catch (PDOException $e) {
    echo "Erro ao conectar com o banco de dados: " . $e->getMessage();
    exit();
} catch (Exception $e) {
    echo "Erro genérico: " . $e->getMessage();
    exit();
}
?>