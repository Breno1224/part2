<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];

$currentPageIdentifier = 'criar_quiz'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// --- LÓGICA DE PROCESSAMENTO DE AÇÕES (EXCLUIR QUIZ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_quiz' && isset($_POST['quiz_id_delete'])) {
        $quiz_id_del = intval($_POST['quiz_id_delete']);
        
        // A tabela de quiz_questoes e quiz_tentativas_alunos tem ON DELETE CASCADE,
        // então todos os registros relacionados (questões, opções, tentativas, respostas) serão apagados automaticamente.
        $sql = "DELETE FROM quizzes WHERE id = ? AND professor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $quiz_id_del, $professor_id);
            if(mysqli_stmt_execute($stmt)){
                if(mysqli_stmt_affected_rows($stmt) > 0) {
                     $_SESSION['quiz_status_message'] = "Prova excluída com sucesso.";
                     $_SESSION['quiz_status_type'] = "status-success";
                } else {
                     $_SESSION['quiz_status_message'] = "Não foi possível excluir a prova (não encontrada ou permissão negada).";
                     $_SESSION['quiz_status_type'] = "status-error";
                }
            } else {
                $_SESSION['quiz_status_message'] = "Erro ao executar a exclusão.";
                $_SESSION['quiz_status_type'] = "status-error";
            }
            mysqli_stmt_close($stmt);
        } else {
             $_SESSION['quiz_status_message'] = "Erro ao preparar a exclusão.";
             $_SESSION['quiz_status_type'] = "status-error";
        }
        
        header("Location: professor_criar_quiz.php");
        exit();
    }
}

// Buscar turmas e disciplinas que o professor leciona para os selects do formulário
$sql_assoc = "SELECT DISTINCT t.id as turma_id, t.nome_turma, d.id as disciplina_id, d.nome_disciplina
              FROM turmas t
              JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id
              JOIN disciplinas d ON ptd.disciplina_id = d.id
              WHERE ptd.professor_id = ? ORDER BY t.nome_turma, d.nome_disciplina";
$stmt_assoc = mysqli_prepare($conn, $sql_assoc);
$associacoes = [];
if($stmt_assoc){
    mysqli_stmt_bind_param($stmt_assoc, "i", $professor_id);
    mysqli_stmt_execute($stmt_assoc);
    $result_assoc = mysqli_stmt_get_result($stmt_assoc);
    while($row = mysqli_fetch_assoc($result_assoc)){ $associacoes[] = $row; }
    mysqli_stmt_close($stmt_assoc);
}

// Buscar quizzes já criados por este professor para a lista
$quizzes_criados = [];
$sql_quizzes = "SELECT q.id, q.titulo, q.data_prazo, tu.nome_turma, d.nome_disciplina,
                (SELECT COUNT(DISTINCT aluno_id) FROM quiz_tentativas_alunos WHERE quiz_id = q.id AND status != 'em_andamento') as total_entregas
                FROM quizzes q
                JOIN turmas tu ON q.turma_id = tu.id
                JOIN disciplinas d ON q.disciplina_id = d.id
                WHERE q.professor_id = ? 
                ORDER BY q.data_criacao DESC";
$stmt_quizzes = mysqli_prepare($conn, $sql_quizzes);
if($stmt_quizzes){
    mysqli_stmt_bind_param($stmt_quizzes, "i", $professor_id);
    mysqli_stmt_execute($stmt_quizzes);
    $result_quizzes = mysqli_stmt_get_result($stmt_quizzes);
    while($row = mysqli_fetch_assoc($result_quizzes)){ $quizzes_criados[] = $row; }
    mysqli_stmt_close($stmt_quizzes);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar e Gerenciar Provas - ACADMIX</title>
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

    /* Cores específicas para botões */
    --button-secondary-color: #6c757d;
    --button-secondary-color-dark: #5a6268;
    --button-info-color: #17a2b8;
    --button-info-color-dark: #138496;
    --button-danger-color: #dc3545;
    --button-danger-color-dark: #c82333;
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
    color: var(--button-text-color);
}
.button-secondary:hover {
    background: linear-gradient(135deg, var(--button-secondary-color-dark) 0%, #495057 100%);
    box-shadow: 0 8px 25px rgba(var(--button-secondary-color-rgb, 108, 117, 125), 0.4);
}

.button-info {
    background: linear-gradient(135deg, var(--button-info-color) 0%, var(--button-info-color-dark) 100%);
    box-shadow: 0 4px 15px rgba(var(--status-info-rgb), 0.3);
    color: var(--button-text-color);
}
.button-info:hover {
    background: linear-gradient(135deg, var(--button-info-color-dark) 0%, #117a8b 100%);
    box-shadow: 0 8px 25px rgba(var(--status-info-rgb), 0.4);
}

.button-danger {
    background: linear-gradient(135deg, var(--button-danger-color) 0%, var(--button-danger-color-dark) 100%);
    box-shadow: 0 4px 15px rgba(var(--status-error-rgb), 0.3);
    color: var(--button-text-color);
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

/* O estado ativo da sidebar, conforme o original aluno.css */
.sidebar ul li a.active {
    background: rgba(255, 255, 255, 0.2);
    color: var(--button-text-color);
    font-weight: 600;
    border-left: 4px solid var(--accent-color); /* A barrinha amarela */
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
    display: flex; /* For icon alignment */
    align-items: center;
    gap: 10px;
}

/* Estilos específicos da página professor_criar_quiz.php */
.main-container {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

@media (min-width: 1200px) {
    .main-container {
        grid-template-columns: 450px 1fr; /* Duas colunas em telas grandes */
    }
}

.form-section, .list-section {
    margin-bottom: 0; /* Gerenciado pelo gap do .main-container */
    padding: 2rem; /* Consistente com .card */
    border-radius: 16px; /* Consistente com .card */
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
    margin-bottom: 0.5rem; /* Ajustado para consistência */
    font-weight: bold;
    color: var(--text-color);
}

.input-field {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px; /* Consistente com outros formulários */
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
    min-height: 100px; /* Altura mínima ajustada */
    resize: vertical;
    margin-bottom: 0.8rem; /* Manter margem consistente */
}

.form-section button {
    display: inline-block; /* Para os botões + Múltipla Escolha / + Dissertativa */
    width: auto;
    margin-top: 1rem;
    padding: 0.7rem 1.5rem;
}

.form-section .grid-2-col {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 768px) {
    .form-section .grid-2-col {
        grid-template-columns: 1fr 1fr;
    }
}

/* Construtor de Perguntas do Quiz */
#questoes-container {
    margin-top: 2rem;
    border-top: 2px solid var(--border-color);
    padding-top: 1.5rem;
}

.questao-card {
    padding: 1.5rem;
    border-radius: 12px; /* Ligeiramente mais arredondado */
    margin-bottom: 1.5rem;
    position: relative;
    background-color: var(--background-color-offset); /* Fundo mais claro para cards aninhados */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); /* Sombra mais suave */
    transition: all 0.2s ease;
}

.questao-card:hover {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.questao-card h4 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--primary-color-dark);
}

.questao-card .remover-questao-btn {
    position: absolute;
    top: 15px; /* Posição ajustada */
    right: 15px; /* Posição ajustada */
    cursor: pointer;
    background: var(--button-danger-color); /* Usar cor de perigo */
    color: var(--button-text-color); /* Texto branco */
    border: none;
    border-radius: 50%;
    width: 32px; /* Botão maior */
    height: 32px;
    font-size: 1.2rem; /* Ícone maior */
    line-height: 32px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(var(--status-error-rgb), 0.2);
    transition: all 0.2s ease;
}
.questao-card .remover-questao-btn:hover {
    background: var(--button-danger-color-dark);
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(var(--status-error-rgb), 0.3);
}

.opcoes-container {
    margin-top: 1rem;
    padding-left: 10px; /* Recuo para as opções */
}

.opcao-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0.8rem; /* Mais espaço entre as opções */
}

.opcao-item input[type="radio"] {
    flex-shrink: 0;
    width: auto;
    margin-right: 5px; /* Espaçamento consistente */
    transform: scale(1.2); /* Aumentar o radio button */
    accent-color: var(--primary-color); /* Colorir o radio button */
}

.opcao-item input[type="text"] {
    flex-grow: 1;
    margin: 0; /* Resetar margem */
    padding: 0.5rem; /* Padding consistente */
    border: 1px solid var(--border-color-soft); /* Borda mais clara */
    border-radius: 6px;
    background-color: var(--card-background); /* Fundo branco para texto da opção */
}
.opcao-item input[type="text"]:focus {
    border-color: var(--primary-color-light);
    outline: none;
    box-shadow: 0 0 0 2px rgba(var(--primary-color-rgb), 0.1);
}

.opcao-item button {
    margin: 0;
    padding: 0.5rem; /* Padding consistente para botões pequenos */
    width: 30px; /* Largura fixa para botões pequenos */
    height: 30px; /* Altura fixa para botões pequenos */
    font-size: 0.9em; /* Tamanho da fonte ajustado */
    line-height: 1;
    border-radius: 50%; /* Botão circular */
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mensagens de status */
.status-message {
    padding: 1rem;
    margin-bottom: 1.5rem;
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

.no-data-message {
    padding: 2rem;
    text-align: center;
    border-radius: 12px;
    background: rgba(var(--primary-color-rgb), 0.03);
    border: 2px dashed rgba(var(--primary-color-rgb), 0.2);
    color: var(--text-color-muted);
    font-size: 1.1rem;
}

/* Tabela de Lista de Quizzes */
.quiz-list {
    width: 100%;
    border-collapse: separate; /* Para border-radius nas células */
    border-spacing: 0; /* Para remover espaços entre as células */
    margin-top: 1.5rem;
    background-color: var(--card-background);
    border-radius: 8px;
    overflow: hidden; /* Garante que bordas arredondadas sejam visíveis */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    font-size: 0.95em; /* Fonte um pouco maior */
}

.quiz-list th, .quiz-list td {
    padding: 0.9rem 1.2rem; /* Padding consistente */
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color-soft);
    color: var(--text-color);
}

.quiz-list th {
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

.quiz-list tbody tr:last-child td {
    border-bottom: none;
}

.quiz-list tbody tr:hover {
    background-color: var(--hover-background-color);
}

.quiz-list .actions-cell {
    white-space: nowrap; /* Impede que os botões quebrem a linha */
}
.quiz-list .actions-cell form, .quiz-list .actions-cell a {
    margin: 0 3px; /* Um pouco mais de espaço */
    display: inline-block;
    vertical-align: middle;
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

    .main-container {
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
        min-height: 80px;
    }

    .form-section button {
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
        width: 100%; /* Make buttons full width on small screens */
    }

    .form-section .grid-2-col {
        grid-template-columns: 1fr; /* Stack columns within form on small screens */
    }

    .questao-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .questao-card h4 {
        font-size: 1.1rem;
    }
    .questao-card .remover-questao-btn {
        width: 28px;
        height: 28px;
        font-size: 1rem;
        top: 10px;
        right: 10px;
    }
    .opcao-item {
        flex-wrap: wrap; /* Allow options to wrap */
    }
    .opcao-item input[type="text"] {
        flex-basis: calc(100% - 60px); /* Adjust width to make room for radio and button */
    }
    .opcao-item button {
        order: 3; /* Move remove button to end of line */
    }


    .quiz-list {
        font-size: 0.85em; /* Smaller font for tables on small screens */
    }
    .quiz-list th, .quiz-list td {
        padding: 0.6rem 0.8rem;
    }
    .quiz-list .actions-cell {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .quiz-list .actions-cell form, .quiz-list .actions-cell a {
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
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .main-container { display: grid; grid-template-columns: 1fr; gap: 2rem; }
        @media (min-width: 1200px) { .main-container { grid-template-columns: 450px 1fr; } }
        
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.3rem; font-weight: bold;}
        .form-section input, .form-section textarea, .form-section select { width: 100%; padding: 0.6rem; margin-bottom: 0.8rem; box-sizing: border-box; border-radius: 4px;}
        .form-section textarea { min-height: 80px; resize: vertical; }
        .form-section button { display: inline-block; width: auto; margin-top: 1rem; padding: 0.7rem 1.5rem; }
        .form-section .grid-2-col { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        @media (min-width: 768px) { .form-section .grid-2-col { grid-template-columns: 1fr 1fr; } }
        
        #questoes-container { margin-top: 2rem; border-top: 2px solid var(--border-color, #ccc); padding-top: 1.5rem; }
        .questao-card { padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; position: relative; background-color: var(--background-color-offset, #f9f9f9); }
        .questao-card h4 { margin-top: 0; }
        .questao-card .remover-questao-btn { position: absolute; top: 10px; right: 10px; cursor: pointer; background: var(--danger-color-light); color: var(--danger-color-dark); border: none; border-radius: 50%; width: 28px; height: 28px; font-size: 1.1rem; line-height: 28px; text-align: center;}
        
        .opcoes-container { margin-top: 1rem; }
        .opcao-item { display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; }
        .opcao-item input[type="radio"] { flex-shrink: 0; width: auto; }
        .opcao-item input[type="text"] { flex-grow: 1; margin: 0; }
        .opcao-item button { margin: 0; padding: 0.4rem; width: auto; font-size: 0.8em; line-height: 1; }
        
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        
        .quiz-list table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
        .quiz-list th, .quiz-list td { padding: 0.75rem; text-align: left; vertical-align: middle; }
        .quiz-list .actions-cell form, .quiz-list .actions-cell a { margin: 0 2px; display: inline-block; }

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
        <h1>ACADMIX - Criar e Gerenciar Provas</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Gerenciamento de Provas e Quizzes</h2>
            <?php if(isset($_SESSION['quiz_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['quiz_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['quiz_status_message']); ?>
                </div>
                <?php unset($_SESSION['quiz_status_message']); unset($_SESSION['quiz_status_type']); ?>
            <?php endif; ?>
            
            <div class="main-container">
                <section class="form-section dashboard-section card">
                    <h3><i class="fas fa-plus-square"></i> Criar Nova Prova</h3>
                    <form action="salvar_quiz.php" method="POST" id="quiz-form">
                        <label for="titulo">Título da Prova/Quiz:</label>
                        <input type="text" id="titulo" name="titulo" class="input-field" required>

                        <div class="grid-2-col">
                             <div>
                                <label for="turma_disciplina_id">Para Turma / Disciplina:</label>
                                <select id="turma_disciplina_id" name="turma_disciplina_id" class="input-field" required>
                                    <option value="">Selecione...</option>
                                    <?php if(!empty($associacoes)): foreach($associacoes as $assoc): ?>
                                        <option value="<?php echo $assoc['turma_id'].'-'.$assoc['disciplina_id']; ?>">
                                            <?php echo htmlspecialchars($assoc['nome_turma']) . ' / ' . htmlspecialchars($assoc['nome_disciplina']); ?>
                                        </option>
                                    <?php endforeach; else: ?>
                                    <option value="" disabled>Você não tem turmas/disciplinas associadas.</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                             <div>
                                <label for="duracao_minutos">Duração (min, 0=livre):</label>
                                <input type="number" id="duracao_minutos" name="duracao_minutos" class="input-field" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="grid-2-col">
                            <div>
                                <label for="data_inicio">Disponível a partir de:</label>
                                <input type="datetime-local" id="data_inicio" name="data_inicio" class="input-field" required>
                            </div>
                            <div>
                                <label for="data_prazo">Prazo final:</label>
                                <input type="datetime-local" id="data_prazo" name="data_prazo" class="input-field" required>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <input type="checkbox" id="aleatorizar_questoes" name="aleatorizar_questoes" value="1" style="width: auto; margin-right: 5px;">
                            <label for="aleatorizar_questoes" style="display:inline; font-weight:normal;">Aleatorizar ordem das questões</label>
                        </div>
                        
                        <label for="descricao" style="margin-top: 1.5rem;">Instruções / Descrição:</label>
                        <textarea id="descricao" name="descricao" class="input-field" placeholder="Ex: Leia atentamente as questões..."></textarea>

                        <div id="questoes-container">
                            </div>

                        <div style="margin-top:1rem; display:flex; gap:1rem;">
                            <button type="button" id="add-multipla-escolha-btn" class="button button-secondary"><i class="fas fa-plus-circle"></i> Múltipla Escolha</button>
                            <button type="button" id="add-dissertativa-btn" class="button button-secondary"><i class="fas fa-plus-circle"></i> Dissertativa</button>
                        </div>

                        <button type="submit" class="button button-primary" style="font-size:1.1rem; width:100%; margin-top: 2rem;"><i class="fas fa-save"></i> Salvar Prova Completa</button>
                    </form>
                </section>

                 <section class="list-section dashboard-section card">
                    <h3><i class="fas fa-history"></i> Provas Criadas</h3>
                    <div style="overflow-x:auto;">
                         <table class="table quiz-list">
                            <thead><tr><th>Título</th><th>Turma</th><th>Prazo</th><th>Entregas</th><th>Ações</th></tr></thead>
                            <tbody>
                                <?php if(!empty($quizzes_criados)): foreach($quizzes_criados as $quiz): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($quiz['titulo']); ?></td>
                                    <td><?php echo htmlspecialchars($quiz['nome_turma']); ?></td>
                                    <td><?php echo date("d/m/y H:i", strtotime($quiz['data_prazo'])); ?></td>
                                    <td><?php echo $quiz['total_entregas']; ?></td>
                                    <td class="actions-cell">
                                        <a href="professor_avaliar_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="button button-info button-xsmall" title="Ver Respostas e Avaliar"><i class="fas fa-list-check"></i></a>
                                        
                                        <form action="professor_criar_quiz.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta prova? Todas as tentativas e respostas dos alunos também serão perdidas.');">
                                            <input type="hidden" name="action" value="delete_quiz">
                                            <input type="hidden" name="quiz_id_delete" value="<?php echo $quiz['id']; ?>">
                                            <button type="submit" class="button button-danger button-xsmall" title="Excluir Prova"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="5" class="no-data-message">Nenhuma prova criada por você ainda.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
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
        // Script do menu lateral
        const menuToggleButtonGlobal = document.getElementById('menu-toggle');
        const sidebarElementGlobal = document.getElementById('sidebar');    
        const pageContainerGlobal = document.getElementById('pageContainer'); 
        if (menuToggleButtonGlobal && sidebarElementGlobal && pageContainerGlobal) {
            menuToggleButtonGlobal.addEventListener('click', function () {
                sidebarElementGlobal.classList.toggle('hidden'); 
                pageContainerGlobal.classList.toggle('full-width'); 
            });
        }

        // --- LÓGICA DO CONSTRUTOR DE QUIZ ---
        document.addEventListener('DOMContentLoaded', function() {
            const questoesContainer = document.getElementById('questoes-container');
            let questaoIndex = 0;

            document.getElementById('add-multipla-escolha-btn').addEventListener('click', () => {
                adicionarQuestao('multipla_escolha');
            });
            document.getElementById('add-dissertativa-btn').addEventListener('click', () => {
                adicionarQuestao('dissertativa');
            });

            window.adicionarQuestao = function(tipo) {
                const questaoId = `q_${questaoIndex}`;
                const questaoCard = document.createElement('div');
                questaoCard.className = 'questao-card card-item';
                questaoCard.id = questaoId;

                let htmlInterno = `
                    <button type="button" class="remover-questao-btn" title="Remover Questão" onclick="removerElemento('${questaoId}')">&times;</button>
                    <h4>Questão ${questaoIndex + 1} (${tipo === 'multipla_escolha' ? 'Múltipla Escolha' : 'Dissertativa'})</h4>
                    <input type="hidden" name="questoes[${questaoIndex}][tipo]" value="${tipo}">
                    
                    <label for="texto_${questaoId}">Enunciado da Questão:</label>
                    <textarea id="texto_${questaoId}" name="questoes[${questaoIndex}][texto]" class="input-field" required></textarea>
                    
                    <label for="pontos_${questaoId}">Pontos:</label>
                    <input type="number" id="pontos_${questaoId}" name="questoes[${questaoIndex}][pontos]" class="input-field" value="1.0" step="0.1" min="0" required style="width:100px;">
                `;

                if (tipo === 'multipla_escolha') {
                    htmlInterno += `
                        <div class="opcoes-container" id="opcoes_container_${questaoId}">
                            <label>Opções de Resposta (marque a correta):</label>
                        </div>
                        <button type="button" class="button button-secondary button-xsmall" onclick="adicionarOpcao('${questaoId}', ${questaoIndex})"><i class="fas fa-plus"></i> Adicionar Opção</button>
                    `;
                }
                
                questaoCard.innerHTML = htmlInterno;
                questoesContainer.appendChild(questaoCard);

                if (tipo === 'multipla_escolha') {
                    adicionarOpcao(questaoId, questaoIndex);
                    adicionarOpcao(questaoId, questaoIndex);
                }
                questaoIndex++;
            }

            window.adicionarOpcao = function(questaoId, qIndex) {
                const opcoesContainer = document.getElementById(`opcoes_container_${questaoId}`);
                const opcaoIndex = opcoesContainer.querySelectorAll('.opcao-item').length;
                const opcaoId = `op_${questaoId}_${opcaoIndex}`;

                const opcaoItem = document.createElement('div');
                opcaoItem.className = 'opcao-item';
                opcaoItem.id = opcaoId;

                opcaoItem.innerHTML = `
                    <input type="radio" name="questoes[${qIndex}][correta]" id="correta_${opcaoId}" value="${opcaoIndex}" required title="Marcar como correta">
                    <input type="text" name="questoes[${qIndex}][opcoes][${opcaoIndex}][texto]" class="input-field" placeholder="Texto da opção ${opcaoIndex + 1}" required>
                    <button type="button" class="button button-danger button-xsmall" onclick="removerElemento('${opcaoId}')" title="Remover Opção"><i class="fas fa-times"></i></button>
                `;
                opcoesContainer.appendChild(opcaoItem);
            }

            window.removerElemento = function(elementId) {
                const elemento = document.getElementById(elementId);
                if (elemento) {
                    elemento.remove();
                }
            }
        });
    </script>
    
    <script>
        // O JavaScript completo e padronizado do chat para PROFESSOR vai aqui.
        // Assegure-se de que ele use as variáveis `currentUserId` e `currentUserSessionRole`
        // definidas a partir das variáveis PHP `$professor_id` e `$_SESSION['role']`.
    </script>

</body>
</html>
<?php 
if(isset($conn) && $conn) mysqli_close($conn); 
?>