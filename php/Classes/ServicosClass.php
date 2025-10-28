<?php
    class Servicos {
        private $pdo;

        public function __construct($pdo) {
            $this->pdo = $pdo;
        }

        public function listarServicos() {
            $stmt = $this->pdo->prepare("SELECT * FROM servicos");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public function buscarPorId($id_servico) {
            $stmt = $this->pdo->prepare("SELECT * FROM servicos WHERE id_servico = :id");    
            $stmt->bindParam(':id', $id_servico, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        public function adicionarServico($nome, $descricao, $preco, $duracao_minutos) {
            $stmt = $this->pdo->prepare("INSERT INTO servicos (nome, descricao, preco, duracao_minutos) VALUES (:nome, :descricao, :preco, :duracao_minutos)");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':preco', $preco);
            $stmt->bindParam(':duracao_minutos', $duracao_minutos);
            return $stmt->execute();
        }

        public function atualizarServico($id_servico, $nome, $descricao, $preco, $duracao_minutos) {
            $stmt = $this->pdo->prepare("UPDATE servicos SET nome = :nome, descricao = :descricao, preco = :preco, duracao_minutos = :duracao_minutos WHERE id_servico = :id");
            $stmt->bindParam(':id', $id_servico, PDO::PARAM_INT);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':preco', $preco);
            $stmt->bindParam(':duracao_minutos', $duracao_minutos);
            return $stmt->execute();
        }

        public function excluirServico($id_servico) {
            $stmt = $this->pdo->prepare("DELETE FROM servicos WHERE id_servico = :id");
            $stmt->bindParam(':id', $id_servico, PDO::PARAM_INT);
            return $stmt->execute();
        }
    
    }

?>