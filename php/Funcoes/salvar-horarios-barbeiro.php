<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
require_once '../conexao.php';

$response = ['sucesso' => false];

try {
    // 1. (Passo 2.1) Segurança de Nível de Acesso
    if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
        throw new Exception('Acesso não autorizado.');
    }

    // 2. Pega o JSON enviado pelo JS
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['horarios'])) {
        throw new Exception('Dados de horários ausentes.');
    }

    // 3. (Passo 3 - Otimizado) Lógica de ID de Barbeiro
    // Em vez de verificar, nós FORÇAMOS o ID correto.
    
    $id_barbeiro_logado = (int)$_SESSION['usuario_id'];
    $tipo_usuario_logado = $_SESSION['usuario_tipo'];
    $id_barbeiro_alvo;

    if ($tipo_usuario_logado === 'admin') {
        // Se for Admin, ele DEVE especificar o ID do barbeiro que quer editar
        if (!isset($data['id_barbeiro']) || empty($data['id_barbeiro'])) {
            throw new Exception('ID do profissional não especificado (requerido para Admin).');
        }
        $id_barbeiro_alvo = (int)$data['id_barbeiro'];
        
    } else {
        // Se for 'barbeiro', ele SÓ PODE editar os seus próprios horários.
        // Ignoramos qualquer 'id_barbeiro' que o JS possa ter enviado.
        $id_barbeiro_alvo = $id_barbeiro_logado;
    }

    // 4. Se chegamos aqui, $id_barbeiro_alvo está 100% seguro.
    $horarios = $data['horarios'];

    $pdo->beginTransaction();

    // 5. Deleta todos os horários antigos desse barbeiro
    $stmt_delete = $pdo->prepare("DELETE FROM horarios_trabalho WHERE id_barbeiro = :id_barbeiro");
    $stmt_delete->execute(['id_barbeiro' => $id_barbeiro_alvo]);

    // 6. Prepara o statement para inserir os novos horários
    $stmt_insert = $pdo->prepare(
        "INSERT INTO horarios_trabalho (id_barbeiro, dia_semana, hora_inicio, hora_fim, inicio_pausa, fim_pausa) 
         VALUES (:id_barbeiro, :dia_semana, :hora_inicio, :hora_fim, :inicio_pausa, :fim_pausa)"
    );

    // 7. Itera e insere apenas os dias em que ele "trabalha"
    foreach ($horarios as $horario) {
        if ($horario['trabalha']) {
            
            $inicio_pausa = !empty($horario['inicio_pausa']) ? $horario['inicio_pausa'] : null;
            $fim_pausa = !empty($horario['fim_pausa']) ? $horario['fim_pausa'] : null;

            $stmt_insert->execute([
                ':id_barbeiro' => $id_barbeiro_alvo, // Usa a variável segura
                ':dia_semana' => $horario['dia_semana'],
                ':hora_inicio' => $horario['hora_inicio'],
                ':hora_fim' => $horario['hora_fim'],
                ':inicio_pausa' => $inicio_pausa,
                ':fim_pausa' => $fim_pausa
            ]);
        }
    }

    $pdo->commit();
    $response = ['sucesso' => true, 'mensagem' => 'Horários salvos com sucesso!'];

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    $response['mensagem'] = 'Erro de banco de dados: ' . $e->getMessage();
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    $response['mensagem'] = $e->getMessage(); // Captura todas as exceções
}

echo json_encode($response);
?>