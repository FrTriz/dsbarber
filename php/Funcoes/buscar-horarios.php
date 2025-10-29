<?php
// Define o tipo de conteúdo como JSON
header('Content-Type: application/json');

// Includes
require_once '../conexao.php';
require_once '../Classes/AgendamentoClass.php';

// Array que será retornado como JSON
$horarios_disponiveis = [];

try {
    // --- 1. OBTER OS DADOS DE ENTRADA (via GET) ---
    if (!isset($_GET['id_barbeiro']) || !isset($_GET['data']) || !isset($_GET['duracao'])) {
        throw new Exception("Parâmetros incompletos (barbeiro, data, duracao).");
    }

    $id_barbeiro = (int)$_GET['id_barbeiro'];
    $data_selecionada = $_GET['data']; // Formato YYYY-MM-DD
    $duracao_servico = (int)$_GET['duracao']; // Em minutos
    
    // Converte a data para saber o dia da semana (0=Domingo, 1=Segunda, ...)
    $dia_semana = date('w', strtotime($data_selecionada));

    // --- 2. BUSCAR A JORNADA DE TRABALHO DO BARBEIRO ---
    $stmt_jornada = $pdo->prepare(
        "SELECT hora_inicio, hora_fim, inicio_pausa, fim_pausa 
         FROM horarios_trabalho 
         WHERE id_barbeiro = :id_barbeiro AND dia_semana = :dia_semana"
    );
    $stmt_jornada->execute(['id_barbeiro' => $id_barbeiro, 'dia_semana' => $dia_semana]);
    $jornada = $stmt_jornada->fetch(PDO::FETCH_ASSOC);

    // Se não encontrou jornada, o barbeiro não trabalha neste dia
    if (!$jornada) {
        echo json_encode(['mensagem' => 'O barbeiro não trabalha neste dia.']);
        exit;
    }

    // --- 3. BUSCAR AGENDAMENTOS JÁ EXISTENTES ---
    $agendamentoObj = new Agendamento($pdo);
    $agendamentos_ocupados = $agendamentoObj->listarPorBarbeiroEData($id_barbeiro, $data_selecionada);

    // --- 4. O ALGORITMO GERADOR DE SLOTS ---
    
    // Definimos o "passo" da nossa verificação (ex: a cada 15 minutos)
    $intervalo_slots = 60; // em minutos

    // Converte os horários da jornada para objetos DateTime
    $inicio_expediente = new DateTime($data_selecionada . ' ' . $jornada['hora_inicio']);
    $fim_expediente = new DateTime($data_selecionada . ' ' . $jornada['hora_fim']);
    $inicio_pausa = $jornada['inicio_pausa'] ? new DateTime($data_selecionada . ' ' . $jornada['inicio_pausa']) : null;
    $fim_pausa = $jornada['fim_pausa'] ? new DateTime($data_selecionada . ' ' . $jornada['fim_pausa']) : null;

    // Define o horário de início da nossa varredura
    $slot_atual = clone $inicio_expediente;

    // Loop: varre o dia do barbeiro, do início ao fim
    while ($slot_atual < $fim_expediente) {
        
        // Calcula o horário de término do serviço
        $slot_fim = (clone $slot_atual)->modify("+$duracao_servico minutes");
        
        $esta_disponivel = true;

        // VERIFICAÇÃO 1: O serviço termina depois do fim do expediente?
        if ($slot_fim > $fim_expediente) {
            $esta_disponivel = false;
        }

        // VERIFICAÇÃO 2: O serviço colide com a pausa/almoço?
        if ($inicio_pausa && $fim_pausa) {
            // O serviço começa antes do fim da pausa E termina depois do início da pausa?
            if ($slot_atual < $fim_pausa && $slot_fim > $inicio_pausa) {
                $esta_disponivel = false;
            }
        }

        // VERIFICAÇÃO 3: O serviço colide com agendamentos existentes?
        foreach ($agendamentos_ocupados as $ocupado) {
            $ocupado_inicio = new DateTime($data_selecionada . ' ' . $ocupado['hora_inicio']);
            $ocupado_fim = new DateTime($data_selecionada . ' ' . $ocupado['hora_fim']);

            // O serviço começa antes do fim do ocupado E termina depois do início do ocupado?
            if ($slot_atual < $ocupado_fim && $slot_fim > $ocupado_inicio) {
                $esta_disponivel = false;
                break; // Se colide com um, não precisa checar os outros
            }
        }

        // Se passou em todas as verificações, o slot está livre!
        if ($esta_disponivel) {
            $horarios_disponiveis[] = $slot_atual->format('H:i');
        }

        // Avança para o próximo slot
        $slot_atual->modify("+$intervalo_slots minutes");
    }

    // --- 5. RETORNAR OS DADOS ---
    echo json_encode($horarios_disponiveis);

} catch (Exception $e) {
    // Captura qualquer erro e envia como JSON
    echo json_encode(['erro' => $e->getMessage()]);
}
?>