<?php
class Usuario {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function buscarPorId($id_usuario) {
        $sql = "SELECT id_usuario, email, tipo FROM usuario WHERE id_usuario = :id_usuario";
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
}

?>