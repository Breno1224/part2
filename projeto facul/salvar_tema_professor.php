<?php
session_start(); // ESSENCIAL: no topo absoluto do arquivo
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    $_SESSION['tema_status_message'] = "Acesso negado.";
    $_SESSION['tema_status_type'] = "status-error";
    header("Location: index.html"); // Ou para uma página de erro apropriada
    exit();
}

$professor_id = $_SESSION['usuario_id'];
$temas_validos = ['padrao', '8bit', 'natureza', 'academico', 'darkmode']; // Mantenha esta lista atualizada

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tema_perfil = isset($_POST['tema_perfil']) ? trim($_POST['tema_perfil']) : 'padrao';

    if (!in_array($tema_perfil, $temas_validos)) {
        $_SESSION['tema_status_message'] = "Tema inválido selecionado.";
        $_SESSION['tema_status_type'] = "status-error";
        header("Location: perfil_professor.php?id=" . $professor_id);
        exit();
    }

    $sql = "UPDATE professores SET tema_perfil = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $tema_perfil, $professor_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['tema_status_message'] = "Tema do perfil atualizado com sucesso!";
            $_SESSION['tema_status_type'] = "status-success";
            
            // **** ATUALIZA O TEMA NA SESSÃO ATUAL ****
            $_SESSION['tema_usuario'] = $tema_perfil; 
            // **** FIM DA ATUALIZAÇÃO ****
        } else {
            $_SESSION['tema_status_message'] = "Erro ao atualizar tema no banco: " . mysqli_stmt_error($stmt);
            $_SESSION['tema_status_type'] = "status-error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['tema_status_message'] = "Erro ao preparar SQL para atualizar tema: " . mysqli_error($conn);
        $_SESSION['tema_status_type'] = "status-error";
    }
} else {
    $_SESSION['tema_status_message'] = "Requisição inválida para salvar tema.";
    $_SESSION['tema_status_type'] = "status-error";
}

if ($conn) mysqli_close($conn); // Fechar conexão
// Redireciona de volta para o perfil para ver a mensagem de status e o tema aplicado (no perfil)
header("Location: perfil_professor.php?id=" . $professor_id); 
exit();
?>