<?php
header('Content-Type: application/json');
require_once '../session-manager.php';
require_once '../conexao.php';

$response = ['status' => 'pendente']; // Resposta padrão

try {
    // 1. Validar se é cliente e se o ID foi enviado
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
        throw new Exception("Não autorizado.");
    }
    if (!isset($_GET['id_agendamento'])) {
        throw new Exception("ID do agendamento não fornecido.");
    }

    $id_agendamento = (int)$_GET['id_agendamento'];
    $id_cliente = $_SESSION['usuario_id'];

    // 2. Verificar o status no banco DE FORMA SEGURA
    $stmt = $pdo->prepare(
        "SELECT status FROM agendamento 
         WHERE id_agendamento = :id_agendamento AND id_cliente = :id_cliente"
    );
    $stmt->execute([
        ':id_agendamento' => $id_agendamento,
        ':id_cliente' => $id_cliente
    ]);
    
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($agendamento) {
        $response['status'] = $agendamento['status']; // Retorna 'pendente' ou 'confirmado'
    } else {
        throw new Exception("Agendamento não encontrado.");
    }

} catch (Exception $e) {
    $response = ['status' => 'erro', 'mensagem' => $e->getMessage()];
}

echo json_encode($response);
?>