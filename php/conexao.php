<?php 
try {
    $pdo = new PDO("mysql:host=localhost;port=3306;dbname=dsbarber_db", "root", "172834Azul.");

} catch (PDOException $e) {
    echo "Erro ao conectar com o banco de dados: " . $e->getMessage();
    exit();
} catch (Exception $e) {
    echo "Erro genérico: " . $e->getMessage();
    exit();
}
?>