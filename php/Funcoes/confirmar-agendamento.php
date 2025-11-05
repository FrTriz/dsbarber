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

        // --- INÍCIO DA CORREÇÃO DE SEGURANÇA (IDOR) ---

        // 3. Montar a query de agendamento base
        $sqlAg = "UPDATE agendamento SET status = 'confirmado' WHERE id_agendamento = :id_agendamento";
        
        // Prepara os parâmetros
        $params = [':id_agendamento' => $id_agendamento];

        // 4. Se o usuário NÃO for 'admin', ele DEVE ser 'barbeiro' (verificado no passo 1)
        // Então, adicionamos a verificação de propriedade
        if ($_SESSION['usuario_tipo'] === 'barbeiro') {
            $sqlAg .= " AND id_barbeiro = :id_barbeiro_logado";
            $params[':id_barbeiro_logado'] = $_SESSION['usuario_id'];
        }

        // 5. Executar a atualização do agendamento
        $stmtAg = $pdo->prepare($sqlAg);
        $stmtAg->execute($params);
        
        // 6. VERIFICAR se alguma linha foi realmente atualizada
        // Se for 0, ou o ID não existe, ou o barbeiro não tinha permissão
        if ($stmtAg->rowCount() === 0) {
            throw new Exception("Agendamento não encontrado ou você não tem permissão para modificá-lo.");
        }
        
        // --- FIM DA CORREÇÃO DE SEGURANÇA ---

        // 7. Se o passo 6 passou, atualiza o pagamento (lógica original)
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
    // A mensagem de erro da nossa exceção (passo 6) será capturada aqui
    $response['mensagem'] = "Erro: " . $e->getMessage();
}

echo json_encode($response);
?>