<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    echo json_encode(['error' => 'Acesso não autorizado']);
    exit();
}

$turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : 0;

if ($turma_id > 0) {
    $sql = "SELECT id, nome FROM alunos WHERE turma_id = ? ORDER BY nome";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $turma_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $alunos = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    echo json_encode($alunos);
} else {
    echo json_encode([]); // Retorna array vazio se não houver turma_id
}
mysqli_close($conn);
?>