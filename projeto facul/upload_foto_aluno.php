<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno' || !isset($_SESSION['usuario_id'])) {
    $_SESSION['upload_aluno_status_message'] = "Acesso negado.";
    $_SESSION['upload_aluno_status_type'] = "status-error";
    header("Location: index.html");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_perfil_aluno'])) {
    $file = $_FILES['foto_perfil_aluno'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        // ... (código de tratamento de erro de upload, como em upload_foto_professor.php) ...
        $_SESSION['upload_aluno_status_message'] = "Erro no upload: " . $file['error'];
        $_SESSION['upload_aluno_status_type'] = "status-error";
        header("Location: perfil_aluno.php");
        exit();
    }

    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_mime_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_mime_type, $allowed_mime_types)) {
        $_SESSION['upload_aluno_status_message'] = "Tipo de arquivo inválido. Apenas JPG, PNG, GIF.";
        $_SESSION['upload_aluno_status_type'] = "status-error";
        header("Location: perfil_aluno.php");
        exit();
    }
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
        $_SESSION['upload_aluno_status_message'] = "Arquivo muito grande (máx 2MB).";
        $_SESSION['upload_aluno_status_type'] = "status-error";
        header("Location: perfil_aluno.php");
        exit();
    }

    $upload_dir = 'img/alunos/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) { /* ... tratamento de erro ... */ }
    if (!is_writable($upload_dir)) { /* ... tratamento de erro ... */ }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_file_name = "aluno_" . $aluno_id . "_" . time() . "." . $file_extension;
    $destination = $upload_dir . $new_file_name;

    $sql_old_photo = "SELECT foto_url FROM alunos WHERE id = ?";
    $stmt_old = mysqli_prepare($conn, $sql_old_photo);
    mysqli_stmt_bind_param($stmt_old, "i", $aluno_id);
    mysqli_stmt_execute($stmt_old);
    $result_old = mysqli_stmt_get_result($stmt_old);
    $old_photo_data = mysqli_fetch_assoc($result_old);
    mysqli_stmt_close($stmt_old);

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $sql_update = "UPDATE alunos SET foto_url = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "si", $destination, $aluno_id);
        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['upload_aluno_status_message'] = "Foto atualizada!";
            $_SESSION['upload_aluno_status_type'] = "status-success";
            if ($old_photo_data && !empty($old_photo_data['foto_url']) && $old_photo_data['foto_url'] !== 'img/alunos/default_avatar.png' && file_exists($old_photo_data['foto_url'])) {
                unlink($old_photo_data['foto_url']);
            }
        } else { /* ... erro DB ... */ }
        mysqli_stmt_close($stmt_update);
    } else { /* ... erro ao mover ... */ }
} else { /* ... requisição inválida ... */ }

if($conn) mysqli_close($conn);
header("Location: perfil_aluno.php");
exit();
?>