<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    $_SESSION['status_message'] = "Acesso não autorizado.";
    $_SESSION['status_type'] = "status-error";
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $professor_id = $_SESSION['usuario_id'];
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $disciplina_id = intval($_POST['disciplina_id']);
    $turma_id = !empty($_POST['turma_id']) ? intval($_POST['turma_id']) : NULL; // Permite NULL
    $tipo_envio = $_POST['tipo_envio'];
    $tipo_material = trim($_POST['tipo_material']);
    
    $arquivo_path_ou_link = "";

    // Validação básica
    if (empty($titulo) || empty($disciplina_id) || empty($tipo_material)) {
        $_SESSION['status_message'] = "Por favor, preencha todos os campos obrigatórios (Título, Disciplina, Tipo do Material).";
        $_SESSION['status_type'] = "status-error";
        header("Location: gerenciar_materiais.php");
        exit();
    }

    if ($tipo_envio === "arquivo") {
        if (isset($_FILES['arquivo_material']) && $_FILES['arquivo_material']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/materiais_didaticos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); // Tenta criar o diretório recursivamente
            }

            $file_tmp_path = $_FILES['arquivo_material']['tmp_name'];
            $file_name = $_FILES['arquivo_material']['name'];
            $file_size = $_FILES['arquivo_material']['size'];
            // Sanitize filename and make it unique to avoid overwrites
            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $sanitized_file_name_base = preg_replace("/[^a-zA-Z0-9_-]/", "_", pathinfo($file_name, PATHINFO_FILENAME));
            $unique_file_name = time() . '_' . uniqid() . '_' . $sanitized_file_name_base . '.' . $file_extension;
            $dest_path = $upload_dir . $unique_file_name;

            // Limite de tamanho (ex: 10MB)
            if ($file_size > 10 * 1024 * 1024) {
                $_SESSION['status_message'] = "Arquivo muito grande. Máximo de 10MB.";
                $_SESSION['status_type'] = "status-error";
                header("Location: gerenciar_materiais.php");
                exit();
            }
            
            // Tipos de arquivo permitidos (exemplo)
            $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
            if (!in_array($file_extension, $allowed_extensions)) {
                 $_SESSION['status_message'] = "Tipo de arquivo não permitido. Permitidos: " . implode(', ', $allowed_extensions);
                 $_SESSION['status_type'] = "status-error";
                 header("Location: gerenciar_materiais.php");
                 exit();
            }

            if (move_uploaded_file($file_tmp_path, $dest_path)) {
                $arquivo_path_ou_link = $dest_path;
            } else {
                $_SESSION['status_message'] = "Falha ao mover o arquivo enviado. Verifique as permissões da pasta 'uploads/materiais_didaticos/'.";
                $_SESSION['status_type'] = "status-error";
                header("Location: gerenciar_materiais.php");
                exit();
            }
        } else {
            $_SESSION['status_message'] = "Nenhum arquivo enviado ou erro no upload. Código do erro: " . ($_FILES['arquivo_material']['error'] ?? 'Não especificado');
            $_SESSION['status_type'] = "status-error";
            header("Location: gerenciar_materiais.php");
            exit();
        }
    } elseif ($tipo_envio === "link") {
        $link_material = trim($_POST['link_material']);
        if (empty($link_material) || !filter_var($link_material, FILTER_VALIDATE_URL)) {
            $_SESSION['status_message'] = "URL do link externo inválida ou não fornecida.";
            $_SESSION['status_type'] = "status-error";
            header("Location: gerenciar_materiais.php");
            exit();
        }
        $arquivo_path_ou_link = $link_material;
    } else {
        $_SESSION['status_message'] = "Tipo de envio inválido.";
        $_SESSION['status_type'] = "status-error";
        header("Location: gerenciar_materiais.php");
        exit();
    }

    // Inserir no banco de dados
    $sql = "INSERT INTO materiais_didaticos (professor_id, disciplina_id, turma_id, titulo, descricao, arquivo_path_ou_link, tipo_material) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    // O tipo para turma_id pode ser 'i' ou 's' dependendo se é NULL ou int, mas o bind trata bem o NULL para int.
    mysqli_stmt_bind_param($stmt, "iiissss", $professor_id, $disciplina_id, $turma_id, $titulo, $descricao, $arquivo_path_ou_link, $tipo_material);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['status_message'] = "Material didático enviado com sucesso!";
        $_SESSION['status_type'] = "status-success";
    } else {
        $_SESSION['status_message'] = "Erro ao salvar o material no banco de dados: " . mysqli_stmt_error($stmt);
        $_SESSION['status_type'] = "status-error";
        // Se o arquivo foi salvo mas o DB falhou, idealmente o arquivo seria removido (rollback)
        if ($tipo_envio === "arquivo" && file_exists($arquivo_path_ou_link)) {
            // unlink($arquivo_path_ou_link); // Descomente se quiser remover o arquivo em caso de falha no DB
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    header("Location: gerenciar_materiais.php");
    exit();

} else {
    header("Location: index.html"); // Redireciona se não for POST
    exit();
}
?>