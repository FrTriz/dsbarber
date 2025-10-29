<?php
class Agendamento {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Busca todos os agendamentos (não cancelados) de um barbeiro específico
     * em uma data específica.
     */
    public function listarPorBarbeiroEData($id_barbeiro, $data) {
        try {
            // Buscamos agendamentos que NÃO estejam 'cancelados' ou 'concluidos'
            // (Assumindo que 'pendente' e 'confirmado' ocupam horário)
            $sql = "SELECT hora_inicio, hora_fim FROM agendamento 
                    WHERE id_barbeiro = :id_barbeiro 
                    AND DATE(data_hora_inicio) = :data
                    AND status IN ('pendente', 'confirmado')";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id_barbeiro', $id_barbeiro, PDO::PARAM_INT);
            $stmt->bindParam(':data', $data);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar agendamentos: " . $e->getMessage());
            return [];
        }
    }

    // (Aqui adicionaremos a função 'criarAgendamento' depois)
}
?>