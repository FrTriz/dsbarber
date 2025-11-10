<?php
// 1. Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// --- INÍCIO DA CORREÇÃO DE SEGURANÇA ---
require_once '../session-manager.php';

// 2. Verifica se o usuário está logado e é admin ou barbeiro
// (Seguindo a mesma lógica de permissão de html/services.php)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
    // Prepara uma resposta de erro e encerra o script
    $response = ['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'];
    echo json_encode($response);
    exit();
}
// --- FIM DA CORREÇÃO DE SEGURANÇA ---


// 3. Includes (agora seguros)
require_once '../conexao.php';
require_once '../Classes/ServicosClass.php';

// 4. Resposta padrão
$response = ['sucesso' => false, 'mensagem' => 'Método de requisição inválido ou dados insuficientes.'];

try {
    $servicos = new Servicos($pdo);

    // 5. Verifica se é POST e se os dados mínimos existem
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
        
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $preco = $_POST['preco'];
        $duracao_minutos = $_POST['duracao_minutos'];

        // 6. Tenta adicionar
        if ($servicos->adicionarServico($nome, $descricao, $preco, $duracao_minutos)) {
            $response = ['sucesso' => true, 'mensagem' => 'Serviço adicionado com sucesso!'];
            // $response['id_servico'] = $pdo->lastInsertId(); 
        } else {
            throw new Exception("Erro desconhecido ao salvar no banco.");
        }
    }
} catch (PDOException $e) {
    // 7. Captura erros de banco
    $response = ['sucesso' => false, 'mensagem' => 'Erro de Banco de Dados: ' . $e->getMessage()];
} catch (Exception $e) {
    // 8. Captura outros erros
    $response = ['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
}

// 9. Envia a resposta como JSON
echo json_encode($response);
?>