<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// --- INÍCIO DA CORREÇÃO DE SEGURANÇA ---
require_once '../session-manager.php';

// (Passo 2.1 - CORRETO)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
$response = ['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'];
echo json_encode($response);
exit();
}
// --- FIM DA CORREÇÃO DE SEGURANÇA ---

// Includes
require_once '../conexao.php';
require_once '../Classes/ServicosClass.php';

$response = ['sucesso' => false]; // Mensagem de erro virá do catch

try {
// Verifica se é POST e se o ID foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_servico'])) {

$servicos = new Servicos($pdo);

        // -----------------------------------------------------------
// (PROTEÇÃO XSS - SANITIZAÇÃO DE ENTRADA)
        // -----------------------------------------------------------
        // Força os tipos numéricos
$id_servico = (int)$_POST['id_servico'];
$preco = (float)$_POST['preco'];
$duracao_minutos = (int)$_POST['duracao_minutos'];

        // Remove tags HTML de campos de texto
$nome = strip_tags($_POST['nome']);
$descricao = strip_tags($_POST['descricao']);
        // -----------------------------------------------------------

        // (Opcional, mas recomendado) Validação
        if (empty($nome)) {
            throw new Exception("O nome do serviço não pode estar vazio.");
        }
        if ($preco <= 0 || $duracao_minutos <= 0) {
            throw new Exception("Preço e duração devem ser valores positivos.");
        }
        if ($id_servico <= 0) {
            throw new Exception("ID do serviço inválido.");
        }

        // Passa os dados sanitizados para o método
if ($servicos->atualizarServico($id_servico, $nome, $descricao, $preco, $duracao_minutos)) {
 $response = ['sucesso' => true, 'mensagem' => 'Serviço atualizado com sucesso!'];
} else {
throw new Exception("Erro desconhecido ao atualizar no banco.");
 }
} else {
        throw new Exception("Método de requisição inválido ou dados insuficientes.");
    }
} catch (PDOException $e) {
$response = ['sucesso' => false, 'mensagem' => 'Erro de Banco de Dados: ' . $e->getMessage()];
} catch (Exception $e) {
$response = ['sucesso' => false, 'mensagem' => $e->getMessage()];
}

// Envia a resposta como JSON
echo json_encode($response);
?>