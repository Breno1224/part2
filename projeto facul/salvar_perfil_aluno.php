<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno' || !isset($_SESSION['usuario_id'])) {
    // Definir uma mensagem genérica se não souber qual ação falhou
    $_SESSION['profile_generic_status_message'] = "Acesso negado.";
    $_SESSION['profile_generic_status_type'] = "status-error";
    header("Location: index.html");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action === 'save_bio_interests') {
        $biografia = isset($_POST['biografia']) ? trim($_POST['biografia']) : '';
        $interesses = isset($_POST['interesses']) ? trim($_POST['interesses']) : '';

        $sql = "UPDATE alunos SET biografia = ?, interesses = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssi", $biografia, $interesses, $aluno_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['bio_interesses_status_message'] = "Biografia e interesses atualizados!";
                $_SESSION['bio_interesses_status_type'] = "status-success";
            } else {
                $_SESSION['bio_interesses_status_message'] = "Erro ao atualizar: " . mysqli_stmt_error($stmt);
                $_SESSION['bio_interesses_status_type'] = "status-error";
            }
            mysqli_stmt_close($stmt);
        } else { /* Erro SQL prepare */ }

    } elseif ($action === 'save_theme') {
        $temas_validos = ['padrao', '8bit', 'natureza', 'academico', 'darkmode'];
        $tema_perfil = isset($_POST['tema_perfil']) ? trim($_POST['tema_perfil']) : 'padrao';

        if (!in_array($tema_perfil, $temas_validos)) {
            $_SESSION['tema_aluno_status_message'] = "Tema inválido.";
            $_SESSION['tema_aluno_status_type'] = "status-error";
        } else {
            $sql = "UPDATE alunos SET tema_perfil = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $tema_perfil, $aluno_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['tema_aluno_status_message'] = "Tema atualizado!";
                    $_SESSION['tema_aluno_status_type'] = "status-success";
                    $_SESSION['tema_usuario'] = $tema_perfil; // Atualiza na sessão!
                } else { /* Erro DB */ }
                mysqli_stmt_close($stmt);
            } else { /* Erro SQL prepare */ }
        }
    } else {
        // Ação desconhecida, pode definir uma mensagem genérica
        $_SESSION['profile_generic_status_message'] = "Ação inválida.";
        $_SESSION['profile_generic_status_type'] = "status-error";
    }
} else { /* Requisição não é POST */ }

if($conn) mysqli_close($conn);
header("Location: perfil_aluno.php"); // Redireciona de volta para o perfil do aluno
exit();
?>