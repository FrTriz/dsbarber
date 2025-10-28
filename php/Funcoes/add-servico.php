<?php
    require_once '../conexao.php';
    require_once '../Classes/ServicosClass.php';
    $servicos = new Servicos($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $preco = $_POST['preco'];
        $duracao_minutos = $_POST['duracao_minutos'];

        if ($servicos->adicionarServico($nome, $descricao, $preco, $duracao_minutos)) {
            echo "Serviço adicionado com sucesso!";
        } else {
            echo "Erro ao adicionar serviço.";
        }
    }
?>