<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
// 1. (Passo 2.2) Controle de Acesso
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso restrito a clientes.']);
    exit();
}
require_once '../conexao.php'; 
// A linha abaixo carrega o autoload, essencial para o MercadoPago\SDK
require_once '../../vendor/autoload.php'; 

$response = ['sucesso' => false];
$id_cliente = $_SESSION['usuario_id']; // Pega o cliente logado

try {
    // 2. Configura o Access Token do .env
    $accessToken = $_ENV['MP_ACCESS_TOKEN']; 
    MercadoPago\SDK::setAccessToken($accessToken);

    // 3. Pega os dados que o 'schedule.js' vai enviar
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!isset($data['id_pagamento']) || !isset($data['valor_a_pagar'])) {
        throw new Exception("Dados de pagamento insuficientes.");
    }

    $id_pagamento_interno = $data['id_pagamento'];
    $valor_a_pagar = (float)$data['valor_a_pagar'];
    
    // -----------------------------------------------------------------
    // 3.5 (Passo 3) VERIFICAÇÃO DE POSSE (PROTEÇÃO CONTRA IDOR)
    // -----------------------------------------------------------------
    // Antes de prosseguir, verificamos se o pagamento pertence ao cliente
    // logado e se o status do pagamento ainda está 'pendente'.
    
    $stmt_check = $pdo->prepare(
        "SELECT 1 FROM pagamento p
         JOIN agendamento a ON p.id_agendamento = a.id_agendamento
         WHERE p.id_pagamento = :id_pagamento
           AND a.id_cliente = :id_cliente
           AND p.status = 'pendente'"
    );

    $stmt_check->execute([
        ':id_pagamento' => $id_pagamento_interno,
        ':id_cliente'   => $id_cliente
    ]);

    // Se fetch() retornar false, o pagamento não existe, não é do cliente,
    // ou não está mais pendente.
    if ($stmt_check->fetch() === false) {
        throw new Exception("Pagamento não encontrado, não autorizado ou já processado.");
    }

    // 4. (SEGURO) Buscar dados do cliente da sessão
    // Só executamos isso se a verificação acima passar
    $stmt_cliente = $pdo->prepare("SELECT nome, email FROM usuarios WHERE id_usuario = ?");
    $stmt_cliente->execute([$id_cliente]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
    $nome_cliente_parts = explode(' ', $cliente['nome'], 2);

    // 5. Cria o objeto de pagamento
    $payment = new MercadoPago\Payment();
    $payment->transaction_amount = $valor_a_pagar;
    $payment->description = "Agendamento DsBarber - Pedido #" . $id_pagamento_interno;
    $payment->payment_method_id = "pix";
    
    // 6. Vincula o ID do MP com o seu banco de dados
    $payment->external_reference = $id_pagamento_interno; 
    
    // 7. URL do Webhook
    $payment->notification_url = "https://beige-sandpiper-991885.hostingersite.com/php/Funcoes/webhook-mp.php";

    $payment->payer = [
        "email" => $cliente['email'],
        "first_name" => $nome_cliente_parts[0],
        "last_name" => $nome_cliente_parts[1] ?? 'Cliente'
    ];

    // 8. Salva o pagamento (envia para a API do MP)
    $payment->save();

    // 9. Se deu tudo certo, retorna os dados do PIX para o JavaScript
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