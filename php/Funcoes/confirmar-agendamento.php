<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
require_once '../conexao.php';

$response = ['sucesso' => false, 'mensagem' => 'ID do agendamento não fornecido.'];

try {
    // 1. Verificar permissões (Admin ou Barbeiro)
    if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
        throw new Exception("Acesso não autorizado.");
    }

    // 2. Verificar se o ID foi enviado via POST
    if (isset($_POST['id_agendamento'])) {
        $id_agendamento = (int)$_POST['id_agendamento'];

        $pdo->beginTransaction();

        // 3. Atualizar o status do agendamento para 'confirmado'
        $stmtAg = $pdo->prepare("UPDATE agendamento SET status = 'confirmado' WHERE id_agendamento = :id");
        $stmtAg->execute(['id' => $id_agendamento]);

        // 4. CORREÇÃO: Atualizar o status do pagamento para 'aprovado'
        // (A tabela 'pagamento' usa 'aprovado', não 'confirmado')
        $stmtPg = $pdo->prepare("UPDATE pagamento SET status = 'aprovado' WHERE id_agendamento = :id");
        $stmtPg->execute(['id' => $id_agendamento]);
        
        $pdo->commit();

        $response = ['sucesso' => true, 'mensagem' => 'Agendamento confirmado com sucesso!'];
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