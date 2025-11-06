<?php
require_once '../../vendor/autoload.php';
require_once '../conexao.php';
require_once '../session-manager.php';

// CRUCIAL: Log para depuração
$log_file = 'webhook_log.txt';
$log_message = "--- [" . date('Y-m-d H:i:s') . "] Nova Notificação --- \n";

try {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    $log_message .= "Dados recebidos: " . $json_data . "\n";

    // USE O MESMO TOKEN DE TESTE
    $accessToken = "xxxxxxxxxxxxxxxxxxxxxxxxxx"; 
    MercadoPago\SDK::setAccessToken($accessToken);

    // Verifica se é uma notificação de pagamento
    if (isset($data['type']) && $data['type'] === 'payment') {
        
        $payment_id_mp = $data['data']['id'];
        
        // Busca o pagamento na API do MP para segurança
        $payment = MercadoPago\Payment::find_by_id($payment_id_mp);

        if ($payment === null) {
            throw new Exception("Pagamento $payment_id_mp não encontrado na API do MP.");
        }

        // Verifica se foi APROVADO e se tem a nossa Referência
        if ($payment->status === 'approved' && !empty($payment->external_reference)) {
            
            $id_pagamento_interno = $payment->external_reference;
            
            // Busca o 'id_agendamento'
            $stmt_get_ag_id = $pdo->prepare("SELECT id_agendamento FROM pagamento WHERE id_pagamento = ?");
            $stmt_get_ag_id->execute([$id_pagamento_interno]);
            $agendamento_info = $stmt_get_ag_id->fetch(PDO::FETCH_ASSOC);

            if (!$agendamento_info) {
                 throw new Exception("Pagamento interno $id_pagamento_interno não encontrado no banco.");
            }
            
            $id_agendamento = $agendamento_info['id_agendamento'];

            // ATUALIZA O BANCO (lógica do confirmar-agendamento.php)
            $pdo->beginTransaction();
            
            $stmtAg = $pdo->prepare("UPDATE agendamento SET status = 'confirmado' WHERE id_agendamento = ? AND status = 'pendente'");
            $stmtAg->execute([$id_agendamento]);

            $stmtPg = $pdo->prepare("UPDATE pagamento SET status = 'aprovado' WHERE id_pagamento = ? AND status = 'pendente'");
            $stmtPg->execute([$id_pagamento_interno]);
            
            $pdo->commit();
            
            $log_message .= "SUCESSO: Agendamento $id_agendamento confirmado.\n";

        } else {
            $log_message .= "Status não 'approved' (Status: $payment->status).\n";
        }
    } else {
        $log_message .= "Notificação não é 'payment'.\n";
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