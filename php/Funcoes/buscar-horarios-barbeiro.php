<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
require_once '../conexao.php';

// Resposta padrão
$response = ['sucesso' => false, 'horarios' => []];

try {
// 1. (Passo 2.1) Segurança de Nível de Acesso
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
throw new Exception('Acesso não autorizado.');
}

    $id_barbeiro_logado = (int)$_SESSION['usuario_id'];
    $tipo_usuario_logado = $_SESSION['usuario_tipo'];
    $id_barbeiro_alvo;

    // 2. (Passo 3 - Otimizado) Lógica de ID de Barbeiro
    // Se for 'barbeiro', FORÇAMOS o ID da sessão e ignoramos o GET.
    if ($tipo_usuario_logado === 'barbeiro') {
        $id_barbeiro_alvo = $id_barbeiro_logado;
        
    } 
    // Se for 'admin', ele DEVE especificar o ID do barbeiro que quer ver.
    else if ($tipo_usuario_logado === 'admin') {
        if (!isset($_GET['id_barbeiro']) || empty($_GET['id_barbeiro'])) {
            throw new Exception('ID do profissional não fornecido (requerido para Admin).');
        }
        $id_barbeiro_alvo = (int)$_GET['id_barbeiro'];
    }

// 3. Se chegamos aqui, $id_barbeiro_alvo está 100% seguro.
$stmt = $pdo->prepare(
"SELECT dia_semana, hora_inicio, hora_fim, inicio_pausa, fim_pausa 
FROM horarios_trabalho 
WHERE id_barbeiro = :id_barbeiro"
 );
 $stmt->execute(['id_barbeiro' => $id_barbeiro_alvo]);

    $response['horarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['sucesso'] = true;

} catch (PDOException $e) {
    $response['mensagem'] = 'Erro de banco de dados: ' . $e->getMessage();
} catch (Exception $e) {
$response['mensagem'] = $e->getMessage();
}

// 4. Resposta JSON unificada
echo json_encode($response);
?>