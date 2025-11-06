<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
require_once '../conexao.php';
// 1. Inclui o SDK do Mercado Pago (instalado via Composer)
require_once '../../vendor/autoload.php'; 

$response = ['sucesso' => false];

try {
    // 2. PEGUE SEU ACCESS TOKEN NO PAINEL DO MERCADO PAGO
    // (TEST-...) para testes, (APP_USR-...) para produção
    $accessToken = "xxxxxxxxxxxxxxxxxx"; 
    MercadoPago\SDK::setAccessToken($accessToken);

    // 3. Pega os dados que o 'schedule.js' vai enviar
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!isset($data['id_pagamento']) || !isset($data['valor_a_pagar'])) {
        throw new Exception("Dados de pagamento insuficientes.");
    }

    $id_pagamento_interno = $data['id_pagamento'];
    $valor_a_pagar = (float)$data['valor_a_pagar'];
    
    // (Opcional, mas recomendado) Buscar e-mail e nome do cliente da sessão
    $id_cliente = $_SESSION['usuario_id'];
    $stmt_cliente = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id_usuario = ?");
    $stmt_cliente->execute([$id_cliente]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
    $nome_cliente_parts = explode(' ', $cliente['nome'], 2);

    // 4. Cria o objeto de pagamento
    $payment = new MercadoPago\Payment();
    $payment->transaction_amount = $valor_a_pagar;
    $payment->description = "Agendamento DsBarber - Pedido #" . $id_pagamento_interno;
    $payment->payment_method_id = "pix";
    
    // 5. ESSENCIAL: Vincula o ID do MP com o seu banco de dados
    $payment->external_reference = $id_pagamento_interno; 
    
    // 6. URL para onde o MP vai te avisar quando o PIX for pago
    // (Você precisará criar este arquivo 'webhook-mp.php' depois)
    $payment->notification_url = "xxxxxxxxxxxxxxxxxxxxxxxxx";

    $payment->payer = [
        "email" => $cliente['email'],
        "first_name" => $nome_cliente_parts[0],
        "last_name" => $nome_cliente_parts[1] ?? 'Cliente'
    ];

    // 7. Salva o pagamento (envia para a API do MP)
    $payment->save();

    // 8. Se deu tudo certo, retorna os dados do PIX para o JavaScript
    if ($payment->id) {
        $response['sucesso'] = true;
        $response['qr_code_base64'] = $payment->point_of_interaction->transaction_data->qr_code_base64;
        $response['qr_code_copy_paste'] = $payment->point_of_interaction->transaction_data->qr_code;
    } else {
        throw new Exception("Erro ao gerar PIX no Mercado Pago.");
    }

} catch (Exception $e) {
    $response['mensagem'] = $e->getMessage();
}

echo json_encode($response);
?>