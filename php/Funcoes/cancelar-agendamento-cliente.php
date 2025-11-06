<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
require_once '../conexao.php';

$response = ['sucesso' => false]; // Mensagem padrão será definida no catch

try {
    // 1. (Passo 2.2) Verificar se é um cliente logado
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
        throw new Exception("Acesso não autorizado.");
    }
    
    $id_cliente_logado = $_SESSION['usuario_id'];

    // 2. Verificar se o ID do agendamento foi enviado
    if (!isset($_POST['id_agendamento'])) {
         throw new Exception("ID do agendamento não fornecido.");
    }
    
    $id_agendamento = (int)$_POST['id_agendamento'];

    $pdo->beginTransaction();

    // 3. (Passo 3 - Otimizado) Atualiza o agendamento
    // A consulta UPDATE agora inclui a verificação de posse (id_cliente)
    // E a regra de negócio (status = 'pendente') em uma única operação.
    
    $stmtAg = $pdo->prepare(
        "UPDATE agendamento SET status = 'cancelado' 
         WHERE id_agendamento = :id_agendamento 
           AND id_cliente = :id_cliente
           AND status = 'pendente'"
    );
    
    $stmtAg->execute([
        ':id_agendamento' => $id_agendamento,
        ':id_cliente' => $id_cliente_logado
    ]);

    // 4. Verificamos se alguma linha foi realmente alterada.
    // Se rowCount() for 0, significa que o WHERE falhou (ou o ID estava errado, 
    // ou não pertencia ao cliente, ou não estava 'pendente').
    if ($stmtAg->rowCount() === 0) {
        throw new Exception("Agendamento não encontrado, não pertence a você ou não pode ser cancelado.");
    }

    // 5. Atualiza o pagamento correspondente.
    // Adicionamos a mesma lógica de segurança aqui, usando um JOIN.
    $stmtPg = $pdo->prepare(
        "UPDATE pagamento p
         JOIN agendamento a ON p.id_agendamento = a.id_agendamento
         SET p.status = 'cancelado' 
         WHERE p.id_agendamento = :id_agendamento
           AND a.id_cliente = :id_cliente"
    );
    
    $stmtPg->execute([
        ':id_agendamento' => $id_agendamento,
        ':id_cliente' => $id_cliente_logado
    ]);
    
    $pdo->commit();

    $response = ['sucesso' => true, 'mensagem' => 'Agendamento cancelado com sucesso!'];

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    $response['mensagem'] = "Erro de Banco de Dados: " . $e->getMessage();
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    $response['mensagem'] = $e->getMessage(); // A mensagem de erro agora vem das exceptions
}

echo json_encode($response);
?>