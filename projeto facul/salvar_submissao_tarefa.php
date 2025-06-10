<?php
session_start();
include 'db.php';

// 1. Autenticação e Autorização: Garante que apenas um aluno logado pode enviar.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno' || !isset($_SESSION['usuario_id'])) {
    $_SESSION['tarefa_status_message'] = "Erro: Acesso negado. Faça login como aluno.";
    $_SESSION['tarefa_status_type'] = "status-error";
    header("Location: tarefas_aluno.php");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// 2. Validação da Requisição: Verifica se os dados do formulário e o arquivo foram enviados.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tarefa_id'], $_FILES['arquivo_aluno'])) {

    $tarefa_id = intval($_POST['tarefa_id']);

    // 3. Validação do Upload do Arquivo
    if ($_FILES['arquivo_aluno']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE   => 'O arquivo excede o tamanho máximo permitido pelo servidor.',
            UPLOAD_ERR_FORM_SIZE  => 'O arquivo excede o tamanho máximo permitido no formulário.',
            UPLOAD_ERR_PARTIAL    => 'O upload do arquivo foi feito parcialmente.',
            UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Faltando uma pasta temporária no servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever o arquivo no disco.',
            UPLOAD_ERR_EXTENSION  => 'Uma extensão do PHP interrompeu o upload do arquivo.',
        ];
        $error_message = $upload_errors[$_FILES['arquivo_aluno']['error']] ?? 'Erro desconhecido no upload.';
        $_SESSION['tarefa_status_message'] = "Erro no upload: " . $error_message;
        $_SESSION['tarefa_status_type'] = "status-error";
        header("Location: tarefas_aluno.php");
        exit();
    }
    
    // Definições de segurança para o upload
    $upload_dir = 'uploads/tarefas/aluno/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0775, true); // Cria o diretório se ele não existir
    }

    // Cria um nome de arquivo único para evitar conflitos e problemas de segurança
    $original_filename = basename($_FILES['arquivo_aluno']['name']);
    $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    $unique_filename = "aluno" . $aluno_id . "_tarefa" . $tarefa_id . "_" . time() . "." . $file_extension;
    $target_path = $upload_dir . $unique_filename;
    
    // (Opcional, mas recomendado) Validar tipo de arquivo
    $allowed_types = ['pdf', 'doc', 'docx', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_types)) {
        $_SESSION['tarefa_status_message'] = "Erro: Tipo de arquivo não permitido. Apenas " . implode(', ', $allowed_types) . " são aceitos.";
        $_SESSION['tarefa_status_type'] = "status-error";
        header("Location: tarefas_aluno.php");
        exit();
    }
    
    // 4. Mover o Arquivo Enviado para o Destino Final
    if (move_uploaded_file($_FILES['arquivo_aluno']['tmp_name'], $target_path)) {
        
        // 5. Inserir ou Atualizar o Registro no Banco de Dados
        // Usamos INSERT ... ON DUPLICATE KEY UPDATE para permitir que o aluno reenvie uma tarefa.
        // A chave UNIQUE(tarefa_id, aluno_id) na tabela tarefas_submissoes é essencial para isso.
        $sql = "INSERT INTO tarefas_submissoes (tarefa_id, aluno_id, arquivo_path_aluno, data_submissao)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                arquivo_path_aluno = VALUES(arquivo_path_aluno), 
                data_submissao = VALUES(data_submissao),
                nota = NULL, -- Reseta a nota e o feedback ao reenviar
                feedback_professor = NULL,
                data_avaliacao = NULL";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iis", $tarefa_id, $aluno_id, $target_path);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['tarefa_status_message'] = "Tarefa enviada com sucesso!";
                $_SESSION['tarefa_status_type'] = "status-success";
            } else {
                $_SESSION['tarefa_status_message'] = "Erro ao registrar o envio no banco de dados.";
                $_SESSION['tarefa_status_type'] = "status-error";
                unlink($target_path); // Exclui o arquivo se a inserção no DB falhar
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['tarefa_status_message'] = "Erro ao preparar a query do banco de dados.";
            $_SESSION['tarefa_status_type'] = "status-error";
            unlink($target_path);
        }
    } else {
        $_SESSION['tarefa_status_message'] = "Erro crítico ao mover o arquivo enviado.";
        $_SESSION['tarefa_status_type'] = "status-error";
    }

} else {
    $_SESSION['tarefa_status_message'] = "Requisição inválida ou arquivo não enviado.";
    $_SESSION['tarefa_status_type'] = "status-error";
}

// 6. Redirecionar de volta para a página de tarefas
header("Location: tarefas_aluno.php");
exit();
?>