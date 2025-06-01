<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao' || !isset($_SESSION['usuario_id'])) {
    $_SESSION['comunicado_coord_status_message'] = "Acesso não autorizado.";
    $_SESSION['comunicado_coord_status_type'] = "status-error";
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $coordenador_id = $_SESSION['usuario_id'];
    $titulo = trim($_POST['titulo']);
    $conteudo = trim($_POST['conteudo']);
    $publico_alvo_selecionado = $_POST['publico_alvo_select'];
    $turma_id = null;
    $publico_alvo_db = null;

    if (empty($titulo) || empty($conteudo) || empty($publico_alvo_selecionado)) {
        $_SESSION['comunicado_coord_status_message'] = "Título, conteúdo e público-alvo são obrigatórios.";
        $_SESSION['comunicado_coord_status_type'] = "status-error";
        header("Location: coordenacao_lancar_comunicado.php");
        exit();
    }

    switch ($publico_alvo_selecionado) {
        case 'TODOS_ALUNOS':
            $publico_alvo_db = 'TODOS_ALUNOS';
            $turma_id = NULL;
            break;
        case 'TURMA_ESPECIFICA_ALUNOS':
            $publico_alvo_db = 'TURMA_ESPECIFICA';
            $turma_id = !empty($_POST['turma_id']) ? intval($_POST['turma_id']) : null;
            if (empty($turma_id)) {
                $_SESSION['comunicado_coord_status_message'] = "Por favor, selecione uma turma específica.";
                $_SESSION['comunicado_coord_status_type'] = "status-error";
                header("Location: coordenacao_lancar_comunicado.php");
                exit();
            }
            break;
        case 'TODOS_PROFESSORES':
            $publico_alvo_db = 'TODOS_PROFESSORES';
            $turma_id = NULL;
            break;
        default:
            $_SESSION['comunicado_coord_status_message'] = "Público-alvo inválido.";
            $_SESSION['comunicado_coord_status_type'] = "status-error";
            header("Location: coordenacao_lancar_comunicado.php");
            exit();
    }
    
    $sql = "INSERT INTO comunicados (coordenador_id, professor_id, turma_id, titulo, conteudo, publico_alvo) 
            VALUES (?, NULL, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iisss", $coordenador_id, $turma_id, $titulo, $conteudo, $publico_alvo_db);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['comunicado_coord_status_message'] = "Comunicado publicado com sucesso!";
            $_SESSION['comunicado_coord_status_type'] = "status-success";
        } else {
            $_SESSION['comunicado_coord_status_message'] = "Erro ao publicar comunicado: " . mysqli_stmt_error($stmt);
            $_SESSION['comunicado_coord_status_type'] = "status-error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['comunicado_coord_status_message'] = "Erro ao preparar SQL: " . mysqli_error($conn);
        $_SESSION['comunicado_coord_status_type'] = "status-error";
    }
    mysqli_close($conn);
    header("Location: coordenacao_lancar_comunicado.php");
    exit();
} else {
    header("Location: coordenacao_lancar_comunicado.php");
    exit();
}
?>