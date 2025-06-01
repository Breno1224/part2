<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    echo json_encode(['error' => 'Acesso não autorizado.']);
    exit();
}

$aluno_id = isset($_GET['aluno_id']) ? intval($_GET['aluno_id']) : 0;
$turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : 0;

if ($aluno_id <= 0 || $turma_id <= 0) {
    echo json_encode(['error' => 'IDs de aluno ou turma inválidos.']);
    exit();
}

$response_data = [
    'totalAulas' => 0,
    'presencas' => 0,
    'faltas' => 0,
    'porcentagemPresenca' => 0,
    'error' => null
];

// Contar total de aulas registradas para o aluno nesta turma
$sql_total = "SELECT COUNT(id) as total_registros FROM frequencia WHERE aluno_id = ? AND turma_id = ?";
$stmt_total = mysqli_prepare($conn, $sql_total);
if ($stmt_total) {
    mysqli_stmt_bind_param($stmt_total, "ii", $aluno_id, $turma_id);
    mysqli_stmt_execute($stmt_total);
    $result_total = mysqli_stmt_get_result($stmt_total);
    $data_total = mysqli_fetch_assoc($result_total);
    $response_data['totalAulas'] = intval($data_total['total_registros']);
    mysqli_stmt_close($stmt_total);
} else {
    $response_data['error'] = "Erro ao buscar total de aulas: " . mysqli_error($conn);
    echo json_encode($response_data);
    exit();
}

if ($response_data['totalAulas'] > 0) {
    // Contar presenças (P e A)
    $sql_presencas = "SELECT COUNT(id) as count_presencas FROM frequencia WHERE aluno_id = ? AND turma_id = ? AND (status = 'P' OR status = 'A')";
    $stmt_presencas = mysqli_prepare($conn, $sql_presencas);
    if($stmt_presencas){
        mysqli_stmt_bind_param($stmt_presencas, "ii", $aluno_id, $turma_id);
        mysqli_stmt_execute($stmt_presencas);
        $result_presencas = mysqli_stmt_get_result($stmt_presencas);
        $data_presencas = mysqli_fetch_assoc($result_presencas);
        $response_data['presencas'] = intval($data_presencas['count_presencas']);
        mysqli_stmt_close($stmt_presencas);
    } else {
        $response_data['error'] = "Erro ao buscar presenças: " . mysqli_error($conn);
        echo json_encode($response_data);
        exit();
    }
    
    // Contar faltas (F e FJ)
    $sql_faltas = "SELECT COUNT(id) as count_faltas FROM frequencia WHERE aluno_id = ? AND turma_id = ? AND (status = 'F' OR status = 'FJ')";
    $stmt_faltas = mysqli_prepare($conn, $sql_faltas);
     if($stmt_faltas){
        mysqli_stmt_bind_param($stmt_faltas, "ii", $aluno_id, $turma_id);
        mysqli_stmt_execute($stmt_faltas);
        $result_faltas = mysqli_stmt_get_result($stmt_faltas);
        $data_faltas = mysqli_fetch_assoc($result_faltas);
        $response_data['faltas'] = intval($data_faltas['count_faltas']);
        mysqli_stmt_close($stmt_faltas);
    } else {
        $response_data['error'] = "Erro ao buscar faltas: " . mysqli_error($conn);
        echo json_encode($response_data);
        exit();
    }

    $response_data['porcentagemPresenca'] = ($response_data['presencas'] / $response_data['totalAulas']) * 100;
}

mysqli_close($conn);
echo json_encode($response_data);
exit();
?>