<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];

$currentPageIdentifier = 'avaliar_quiz';
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

if (!isset($_GET['quiz_id'])) {
    header("Location: professor_criar_quiz.php");
    exit();
}
$quiz_id_selecionado = intval($_GET['quiz_id']);

// --- LÓGICA PARA SALVAR AVALIAÇÕES (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_avaliacoes') {
    // ... (A lógica POST para salvar avaliações permanece a mesma da resposta anterior) ...
}

// --- LÓGICA DE VISUALIZAÇÃO (GET) ---
$sql_quiz_info = "SELECT q.id, q.titulo, q.descricao, d.nome_disciplina, tu.nome_turma, tu.id as turma_id
                  FROM quizzes q
                  JOIN disciplinas d ON q.disciplina_id = d.id
                  JOIN turmas tu ON q.turma_id = tu.id
                  WHERE q.id = ? AND q.professor_id = ?";
$stmt_info = mysqli_prepare($conn, $sql_quiz_info);
mysqli_stmt_bind_param($stmt_info, "ii", $quiz_id_selecionado, $professor_id);
mysqli_stmt_execute($stmt_info);
$quiz_info_selecionado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));
mysqli_stmt_close($stmt_info);

if (!$quiz_info_selecionado) {
    die("Erro: Quiz não encontrado ou acesso negado.");
}

$questoes_do_quiz = [];
// ... (A lógica para buscar as questões permanece a mesma) ...

$entregas = [];
$turma_id_do_quiz = $quiz_info_selecionado['turma_id'];

// ***** QUERY SQL CORRIGIDA *****
// Alterado 'tentativa.data_submissao' para 'tentativa.data_fim'
$sql_entregas = "
    SELECT 
        a.id as aluno_id, a.nome as aluno_nome, a.foto_url,
        tentativa.id as tentativa_id, tentativa.status as status_tentativa, tentativa.nota_final, tentativa.data_fim as data_submissao, tentativa.feedback_professor as feedback_geral,
        resposta.id as resposta_id, resposta.questao_id, resposta.opcao_id_selecionada, resposta.texto_resposta_dissertativa, resposta.pontos_obtidos,
        opcao.texto_opcao as texto_opcao_selecionada
    FROM alunos a
    LEFT JOIN quiz_tentativas_alunos tentativa ON a.id = tentativa.aluno_id AND tentativa.quiz_id = ?
    LEFT JOIN quiz_respostas_alunos resposta ON tentativa.id = resposta.tentativa_id
    LEFT JOIN quiz_opcoes opcao ON resposta.opcao_id_selecionada = opcao.id
    WHERE a.turma_id = ?
    ORDER BY a.nome, resposta.questao_id
";
$stmt_entregas = mysqli_prepare($conn, $sql_entregas);

if ($stmt_entregas === false) {
    $error_message = mysqli_error($conn);
    error_log("Erro Crítico ao preparar a query de entregas: " . $error_message);
    die("<h3>Erro na Consulta SQL</h3><p>Ocorreu um erro crítico ao carregar os dados das entregas.</p><p><strong>Erro MySQL:</strong></p><pre>" . htmlspecialchars($error_message) . "</pre>");
}

mysqli_stmt_bind_param($stmt_entregas, "ii", $quiz_id_selecionado, $turma_id_do_quiz);
mysqli_stmt_execute($stmt_entregas);
$result_entregas = mysqli_stmt_get_result($stmt_entregas);
while($row = mysqli_fetch_assoc($result_entregas)){
    $aluno_id_atual = $row['aluno_id'];
    if(!isset($entregas[$aluno_id_atual])) {
        $entregas[$aluno_id_atual] = [
            'aluno_nome' => $row['aluno_nome'], 'foto_url' => $row['foto_url'],
            'tentativa_id' => $row['tentativa_id'], 'status' => $row['status_tentativa'],
            'nota_final' => $row['nota_final'], 'data_submissao' => $row['data_submissao'],
            'feedback_geral' => $row['feedback_geral'], 'respostas' => []
        ];
    }
    if($row['resposta_id']) {
        $entregas[$aluno_id_atual]['respostas'][$row['questao_id']] = $row;
    }
}
mysqli_stmt_close($stmt_entregas);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Avaliar Quiz: <?php echo htmlspecialchars($quiz_info_selecionado['titulo']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/professor.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Variáveis de Cores e Temas */
:root {
    /* Cores Padrão (Tema Claro) */
    --primary-color: #208A87;
    /* Cor principal (verde água) */
    --primary-color-dark: #186D6A;
    /* Verde mais escuro */
    --primary-color-light: #e0f2f2;
    /* Verde muito claro para chat */
    --secondary-color: #D69D2A;
    /* Cor secundária (amarelo/dourado) */
    --secondary-color-dark: #C58624;
    /* Amarelo/dourado mais escuro */
    --background-color: #F8F9FA;
    /* Fundo geral claro */
    --background-color-offset: #E9ECEF;
    /* Fundo para elementos ligeiramente diferentes */
    --text-color: #2C1B17;
    /* Cor do texto principal (quase preto) */
    --text-color-light: #555;
    /* Texto mais claro */
    --text-color-alt: #666;
    /* Texto alternativo */
    --border-color: #ddd;
    /* Cor de borda */
    --border-color-soft: #eee;
    /* Cor de borda mais suave */
    --card-background: white;
    /* Fundo dos cards */
    --card-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    /* Sombra dos cards */
    --card-hover-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
    /* Sombra dos cards no hover */
    --button-text-color: white;
    /* Cor do texto em botões */
    --info-color: #17a2b8;
    /* Cor para informações (azul) */
    --success-color: #28a745;
    /* Cor para sucesso (verde) */
    --warning-color: #ffc107;
    /* Cor para aviso (amarelo) */
    --danger-color: #dc3545;
    /* Cor para perigo (vermelho) */
    --accent-color: #6c757d;
    /* Cor de destaque (cinza) */
    --accent-color-extra-light: #f1f0f0;
    /* Cinza muito claro para chat */
    --hover-background-color: #f0f0f0;
    /* Fundo para hover em listas */

    /* Cores para o tema "8bit" */
    --8bit-primary-color: #008080;
    /* Teal escuro */
    --8bit-primary-color-dark: #005f5f;
    --8bit-primary-color-light: #d0f0f0;
    --8bit-secondary-color: #FFD700;
    /* Dourado */
    --8bit-secondary-color-dark: #ccaa00;
    --8bit-background-color: #2c3e50;
    /* Azul escuro */
    --8bit-background-color-offset: #34495e;
    /* Azul escuro mais claro */
    --8bit-text-color: #ecf0f1;
    /* Branco/cinza claro */
    --8bit-text-color-light: #bdc3c7;
    --8bit-text-color-alt: #95a5a6;
    --8bit-border-color: #7f8c8d;
    --8bit-border-color-soft: #95a5a6;
    --8bit-card-background: #34495e;
    /* Fundo dos cards 8bit */
    --8bit-card-shadow: 8px 8px 0px rgba(0, 0, 0, 0.5);
    /* Sombra estilo 8bit */
    --8bit-card-hover-shadow: 12px 12px 0px rgba(0, 0, 0, 0.7);
    --8bit-button-text-color: #ecf0f1;
    --8bit-info-color: #3498db;
    --8bit-success-color: #27ae60;
    --8bit-warning-color: #f39c12;
    --8bit-danger-color: #e74c3c;
    --8bit-accent-color: #7f8c8d;
    --8bit-accent-color-extra-light: #505d6b;
    --8bit-hover-background-color: #4a627a;
}

/* Base Global */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    color: var(--text-color);
    line-height: 1.6;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Temas globais aplicados ao body */
body.theme-padrao {
    background: linear-gradient(135deg, var(--background-color) 0%, var(--background-color-offset) 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body.theme-8bit {
    background-color: var(--8bit-background-color);
    color: var(--8bit-text-color);
    font-family: 'Press Start 2P', cursive;
    text-rendering: optimizeLegibility;
    -webkit-font-smoothing: auto;
}

/* Estilos para elementos específicos do tema */
body.theme-8bit header h1,
body.theme-8bit .main-content h2.page-title {
    font-family: 'Press Start 2P', cursive;
    text-shadow: 3px 3px 0px var(--8bit-primary-color-dark);
}

body.theme-8bit .card,
body.theme-8bit .aluno-avaliacao-card {
    border: 2px solid var(--8bit-border-color) !important;
}

/* Cabeçalho */
header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    color: var(--button-text-color);
    padding: 1.2rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(32, 138, 135, 0.3);
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
    background: linear-gradient(to bottom, rgba(32, 138, 135, 0.1), transparent);
}

header h1 {
    font-size: 1.6rem;
    font-weight: 600;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    letter-spacing: 0.5px;
}

header button {
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color-dark) 100%);
    color: var(--button-text-color);
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(214, 157, 42, 0.3);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

header button:hover {
    background: linear-gradient(135deg, var(--secondary-color-dark) 0%, #B07420 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(214, 157, 42, 0.4);
}

header button:active {
    transform: translateY(0);
}

.menu-btn {
    background: rgba(255, 255, 255, 0.2) !important;
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
    padding: 0.6rem !important;
    border-radius: 12px !important;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
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
    box-shadow: 4px 0 20px rgba(32, 138, 135, 0.15);
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
    border-left: 4px solid var(--secondary-color);
}

/* Conteúdo principal */
.main-content {
    flex: 1;
    padding: 2.5rem;
    background: var(--background-color);
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
    background: linear-gradient(135deg, rgba(32, 138, 135, 0.05) 0%, rgba(214, 157, 42, 0.05) 100%);
    border-radius: 0 0 50px 0;
    z-index: 0;
}

body.theme-8bit .main-content::before {
    background: linear-gradient(135deg, rgba(0, 128, 128, 0.05) 0%, rgba(255, 215, 0, 0.05) 100%);
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
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border-radius: 2px;
}

.main-content h2.page-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    border-radius: 2px;
}

/* Quiz Details Header */
.quiz-details-header {
    text-align: center;
    margin-bottom: 2rem;
    padding: 1rem;
    border-radius: 8px;
    background-color: var(--card-background);
    box-shadow: var(--card-shadow);
    color: var(--text-color);
    position: relative;
}

body.theme-8bit .quiz-details-header {
    background-color: var(--8bit-card-background);
    box-shadow: var(--8bit-card-shadow);
    color: var(--8bit-text-color);
    border: 2px solid var(--8bit-border-color);
}

.quiz-details-header .button.button-secondary {
    position: absolute;
    top: 1rem;
    left: 1rem;
    margin-bottom: 0;
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
}

.quiz-details-header h2 {
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
    color: var(--text-color);
}

body.theme-8bit .quiz-details-header h2 {
    color: var(--8bit-text-color);
}

.quiz-details-header p {
    margin: 0.2rem 0;
    color: var(--text-color-light);
}

body.theme-8bit .quiz-details-header p {
    color: var(--8bit-text-color-light);
}

/* Aluno Avaliacao Card */
.aluno-avaliacao-card {
    margin-bottom: 1.5rem;
    border-radius: 8px;
    overflow: hidden;
    background-color: var(--card-background);
    box-shadow: var(--card-shadow);
    color: var(--text-color);
}

body.theme-8bit .aluno-avaliacao-card {
    background-color: var(--8bit-card-background);
    box-shadow: var(--8bit-card-shadow);
    color: var(--8bit-text-color);
    border: 2px solid var(--8bit-border-color);
}


.aluno-header {
    padding: 1rem;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background-color 0.2s;
}

.aluno-header:hover {
    background-color: var(--hover-background-color);
}

body.theme-8bit .aluno-header:hover {
    background-color: var(--8bit-hover-background-color);
}

.aluno-header.active {
    border-bottom: 1px solid var(--border-color-soft);
}

body.theme-8bit .aluno-header.active {
    border-bottom: 1px dashed var(--8bit-border-color-soft);
}


.aluno-header img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
    border: 2px solid var(--border-color-soft);
}

body.theme-8bit .aluno-header img {
    border: 2px dashed var(--8bit-border-color-soft);
}

.aluno-info {
    display: flex;
    align-items: center;
    flex-grow: 1;
}

.aluno-status-badge {
    font-size: 0.8rem;
    font-weight: bold;
    padding: 0.4rem 0.8rem;
    border-radius: 15px;
    color: white;
    margin-left: 1rem;
}

.status-entregue {
    background-color: var(--success-color);
}

.status-pendente {
    background-color: var(--accent-color);
}

.status-avaliado {
    background-color: var(--primary-color);
}

body.theme-8bit .status-entregue {
    background-color: var(--8bit-success-color);
}

body.theme-8bit .status-pendente {
    background-color: var(--8bit-accent-color);
}

body.theme-8bit .status-avaliado {
    background-color: var(--8bit-primary-color);
}


/* Aluno Respostas Body */
.aluno-respostas-body {
    display: none;
    padding: 1.5rem;
}

.questao-avaliacao {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px dashed var(--border-color-soft);
}

body.theme-8bit .questao-avaliacao {
    border-bottom: 1px dashed var(--8bit-border-color-soft);
}


.questao-avaliacao:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.questao-avaliacao strong {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-color);
}

body.theme-8bit .questao-avaliacao strong {
    color: var(--8bit-text-color);
}

.resposta-aluno {
    background-color: var(--background-color-offset);
    padding: 0.8rem;
    border-radius: 4px;
    white-space: pre-wrap;
    margin-top: 0.5rem;
    color: var(--text-color);
    border: 1px solid var(--border-color-soft);
}

body.theme-8bit .resposta-aluno {
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
    border: 1px dashed var(--8bit-border-color-soft);
}


.resposta-correta {
    color: var(--success-color);
    font-weight: bold;
}

.resposta-incorreta {
    color: var(--danger-color);
}

body.theme-8bit .resposta-correta {
    color: var(--8bit-success-color);
}

body.theme-8bit .resposta-incorreta {
    color: var(--8bit-danger-color);
}


.correcao-area {
    margin-top: 1rem;
}

.correcao-area label {
    font-weight: bold;
    color: var(--text-color);
}

body.theme-8bit .correcao-area label {
    color: var(--8bit-text-color);
}


.correcao-area input[type="number"] {
    width: 80px;
    padding: 0.3rem;
    text-align: center;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background-color: var(--background-color-offset);
    color: var(--text-color);
}

body.theme-8bit .correcao-area input[type="number"] {
    border: 1px dashed var(--8bit-border-color);
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
}


.feedback-geral-area textarea {
    width: 100%;
    min-height: 60px;
    padding: 0.8rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background-color: var(--background-color-offset);
    color: var(--text-color);
    resize: vertical;
}

body.theme-8bit .feedback-geral-area textarea {
    border: 1px dashed var(--8bit-border-color);
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
}


/* Botão Salvar Avaliações */
.btn-salvar-avaliacoes {
    display: block;
    margin: 2rem auto;
    width: 100%;
    max-width: 400px;
    padding: 1rem;
    font-size: 1.2rem;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    color: var(--button-text-color);
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(32, 138, 135, 0.3);
}

.btn-salvar-avaliacoes:hover {
    background: linear-gradient(135deg, var(--primary-color-dark) 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(32, 138, 135, 0.4);
}

body.theme-8bit .btn-salvar-avaliacoes {
    background: var(--8bit-primary-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
    box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.5);
}

body.theme-8bit .btn-salvar-avaliacoes:hover {
    background: var(--8bit-primary-color-dark);
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.7);
}


/* Status Message */
.status-message {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    text-align: center;
}

.status-message.success {
    background-color: rgba(var(--success-color-rgb, 40, 167, 69), 0.1);
    border: 1px solid var(--success-color);
    color: var(--success-color);
}

.status-message.error {
    background-color: rgba(var(--danger-color-rgb, 220, 53, 69), 0.1);
    border: 1px solid var(--danger-color);
    color: var(--danger-color);
}

body.theme-8bit .status-message.success {
    background-color: rgba(39, 174, 96, 0.1);
    border: 1px dashed var(--8bit-success-color);
    color: var(--8bit-success-color);
}

body.theme-8bit .status-message.error {
    background-color: rgba(231, 76, 60, 0.1);
    border: 1px dashed var(--8bit-danger-color);
    color: var(--8bit-danger-color);
}

/* No data message */
.no-data-message {
    padding: 1rem;
    text-align: center;
    background-color: rgba(var(--info-color-rgb, 23, 162, 184), 0.05);
    border: 1px dashed rgba(var(--info-color-rgb, 23, 162, 184), 0.2);
    border-radius: 4px;
    color: var(--text-color-alt);
}

body.theme-8bit .no-data-message {
    background-color: rgba(52, 152, 219, 0.05);
    border: 1px dashed rgba(52, 152, 219, 0.2);
    color: var(--8bit-text-color-alt);
}


/* Sidebar hidden */
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
        box-shadow: 0 4px 20px rgba(32, 138, 135, 0.2);
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

    .quiz-details-header {
        padding: 1rem;
    }

    .quiz-details-header .button.button-secondary {
        position: static;
        float: none;
        display: block;
        margin: 0 auto 1rem auto;
        width: fit-content;
    }


    .quiz-details-header h2 {
        font-size: 1.5rem;
    }

    .aluno-avaliacao-card {
        padding: 1rem;
    }

    .aluno-header {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }

    .aluno-header img {
        margin-right: 0;
        margin-bottom: 10px;
    }

    .aluno-info {
        width: 100%;
        justify-content: center;
        text-align: center;
        flex-direction: column;
    }

    .aluno-status-badge {
        margin-left: 0;
        margin-top: 5px;
        align-self: center;
    }

    .aluno-respostas-body {
        padding: 1rem;
    }

    .questao-avaliacao strong {
        font-size: 0.95rem;
    }

    .resposta-aluno,
    .feedback-geral-area textarea {
        font-size: 0.9rem;
    }

    .correcao-area label {
        font-size: 0.9rem;
    }

    .correcao-area input[type="number"] {
        width: 60px;
        padding: 0.2rem;
        font-size: 0.8rem;
    }

    .btn-salvar-avaliacoes {
        width: 90%;
        font-size: 1rem;
    }
}


/* Animations */
.sidebar,
.main-content {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color-offset);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-color-dark));
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, var(--primary-color-dark), #145A57);
}

body.theme-8bit ::-webkit-scrollbar-track {
    background: var(--8bit-background-color-offset);
}

body.theme-8bit ::-webkit-scrollbar-thumb {
    background: var(--8bit-primary-color);
    border: 1px solid var(--8bit-border-color);
}

body.theme-8bit ::-webkit-scrollbar-thumb:hover {
    background: var(--8bit-primary-color-dark);
}
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 0.5rem; }
        .quiz-details-header { text-align: center; margin-bottom: 2rem; padding: 1rem; border-radius: 8px; }
        .quiz-details-header p { margin: 0.2rem 0; }
        .aluno-avaliacao-card { margin-bottom: 1.5rem; border-radius: 8px; overflow: hidden; }
        .aluno-header { padding: 1rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: background-color 0.2s; }
        .aluno-header:hover { background-color: var(--hover-background-color); }
        .aluno-header.active { border-bottom: 1px solid var(--border-color-soft); }
        .aluno-header img { width: 40px; height: 40px; border-radius: 50%; margin-right: 15px; }
        .aluno-info { display: flex; align-items: center; flex-grow: 1; }
        .aluno-status-badge { font-size: 0.8rem; font-weight: bold; padding: 0.4rem 0.8rem; border-radius: 15px; color: white; margin-left: 1rem; }
        .status-entregue { background-color: var(--success-color, green); }
        .status-pendente { background-color: var(--text-color-muted, #6c757d); }
        .status-avaliado { background-color: var(--primary-color, #007bff); }
        .aluno-respostas-body { display: none; padding: 1.5rem; }
        .questao-avaliacao { margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px dashed var(--border-color-soft); }
        .questao-avaliacao:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .questao-avaliacao strong { display: block; margin-bottom: 0.5rem; }
        .resposta-aluno { background-color: var(--background-color-offset, #f8f9fa); padding: 0.8rem; border-radius: 4px; white-space: pre-wrap; margin-top: 0.5rem;}
        .resposta-correta { color: var(--success-color, green); font-weight: bold; }
        .resposta-incorreta { color: var(--danger-color, red); }
        .correcao-area { margin-top: 1rem; }
        .correcao-area label { font-weight: bold; }
        .correcao-area input[type="number"] { width: 80px; padding: 0.3rem; text-align: center; }
        .feedback-geral-area textarea { width: 100%; min-height: 60px; }
        .btn-salvar-avaliacoes { display: block; margin: 2rem auto; width: 100%; max-width: 400px; padding: 1rem; font-size: 1.2rem; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        .no-data-message { padding: 1rem; text-align: center; }

        /* Cole aqui o CSS completo do Chat */
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Avaliar Prova</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>
        <main class="main-content">
            <div class="quiz-details-header card">
                <a href="professor_criar_quiz.php" class="button button-secondary" style="float: left; margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Voltar</a>
                <h2 class="page-title" style="display:inline-block; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($quiz_info_selecionado['titulo']); ?></h2>
                <p>
                    <strong>Turma:</strong> <?php echo htmlspecialchars($quiz_info_selecionado['nome_turma']); ?> | 
                    <strong>Disciplina:</strong> <?php echo htmlspecialchars($quiz_info_selecionado['nome_disciplina']); ?>
                </p>
            </div>

            <?php if(isset($_SESSION['quiz_prof_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['quiz_prof_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['quiz_prof_status_message']); ?>
                </div>
                <?php unset($_SESSION['quiz_prof_status_message']); unset($_SESSION['quiz_prof_status_type']); ?>
            <?php endif; ?>

            <form action="professor_avaliar_quiz.php?quiz_id=<?php echo $quiz_id_selecionado; ?>" method="POST">
                <input type="hidden" name="action" value="salvar_avaliacoes">
                <?php foreach($entregas as $aluno_id_entrega => $entrega): ?>
                    <div class="aluno-avaliacao-card card">
                        <div class="aluno-header">
                            <div class="aluno-info">
                                <img src="<?php echo htmlspecialchars(!empty($entrega['foto_url']) ? $entrega['foto_url'] : 'img/alunos/default_avatar.png'); ?>" alt="Foto" onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                                <span><?php echo htmlspecialchars($entrega['aluno_nome']); ?></span>
                            </div>
                            <?php 
                                if($entrega['status'] === 'avaliado') {
                                    echo '<span class="aluno-status-badge status-avaliado">Avaliado: '.number_format($entrega['nota_final'], 2, ',', '.').'</span>';
                                } elseif($entrega['status'] === 'finalizado') {
                                    // CORREÇÃO AQUI: Usando data_fim (que foi renomeado para data_submissao no array)
                                    $sub_time = new DateTime($entrega['data_submissao']);
                                    echo '<span class="aluno-status-badge status-entregue">Entregue em ' . $sub_time->format('d/m/Y H:i') . '</span>';
                                } else {
                                    echo '<span class="aluno-status-badge status-pendente">Pendente</span>';
                                }
                            ?>
                        </div>
                        <?php if($entrega['tentativa_id']): ?>
                        <div class="aluno-respostas-body">
                            <?php foreach($questoes_do_quiz as $questao_id_quiz => $questao): ?>
                                <div class="questao-avaliacao">
                                    <p><strong><?php echo htmlspecialchars($questao['texto_pergunta']); ?></strong> (<?php echo number_format($questao['pontos'], 1, ',', '.'); ?> pts)</p>
                                    
                                    <?php 
                                        $resposta_do_aluno = $entrega['respostas'][$questao_id_quiz] ?? null;
                                        $acertou_auto = false;
                                    ?>
                                    <?php if ($questao['tipo_pergunta'] === 'multipla_escolha'): ?>
                                        <p>Resposta do Aluno: 
                                            <?php 
                                                $texto_aluno = $resposta_do_aluno['texto_opcao_selecionada'] ?? 'Não respondeu';
                                                $opcao_correta_id = $questao['opcao_correta']['id'] ?? -1;
                                                if ($resposta_do_aluno && $resposta_do_aluno['opcao_id_selecionada'] == $opcao_correta_id) {
                                                    echo '<span class="resposta-correta">' . htmlspecialchars($texto_aluno) . ' <i class="fas fa-check-circle"></i></span>';
                                                    $acertou_auto = true;
                                                } else {
                                                    echo '<span class="resposta-incorreta">' . htmlspecialchars($texto_aluno) . '</span>';
                                                }
                                            ?>
                                        </p>
                                        <p>Resposta Correta: <span class="resposta-correta"><?php echo htmlspecialchars($questao['opcao_correta']['texto_opcao'] ?? 'N/A'); ?></span></p>
                                    <?php else: // Dissertativa ?>
                                        <p>Resposta do Aluno:</p>
                                        <div class="resposta-aluno"><?php echo nl2br(htmlspecialchars($resposta_do_aluno['texto_resposta_dissertativa'] ?? 'Não respondeu')); ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="correcao-area">
                                        <label for="pontos_<?php echo $resposta_do_aluno['resposta_id'] ?? $questao_id_quiz; ?>">Pontos Atribuídos:</label>
                                        <input type="number" name="avaliacoes[<?php echo $entrega['tentativa_id']; ?>][respostas][<?php echo $resposta_do_aluno['resposta_id']; ?>][pontos]" 
                                               id="pontos_<?php echo $resposta_do_aluno['resposta_id'] ?? $questao_id_quiz; ?>" 
                                               value="<?php echo htmlspecialchars($resposta_do_aluno['pontos_obtidos'] ?? ($acertou_auto ? $questao['pontos'] : '0.0')); ?>" 
                                               step="0.1" min="0" max="<?php echo $questao['pontos']; ?>" class="input-field">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="feedback-geral-area">
                                <label for="feedback_geral_<?php echo $entrega['tentativa_id']; ?>">Feedback Geral para o Aluno:</label>
                                <textarea name="feedback_geral[<?php echo $entrega['tentativa_id']; ?>]" id="feedback_geral_<?php echo $entrega['tentativa_id']; ?>" class="input-field"><?php echo htmlspecialchars($entrega['feedback_geral'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn-salvar-avaliacoes button button-primary"><i class="fas fa-save"></i> Salvar Todas as Avaliações</button>
            </form>
        </main>
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

        // Script para expandir/colapsar respostas dos alunos
        document.querySelectorAll('.aluno-header').forEach(header => {
            header.addEventListener('click', function() {
                this.classList.toggle('active');
                const body = this.nextElementSibling;
                if (body) { 
                    if (body.style.display === "block") {
                        body.style.display = "none";
                    } else {
                        body.style.display = "block";
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php 
if(isset($conn) && $conn) mysqli_close($conn); 
?>