<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Includes
require_once '../conexao.php';
require_once '../Classes/AgendamentoClass.php';

$horarios_disponiveis = [];

try {
    // --- 1. OBTER OS DADOS DE ENTRADA (via GET) ---
    if (!isset($_GET['id_barbeiro']) || !isset($_GET['data']) || !isset($_GET['duracao'])) {
        throw new Exception("Parâmetros incompletos (barbeiro, data, duracao).");
    }

    $id_barbeiro = (int)$_GET['id_barbeiro'];
    $data_selecionada = $_GET['data']; // Formato YYYY-MM-DD
    $duracao_servico = (int)$_GET['duracao']; // Em minutos
    
    $dia_semana = date('w', strtotime($data_selecionada));

    // --- 2. BUSCAR A JORNADA DE TRABALHO DO BARBEIRO ---
    $stmt_jornada = $pdo->prepare(
        "SELECT hora_inicio, hora_fim, inicio_pausa, fim_pausa 
         FROM horarios_trabalho 
         WHERE id_barbeiro = :id_barbeiro AND dia_semana = :dia_semana"
    );
    $stmt_jornada->execute(['id_barbeiro' => $id_barbeiro, 'dia_semana' => $dia_semana]);
    $jornada = $stmt_jornada->fetch(PDO::FETCH_ASSOC);

    if (!$jornada) {
        echo json_encode(['mensagem' => 'O barbeiro não trabalha neste dia.']);
        exit;
    }

    // --- 3. BUSCAR AGENDAMENTOS JÁ EXISTENTES ---
    $agendamentoObj = new Agendamento($pdo);
    $agendamentos_ocupados = $agendamentoObj->listarPorBarbeiroEData($id_barbeiro, $data_selecionada);

    // --- 4. O ALGORITMO GERADOR DE SLOTS (MODIFICADO) ---
    $intervalo_slots = 60; // em minutos (slots de 1 em 1 hora)

    $inicio_expediente = new DateTime($data_selecionada . ' ' . $jornada['hora_inicio']);
    $fim_expediente = new DateTime($data_selecionada . ' ' . $jornada['hora_fim']);
    $inicio_pausa = $jornada['inicio_pausa'] ? new DateTime($data_selecionada . ' ' . $jornada['inicio_pausa']) : null;
    $fim_pausa = $jornada['fim_pausa'] ? new DateTime($data_selecionada . ' ' . $jornada['fim_pausa']) : null;

    $slot_atual = clone $inicio_expediente;

    while ($slot_atual < $fim_expediente) {
        
        $slot_fim = (clone $slot_atual)->modify("+$duracao_servico minutes");
        $esta_disponivel = true;

        // VERIFICAÇÃO 1: Termina depois do fim do expediente?
        if ($slot_fim > $fim_expediente) {
            $esta_disponivel = false;
        }

        // VERIFICAÇÃO 2: Colide com a pausa?
        if ($esta_disponivel && $inicio_pausa && $fim_pausa) {
            if ($slot_atual < $fim_pausa && $slot_fim > $inicio_pausa) {
                $esta_disponivel = false;
            }
        }

        // VERIFICAÇÃO 3: Colide com agendamentos existentes?
        if ($esta_disponivel) {
            foreach ($agendamentos_ocupados as $ocupado) {
                $ocupado_inicio = new DateTime($data_selecionada . ' ' . $ocupado['hora_inicio']);
                $ocupado_fim = new DateTime($data_selecionada . ' ' . $ocupado['hora_fim']);

                if ($slot_atual < $ocupado_fim && $slot_fim > $ocupado_inicio) {
                    $esta_disponivel = false;
                    break;
                }
            }
        }

        // (CORREÇÃO) Envia um objeto
        $horarios_disponiveis[] = [
            'time' => $slot_atual->format('H:i'),
            'available' => $esta_disponivel
        ];

        $slot_atual->modify("+$intervalo_slots minutes");
    }

    // --- 5. RETORNAR OS DADOS ---
    echo json_encode($horarios_disponiveis);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
?>