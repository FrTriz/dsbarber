<?php
header('Content-Type: application/json');

// Includes de sessão e conexão
require_once '../session-manager.php';
require_once '../conexao.php';

$response = ['sucesso' => false, 'mensagem' => 'ID do agendamento não fornecido.'];

try {
    // 1. Verificar se é um cliente logado
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'cliente') {
        throw new Exception("Acesso não autorizado.");
    }
    
    $id_cliente_logado = $_SESSION['usuario_id'];

    // 2. Verificar se o ID do agendamento foi enviado
    if (isset($_POST['id_agendamento'])) {
        $id_agendamento = (int)$_POST['id_agendamento'];

        $pdo->beginTransaction();

        // 3. VERIFICAÇÃO DE SEGURANÇA:
        // Checa se o agendamento pertence ao cliente E se está 'pendente'
        $stmtCheck = $pdo->prepare(
            "SELECT status FROM agendamento 
             WHERE id_agendamento = :id_agendamento AND id_cliente = :id_cliente"
        );
        $stmtCheck->execute([
            ':id_agendamento' => $id_agendamento, 
            ':id_cliente' => $id_cliente_logado
        ]);
        $agendamento = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$agendamento) {
            throw new Exception("Agendamento não encontrado ou não pertence a você.");
        }

        if ($agendamento['status'] !== 'pendente') {
            throw new Exception("Este agendamento não está mais pendente e não pode ser cancelado.");
        }

        // 4. Se passou, atualiza ambos os status
        $stmtAg = $pdo->prepare("UPDATE agendamento SET status = 'cancelado' WHERE id_agendamento = :id");
        $stmtAg->execute(['id' => $id_agendamento]);

        $stmtPg = $pdo->prepare("UPDATE pagamento SET status = 'cancelado' WHERE id_agendamento = :id");
        $stmtPg->execute(['id' => $id_agendamento]);
        
        $pdo->commit();

        $response = ['sucesso' => true, 'mensagem' => 'Agendamento cancelado com sucesso!'];
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    $response['mensagem'] = "Erro de Banco de Dados: " . $e->getMessage();
} catch (Exception $e) {
    $pdo->rollBack();
    $response['mensagem'] = "Erro: " . $e->getMessage();
}

echo json_encode($response);
?>