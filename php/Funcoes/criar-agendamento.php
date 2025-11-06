<?php

require_once '../session-manager.php';
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {

    // Se for uma página HTML, redirecione
    // header('Location: ../html/login.php');
    
    // Se for uma API (retorna JSON)
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso restrito a clientes.']);
    exit();
} 

header('Content-Type: application/json');

// Includes
require_once '../conexao.php';
require_once '../Classes/AgendamentoClass.php';

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

    // --- 2. INICIAR A TRANSAÇÃO E VERIFICAR COLISÃO ---
    $pdo->beginTransaction(); 

    // VERIFICAÇÃO ANTI-RACE-CONDITION
    // (Esta é a Linha 1 corrigida)
    $sqlCheck = "SELECT id_agendamento FROM agendamento
                 WHERE id_barbeiro = :id_barbeiro
                 AND status IN ('pendente', 'confirmado')
                 AND (
                    (data_hora_inicio < :data_hora_fim) AND (data_hora_fim > :data_hora_inicio)
                 )";
    $stmtCheck = $pdo->prepare($sqlCheck); 
    $stmtCheck->execute([
        ':id_barbeiro' => $idBarbeiro,
        ':data_hora_inicio' => $dataHoraInicio,
        ':data_hora_fim' => $dataHoraFim
    ]); 

    if ($stmtCheck->fetch()) { 
        // Se encontrar, lança uma exceção que será pega pelo JS
        throw new Exception("O horário selecionado (".$horaSelecionada.") já está reservado. Por favor, volte e escolha um novo horário."); 
    }
    // --- FIM DA VERIFICAÇÃO ---


    // --- 3. AÇÃO 1: CRIAR O AGENDAMENTO ---
    $agendamentoObj = new Agendamento($pdo); 
    $idAgendamento = $agendamentoObj->criarAgendamento($idCliente, $idBarbeiro, $dataHoraInicio, $dataHoraFim, $listaServicosIDs); 

    // --- 4. AÇÃO 2: CRIAR O REGISTRO DE PAGAMENTO ---
    // (Esta é a Linha 2 corrigida)
    $sqlPagamento = "INSERT INTO pagamento (id_agendamento, valor, status) 
                       VALUES (:id_agendamento, :valor, 'pendente')"; 
    $stmtPag = $pdo->prepare($sqlPagamento); 
    $stmtPag->execute([
        ':id_agendamento' => $idAgendamento,
        ':valor' => $valorAPagar
    ]); 
    
    $idPagamento = $pdo->lastInsertId(); 

    // --- 5. FINALIZAR TRANSAÇÃO ---
    $pdo->commit(); 
    
    // --- 6. SUCESSO ---
    $response['sucesso'] = true;
    $response['id_agendamento'] = $idAgendamento; 
    $response['id_pagamento'] = $idPagamento; 
    $response['valor_a_pagar'] = $valorAPagar; 

} catch (Exception $e) {
    // --- ATUALIZAÇÃO IMPORTANTE ---
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); 
    }
    $response['mensagem'] = $e->getMessage(); 
}

// Se o script chegar até aqui, ele ENVIARÁ um JSON válido
echo json_encode($response);
?>