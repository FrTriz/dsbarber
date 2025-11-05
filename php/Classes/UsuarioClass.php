<?php
class Usuario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function buscarPorId($id_usuario) {
        $sql = "SELECT id_usuario, email, tipo, nome FROM usuarios WHERE id_usuario = :id_usuario";
        $cmd = $this->pdo->prepare($sql);
        $cmd->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $cmd->execute();
        return $cmd->fetch(PDO::FETCH_ASSOC);
    }

    public function logOut() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }
public function listarPorTipo($tipo) {
        try {
            $stmt = $this->pdo->prepare("SELECT id_usuario, nome FROM usuarios WHERE tipo = :tipo");
            $stmt->bindParam(':tipo', $tipo);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Lidar com o erro
            error_log("Erro ao listar usuários por tipo: " . $e->getMessage());
            return [];
        }
    }
}

?>