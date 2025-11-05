<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
require_once '../conexao.php';

$response = ['sucesso' => false, 'mensagem' => 'Erro desconhecido.'];

// Segurança
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
    $response['mensagem'] = 'Acesso não autorizado.';
    echo json_encode($response);
    exit();
}

// Pega o JSON enviado pelo JS
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id_barbeiro']) || !isset($data['horarios'])) {
    $response['mensagem'] = 'Dados inválidos ou ausentes.';
    echo json_encode($response);
    exit();
}

$id_barbeiro = (int)$data['id_barbeiro'];
$horarios = $data['horarios'];

// Segurança Adicional: Barbeiro só pode editar os próprios horários
if ($_SESSION['usuario_tipo'] === 'barbeiro' && $id_barbeiro !== (int)$_SESSION['usuario_id']) {
    $response['mensagem'] = 'Você não tem permissão para salvar horários de outro profissional.';
    echo json_encode($response);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Deleta todos os horários antigos desse barbeiro
    $stmt_delete = $pdo->prepare("DELETE FROM horarios_trabalho WHERE id_barbeiro = :id_barbeiro");
    $stmt_delete->execute(['id_barbeiro' => $id_barbeiro]);

    // 2. Prepara o statement para inserir os novos horários
    $stmt_insert = $pdo->prepare(
        "INSERT INTO horarios_trabalho (id_barbeiro, dia_semana, hora_inicio, hora_fim, inicio_pausa, fim_pausa) 
         VALUES (:id_barbeiro, :dia_semana, :hora_inicio, :hora_fim, :inicio_pausa, :fim_pausa)"
    );

    // 3. Itera e insere apenas os dias em que ele "trabalha"
    foreach ($horarios as $horario) {
        if ($horario['trabalha']) {
            
            // Converte pausas vazias para NULL (para o banco de dados)
            $inicio_pausa = !empty($horario['inicio_pausa']) ? $horario['inicio_pausa'] : null;
            $fim_pausa = !empty($horario['fim_pausa']) ? $horario['fim_pausa'] : null;

            $stmt_insert->execute([
                ':id_barbeiro' => $id_barbeiro,
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
    $pdo->rollBack();
    $response['mensagem'] = 'Erro de banco de dados: ' . $e->getMessage();
} catch (Exception $e) {
    $pdo->rollBack();
    $response['mensagem'] = 'Erro: ' . $e->getMessage();
}

echo json_encode($response);
?>