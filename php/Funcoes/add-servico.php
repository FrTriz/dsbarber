<?php
// 1. Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// 2. Includes
require_once '../conexao.php';
require_once '../Classes/ServicosClass.php';

// 3. Resposta padrão
$response = ['sucesso' => false, 'mensagem' => 'Método de requisição inválido ou dados insuficientes.'];

try {
    $servicos = new Servicos($pdo);

    // 4. Verifica se é POST e se os dados mínimos existem
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'])) {
        
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $preco = $_POST['preco'];
        $duracao_minutos = $_POST['duracao_minutos'];

        // 5. Tenta adicionar
        if ($servicos->adicionarServico($nome, $descricao, $preco, $duracao_minutos)) {
            $response = ['sucesso' => true, 'mensagem' => 'Serviço adicionado com sucesso!'];
            // $response['id_servico'] = $pdo->lastInsertId(); 
        } else {
            throw new Exception("Erro desconhecido ao salvar no banco.");
        }
    }
} catch (PDOException $e) {
    // 6. Captura erros de banco
    $response = ['sucesso' => false, 'mensagem' => 'Erro de Banco de Dados: ' . $e->getMessage()];
} catch (Exception $e) {
    // 7. Captura outros erros
    $response = ['sucesso' => false, 'mensagem' => 'Erro: ' . $e->getMessage()];
}

// 8. Envia a resposta como JSON
echo json_encode($response);
?>