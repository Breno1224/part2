<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    $_SESSION['comunicado_status_message'] = "Acesso não autorizado.";
    $_SESSION['comunicado_status_type'] = "status-error";
    // Redirecionar para uma página de erro ou login, ou para o perfil se o ID for conhecido
    header("Location: index.html"); 
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $professor_id = $_SESSION['usuario_id'];
    $titulo = trim($_POST['titulo']);
    $conteudo = trim($_POST['conteudo']);
    // Se "Geral" for selecionado, o value é string vazia, converter para NULL para o banco
    $turma_id = !empty($_POST['turma_id']) ? intval($_POST['turma_id']) : NULL;
    $publico_alvo_db = ''; // Será definido abaixo

    if (empty($titulo) || empty($conteudo)) {
        $_SESSION['comunicado_status_message'] = "Título e conteúdo do comunicado são obrigatórios.";
        $_SESSION['comunicado_status_type'] = "status-error";
        header("Location: lancar_comunicado.php");
        exit();
    }

    // Definir o publico_alvo com base na seleção da turma
    if ($turma_id === NULL) {
        // Professor enviou um comunicado geral para seus alunos
        $publico_alvo_db = 'PROFESSOR_GERAL_ALUNOS';
    } else {
        // Professor enviou para uma turma específica
        $publico_alvo_db = 'TURMA_ESPECIFICA';
    }
    
    // coordenador_id será NULL, pois é um professor enviando
    $sql = "INSERT INTO comunicados (professor_id, coordenador_id, turma_id, titulo, conteudo, publico_alvo) 
            VALUES (?, NULL, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iisss", $professor_id, $turma_id, $titulo, $conteudo, $publico_alvo_db);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['comunicado_status_message'] = "Comunicado publicado com sucesso!";
            $_SESSION['comunicado_status_type'] = "status-success";
        } else {
            $_SESSION['comunicado_status_message'] = "Erro ao publicar o comunicado: " . mysqli_stmt_error($stmt);
            $_SESSION['comunicado_status_type'] = "status-error";
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['comunicado_status_message'] = "Erro ao preparar a declaração SQL: " . mysqli_error($conn);
        $_SESSION['comunicado_status_type'] = "status-error";
    }
    mysqli_close($conn);
    header("Location: lancar_comunicado.php"); // Redireciona de volta para a página de lançar
    exit();

} else {
    // Redireciona se não for POST
    header("Location: lancar_comunicado.php");
    exit();
}
?>