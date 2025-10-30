<?php
header('Content-Type: application/json');

require_once '../session-manager.php';
require_once '../conexao.php';

// Array de resposta
$response = ['sucesso' => false, 'agendamentos' => []];

try {
    // 1. Verificar se o usuário está logado (Admin ou Barbeiro)
    if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_tipo'] !== 'admin' && $_SESSION['usuario_tipo'] !== 'barbeiro')) {
        throw new Exception("Acesso não autorizado.");
    }
    
    // 2. Obter parâmetros da requisição (Ano, Mês, Filtros)
    $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
    $filterBarberId = isset($_GET['barber']) ? $_GET['barber'] : 'all'; // Pode ser 'all' ou um ID
    $filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all'; // 'all', 'pendente', etc.
    
    // Formata o mês para o SQL (ex: 2025-10)
    $mesAno = sprintf('%d-%02d', $year, $month);

    // 3. Montar a Query SQL usando a VIEW
    $sqlBase = "SELECT 
                    id_agendamento, 
                    DATE(data_hora_inicio) as dia, 
                    TIME(data_hora_inicio) as hora_inicio_fmt, 
                    nome_cliente, 
                    servicos_agendados, 
                    status_agendamento,
                    id_barbeiro -- Adicionado para a lógica de filtro
                FROM vw_agendamentos_completos";
                
    $conditions = ["DATE_FORMAT(data_hora_inicio, '%Y-%m') = :mes_ano"];
    $params = [':mes_ano' => $mesAno];

    // LÓGICA DE PERMISSÃO:
    // Se o usuário logado for um BARBEIRO, ele só pode ver os *seus* agendamentos
    if ($_SESSION['usuario_tipo'] === 'barbeiro') {
         $conditions[] = "id_barbeiro = :id_barbeiro_logado";
         $params[':id_barbeiro_logado'] = $_SESSION['usuario_id'];
    } 
    // Se for ADMIN e escolheu um barbeiro específico
    elseif ($filterBarberId !== 'all') {
        $conditions[] = "id_barbeiro = :id_barbeiro_filtro";
        $params[':id_barbeiro_filtro'] = (int)$filterBarberId;
    }

    // Adicionar filtro de Status (se não for 'all')
    if ($filterStatus !== 'all') {
        $conditions[] = "status_agendamento = :status_filtro";
        $params[':status_filtro'] = $filterStatus;
    }
    
    // Montar a query final
    $sql = $sqlBase . " WHERE " . implode(" AND ", $conditions) . " ORDER BY data_hora_inicio ASC";
    
    // 4. Executar e retornar
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $response['agendamentos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['sucesso'] = true;

} catch (PDOException $e) {
    $response['mensagem'] = "Erro de Banco de Dados: " . $e->getMessage();
} catch (Exception $e) {
    $response['mensagem'] = "Erro: " . $e->getMessage();
}

echo json_encode($response);
?>