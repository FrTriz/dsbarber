<?php

require_once '../session-manager.php'; 

header('Content-Type: application/json');

// Includes
require_once '../conexao.php';
require_once '../Classes/AgendamentoClass.php';
// (Vamos criar a PagamentoClass no próximo passo, por enquanto inserimos direto)

$response = ['sucesso' => false];

try {
    // --- 0. OBTER DADOS ---
    
    // Pega o JSON enviado pelo fetch do schedule.js
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    // Dados do front-end (do state do JS)
    $idBarbeiro = $data['barbeiroId'];
    $dataSelecionada = $data['date']; // YYYY-MM-DD
    $horaSelecionada = $data['time']; // HH:MM
    $servicos = $data['servicos']; // Array de objetos [{id, nome, price, duration}, ...]
    $duracaoTotal = $data['totalDuration'];
    $precoTotal = $data['totalPrice'];
    $paymentOption = $data['paymentOption']; // 'full' ou 'half'
    
    // Dado do back-end (sessão)
    // !! IMPORTANTE: Substitua 'usuario_id' pelo nome real da sua variável de sessão
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception("Usuário não está logado. Sessão não encontrada.");
    }
    $idCliente = $_SESSION['usuario_id']; 

    // --- 1. PREPARAR OS DADOS ---

    // IDs dos serviços
    $listaServicosIDs = array_map(function($servico) {
        return $servico['id'];
    }, $servicos);

    // Calcular data/hora de início e fim
    $dataHoraInicio = $dataSelecionada . ' ' . $horaSelecionada . ':00';
    $inicioObj = new DateTime($dataHoraInicio);
    $fimObj = (clone $inicioObj)->modify("+$duracaoTotal minutes");
    $dataHoraFim = $fimObj->format('Y-m-d H:i:s');
    
    // Calcular valor a pagar
    $valorAPagar = ($paymentOption === 'half') ? ($precoTotal / 2) : $precoTotal;

    // --- 2. AÇÃO 1: CRIAR O AGENDAMENTO ---
    $agendamentoObj = new Agendamento($pdo);
    $idAgendamento = $agendamentoObj->criarAgendamento($idCliente, $idBarbeiro, $dataHoraInicio, $dataHoraFim, $listaServicosIDs);

    // --- 3. AÇÃO 2: CRIAR O REGISTRO DE PAGAMENTO ---
    $sqlPagamento = "INSERT INTO pagamento (id_agendamento, valor, status) 
                       VALUES (:id_agendamento, :valor, 'pendente')";
    $stmtPag = $pdo->prepare($sqlPagamento);
    $stmtPag->execute([
        ':id_agendamento' => $idAgendamento,
        ':valor' => $valorAPagar
    ]);
    
    $idPagamento = $pdo->lastInsertId();

    // --- 4. SUCESSO ---
    // Retornamos os IDs para o JS
    $response['sucesso'] = true;
    $response['id_agendamento'] = $idAgendamento;
    $response['id_pagamento'] = $idPagamento;
    $response['valor_a_pagar'] = $valorAPagar;

} catch (Exception $e) {
    $response['mensagem'] = $e->getMessage();
}

// Envia a resposta como JSON
echo json_encode($response);
?>