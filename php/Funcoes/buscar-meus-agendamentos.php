<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
require_once '../conexao.php';

$response = ['sucesso' => false, 'agendamentos' => []];

try {
    // 1. Verificar se o usuário está logado e é um cliente
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
        throw new Exception("Acesso não autorizado. Faça login como cliente.");
    }
    
    $id_cliente = $_SESSION['usuario_id'];

   // 2. Montar a Query SQL (CORRIGIDA)
    // (Calcula o valor_total real somando os serviços, ignorando o valor da view)
    $sql = "SELECT 
                a.id_agendamento, 
                DATE_FORMAT(a.data_hora_inicio, '%d/%m/%Y') as data_fmt, 
                DATE_FORMAT(a.data_hora_inicio, '%H:%i') as hora_fmt, 
                vw.servicos_agendados, 
                a.status as status_agendamento,
                p.valor as valor_pendente,
                (
                    SELECT SUM(s.preco) 
                    FROM agendamento_servicos asoc 
                    JOIN servicos s ON asoc.id_servico = s.id_servico
                    WHERE asoc.id_agendamento = a.id_agendamento
                ) as valor_total,
                p.id_pagamento
            FROM 
                agendamento a
            JOIN 
                vw_agendamentos_completos vw ON a.id_agendamento = vw.id_agendamento
            JOIN 
                pagamento p ON a.id_agendamento = p.id_agendamento
            WHERE 
                a.id_cliente = :id_cliente
                AND a.status != 'cancelado' 
            ORDER BY
                a.data_hora_inicio DESC"; // Mais recentes primeiro
    
    // 3. Executar e retornar
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_cliente' => $id_cliente]);
    
    $response['agendamentos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['sucesso'] = true;

} catch (PDOException $e) {
    $response['mensagem'] = "Erro de Banco de Dados: " . $e->getMessage();
} catch (Exception $e) {
    $response['mensagem'] = "Erro: " . $e->getMessage();
}

echo json_encode($response);
?>