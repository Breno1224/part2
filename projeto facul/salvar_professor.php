<?php
session_start(); 
include 'db.php'; 

// 1. VERIFICAÇÃO DE SESSÃO E PAPEL DA COORDENAÇÃO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao' || !isset($_SESSION['usuario_id'])) {
    // Se a sessão não for válida, redireciona para o login.
    // Não deve definir mensagens de status aqui, pois não serão vistas.
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha_texto_plano = trim($_POST['senha']);
    $foto_path = null; // Valor padrão se nenhuma foto for enviada ou se houver erro no upload

    // Validação básica dos campos obrigatórios
    if (empty($nome) || empty($email) || empty($senha_texto_plano)) {
        $_SESSION['form_status_message'] = "Nome, email e senha inicial são obrigatórios.";
        $_SESSION['form_status_type'] = "status-error";
        header("Location: coordenacao_add_professor.php");
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['form_status_message'] = "Formato de email inválido.";
        $_SESSION['form_status_type'] = "status-error";
        header("Location: coordenacao_add_professor.php");
        exit();
    }
    if (strlen($senha_texto_plano) < 6) { // Exemplo de validação mínima de senha
        $_SESSION['form_status_message'] = "A senha inicial deve ter pelo menos 6 caracteres.";
        $_SESSION['form_status_type'] = "status-error";
        header("Location: coordenacao_add_professor.php");
        exit();
    }

    // Hash da senha (ESSENCIAL PARA SEGURANÇA)
    $senha_hash = password_hash($senha_texto_plano, PASSWORD_DEFAULT);
    if ($senha_hash === false) {
        $_SESSION['form_status_message'] = "Erro crítico ao processar a senha. Tente novamente.";
        $_SESSION['form_status_type'] = "status-error";
        error_log("Falha no password_hash ao adicionar professor: " . $email);
        header("Location: coordenacao_add_professor.php");
        exit();
    }

    // Upload da Foto (Opcional)
    if (isset($_FILES['foto_professor']) && $_FILES['foto_professor']['error'] == UPLOAD_ERR_OK && $_FILES['foto_professor']['size'] > 0) {
        $upload_dir = 'img/professores/'; 
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0775, true)) { 
                 $_SESSION['form_status_message'] = "Falha técnica: não foi possível criar o diretório de uploads (" . $upload_dir . "). Contate o suporte.";
                 $_SESSION['form_status_type'] = "status-error";
                 error_log("Falha ao criar diretório para fotos de professor: " . $upload_dir);
                 header("Location: coordenacao_add_professor.php");
                 exit();
            }
        }
        if(!is_writable($upload_dir)){
            $_SESSION['form_status_message'] = "Falha técnica: o diretório de uploads não tem permissão de escrita (" . $upload_dir . "). Contate o suporte.";
            $_SESSION['form_status_type'] = "status-error";
            error_log("Diretório de fotos de professor sem permissão de escrita: " . $upload_dir);
            header("Location: coordenacao_add_professor.php");
            exit();
        }

        $file_tmp_path = $_FILES['foto_professor']['tmp_name'];
        $file_name = basename($_FILES['foto_professor']['name']); 
        $file_size = $_FILES['foto_professor']['size'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $unique_file_name = "professor_" . time() . "_" . uniqid() . "." . $file_extension;
        $dest_path = $upload_dir . $unique_file_name;
        
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_mime_type = mime_content_type($file_tmp_path); 

        if (in_array($file_mime_type, $allowed_mime_types) && $file_size <= 2 * 1024 * 1024) { // Max 2MB
            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                $foto_path = $dest_path;
            } else {
                error_log("Falha ao mover o arquivo da foto do professor para: " . $dest_path . ". Erro PHP: " . $_FILES['foto_professor']['error']); 
                $_SESSION['form_status_message'] = "Houve um problema ao salvar a foto. O cadastro prosseguirá sem ela.";
                // Não definir status_type como erro aqui, pois o cadastro pode continuar
            }
        } else {
             error_log("Arquivo de foto do professor inválido. Tipo: " . $file_mime_type . ", Tamanho: " . $file_size);
             $_SESSION['form_status_message'] = "Arquivo de foto inválido (tipo ou tamanho). Máx 2MB, JPG/PNG/GIF. Cadastro prosseguirá sem foto.";
             // Não definir status_type como erro aqui
        }
    }
    // Se $foto_path continuar NULL, o DEFAULT 'img/professores/default_avatar.png' da tabela será usado se configurado no DDL.
    // Caso contrário, será NULL no banco. É melhor ter o DEFAULT no DDL da tabela.

    // Inserir no banco de dados
    // A tabela `professores` deve ter `tema_perfil` com DEFAULT 'padrao' e `biografia` como NULLABLE.
    $sql_insert = "INSERT INTO professores (nome, email, senha, foto_url) VALUES (?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($conn, $sql_insert);

    if ($stmt_insert) {
        // Se $foto_path for NULL, e a coluna foto_url no DB tiver um DEFAULT, esse default será usado.
        // Se a coluna foto_url for apenas NULLABLE, ela ficará NULL.
        mysqli_stmt_bind_param($stmt_insert, "ssss", $nome, $email, $senha_hash, $foto_path);

        if (mysqli_stmt_execute($stmt_insert)) {
            $_SESSION['form_status_message'] = "Professor(a) " . htmlspecialchars($nome) . " cadastrado(a) com sucesso!";
            $_SESSION['form_status_type'] = "status-success";
        } else {
            if (mysqli_errno($conn) == 1062) { 
                $_SESSION['form_status_message'] = "Erro: O email '" . htmlspecialchars($email) . "' já está cadastrado para outro professor.";
            } else {
                $_SESSION['form_status_message'] = "Erro ao cadastrar professor: " . mysqli_stmt_error($stmt_insert);
                error_log("Erro SQL ao salvar professor: " . mysqli_stmt_error($stmt_insert));
            }
            $_SESSION['form_status_type'] = "status-error";
            // Se o DB falhou, mas o arquivo de foto foi salvo, remove o arquivo
            if ($foto_path && file_exists($foto_path)) { 
                unlink($foto_path); 
            }
        }
        mysqli_stmt_close($stmt_insert);
    } else {
         $_SESSION['form_status_message'] = "Erro crítico ao preparar para salvar dados do professor: " . mysqli_error($conn);
         $_SESSION['form_status_type'] = "status-error";
         error_log("Erro prepare SQL salvar professor: " . mysqli_error($conn));
    }
} else {
    // Se não for POST, apenas redireciona, sem mensagem de erro de formulário
    header("Location: coordenacao_add_professor.php");
    exit();
}

if (isset($conn) && $conn) {
    mysqli_close($conn);
}
// SEMPRE redireciona de volta para a página de adicionar professor para mostrar a mensagem
header("Location: coordenacao_add_professor.php");
exit();
?>