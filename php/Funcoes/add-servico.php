<?php
// 1. Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// --- INÍCIO DA CORREÇÃO DE SEGURANÇA ---
require_once '../session-manager.php';

// 2. Verifica se o usuário está logado e é admin ou barbeiro
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {// Prepara uma resposta de erro e encerra o script
$response = ['sucesso' => false, 'mensagem' => 'Acesso não autorizado.'];
echo json_encode($response);
exit();
}
// --- FIM DA CORREÇÃO DE SEGURANÇA ---

// 3. Includes (agora seguros)
require_once '../conexao.php';
require_once '../Classes/ServicosClass.php';

// 4. Resposta padrão
$response = ['sucesso' => false]; // Mensagem de erro virá do catch

try {
$servicos = new Servicos($pdo);

// 5. Verifica se é POST e se os dados mínimos existem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {

        // -----------------------------------------------------------
// 5.1 (PROTEÇÃO XSS - SANITIZAÇÃO DE ENTRADA)
        // -----------------------------------------------------------
        // Remove todas as tags HTML do nome e da descrição.
        // Força os valores de preço e duração para os tipos numéricos corretos.

        // strip_tags() remove "<script>...</script>" e outras tags.
$nome = strip_tags($_POST['nome']); 
$descricao = strip_tags($_POST['descricao']);
        // Forçar o tipo para float/int é a melhor sanitização para números.
$preco = (float)$_POST['preco'];
$duracao_minutos = (int)$_POST['duracao_minutos'];

        // Validação extra (opcional, mas boa)
        if (empty($nome)) {
            throw new Exception("O nome do serviço não pode estar vazio.");
        }
        if ($preco <= 0 || $duracao_minutos <= 0) {
            throw new Exception("Preço e duração devem ser valores positivos.");
        }
        // -----------------------------------------------------------

// 6. Tenta adicionar (agora com dados sanitizados)
if ($servicos->adicionarServico($nome, $descricao, $preco, $duracao_minutos)) {
$response = ['sucesso' => true, 'mensagem' => 'Serviço adicionado com sucesso!'];
 } else {
 throw new Exception("Erro desconhecido ao salvar no banco.");
}
} else {
        throw new Exception("Método de requisição inválido ou dados insuficientes.");
    }

} catch (PDOException $e) {
// 7. Captura erros de banco
$response = ['sucesso' => false, 'mensagem' => 'Erro de Banco de Dados: ' . $e->getMessage()];
} catch (Exception $e) {
// 8. Captura outros erros
$response = ['sucesso' => false, 'mensagem' => $e->getMessage()];
}

// 9. Envia a resposta como JSON
echo json_encode($response);
?>