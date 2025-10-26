<?php
 class Clientes {
     private $pdo;

     public function __construct($pdo) {
         $this->pdo = $pdo;
     }

     public function registrar($nome, $email, $senha) {
         // Verifica se o email já está registrado
         $stmt = $this->pdo->prepare("SELECT id FROM clientes WHERE email = ?");
         $stmt->execute([$email]);
         if ($stmt->fetch()) {
             return ['sucesso' => false, 'mensagem' => 'Email já registrado.'];
         }

         // Insere o novo cliente
         $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
         $stmt = $this->pdo->prepare("INSERT INTO clientes (nome, email, senha) VALUES (?, ?, ?)");
         if ($stmt->execute([$nome, $email, $senha_hash])) {
             return ['sucesso' => true];
         } else {
             return ['sucesso' => false, 'mensagem' => 'Erro ao registrar cliente.'];
         }
     }

     public function autenticar($email, $senha) {
         // Busca o cliente pelo email
         $stmt = $this->pdo->prepare("SELECT id, nome, senha FROM clientes WHERE email = ?");
         $stmt->execute([$email]);
         $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
         if ($cliente && password_verify($senha, $cliente['senha'])) {
             // Autenticação bem-sucedida
             $_SESSION['cliente_id'] = $cliente['id'];
             $_SESSION['cliente_nome'] = $cliente['nome'];
             return ['sucesso' => true];
         } else {
             return ['sucesso' => false, 'mensagem' => 'Email ou senha incorretos.'];
         }
     }

     public function estaLogado() {
         return isset($_SESSION['cliente_id']);
     }

     public function logout() {
         session_unset();
         session_destroy();
     }

        public function buscarPorId($id_cliente) {
            $stmt = $this->pdo->prepare("SELECT * FROM Cliente WHERE id_cliente = :id");    
            $stmt->bindParam(':id', $id_cliente, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
?> 
