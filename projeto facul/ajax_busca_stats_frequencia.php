<?php
session_start();
header('Content-Type: application/json');

// Validação de segurança básica: apenas professores logados podem acessar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

include 'db.php';

$aluno_id = isset($_GET['aluno_id']) ? intval($_GET['aluno_id']) : 0;
$turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : 0;

if ($aluno_id === 0 || $turma_id === 0) {
    echo json_encode(['error' => 'ID do aluno ou da turma não fornecido.']);
    exit;
}

$stats = [
    'total_aulas' => 0,
    'presencas' => 0,
    'faltas' => 0,
    'atestados' => 0,
    'faltas_justificadas' => 0
];

// Query para contar cada tipo de status de frequência para o aluno na turma
$sql = "SELECT status, COUNT(*) as total 
        FROM frequencia 
        WHERE aluno_id = ? AND turma_id = ? 
        GROUP BY status";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $aluno_id, $turma_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $stats['total_aulas'] += $row['total'];
        switch ($row['status']) {
            case 'P':
                $stats['presencas'] = $row['total'];
                break;
            case 'F':
                $stats['faltas'] = $row['total'];
                break;
            case 'A':
                $stats['atestados'] = $row['total'];
                break;
            case 'FJ':
                $stats['faltas_justificadas'] = $row['total'];
                break;
        }
    }
    mysqli_stmt_close($stmt);
    echo json_encode($stats);
} else {
    echo json_encode(['error' => 'Erro ao consultar estatísticas do banco de dados.']);
}

mysqli_close($conn);
?>