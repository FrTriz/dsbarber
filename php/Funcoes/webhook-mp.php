<?php
// Inclua o conexao.php PRIMEIRO
require_once '../conexao.php'; 
require_once '../../vendor/autoload.php'; 

// CRUCIAL: Log para depuração
$log_file = 'webhook_log.txt';
$log_message = "--- [" . date('Y-m-d H:i:s') . "] Nova Notificação --- \n";

try {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    $log_message .= "Dados recebidos: " . $json_data . "\n";


    $accessToken = "xxxxxxxxxxxxxxxxxxx"; 

    MercadoPago\SDK::setAccessToken($accessToken);

    $payment_id_mp = null;

    // Tenta obter o ID do pagamento de qualquer um dos formatos
    if (isset($data['type']) && $data['type'] === 'payment') {
        $payment_id_mp = $data['data']['id'];
    } elseif (isset($data['topic']) && $data['topic'] === 'payment') {
        $payment_id_mp = $data['resource'];
    }

    // Se encontramos um ID de pagamento, continuamos
    if ($payment_id_mp) {
        
        $payment = MercadoPago\Payment::find_by_id($payment_id_mp);

        if ($payment === null) {
            throw new Exception("Pagamento $payment_id_mp não encontrado na API do MP.");
        }

        if (empty($payment->external_reference)) {
            throw new Exception("Pagamento não possui referência interna (external_reference).");
        }
            
        $id_pagamento_interno = $payment->external_reference;
        
        // Busca o 'id_agendamento'
        $stmt_get_ag_id = $pdo->prepare("SELECT id_agendamento FROM pagamento WHERE id_pagamento = ?");
        $stmt_get_ag_id->execute([$id_pagamento_interno]);
        $agendamento_info = $stmt_get_ag_id->fetch(PDO::FETCH_ASSOC);

        if (!$agendamento_info) {
             throw new Exception("Pagamento interno $id_pagamento_interno não encontrado no banco.");
        }
        
        $id_agendamento = $agendamento_info['id_agendamento'];

        // --- (LÓGICA DE STATUS MELHORADA) ---
        $pdo->beginTransaction();
        
        switch ($payment->status) {
            case 'approved':
                // CLIENTE PAGOU: Confirma o agendamento
                $stmtAg = $pdo->prepare("UPDATE agendamento SET status = 'confirmado' WHERE id_agendamento = ? AND status = 'pendente'");
                $stmtAg->execute([$id_agendamento]);

                $stmtPg = $pdo->prepare("UPDATE pagamento SET status = 'aprovado' WHERE id_pagamento = ? AND status = 'pendente'");
                $stmtPg->execute([$id_pagamento_interno]);
                
                $log_message .= "SUCESSO: Agendamento $id_agendamento confirmado.\n";
                break;
                
            case 'cancelled':
            case 'expired':
                // CLIENTE NÃO PAGOU (ou o PIX expirou): Cancela o agendamento
                $stmtAg = $pdo->prepare("UPDATE agendamento SET status = 'cancelado' WHERE id_agendamento = ? AND status = 'pendente'");
                $stmtAg->execute([$id_agendamento]);

                $stmtPg = $pdo->prepare("UPDATE pagamento SET status = 'cancelado' WHERE id_pagamento = ? AND status = 'pendente'");
                $stmtPg->execute([$id_pagamento_interno]);
                
                $log_message .= "EXPIRADO/CANCELADO: Agendamento $id_agendamento cancelado (PIX não pago).\n";
                break;
                
            default:
                // Outros status (ex: 'pending', 'in_process')
                $log_message .= "Status ignorado: $payment->status.\n";
                break;
        }
        
        $pdo->commit();

    } else {
        $log_message .= "Notificação de tipo desconhecido.\n";
    }

} catch (Exception $e) {
    $log_message .= "ERRO: " . $e->getMessage() . "\n";
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500); 
}

// Salva tudo no arquivo de log
file_put_contents($log_file, $log_message, FILE_APPEND);

http_response_code(200);
echo json_encode(['status' => 'received']);
?>