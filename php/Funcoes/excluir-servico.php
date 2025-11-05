<?php
// Define o tipo de conteúdo (bom para o fetch)
header('Content-Type: application/json');

// --- INÍCIO DA CORREÇÃO DE SEGURANÇA ---
require_once '../session-manager.php';

// Verifica se o usuário está logado e é admin ou barbeiro
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
    $response = ['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'];
    echo json_encode($response);
    exit();
}
// --- FIM DA CORREÇÃO DE SEGURANÇA ---


// Includes
require_once '../conexao.php';
require_once '../Classes/ServicosClass.php';

$response = ['sucesso' => false, 'mensagem' => 'Método de requisição inválido ou ID não fornecido.'];

try {
    // Verifica se é POST e se o ID foi enviado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_servico'])) {
        
        $servicos = new Servicos($pdo);
        $id_servico = $_POST['id_servico'];

        if ($servicos->excluirServico($id_servico)) {
            $response = ['sucesso' => true, 'mensagem' => 'Serviço excluído com sucesso!'];
        } else {
            throw new Exception("Erro desconhecido ao excluir do banco.");
        }
    }
} catch (PDOException $e) {
    // Erro de banco de dados
    $response = ['sucesso' => false, 'mensagem' => 'Erro de Banco de Dados: ' . $e->getMessage()];
} catch (Exception $e) {
    // Outros erros
    $response = ['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
}

// Envia a resposta como JSON
echo json_encode($response);
?>