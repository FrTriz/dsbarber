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

    public function criarAgendamento($idCliente, $idBarbeiro, $dataHoraInicio, $dataHoraFim, $listaServicosIDs) {
        
        // Validação
        if (empty($idCliente) || empty($idBarbeiro) || empty($dataHoraInicio) || empty($dataHoraFim) || empty($listaServicosIDs)) {
            throw new Exception("Dados insuficientes para criar o agendamento.");
        }

        try {
            // 1. Iniciar a Transação
            $this->pdo->beginTransaction();

            // 2. Inserir na tabela principal 'agendamento'
            $sqlAgendamento = "INSERT INTO agendamento (id_cliente, id_barbeiro, data_hora_inicio, data_hora_fim, status) 
                               VALUES (:id_cliente, :id_barbeiro, :data_hora_inicio, :data_hora_fim, 'pendente')";
            
            $stmtAg = $this->pdo->prepare($sqlAgendamento);
            $stmtAg->execute([
                ':id_cliente' => $idCliente,
                ':id_barbeiro' => $idBarbeiro,
                ':data_hora_inicio' => $dataHoraInicio,
                ':data_hora_fim' => $dataHoraFim
            ]);

            // 3. Obter o ID do agendamento que acabamos de criar
            $idAgendamento = $this->pdo->lastInsertId();

            // 4. Inserir os serviços na nova tabela 'agendamento_servicos' (um por um)
            $sqlServicos = "INSERT INTO agendamento_servicos (id_agendamento, id_servico) VALUES (:id_agendamento, :id_servico)";
            $stmtServ = $this->pdo->prepare($sqlServicos);

            foreach ($listaServicosIDs as $idServico) {
                $stmtServ->execute([
                    ':id_agendamento' => $idAgendamento,
                    ':id_servico' => $idServico
                ]);
            }

            // 5. Se tudo deu certo, comitar a transação
            $this->pdo->commit();

            // 6. Retornar o ID do novo agendamento
            return $idAgendamento;

        } catch (Exception $e) {
            // 7. Se algo deu errado, reverter tudo
            $this->pdo->rollBack();
            // Propaga o erro para ser pego pelo 'catch' do script principal
            throw new Exception("Erro ao salvar agendamento no banco: " . $e->getMessage());
        }
    }
}
?>