<?php
session_start();
include 'db.php';

// Verificar se o professor está logado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    $_SESSION['upload_status_message'] = "Acesso negado. Faça login como professor.";
    $_SESSION['upload_status_type'] = "status-error";
    // Redirecionar para uma página de erro ou login, ou para o perfil se o ID for conhecido
    // Se não soubermos o ID, não podemos redirecionar para o perfil específico.
    header("Location: index.html"); 
    exit();
}

$professor_id = $_SESSION['usuario_id']; // ID do professor logado

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_perfil'])) {
    
    $file = $_FILES['foto_perfil'];

    // Verificar erros de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $phpFileUploadErrors = [
            0 => 'Não houve erro, o upload foi bem sucedido.',
            1 => 'O arquivo enviado excede a diretiva upload_max_filesize no php.ini.',
            2 => 'O arquivo enviado excede a diretiva MAX_FILE_SIZE que foi especificada no formulário HTML.',
            3 => 'O arquivo enviado foi apenas parcialmente carregado.',
            4 => 'Nenhum arquivo foi enviado.',
            6 => 'Faltando uma pasta temporária.',
            7 => 'Falha ao escrever o arquivo em disco.',
            8 => 'Uma extensão do PHP interrompeu o upload do arquivo.',
        ];
        $error_message = isset($phpFileUploadErrors[$file['error']]) ? $phpFileUploadErrors[$file['error']] : 'Erro desconhecido no upload.';
        $_SESSION['upload_status_message'] = "Erro no upload: " . $error_message;
        $_SESSION['upload_status_type'] = "status-error";
        header("Location: perfil_professor.php?id=" . $professor_id);
        exit();
    }

    // Validação do arquivo
    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_mime_type = mime_content_type($file['tmp_name']); // Melhor que verificar extensão

    if (!in_array($file_mime_type, $allowed_mime_types)) {
        $_SESSION['upload_status_message'] = "Tipo de arquivo inválido. Apenas JPG, PNG e GIF são permitidos.";
        $_SESSION['upload_status_type'] = "status-error";
        header("Location: perfil_professor.php?id=" . $professor_id);
        exit();
    }

    // Limite de tamanho (ex: 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        $_SESSION['upload_status_message'] = "Arquivo muito grande. O tamanho máximo é 2MB.";
        $_SESSION['upload_status_type'] = "status-error";
        header("Location: perfil_professor.php?id=" . $professor_id);
        exit();
    }

    // Diretório de upload e nome do arquivo
    $upload_dir = 'img/professores/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
        $_SESSION['upload_status_message'] = "Falha ao criar o diretório de uploads.";
        $_SESSION['upload_status_type'] = "status-error";
        header("Location: perfil_professor.php?id=" . $professor_id);
        exit();
    }
    if (!is_writable($upload_dir)) {
        $_SESSION['upload_status_message'] = "O diretório de uploads não tem permissão de escrita.";
        $_SESSION['upload_status_type'] = "status-error";
        header("Location: perfil_professor.php?id=" . $professor_id);
        exit();
    }


    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_file_name = "professor_" . $professor_id . "_" . time() . "." . $file_extension;
    $destination = $upload_dir . $new_file_name;

    // Antes de salvar a nova foto, buscar a antiga para deletar (se não for a default)
    $sql_old_photo = "SELECT foto_url FROM professores WHERE id = ?";
    $stmt_old = mysqli_prepare($conn, $sql_old_photo);
    mysqli_stmt_bind_param($stmt_old, "i", $professor_id);
    mysqli_stmt_execute($stmt_old);
    $result_old = mysqli_stmt_get_result($stmt_old);
    $old_photo_data = mysqli_fetch_assoc($result_old);
    mysqli_stmt_close($stmt_old);

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Atualizar o banco de dados
        $sql_update = "UPDATE professores SET foto_url = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "si", $destination, $professor_id);

        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['upload_status_message'] = "Foto de perfil atualizada com sucesso!";
            $_SESSION['upload_status_type'] = "status-success";

            // Deletar a foto antiga se não for a default e existir
            if ($old_photo_data && !empty($old_photo_data['foto_url']) && $old_photo_data['foto_url'] !== 'img/professores/default_avatar.png') {
                if (file_exists($old_photo_data['foto_url'])) {
                    unlink($old_photo_data['foto_url']);
                }
            }
        } else {
            $_SESSION['upload_status_message'] = "Erro ao atualizar o banco de dados: " . mysqli_stmt_error($stmt_update);
            $_SESSION['upload_status_type'] = "status-error";
            // Se o DB falhou, deletar a foto que acabou de ser upada
            if (file_exists($destination)) {
                unlink($destination);
            }
        }
        mysqli_stmt_close($stmt_update);
    } else {
        $_SESSION['upload_status_message'] = "Falha ao mover o arquivo para o destino.";
        $_SESSION['upload_status_type'] = "status-error";
    }
} else {
    $_SESSION['upload_status_message'] = "Nenhum arquivo enviado ou requisição inválida.";
    $_SESSION['upload_status_type'] = "status-error";
}

mysqli_close($conn);
header("Location: perfil_professor.php?id=" . $professor_id);
exit();
?>