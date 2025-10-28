<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// --- CORREÇÃO DOS CAMINHOS ---
require_once '../conexao.php';
require_once '../Classes/ServicosClass.php';

$response = ['sucesso' => false, 'mensagem' => 'Método de requisição inválido ou dados insuficientes.'];

try {
    // Verifica se é POST e se o ID foi enviado
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_servico'])) {
        
        $servicos = new Servicos($pdo);
        
        // Obter todos os dados do POST
        $id_servico = $_POST['id_servico'];
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $preco = $_POST['preco'];
        $duracao_minutos = $_POST['duracao_minutos'];

        if ($servicos->atualizarServico($id_servico, $nome, $descricao, $preco, $duracao_minutos)) {
            $response = ['sucesso' => true, 'mensagem' => 'Serviço atualizado com sucesso!'];
        } else {
            throw new Exception("Erro desconhecido ao atualizar no banco.");
        }
    }
} catch (PDOException $e) {
    // Erro de banco de dados
    $response = ['sucesso' => false, 'mensagem' => 'Erro de Banco de Dados: ' . $e.getMessage()];
} catch (Exception $e) {
    // Outros erros
    $response = ['sucesso' => false, 'mensagem' => 'Erro: ' . $e.getMessage()];
}

// Envia a resposta como JSON
echo json_encode($response);
?>