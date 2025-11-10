<?php
header('Content-Type: application/json');
require_once '../session-manager.php';
require_once '../conexao.php';
require_once '../../vendor/autoload.php'; // Carrega o SDK do Mercado Pago

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

    // 2. Buscar o status ATUAL no nosso banco
    $stmt_local = $pdo->prepare(
        "SELECT a.status, p.mp_payment_id 
         FROM agendamento a
         JOIN pagamento p ON a.id_agendamento = p.id_agendamento
         WHERE a.id_agendamento = :id_agendamento AND a.id_cliente = :id_cliente"
    );
    $stmt_local->execute([
        ':id_agendamento' => $id_agendamento,
        ':id_cliente' => $id_cliente
    ]);
    
    $agendamento = $stmt_local->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        throw new Exception("Agendamento não encontrado.");
    }

    // Se o status no NOSSO banco já é 'confirmado', só retorna
    if ($agendamento['status'] === 'confirmado') {
        $response['status'] = 'confirmado';
        echo json_encode($response);
        exit();
    }
    
    // --- VERIFICAÇÃO ATIVA ---
    // Se chegou aqui, o status é 'pendente'. Vamos perguntar ao MP.
    
    if (empty($agendamento['mp_payment_id'])) {
        // Isso só vai acontecer se o gerar-pix-mp falhar
        throw new Exception("ID de pagamento do MP não encontrado.");
    }
    
    $mp_payment_id = $agendamento['mp_payment_id'];
    
    // Configura o SDK do MP
    $accessToken = 'xxxxxxxxxxxxxxxxxxx'; 
    MercadoPago\SDK::setAccessToken($accessToken);

    // Busca o pagamento na API do MP
    $payment_mp = MercadoPago\Payment::find_by_id($mp_payment_id);

    if ($payment_mp && $payment_mp->status === 'approved') {
        // O MP DIZ QUE ESTÁ PAGO! Vamos atualizar nosso banco.
        
        $pdo->beginTransaction();
        
        // Atualiza o agendamento
        $stmtAg = $pdo->prepare("UPDATE agendamento SET status = 'confirmado' WHERE id_agendamento = ? AND status = 'pendente'");
        $stmtAg->execute([$id_agendamento]);

        // Atualiza o pagamento
        $stmtPg = $pdo->prepare("UPDATE pagamento SET status = 'aprovado' WHERE id_agendamento = ? AND status = 'pendente'");
        $stmtPg->execute([$id_agendamento]);
        
        $pdo->commit();

        // Informa o JS que foi confirmado
        $response['status'] = 'confirmado';
        
    } else {
        // Se o MP disser 'pending', 'expired', etc., o JS continua esperando.
        $response['status'] = 'pendente';
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    $response = ['status' => 'erro', 'mensagem' => $e->getMessage()];
}

echo json_encode($response);
?>