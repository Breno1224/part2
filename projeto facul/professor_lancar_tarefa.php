<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];

$currentPageIdentifier = 'lancar_tarefas_prof'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Lógica de Ações (POST) para Criar, Avaliar e Excluir Tarefas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $redirect_url = "professor_lancar_tarefa.php";

    if ($_POST['action'] === 'add_tarefa') {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        list($turma_id, $disciplina_id) = explode('-', $_POST['turma_disciplina_id']);
        $turma_id = intval($turma_id);
        $disciplina_id = intval($disciplina_id);
        $data_prazo = $_POST['data_prazo'];
        $arquivo_path = null;

        if (empty($titulo) || empty($turma_id) || empty($disciplina_id) || empty($data_prazo)) {
            $_SESSION['tarefa_prof_status_message'] = "Erro: Todos os campos (exceto anexo) são obrigatórios.";
            $_SESSION['tarefa_prof_status_type'] = "status-error";
        } else {
            if (isset($_FILES['arquivo_professor']) && $_FILES['arquivo_professor']['error'] == 0) {
                $upload_dir = 'uploads/tarefas/professor/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0775, true); }
                $file_name = "prof" . $professor_id . "_" . time() . '_' . basename($_FILES['arquivo_professor']['name']);
                $arquivo_path = $upload_dir . $file_name;
                if (!move_uploaded_file($_FILES['arquivo_professor']['tmp_name'], $arquivo_path)) {
                    $_SESSION['tarefa_prof_status_message'] = "Erro ao fazer upload do arquivo.";
                    $_SESSION['tarefa_prof_status_type'] = "status-error";
                    $arquivo_path = null;
                }
            }

            if (!isset($_SESSION['tarefa_prof_status_message'])) {
                $sql = "INSERT INTO tarefas (titulo, descricao, professor_id, disciplina_id, turma_id, data_prazo, arquivo_path_professor) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssiiiss", $titulo, $descricao, $professor_id, $disciplina_id, $turma_id, $data_prazo, $arquivo_path);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['tarefa_prof_status_message'] = "Tarefa criada com sucesso!";
                    $_SESSION['tarefa_prof_status_type'] = "status-success";
                } else {
                    $_SESSION['tarefa_prof_status_message'] = "Erro ao criar tarefa: " . mysqli_stmt_error($stmt);
                    $_SESSION['tarefa_prof_status_type'] = "status-error";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    elseif ($_POST['action'] === 'avaliar_submissoes' && isset($_POST['tarefa_id_avaliada'], $_POST['submissoes'])) {
        $tarefa_id_avaliada = intval($_POST['tarefa_id_avaliada']);
        $redirect_url = "professor_lancar_tarefa.php?view_submissions_for=" . $tarefa_id_avaliada;
        $erros = 0;
        foreach ($_POST['submissoes'] as $submissao_id => $dados) {
            if (empty($submissao_id)) continue;
            $nota = !empty($dados['nota']) ? floatval(str_replace(',', '.', $dados['nota'])) : null;
            $feedback = !empty($dados['feedback']) ? trim($dados['feedback']) : null;
            
            $sql = "UPDATE tarefas_submissoes SET nota = ?, feedback_professor = ?, data_avaliacao = NOW() WHERE id = ? AND tarefa_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "dsii", $nota, $feedback, $submissao_id, $tarefa_id_avaliada);
            if (!mysqli_stmt_execute($stmt)) {
                $erros++;
            }
            mysqli_stmt_close($stmt);
        }
        $_SESSION['tarefa_prof_status_message'] = $erros > 0 ? "Ocorreram {$erros} erros ao salvar as avaliações." : "Avaliações salvas com sucesso!";
        $_SESSION['tarefa_prof_status_type'] = $erros > 0 ? "status-error" : "status-success";
    }
    elseif ($_POST['action'] === 'delete_tarefa' && isset($_POST['tarefa_id_delete'])) {
        $tarefa_id_del = intval($_POST['tarefa_id_delete']);
        $sql = "DELETE FROM tarefas WHERE id = ? AND professor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $tarefa_id_del, $professor_id);
        if(mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0){
            $_SESSION['tarefa_prof_status_message'] = "Tarefa excluída com sucesso.";
            $_SESSION['tarefa_prof_status_type'] = "status-success";
        } else {
            $_SESSION['tarefa_prof_status_message'] = "Não foi possível excluir a tarefa.";
            $_SESSION['tarefa_prof_status_type'] = "status-error";
        }
        mysqli_stmt_close($stmt);
    }
    
    header("Location: " . $redirect_url);
    exit();
}

// --- LÓGICA DE VISUALIZAÇÃO (GET) ---
$view_mode = isset($_GET['view_submissions_for']) && !empty($_GET['view_submissions_for']) ? 'submissions' : 'list';
$tarefas_enviadas = [];
$submissoes_da_tarefa = [];
$tarefa_info_selecionada = null;
$associacoes = [];

if ($view_mode === 'list') {
    $sql = "SELECT t.id, t.titulo, t.data_prazo, d.nome_disciplina, tu.nome_turma, 
                   (SELECT COUNT(*) FROM tarefas_submissoes WHERE tarefa_id = t.id) as total_entregas
            FROM tarefas t
            JOIN disciplinas d ON t.disciplina_id = d.id
            JOIN turmas tu ON t.turma_id = tu.id
            WHERE t.professor_id = ? ORDER BY t.data_prazo DESC";
    $stmt_list = mysqli_prepare($conn, $sql);
    if ($stmt_list) {
        mysqli_stmt_bind_param($stmt_list, "i", $professor_id);
        mysqli_stmt_execute($stmt_list);
        $result = mysqli_stmt_get_result($stmt_list);
        while ($row = mysqli_fetch_assoc($result)) { $tarefas_enviadas[] = $row; }
        mysqli_stmt_close($stmt_list);
    }
    
    $sql_assoc = "SELECT DISTINCT t.id as turma_id, t.nome_turma, d.id as disciplina_id, d.nome_disciplina
                  FROM turmas t
                  JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id
                  JOIN disciplinas d ON ptd.disciplina_id = d.id
                  WHERE ptd.professor_id = ? ORDER BY t.nome_turma, d.nome_disciplina";
    $stmt_assoc = mysqli_prepare($conn, $sql_assoc);
    if($stmt_assoc){
        mysqli_stmt_bind_param($stmt_assoc, "i", $professor_id);
        mysqli_stmt_execute($stmt_assoc);
        $result_assoc = mysqli_stmt_get_result($stmt_assoc);
        while($row = mysqli_fetch_assoc($result_assoc)){ $associacoes[] = $row; }
        mysqli_stmt_close($stmt_assoc);
    }
} elseif ($view_mode === 'submissions') {
    $tarefa_id_selecionada = intval($_GET['view_submissions_for']);
    
    $sql_tarefa_info = "SELECT t.id, t.titulo, t.descricao, t.data_prazo, d.nome_disciplina, tu.nome_turma, tu.id as turma_id
                        FROM tarefas t
                        JOIN disciplinas d ON t.disciplina_id = d.id
                        JOIN turmas tu ON t.turma_id = tu.id
                        WHERE t.id = ? AND t.professor_id = ?";
    $stmt_info = mysqli_prepare($conn, $sql_tarefa_info);
    mysqli_stmt_bind_param($stmt_info, "ii", $tarefa_id_selecionada, $professor_id);
    mysqli_stmt_execute($stmt_info);
    $tarefa_info_selecionada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));
    mysqli_stmt_close($stmt_info);
    
    if($tarefa_info_selecionada){
        $turma_id_da_tarefa = $tarefa_info_selecionada['turma_id'];
        $sql_submissoes = "
            SELECT a.id as aluno_id, a.nome, a.foto_url,
                   sub.id as submissao_id, sub.data_submissao, sub.arquivo_path_aluno, sub.nota, sub.feedback_professor
            FROM alunos a
            LEFT JOIN tarefas_submissoes sub ON a.id = sub.aluno_id AND sub.tarefa_id = ?
            WHERE a.turma_id = ?
            ORDER BY a.nome";
        $stmt_sub = mysqli_prepare($conn, $sql_submissoes);
        mysqli_stmt_bind_param($stmt_sub, "ii", $tarefa_id_selecionada, $turma_id_da_tarefa);
        mysqli_stmt_execute($stmt_sub);
        $result_sub = mysqli_stmt_get_result($stmt_sub);
        while ($row = mysqli_fetch_assoc($result_sub)) {
            $submissoes_da_tarefa[] = $row;
        }
        mysqli_stmt_close($stmt_sub);
    } else {
        $_SESSION['tarefa_prof_status_message'] = "Tarefa não encontrada ou acesso negado.";
        $_SESSION['tarefa_prof_status_type'] = "status-error";
        header("Location: professor_lancar_tarefa.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Tarefas - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/professor.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Variáveis CSS (Manter as cores e temas existentes) */
:root {
    --primary-color: #208A87; /* Cor principal - Verde Água */
    --primary-color-rgb: 32, 138, 135; /* RGB para uso em rgba() */
    --primary-color-dark: #186D6A; /* Verde Água mais escuro */
    --primary-color-light: #66b2b2; /* Verde Água mais claro para o chat enviado */

    --accent-color: #D69D2A; /* Cor de destaque - Dourado/Laranja */
    --accent-color-rgb: 214, 157, 42; /* RGB para uso em rgba() */
    --accent-color-dark: #C58624; /* Dourado/Laranja mais escuro */
    --accent-color-extra-light: #f1f0f0; /* Cinza claro para chat recebido */

    --background-color: #F8F9FA; /* Fundo geral claro */
    --background-color-offset: #E9ECEF; /* Fundo sutilmente diferente */
    --card-background: white;
    --text-color: #2C1B17; /* Texto principal escuro */
    --text-color-muted: #666; /* Texto secundário */
    --border-color: #ddd; /* Borda padrão */
    --border-color-soft: #eee; /* Borda mais suave */
    --hover-background-color: #f0f0f0; /* Fundo ao passar o mouse */

    --button-text-color: white;

    /* Cores de status/informação */
    --status-success: #28a745;
    --status-success-rgb: 40, 167, 69;
    --status-error: #dc3545;
    --status-error-rgb: 220, 53, 69;
    --status-info: #17a2b8;
    --status-info-rgb: 23, 162, 184;

    /* Cores específicas para ações e status de tarefas */
    --button-secondary-color: #6c757d;
    --button-secondary-color-dark: #5a6268;
    --button-info-color: #17a2b8;
    --button-info-color-dark: #138496;
    --button-danger-color: #dc3545;
    --button-danger-color-dark: #c82333;
    
    --submission-status-entregue-color: #28a745; /* Green */
    --submission-status-atraso-color: #ffc107; /* Orange */
    --submission-status-pendente-color: #6c757d; /* Grey */
}

/* Base e Tipografia */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: linear-gradient(135deg, var(--background-color) 0%, var(--background-color-offset) 100%);
    color: var(--text-color);
    line-height: 1.6;
}

/* Cabeçalho */
header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    color: var(--button-text-color);
    padding: 1.2rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(var(--primary-color-rgb), 0.3);
    position: relative;
    z-index: 100;
}

header::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    right: 0;
    height: 10px;
    background: linear-gradient(to bottom, rgba(var(--primary-color-rgb), 0.1), transparent);
}

header h1 {
    font-size: 1.6rem;
    font-weight: 600;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    letter-spacing: 0.5px;
}

/* Botões */
.button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    color: var(--button-text-color);
    text-decoration: none;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
    text-align: center;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(var(--primary-color-rgb), 0.3);
    position: relative;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.button:hover {
    background: linear-gradient(135deg, var(--primary-color-dark) 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(var(--primary-color-rgb), 0.4);
}

.button:active {
    transform: translateY(0);
}

.button-logout {
    background: linear-gradient(135deg, var(--accent-color) 0%, var(--accent-color-dark) 100%) !important;
    box-shadow: 0 4px 15px rgba(var(--accent-color-rgb), 0.3) !important;
}

.button-logout:hover {
    background: linear-gradient(135deg, var(--accent-color-dark) 0%, #B07420 100%) !important;
    box-shadow: 0 8px 25px rgba(var(--accent-color-rgb), 0.4) !important;
}

.button-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    box-shadow: 0 4px 15px rgba(var(--primary-color-rgb), 0.3);
}
.button-primary:hover {
    background: linear-gradient(135deg, var(--primary-color-dark) 0%, #145A57 100%);
    box-shadow: 0 8px 25px rgba(var(--primary-color-rgb), 0.4);
}

.button-secondary {
    background: linear-gradient(135deg, var(--button-secondary-color) 0%, var(--button-secondary-color-dark) 100%);
    box-shadow: 0 4px 15px rgba(var(--button-secondary-color-rgb, 108, 117, 125), 0.3);
}
.button-secondary:hover {
    background: linear-gradient(135deg, var(--button-secondary-color-dark) 0%, #495057 100%);
    box-shadow: 0 8px 25px rgba(var(--button-secondary-color-rgb, 108, 117, 125), 0.4);
}

.button-info {
    background: linear-gradient(135deg, var(--button-info-color) 0%, var(--button-info-color-dark) 100%);
    box-shadow: 0 4px 15px rgba(var(--status-info-rgb), 0.3);
}
.button-info:hover {
    background: linear-gradient(135deg, var(--button-info-color-dark) 0%, #117a8b 100%);
    box-shadow: 0 8px 25px rgba(var(--status-info-rgb), 0.4);
}

.button-danger {
    background: linear-gradient(135deg, var(--button-danger-color) 0%, var(--button-danger-color-dark) 100%);
    box-shadow: 0 4px 15px rgba(var(--status-error-rgb), 0.3);
}
.button-danger:hover {
    background: linear-gradient(135deg, var(--button-danger-color-dark) 0%, #bd2130 100%);
    box-shadow: 0 8px 25px rgba(var(--status-error-rgb), 0.4);
}

.button-xsmall {
    padding: 0.4rem 0.8rem;
    font-size: 0.75rem;
    border-radius: 18px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
}
.button-xsmall:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}


.menu-btn {
    background: rgba(255, 255, 255, 0.2) !important;
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
    padding: 0.6rem !important;
    border-radius: 12px !important;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    color: var(--button-text-color);
}

.menu-btn:hover {
    background: rgba(255, 255, 255, 0.3) !important;
    border-color: rgba(255, 255, 255, 0.5) !important;
    transform: scale(1.05);
}

/* Layout principal */
.container {
    display: flex;
    flex: 1;
    gap: 0;
}

/* Menu lateral */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    padding-top: 1.5rem;
    height: 100%;
    min-height: calc(100vh - 80px);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 4px 0 20px rgba(var(--primary-color-rgb), 0.15);
    position: relative;
    overflow: hidden;
}

.sidebar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 100%;
    background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

.sidebar ul {
    list-style: none;
    padding: 0 1rem;
}

.sidebar ul li {
    margin-bottom: 0.5rem;
}

.sidebar ul li a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.2rem;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 12px;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.sidebar ul li a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.5s;
}

.sidebar ul li a:hover {
    background: rgba(255, 255, 255, 0.15);
    color: var(--button-text-color);
    transform: translateX(8px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.sidebar ul li a:hover::before {
    left: 100%;
}

.sidebar ul li a i {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.sidebar ul li a.active {
    background: rgba(255, 255, 255, 0.2);
    color: var(--button-text-color);
    font-weight: 600;
    border-left: 4px solid var(--accent-color);
}

/* Conteúdo principal */
.main-content {
    flex: 1;
    padding: 2.5rem;
    background: var(--card-background);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow-x: hidden;
}

.main-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 200px;
    background: linear-gradient(135deg, rgba(var(--primary-color-rgb), 0.05) 0%, rgba(var(--accent-color-rgb), 0.05) 100%);
    border-radius: 0 0 50px 0;
    z-index: 0;
}

.main-content > * {
    position: relative;
    z-index: 1;
}

.main-content h2 {
    margin-bottom: 1.5rem;
    color: var(--text-color);
    font-weight: 600;
    font-size: 1.8rem;
}

.main-content h2.section-title {
    position: relative;
    padding-bottom: 1rem;
    margin-bottom: 2rem;
}

.main-content h2.section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    border-radius: 2px;
}

.page-title {
    text-align: center;
    font-size: 1.8rem;
    margin-bottom: 2rem; /* Consistent with other pages */
    padding-bottom: 0.5rem;
    display: inline-block;
    position: relative;
    color: var(--text-color);
    font-weight: 600;
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    border-radius: 2px;
}

/* Cards (Dashboard Sections) */
.dashboard-section.card {
    margin-top: 1.5rem;
    padding: 2rem;
    background: var(--card-background);
    border: none;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.dashboard-section.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.dashboard-section.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
}

.dashboard-section h3 {
    margin-bottom: 1rem;
    color: var(--primary-color);
    font-weight: 600;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Specific styles for professor_lancar_tarefa.php */
.tarefas-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

@media (min-width: 1200px) {
    .tarefas-container {
        grid-template-columns: 400px 1fr; /* Two columns on large screens */
    }
}

.form-section, .list-section {
    margin-bottom: 0; /* Managed by .tarefas-container gap */
    padding: 2rem; /* Consistent with .card */
    border-radius: 16px; /* Consistent with .card */
    background: var(--card-background);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.form-section::before, .list-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.form-section:hover, .list-section:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
}

.form-section label {
    display: block;
    margin-top: 1rem;
    margin-bottom: 0.5rem; /* Adjusted for consistency */
    font-weight: bold;
    color: var(--text-color);
}

.input-field {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px; /* Consistent with other forms */
    box-sizing: border-box;
    font-size: 1rem;
    color: var(--text-color);
    background-color: var(--background-color);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.input-field:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.2);
}

.form-section textarea {
    min-height: 120px;
    resize: vertical;
    margin-bottom: 0.8rem; /* Keep consistent margin */
}

.form-section button {
    display: block;
    width: 100%;
    padding: 0.7rem;
    margin-top: 1.5rem; /* Keep consistent margin */
}

/* List Section Table (main tasks list) */
.list-section table.table { /* Added .table for specificity */
    width: 100%;
    border-collapse: separate; /* For border-radius on cells */
    border-spacing: 0; /* For no space between cells */
    margin-top: 1.5rem;
    background-color: var(--card-background);
    border-radius: 8px;
    overflow: hidden; /* Ensures rounded corners are visible */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.list-section table.table th, .list-section table.table td {
    padding: 0.9rem 1.2rem; /* Adjusted padding */
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color-soft);
    color: var(--text-color);
}

.list-section table.table th {
    background-color: var(--primary-color);
    color: var(--button-text-color);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.9em;
    letter-spacing: 0.05em;
    position: sticky;
    top: 0;
    z-index: 2;
}

.list-section table.table tbody tr:last-child td {
    border-bottom: none;
}

.list-section table.table tbody tr:hover {
    background-color: var(--hover-background-color);
}

.list-section .actions-cell {
    white-space: nowrap; /* Prevent buttons from wrapping */
}
.list-section .actions-cell form, .list-section .actions-cell a {
    margin: 0 3px; /* Slightly more space */
    display: inline-block;
    vertical-align: middle; /* Align buttons with content */
}

/* Submission List Table (for viewing submissions) */
.submission-list-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 1.5rem;
    background-color: var(--card-background);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    font-size: 0.95em; /* Slightly larger font */
}

.submission-list-table th, .submission-list-table td {
    padding: 0.8rem 1rem; /* Adjusted padding */
    text-align: left;
    vertical-align: top; /* Align content to top */
    border-bottom: 1px solid var(--border-color-soft);
    color: var(--text-color);
}

.submission-list-table th {
    background-color: var(--primary-color);
    color: var(--button-text-color);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.9em;
    letter-spacing: 0.05em;
}

.submission-list-table tbody tr:last-child td {
    border-bottom: none;
}

.submission-list-table tbody tr:hover {
    background-color: var(--hover-background-color);
}

.submission-list-table img {
    width: 38px; /* Slightly larger image */
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    vertical-align: middle;
    margin-right: 12px; /* More space */
    border: 2px solid var(--primary-color-light); /* Added border */
}

.submission-list-table input[type="number"] {
    width: 80px; /* Wider input */
    padding: 6px; /* More padding */
    text-align: center;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 1em;
    background-color: var(--background-color-offset);
}
.submission-list-table input[type="number"]:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb), 0.1);
}

.submission-list-table textarea {
    width: 100%;
    min-height: 60px; /* Taller textarea */
    padding: 8px; /* More padding */
    box-sizing: border-box;
    font-size: 0.95em; /* Slightly larger font */
    border: 1px solid var(--border-color);
    border-radius: 6px;
    resize: vertical;
    background-color: var(--background-color-offset);
}
.submission-list-table textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb), 0.1);
}

.submission-status-entregue {
    color: var(--submission-status-entregue-color);
    font-weight: bold;
}
.submission-status-atraso {
    color: var(--submission-status-atraso-color);
    font-weight: bold;
}
.submission-status-pendente {
    color: var(--submission-status-pendente-color);
    font-weight: bold;
    opacity: 0.8;
}

.status-message {
    padding: 1rem;
    margin-bottom: 1.5rem; /* Consistent margin */
    border-radius: 8px;
    text-align: center;
    font-weight: 500;
    transition: all 0.3s ease;
}

.status-message.status-success {
    background-color: rgba(var(--status-success-rgb), 0.1);
    color: var(--status-success);
    border: 1px solid var(--status-success);
}

.status-message.status-error {
    background-color: rgba(var(--status-error-rgb), 0.1);
    color: var(--status-error);
    border: 1px solid var(--status-error);
}

.info-message {
    background-color: rgba(var(--status-info-rgb), 0.1);
    color: var(--status-info);
    border: 1px solid var(--status-info);
}

.no-data-message {
    padding: 2rem;
    text-align: center;
    border-radius: 12px;
    background: rgba(var(--primary-color-rgb), 0.03);
    border: 2px dashed rgba(var(--primary-color-rgb), 0.2);
    color: var(--text-color-muted);
    font-size: 1.1rem;
}


/* CSS do Chat Widget */
.chat-widget-acad {
    position: fixed;
    bottom: 0;
    right: 20px;
    width: 340px; /* Ligeiramente mais largo */
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
    box-shadow: 0 -5px 20px rgba(0,0,0,0.2);
    z-index: 1000;
    overflow: hidden;
    transition: height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.chat-widget-acad.chat-collapsed {
    height: 55px; /* Um pouco mais alto quando colapsado */
}

.chat-widget-acad.chat-expanded {
    height: 500px; /* Um pouco mais alto quando expandido */
}

.chat-header-acad {
    padding: 12px 18px; /* Mais padding */
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: var(--primary-color);
    color: var(--button-text-color);
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
    font-size: 1.1rem;
}

.chat-header-acad span {
    font-weight: 600;
}

.chat-toggle-btn-acad {
    background: none;
    border: none;
    color: var(--button-text-color);
    font-size: 1.3rem; /* Ícone um pouco maior */
    cursor: pointer;
    transition: transform 0.3s ease-in-out;
}

.chat-expanded .chat-toggle-btn-acad {
    transform: rotate(180deg);
}

.chat-body-acad {
    height: calc(100% - 55px); /* Ajuste com a nova altura do header */
    display: flex;
    flex-direction: column;
    background-color: var(--background-color);
    border-left: 1px solid var(--border-color);
    border-right: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
}

#chatUserListScreenAcad, #chatConversationScreenAcad {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

.chat-search-container-acad {
    padding: 10px;
    border-bottom: 1px solid var(--border-color-soft);
}

#chatSearchUserAcad {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: 25px;
    box-sizing: border-box;
    font-size: 0.95em;
    background-color: var(--background-color-offset);
    color: var(--text-color);
}

#chatUserListUlAcad {
    list-style: none;
    padding: 0;
    margin: 0;
    overflow-y: auto;
    flex-grow: 1;
}

#chatUserListUlAcad li {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid var(--border-color-soft);
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--text-color);
    transition: background-color 0.2s ease;
}

#chatUserListUlAcad li:hover {
    background-color: var(--hover-background-color);
}

#chatUserListUlAcad li img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary-color-light);
}

#chatUserListUlAcad li .chat-user-name-acad {
    flex-grow: 1;
    font-size: 1em;
    font-weight: 500;
}

.chat-user-professor-acad .chat-user-name-acad {
    font-weight: bold;
    color: var(--primary-color-dark);
}

.chat-user-coordenador-acad .chat-user-name-acad {
    font-weight: bold;
    font-style: italic;
    color: var(--status-info);
}

.teacher-icon-acad {
    margin-left: 5px;
    color: var(--primary-color);
    font-size: 0.85em;
}

.student-icon-acad {
    margin-left: 5px;
    color: var(--accent-color);
    font-size: 0.85em;
}

.coord-icon-acad {
    margin-left: 5px;
    color: var(--status-info);
    font-size: 0.85em;
}

.chat-conversation-header-acad {
    padding: 10px 15px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid var(--border-color-soft);
    background-color: var(--background-color-offset);
    gap: 10px;
}

#chatBackToListBtnAcad {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 5px;
    color: var(--primary-color);
    transition: color 0.2s ease;
}

#chatBackToListBtnAcad:hover {
    color: var(--primary-color-dark);
}

.chat-conversation-photo-acad {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary-color-light);
}

#chatConversationUserNameAcad {
    font-weight: bold;
    font-size: 1.05em;
    color: var(--text-color);
}

#chatMessagesContainerAcad {
    flex-grow: 1;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scroll-behavior: smooth;
}

.message-acad {
    padding: 10px 15px;
    border-radius: 20px; /* Mais arredondado */
    max-width: 80%; /* Aumentar um pouco */
    word-wrap: break-word;
    font-size: 0.95em;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.message-acad.sent-acad {
    background-color: var(--primary-color-light);
    color: var(--text-color);
    align-self: flex-end;
    border-bottom-right-radius: 8px; /* Ajuste para cauda */
}

.message-acad.received-acad {
    background-color: var(--accent-color-extra-light);
    color: var(--text-color);
    align-self: flex-start;
    border-bottom-left-radius: 8px; /* Ajuste para cauda */
}

.message-acad.error-acad {
    background-color: #f8d7da; /* Vermelho claro */
    color: #721c24; /* Vermelho escuro */
    align-self: flex-end;
    border: 1px solid #f5c6cb;
}

.chat-message-input-area-acad {
    display: flex;
    padding: 10px 15px;
    border-top: 1px solid var(--border-color-soft);
    background-color: var(--background-color-offset);
    gap: 10px;
    align-items: flex-end;
}

#chatMessageInputAcad {
    flex-grow: 1;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: 25px;
    resize: none;
    font-size: 0.95em;
    min-height: 40px; /* Altura mínima ajustada */
    max-height: 100px; /* Altura máxima ajustada */
    overflow-y: auto;
    background-color: var(--card-background);
    color: var(--text-color);
}

#chatMessageInputAcad:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb), 0.1);
}

#chatSendMessageBtnAcad {
    background: var(--primary-color);
    color: var(--button-text-color);
    border: none;
    border-radius: 50%;
    width: 45px; /* Tamanho do botão */
    height: 45px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem; /* Ícone um pouco maior */
    transition: background 0.2s ease, transform 0.2s ease;
}

#chatSendMessageBtnAcad:hover {
    background: var(--primary-color-dark);
    transform: scale(1.05);
}

/* Sidebar escondida */
.sidebar.hidden {
    transform: translateX(-100%);
    width: 0;
    padding: 0;
    opacity: 0;
}

.container.full-width .main-content {
    flex: 1 1 100%;
    width: 100%;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        min-height: auto;
        position: fixed;
        top: 80px;
        left: 0;
        z-index: 1000;
        transform: translateX(-100%);
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .main-content {
        padding: 1.5rem;
    }

    header {
        padding: 1rem;
    }

    header h1 {
        font-size: 1.3rem;
    }

    .tarefas-container {
        grid-template-columns: 1fr; /* Stack columns on small screens */
    }

    .form-section, .list-section {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .form-section label {
        font-size: 0.95rem;
        margin-top: 0.8rem;
    }

    .input-field {
        padding: 0.6rem;
        font-size: 0.9rem;
    }

    .form-section textarea {
        min-height: 100px;
    }

    .form-section button {
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
        width: 100%; /* Make buttons full width on small screens */
    }

    .list-section table.table, .submission-list-table {
        font-size: 0.85em; /* Smaller font for tables on small screens */
    }
    .list-section table.table th, .list-section table.table td,
    .submission-list-table th, .submission-list-table td {
        padding: 0.6rem 0.8rem;
    }

    .submission-list-table img {
        width: 30px;
        height: 30px;
        margin-right: 8px;
    }
    .submission-list-table input[type="number"] {
        width: 60px;
    }
    .submission-list-table textarea {
        min-height: 50px;
    }
    .list-section .actions-cell {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .list-section .actions-cell form, .list-section .actions-cell a {
        width: 100%;
        margin: 0; /* Remove horizontal margin */
        text-align: center;
        justify-content: center; /* Center icon/text in buttons */
    }

    .no-data-message {
        padding: 1.5rem;
        font-size: 1rem;
    }

    .chat-widget-acad {
        width: 100%;
        right: 0;
        border-radius: 0;
    }
    .chat-widget-acad.chat-expanded {
        height: 80vh; /* Occupy more screen height on mobile */
    }
    .chat-header-acad {
        border-radius: 0;
    }
}

/* Animações suaves */
.sidebar,
.main-content {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Scroll suave */
html {
    scroll-behavior: smooth;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-color-dark));
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, var(--primary-color-dark), #145A57);
}
/*acaba aqui*/
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .tarefas-container { display: grid; grid-template-columns: 1fr; gap: 2rem; }
        @media (min-width: 1200px) { .tarefas-container { grid-template-columns: 400px 1fr; } }
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.3rem; font-weight: bold; }
        .form-section input, .form-section textarea, .form-section select { width: 100%; padding: 0.6rem; margin-bottom: 0.8rem; box-sizing: border-box; border-radius: 4px; }
        .form-section textarea { min-height: 120px; }
        .form-section button { display: block; width: 100%; padding: 0.7rem; }
        .list-section table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
        .list-section th, .list-section td { padding: 0.75rem; text-align: left; vertical-align: middle; }
        .list-section .actions-cell form, .list-section .actions-cell a { margin: 0 2px; display: inline-block; }
        .submission-list-table img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 10px; }
        .submission-list-table input[type="number"] { width: 70px; padding: 5px; text-align: center; }
        .submission-list-table textarea { width: 100%; min-height: 40px; padding: 5px; box-sizing: border-box; font-size: 0.9em;}
        .submission-status-entregue { color: green; }
        .submission-status-atraso { color: orange; }
        .submission-status-pendente { opacity: 0.6; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        .no-data-message { padding: 1rem; text-align: center; }

        /* CSS do Chat */
        .chat-widget-acad { position: fixed; bottom: 0; right: 20px; width: 320px; border-top-left-radius: 10px; border-top-right-radius: 10px; box-shadow: 0 -2px 10px rgba(0,0,0,0.15); z-index: 1000; overflow: hidden; transition: height 0.3s ease-in-out; }
        .chat-widget-acad.chat-collapsed { height: 45px; }
        .chat-widget-acad.chat-expanded { height: 450px; }
        .chat-header-acad { padding: 10px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background-color: var(--primary-color, #007bff); color: var(--button-text-color, white); border-top-left-radius: 10px; border-top-right-radius: 10px; }
        .chat-header-acad span { font-weight: bold; }
        .chat-toggle-btn-acad { background: none; border: none; color: var(--button-text-color, white); font-size: 1.2rem; cursor: pointer; transition: transform 0.3s ease-in-out; }
        .chat-expanded .chat-toggle-btn-acad { transform: rotate(180deg); }
        .chat-body-acad { height: calc(100% - 45px); display: flex; flex-direction: column; background-color: var(--background-color, white); border-left: 1px solid var(--border-color, #ddd); border-right: 1px solid var(--border-color, #ddd); border-bottom: 1px solid var(--border-color, #ddd); }
        #chatUserListScreenAcad, #chatConversationScreenAcad { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        .chat-search-container-acad { padding: 8px; }
        #chatSearchUserAcad { width: 100%; padding: 8px 10px; border: 1px solid var(--border-color-soft, #eee); border-radius: 20px; box-sizing: border-box; font-size: 0.9em; }
        #chatUserListUlAcad { list-style: none; padding: 0; margin: 0; overflow-y: auto; flex-grow: 1; }
        #chatUserListUlAcad li { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid var(--border-color-soft, #eee); display: flex; align-items: center; gap: 10px; color: var(--text-color, #333); }
        #chatUserListUlAcad li:hover { background-color: var(--hover-background-color, #f0f0f0); }
        #chatUserListUlAcad li img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
        #chatUserListUlAcad li .chat-user-name-acad { flex-grow: 1; font-size: 0.9em; }
        .chat-user-professor-acad .chat-user-name-acad { font-weight: bold; }
        .chat-user-coordenador-acad .chat-user-name-acad { font-weight: bold; font-style: italic; }
        .teacher-icon-acad { margin-left: 5px; color: var(--primary-color, #007bff); font-size: 0.9em; }
        .student-icon-acad { margin-left: 5px; color: var(--accent-color, #6c757d); font-size: 0.9em; } 
        .coord-icon-acad { margin-left: 5px; color: var(--info-color, #17a2b8); font-size: 0.9em; } 
        .chat-conversation-header-acad { padding: 8px 10px; display: flex; align-items: center; border-bottom: 1px solid var(--border-color-soft, #eee); background-color: var(--background-color-offset, #f9f9f9); gap: 10px; }
        #chatBackToListBtnAcad { background: none; border: none; font-size: 1.1rem; cursor: pointer; padding: 5px; color: var(--primary-color, #007bff); }
        .chat-conversation-photo-acad { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
        #chatConversationUserNameAcad { font-weight: bold; font-size: 0.95em; color: var(--text-color, #333); }
        #chatMessagesContainerAcad { flex-grow: 1; padding: 10px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
        .message-acad { padding: 8px 12px; border-radius: 15px; max-width: 75%; word-wrap: break-word; font-size: 0.9em; }
        .message-acad.sent-acad { background-color: var(--primary-color-light, #dcf8c6); color: var(--text-color, #333); align-self: flex-end; border-bottom-right-radius: 5px; }
        .message-acad.received-acad { background-color: var(--accent-color-extra-light, #f1f0f0); color: var(--text-color, #333); align-self: flex-start; border-bottom-left-radius: 5px; }
        .message-acad.error-acad { background-color: #f8d7da; color: #721c24; align-self: flex-end; border: 1px solid #f5c6cb;}
        .chat-message-input-area-acad { display: flex; padding: 8px 10px; border-top: 1px solid var(--border-color-soft, #eee); background-color: var(--background-color-offset, #f9f9f9); gap: 8px; }
        #chatMessageInputAcad { flex-grow: 1; padding: 8px 12px; border: 1px solid var(--border-color, #ddd); border-radius: 20px; resize: none; font-size: 0.9em; min-height: 20px; max-height: 80px; overflow-y: auto; }
        #chatSendMessageBtnAcad { background: var(--primary-color, #007bff); color: var(--button-text-color, white); border: none; border-radius: 50%; width: 38px; height: 38px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        #chatSendMessageBtnAcad:hover { background: var(--primary-color-dark, #0056b3); }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Gerenciar Tarefas</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Gerenciamento de Tarefas</h2>

            <?php if(isset($_SESSION['tarefa_prof_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['tarefa_prof_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['tarefa_prof_status_message']); ?>
                </div>
                <?php unset($_SESSION['tarefa_prof_status_message']); unset($_SESSION['tarefa_prof_status_type']); ?>
            <?php endif; ?>

            <?php if ($view_mode === 'submissions'): ?>
                <section class="dashboard-section card">
                    <a href="professor_lancar_tarefa.php" class="button button-secondary button-small" style="margin-bottom: 1rem; display:inline-block;"><i class="fas fa-arrow-left"></i> Voltar para Lista de Tarefas</a>
                    <h3>Entregas para: "<?php echo htmlspecialchars($tarefa_info_selecionada['titulo']); ?>"</h3>
                    <p><strong>Turma:</strong> <?php echo htmlspecialchars($tarefa_info_selecionada['nome_turma']); ?> | <strong>Disciplina:</strong> <?php echo htmlspecialchars($tarefa_info_selecionada['nome_disciplina']); ?> | <strong>Prazo:</strong> <?php echo date("d/m/Y H:i", strtotime($tarefa_info_selecionada['data_prazo'])); ?></p>

                    <?php if (!empty($submissoes_da_tarefa)): ?>
                        <form action="professor_lancar_tarefa.php" method="POST">
                            <input type="hidden" name="action" value="avaliar_submissoes">
                            <input type="hidden" name="tarefa_id_avaliada" value="<?php echo $tarefa_id_selecionada; ?>">
                            <div style="overflow-x:auto;">
                                <table class="table submission-list-table">
                                    <thead>
                                        <tr>
                                            <th>Aluno</th>
                                            <th>Status da Entrega</th>
                                            <th>Arquivo Enviado</th>
                                            <th>Nota (0-10)</th>
                                            <th>Feedback</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissoes_da_tarefa as $sub): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo htmlspecialchars(!empty($sub['foto_url']) ? $sub['foto_url'] : 'img/alunos/default_avatar.png'); ?>" alt="Foto" onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                                                    <?php echo htmlspecialchars($sub['aluno_nome']); ?>
                                                </td>
                                                <td>
                                                    <?php if($sub['submissao_id']): 
                                                        $sub_time = new DateTime($sub['data_submissao']);
                                                        $prazo_time = new DateTime($tarefa_info_selecionada['data_prazo']);
                                                        if ($sub_time > $prazo_time) {
                                                            echo '<span class="submission-status-atraso">Entregue com atraso</span><br><small>' . $sub_time->format('d/m/Y H:i') . '</small>';
                                                        } else {
                                                            echo '<span class="submission-status-entregue">Entregue</span><br><small>' . $sub_time->format('d/m/Y H:i') . '</small>';
                                                        }
                                                    ?>
                                                    <?php else: ?>
                                                        <span class="submission-status-pendente">Pendente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($sub['submissao_id']): ?>
                                                        <a href="<?php echo htmlspecialchars($sub['arquivo_path_aluno']); ?>" class="button button-info button-xsmall" target="_blank"><i class="fas fa-download"></i> Ver Arquivo</a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($sub['submissao_id']): ?>
                                                        <input type="number" name="submissoes[<?php echo $sub['submissao_id']; ?>][nota]" value="<?php echo htmlspecialchars($sub['nota'] ?? ''); ?>" step="0.1" min="0" max="10" class="input-field">
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                     <?php if($sub['submissao_id']): ?>
                                                        <textarea name="submissoes[<?php echo $sub['submissao_id']; ?>][feedback]" class="input-field"><?php echo htmlspecialchars($sub['feedback_professor'] ?? ''); ?></textarea>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="button button-primary" style="margin-top: 1.5rem;"><i class="fas fa-save"></i> Salvar Todas as Avaliações</button>
                        </form>
                    <?php else: ?>
                        <p class="no-data-message info-message">Nenhum aluno nesta turma para exibir.</p>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <div class="tarefas-container">
                    <section class="form-section dashboard-section card">
                        <h3><i class="fas fa-plus-square"></i> Criar Nova Tarefa</h3>
                        <form action="professor_lancar_tarefa.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_tarefa">
                            <label for="titulo">Título da Tarefa:</label>
                            <input type="text" id="titulo" name="titulo" class="input-field" required>

                            <label for="turma_disciplina_select">Para Turma / Disciplina:</label>
                            <select id="turma_disciplina_select" name="turma_disciplina_id" class="input-field" required>
                                <option value="">Selecione...</option>
                                <?php if(!empty($associacoes)): foreach($associacoes as $assoc): ?>
                                    <option value="<?php echo $assoc['turma_id'].'-'.$assoc['disciplina_id']; ?>">
                                        <?php echo htmlspecialchars($assoc['nome_turma']) . ' / ' . htmlspecialchars($assoc['nome_disciplina']); ?>
                                    </option>
                                <?php endforeach; else: ?>
                                <option value="" disabled>Você não tem turmas/disciplinas associadas.</option>
                                <?php endif; ?>
                            </select>

                            <label for="data_prazo">Data e Hora do Prazo Final:</label>
                            <input type="datetime-local" id="data_prazo" name="data_prazo" class="input-field" required>
                            
                            <label for="descricao">Descrição / Instruções:</label>
                            <textarea id="descricao" name="descricao" class="input-field"></textarea>

                            <label for="arquivo_professor">Anexar Arquivo de Apoio (Opcional):</label>
                            <input type="file" id="arquivo_professor" name="arquivo_professor" class="input-field">

                            <button type="submit" class="button button-primary"><i class="fas fa-paper-plane"></i> Enviar Tarefa</button>
                        </form>
                    </section>

                    <section class="list-section dashboard-section card">
                        <h3><i class="fas fa-history"></i> Tarefas Enviadas</h3>
                        <div style="overflow-x:auto;">
                             <table class="table list-section">
                                <thead><tr><th>Título</th><th>Turma</th><th>Disciplina</th><th>Prazo</th><th>Entregas</th><th>Ações</th></tr></thead>
                                <tbody>
                                    <?php if(!empty($tarefas_enviadas)): foreach($tarefas_enviadas as $tarefa_enviada): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tarefa_enviada['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($tarefa_enviada['nome_turma']); ?></td>
                                        <td><?php echo htmlspecialchars($tarefa_enviada['nome_disciplina']); ?></td>
                                        <td><?php echo date("d/m/y H:i", strtotime($tarefa_enviada['data_prazo'])); ?></td>
                                        <td><?php echo $tarefa_enviada['total_entregas']; ?></td>
                                        <td class="actions-cell">
                                            <a href="?view_submissions_for=<?php echo $tarefa_enviada['id']; ?>" class="button button-info button-xsmall" title="Ver Entregas e Avaliar"><i class="fas fa-list-check"></i></a>
                                            <form action="professor_lancar_tarefa.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta tarefa? Todas as submissões dos alunos também serão perdidas.');">
                                                <input type="hidden" name="action" value="delete_tarefa">
                                                <input type="hidden" name="tarefa_id_delete" value="<?php echo $tarefa_enviada['id']; ?>">
                                                <button type="submit" class="button button-danger button-xsmall" title="Excluir Tarefa"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data-message">Nenhuma tarefa criada por você ainda.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <div id="academicChatWidget" class="chat-widget-acad chat-collapsed">
        <div id="chatWidgetHeaderAcad" class="chat-header-acad">
            <span><i class="fas fa-comments"></i> Chat Acadêmico</span>
            <button id="chatToggleBtnAcad" class="chat-toggle-btn-acad" aria-label="Abrir ou fechar chat"><i class="fas fa-chevron-up"></i></button>
        </div>
        <div id="chatWidgetBodyAcad" class="chat-body-acad" style="display: none;">
            <div id="chatUserListScreenAcad">
                <div class="chat-search-container-acad">
                    <input type="text" id="chatSearchUserAcad" placeholder="Pesquisar Contatos...">
                </div>
                <ul id="chatUserListUlAcad"></ul>
            </div>
            <div id="chatConversationScreenAcad" style="display: none;">
                <div class="chat-conversation-header-acad">
                    <button id="chatBackToListBtnAcad" aria-label="Voltar para lista de contatos"><i class="fas fa-arrow-left"></i></button>
                    <img id="chatConversationUserPhotoAcad" src="img/alunos/default_avatar.png" alt="Foto do Contato" class="chat-conversation-photo-acad">
                    <span id="chatConversationUserNameAcad">Nome do Contato</span>
                </div>
                <div id="chatMessagesContainerAcad"></div>
                <div class="chat-message-input-area-acad">
                    <textarea id="chatMessageInputAcad" placeholder="Digite sua mensagem..." rows="1"></textarea>
                    <button id="chatSendMessageBtnAcad" aria-label="Enviar mensagem"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Script do menu lateral padronizado
        const menuToggleButtonGlobal = document.getElementById('menu-toggle');
        const sidebarElementGlobal = document.getElementById('sidebar');    
        const pageContainerGlobal = document.getElementById('pageContainer'); 

        if (menuToggleButtonGlobal && sidebarElementGlobal && pageContainerGlobal) {
            menuToggleButtonGlobal.addEventListener('click', function () {
                sidebarElementGlobal.classList.toggle('hidden'); 
                pageContainerGlobal.classList.toggle('full-width'); 
            });
        }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const currentUserId = <?php echo json_encode($professor_id); ?>;
        const currentUserSessionRole = <?php echo json_encode($_SESSION['role']); ?>; 
        let currentUserChatRole = '';
        if (currentUserSessionRole === 'docente') {
            currentUserChatRole = 'professor'; 
        } else {
            currentUserChatRole = currentUserSessionRole; 
        }
        
        const defaultUserPhoto = 'img/alunos/default_avatar.png';
        const defaultProfessorPhoto = 'img/professores/default_avatar_prof.png'; 
        const defaultCoordenadorPhoto = 'img/coordenadores/default_avatar.png'; 

        const chatWidget = document.getElementById('academicChatWidget');
        const chatHeader = document.getElementById('chatWidgetHeaderAcad');
        const chatToggleBtn = document.getElementById('chatToggleBtnAcad');
        const chatBody = document.getElementById('chatWidgetBodyAcad');
        const userListScreen = document.getElementById('chatUserListScreenAcad');
        const searchUserInput = document.getElementById('chatSearchUserAcad');
        const userListUl = document.getElementById('chatUserListUlAcad');
        const conversationScreen = document.getElementById('chatConversationScreenAcad');
        const backToListBtn = document.getElementById('chatBackToListBtnAcad');
        const conversationUserPhoto = document.getElementById('chatConversationUserPhotoAcad');
        const conversationUserName = document.getElementById('chatConversationUserNameAcad');
        const messagesContainer = document.getElementById('chatMessagesContainerAcad');
        const messageInput = document.getElementById('chatMessageInputAcad');
        const sendMessageBtn = document.getElementById('chatSendMessageBtnAcad');

        let allContacts = []; 
        let currentConversationWith = null; 
        let isChatInitiallyLoaded = false;

        function toggleChat() {
            if (!chatWidget) return; 
            const isCollapsed = chatWidget.classList.contains('chat-collapsed');
            if (isCollapsed) {
                chatWidget.classList.remove('chat-collapsed');
                chatWidget.classList.add('chat-expanded');
                if(chatBody) chatBody.style.display = 'flex';
                if(chatToggleBtn) chatToggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i>';
                
                if (!isChatInitiallyLoaded && currentUserChatRole) { 
                    loadInitialContacts();
                    isChatInitiallyLoaded = true;
                }
                if (!currentConversationWith) { 
                    showUserListScreen();
                } else { 
                    showConversationScreen(currentConversationWith, false); 
                }
            } else {
                chatWidget.classList.add('chat-collapsed');
                chatWidget.classList.remove('chat-expanded');
                if(chatBody) chatBody.style.display = 'none';
                if(chatToggleBtn) chatToggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
            }
        }

        function showUserListScreen() {
            if(userListScreen) userListScreen.style.display = 'flex';
            if(conversationScreen) conversationScreen.style.display = 'none';
        }

        function showConversationScreen(contact, shouldFetchMessages = true) {
            currentConversationWith = contact; 
            if(userListScreen) userListScreen.style.display = 'none';
            if(conversationScreen) conversationScreen.style.display = 'flex';
            if(conversationUserName) conversationUserName.textContent = contact.nome;
            
            let photoToUse = defaultUserPhoto;
            if (contact.role === 'professor') photoToUse = defaultProfessorPhoto;
            else if (contact.role === 'coordenador') photoToUse = defaultCoordenadorPhoto;
            if (contact.foto_url) photoToUse = contact.foto_url;
            if(conversationUserPhoto) conversationUserPhoto.src = photoToUse;
            
            if (shouldFetchMessages) {
                if(messagesContainer) messagesContainer.innerHTML = ''; 
                fetchAndDisplayMessages(contact.id, contact.role);
            }
            if(messageInput) messageInput.focus();
        }
        
        async function loadInitialContacts() { 
            let actionApi = '';
            if (currentUserChatRole === 'aluno') { 
                actionApi = 'get_turma_users';
            } else if (currentUserChatRole === 'professor') {
                actionApi = 'get_professor_contacts';
            } else if (currentUserChatRole === 'coordenador') {
                actionApi = 'get_coordenador_contacts'; 
            } else {
                if(userListUl) userListUl.innerHTML = '<li>Lista de contatos não disponível para este perfil.</li>';
                return;
            }

            if(userListUl) userListUl.innerHTML = '<li><i class="fas fa-spinner fa-spin"></i> Carregando contatos...</li>';
            
            try {
                const response = await fetch(`chat_api.php?action=${actionApi}`); 
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error ${response.status}: ${errorText}`);
                }
                const users = await response.json();

                if (users.error) {
                    console.error('API Erro ('+actionApi+'):', users.error);
                    if(userListUl) userListUl.innerHTML = `<li>Erro: ${users.error}</li>`;
                    return;
                }
                allContacts = users; 
                renderUserList(allContacts);

            } catch (error) {
                console.error('Falha ao buscar contatos ('+actionApi+'):', error);
                if(userListUl) userListUl.innerHTML = '<li>Falha ao carregar contatos.</li>';
            }
        }

        function renderUserList(usersToRender) {
            if (!userListUl) return;
            userListUl.innerHTML = '';
            if (!usersToRender || usersToRender.length === 0) {
                userListUl.innerHTML = '<li>Nenhum contato encontrado.</li>';
                return;
            }
            usersToRender.forEach(user => {
                const li = document.createElement('li');
                li.dataset.userid = user.id;
                li.dataset.userrole = user.role;
                
                let photoToUseInList = defaultUserPhoto;
                if (user.role === 'professor') photoToUseInList = defaultProfessorPhoto;
                else if (user.role === 'coordenador') photoToUseInList = defaultCoordenadorPhoto;
                if (user.foto_url) photoToUseInList = user.foto_url;

                const img = document.createElement('img');
                img.src = photoToUseInList;
                img.alt = `Foto de ${user.nome}`;
                li.appendChild(img);

                const nameSpan = document.createElement('span');
                nameSpan.classList.add('chat-user-name-acad');
                nameSpan.textContent = user.nome;
                li.appendChild(nameSpan);

                if (user.role === 'professor') {
                    li.classList.add('chat-user-professor-acad');
                    const teacherIcon = document.createElement('i');
                    teacherIcon.className = 'fas fa-chalkboard-teacher teacher-icon-acad';
                    nameSpan.appendChild(teacherIcon);
                } else if (user.role === 'aluno') {
                    li.classList.add('chat-user-aluno-acad');
                } else if (user.role === 'coordenador') {
                    li.classList.add('chat-user-coordenador-acad'); 
                    const coordIcon = document.createElement('i'); 
                    coordIcon.className = 'fas fa-user-tie coord-icon-acad'; 
                    nameSpan.appendChild(coordIcon);
                }
                
                li.addEventListener('click', () => {
                    showConversationScreen(user, true); 
                });
                userListUl.appendChild(li);
            });
        }

        async function fetchAndDisplayMessages(contactId, contactRole) {
            if(!messagesContainer) return;
            messagesContainer.innerHTML = '<p style="text-align:center;font-size:0.8em;"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';
            console.log("fetchAndDisplayMessages - Enviando para API:", { action: 'get_messages', contact_id: contactId, contact_role: contactRole });
            try {
                const response = await fetch(`chat_api.php?action=get_messages&contact_id=${contactId}&contact_role=${encodeURIComponent(contactRole)}`);
                if (!response.ok) {
                    const errorText = await response.text(); 
                    console.error("fetchAndDisplayMessages - Erro HTTP:", response.status, errorText);
                    throw new Error(`HTTP error ${response.status}: ${errorText}`);
                }
                const messages = await response.json();

                if (messages.error) {
                    console.error('API Erro (get_messages):', messages.error);
                    messagesContainer.innerHTML = `<p style="text-align:center;color:red;">Erro: ${messages.error}</p>`;
                    return;
                }

                messagesContainer.innerHTML = ''; 
                if (messages.length === 0) {
                    messagesContainer.innerHTML = '<p style="text-align:center;font-size:0.8em;color:#888;">Sem mensagens.</p>';
                } else {
                    messages.forEach(msg => {
                        const messageType = (parseInt(msg.sender_id) === currentUserId && msg.sender_role === currentUserChatRole) ? 'sent-acad' : 'received-acad';
                        appendMessageToChat(msg.message_text, messageType);
                    });
                }
            } catch (error) {
                console.error('Falha ao buscar mensagens (catch):', error); 
                messagesContainer.innerHTML = '<p style="text-align:center;color:red;">Falha ao carregar.</p>';
            }
        }

        function appendMessageToChat(text, type) {
            if(!messagesContainer) return;
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message-acad', type);
            messageDiv.textContent = text; 
            messagesContainer.appendChild(messageDiv);
            if (messagesContainer.scrollHeight > messagesContainer.clientHeight) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        async function handleSendMessage() {
            if(!messageInput || !currentConversationWith) return;
            const text = messageInput.value.trim();
            if (text === '') return;

            appendMessageToChat(text, 'sent-acad');
            const messageTextForApi = text; 
            messageInput.value = '';
            messageInput.style.height = 'auto';
            messageInput.focus();

            try {
                const response = await fetch('chat_api.php', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', },
                    body: JSON.stringify({
                        action: 'send_message',
                        receiver_id: currentConversationWith.id,
                        receiver_role: currentConversationWith.role,
                        text: messageTextForApi 
                    })
                });
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error ${response.status}: ${errorText}`);
                }
                const result = await response.json();

                if (result.error) {
                    console.error('API Erro (send_message):', result.error);
                    appendMessageToChat(`Falha: ${result.error.substring(0,50)}...`, 'error-acad');
                } else if (!result.success) {
                     console.error('API reportou falha no envio:', result);
                     appendMessageToChat(`Falha (API).`, 'error-acad');
                }
            } catch (error) {
                console.error('Falha ao enviar mensagem:', error);
                appendMessageToChat(`Falha na rede.`, 'error-acad');
            }
        }
        
        if(chatHeader) { 
            chatHeader.addEventListener('click', (event) => {
                if (event.target.closest('#chatToggleBtnAcad') || event.target.id === 'chatToggleBtnAcad') {
                    toggleChat();
                } else if (event.target === chatHeader || chatHeader.contains(event.target)) {
                    toggleChat();
                }
            });
        }

        if(backToListBtn) backToListBtn.addEventListener('click', showUserListScreen); 
        if(searchUserInput) searchUserInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const filteredUsers = allContacts.filter(user => 
                user.nome.toLowerCase().includes(searchTerm)
            );
            renderUserList(filteredUsers);
        });
        if(sendMessageBtn) sendMessageBtn.addEventListener('click', handleSendMessage);
        if(messageInput) {
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    handleSendMessage();
                }
            });
            messageInput.addEventListener('input', function() { 
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }
    });
    </script>
</body>
</html>
<?php 
if(isset($conn) && $conn) mysqli_close($conn); 
?>