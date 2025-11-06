<?php
header('Content-Type: application/json');

// 1. Verifica se o usuário está logado (qualquer tipo)
require_once '../session-manager.php';
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => 'Acesso não autorizado. Você precisa estar logado.']);
    exit();
}

// 2. Conecta ao banco
require_once '../conexao.php';

// 3. Valida a entrada
if (!isset($_GET['id_barbeiro'])) {
    echo json_encode(['erro' => 'ID do barbeiro não fornecido.']);
    exit();
}
$id_barbeiro = (int)$_GET['id_barbeiro'];

try {
    // 4. Busca apenas os dias da semana (distintos)
    $stmt = $pdo->prepare(
        "SELECT DISTINCT dia_semana 
         FROM horarios_trabalho 
         WHERE id_barbeiro = :id_barbeiro"
    );
    $stmt->execute(['id_barbeiro' => $id_barbeiro]);
    
    // 5. Retorna um array simples de números [0, 1, 2, 3, 4, 5]
    $dias = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); 
    $dias_int = array_map('intval', $dias); // Garante que são números

    echo json_encode($dias_int); // Retorna o array de dias (ex: [1, 2, 3, 4, 5])

} catch (PDOException $e) {
    echo json_encode(['erro' => 'Erro de banco de dados: ' . $e->getMessage()]);
}
?>