<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    $_SESSION['bio_status_message'] = "Acesso negado.";
    $_SESSION['bio_status_type'] = "status-error";
    header("Location: index.html");
    exit();
}

$professor_id = $_SESSION['usuario_id']; // Professor só pode editar a própria bio

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $biografia = isset($_POST['biografia']) ? trim($_POST['biografia']) : '';

    // Atualizar o banco de dados
    $sql = "UPDATE professores SET biografia = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $biografia, $professor_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['bio_status_message'] = "Biografia atualizada com sucesso!";
            $_SESSION['bio_status_type'] = "status-success";
        } else {
            $_SESSION['bio_status_message'] = "Erro ao atualizar biografia: " . mysqli_stmt_error($stmt);
            $_SESSION['bio_status_type'] = "status-error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['bio_status_message'] = "Erro ao preparar SQL: " . mysqli_error($conn);
        $_SESSION['bio_status_type'] = "status-error";
    }
} else {
    $_SESSION['bio_status_message'] = "Requisição inválida.";
    $_SESSION['bio_status_type'] = "status-error";
}
mysqli_close($conn);
header("Location: perfil_professor.php?id=" . $professor_id); // Redireciona de volta para o perfil
exit();
?>