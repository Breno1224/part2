<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    $_SESSION['form_status_message'] = "Acesso não autorizado.";
    $_SESSION['form_status_type'] = "status-error";
    header("Location: coordenacao_add_aluno.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha_texto_plano = trim($_POST['senha']); // Senha inicial
    $turma_id = intval($_POST['turma_id']);
    $foto_path = null; // Caminho da foto a ser salvo no BD

    // Validação básica
    if (empty($nome) || empty($email) || empty($senha_texto_plano) || empty($turma_id)) {
        $_SESSION['form_status_message'] = "Todos os campos (exceto foto) são obrigatórios.";
        $_SESSION['form_status_type'] = "status-error";
        header("Location: coordenacao_add_aluno.php");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['form_status_message'] = "Formato de email inválido.";
        $_SESSION['form_status_type'] = "status-error";
        header("Location: coordenacao_add_aluno.php");
        exit();
    }

    // Hash da senha
    $senha_hash = password_hash($senha_texto_plano, PASSWORD_DEFAULT);

    // Upload da Foto (similar ao upload_foto_professor.php)
    if (isset($_FILES['foto_aluno']) && $_FILES['foto_aluno']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'img/alunos/'; // Certifique-se que esta pasta existe e tem permissão de escrita
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        $file_tmp_path = $_FILES['foto_aluno']['tmp_name'];
        $file_name = $_FILES['foto_aluno']['name'];
        $file_size = $_FILES['foto_aluno']['size'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        // Gerar nome único para a foto
        $unique_file_name = "aluno_" . time() . "_" . uniqid() . "." . $file_extension;
        $dest_path = $upload_dir . $unique_file_name;

        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_mime_type = mime_content_type($file_tmp_path);

        if (in_array($file_mime_type, $allowed_mime_types) && $file_size <= 2 * 1024 * 1024) { // Max 2MB
            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                $foto_path = $dest_path;
            } else {
                $_SESSION['form_status_message'] = "Falha ao mover o arquivo da foto.";
                $_SESSION['form_status_type'] = "status-error";
                // Continuar sem foto ou parar? Decidi continuar sem foto.
            }
        } else {
            $_SESSION['form_status_message'] = "Arquivo de foto inválido (tipo ou tamanho). Máx 2MB, JPG/PNG/GIF.";
            $_SESSION['form_status_type'] = "status-error";
            // Continuar sem foto.
        }
    }
    // Se nenhuma foto foi enviada ou houve erro, $foto_path será null, e o BD usará o DEFAULT 'img/alunos/default_avatar.png'

    // Inserir no banco de dados
    $sql = "INSERT INTO alunos (nome, email, senha, turma_id, foto_url) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    // Se foto_path for null, o default da tabela será usado
    mysqli_stmt_bind_param($stmt, "sssis", $nome, $email, $senha_hash, $turma_id, $foto_path);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['form_status_message'] = "Aluno cadastrado com sucesso!";
        $_SESSION['form_status_type'] = "status-success";
    } else {
        if (mysqli_errno($conn) == 1062) { // Código de erro para entrada duplicada (UNIQUE constraint)
            $_SESSION['form_status_message'] = "Erro: Email já cadastrado no sistema.";
        } else {
            $_SESSION['form_status_message'] = "Erro ao cadastrar aluno: " . mysqli_stmt_error($stmt);
        }
        $_SESSION['form_status_type'] = "status-error";
        if ($foto_path && file_exists($foto_path)) { unlink($foto_path); } // Remove foto se DB falhar
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['form_status_message'] = "Requisição inválida.";
    $_SESSION['form_status_type'] = "status-error";
}
mysqli_close($conn);
header("Location: coordenacao_add_aluno.php");
exit();
?>