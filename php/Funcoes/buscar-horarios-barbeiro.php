<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
require_once '../conexao.php';

// Segurança: Apenas admin ou o próprio barbeiro podem ver os horários
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
    echo json_encode(['erro' => 'Acesso não autorizado.']);
    exit();
}

if (!isset($_GET['id_barbeiro'])) {
    echo json_encode(['erro' => 'ID do barbeiro não fornecido.']);
    exit();
}

$id_barbeiro = (int)$_GET['id_barbeiro'];

// Segurança Adicional: Se for um barbeiro, ele só pode ver os próprios horários
if ($_SESSION['usuario_tipo'] === 'barbeiro' && $id_barbeiro !== (int)$_SESSION['usuario_id']) {
     echo json_encode(['erro' => 'Você não tem permissão para ver os horários de outro profissional.']);
    exit();
}

try {
    $stmt = $pdo->prepare(
        "SELECT dia_semana, hora_inicio, hora_fim, inicio_pausa, fim_pausa 
         FROM horarios_trabalho 
         WHERE id_barbeiro = :id_barbeiro"
    );
    $stmt->execute(['id_barbeiro' => $id_barbeiro]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($horarios);

} catch (PDOException $e) {
    echo json_encode(['erro' => 'Erro de banco de dados: ' . $e->getMessage()]);
}
?>