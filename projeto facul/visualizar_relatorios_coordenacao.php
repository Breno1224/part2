<?php
session_start(); // GARANTIR que está no topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_coordenador = $_SESSION['usuario_nome'];
$coordenador_id = $_SESSION['usuario_id']; // Nome padronizado para o chat JS
$currentPageIdentifier = 'ver_relatorios_coord';

// PEGAR TEMA DA SESSÃO
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Buscar dados para os filtros
$professores_query_result = mysqli_query($conn, "SELECT id, nome FROM professores ORDER BY nome"); // Nome da variável ajustado
$turmas_query_result = mysqli_query($conn, "SELECT id, nome_turma FROM turmas ORDER BY nome_turma"); // Nome da variável ajustado
$disciplinas_query_result = mysqli_query($conn, "SELECT id, nome_disciplina FROM disciplinas ORDER BY nome_disciplina"); // Nome da variável ajustado

// Lógica de Filtro
$where_clauses = [];
$params = [];
$param_types = "";

if (!empty($_GET['professor_id'])) {
    $where_clauses[] = "r.professor_id = ?";
    $params[] = intval($_GET['professor_id']);
    $param_types .= "i";
}
if (!empty($_GET['turma_id'])) {
    $where_clauses[] = "r.turma_id = ?";
    $params[] = intval($_GET['turma_id']);
    $param_types .= "i";
}
if (!empty($_GET['disciplina_id'])) {
    $where_clauses[] = "r.disciplina_id = ?";
    $params[] = intval($_GET['disciplina_id']);
    $param_types .= "i";
}
if (!empty($_GET['data_de'])) {
    $where_clauses[] = "r.data_aula >= ?";
    $params[] = $_GET['data_de'];
    $param_types .= "s";
}
if (!empty($_GET['data_ate'])) {
    $where_clauses[] = "r.data_aula <= ?";
    $params[] = $_GET['data_ate'];
    $param_types .= "s";
}

$sql_relatorios = "
    SELECT 
        r.id as relatorio_id, r.data_aula, r.conteudo_lecionado, r.observacoes, r.material_aula_path, r.data_envio,
        r.comentario_coordenacao, r.data_comentario_coordenacao,
        p.nome as nome_professor, 
        t.nome_turma, 
        d.nome_disciplina,
        coord_comment.nome as nome_coordenador_comentario
    FROM relatorios_aula r
    JOIN professores p ON r.professor_id = p.id
    JOIN turmas t ON r.turma_id = t.id
    JOIN disciplinas d ON r.disciplina_id = d.id
    LEFT JOIN coordenadores coord_comment ON r.coordenador_id_comentario = coord_comment.id ";

if (!empty($where_clauses)) {
    $sql_relatorios .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql_relatorios .= " ORDER BY r.data_aula DESC, p.nome, t.nome_turma";

$stmt_relatorios_prepare = mysqli_prepare($conn, $sql_relatorios); // Nome da variável ajustado
if ($stmt_relatorios_prepare && !empty($params)) {
    mysqli_stmt_bind_param($stmt_relatorios_prepare, $param_types, ...$params);
}

$relatorios_result_data = null; // Nome da variável ajustado
if ($stmt_relatorios_prepare) {
    mysqli_stmt_execute($stmt_relatorios_prepare);
    $relatorios_result_data = mysqli_stmt_get_result($stmt_relatorios_prepare);
} else {
     error_log("Erro ao preparar query de relatórios (visualizar_relatorios_coordenacao.php): " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Relatórios de Aula - Coordenação ACADMIX</title>
    <link rel="stylesheet" href="css/coordenacao.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Variáveis de Cores e Temas */
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
body.theme-8bit .main-content h2,
body.theme-8bit .dashboard-section h3 {
    font-family: 'Press Start 2P', cursive;
    text-shadow: 3px 3px 0px var(--8bit-primary-color-dark);
}

body.theme-8bit .card,
body.theme-8bit .modal-content,
body.theme-8bit .chat-widget-acad {
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
    background: linear-gradient(135deg, rgba(var(--primary-color-rgb, 32, 138, 135), 0.05) 0%, rgba(var(--secondary-color-rgb, 214, 157, 42), 0.05) 100%);
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

/* Dashboard Section */
.dashboard-section {
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    background-color: var(--card-background);
    box-shadow: var(--card-shadow);
}

body.theme-8bit .dashboard-section {
    background-color: var(--8bit-card-background);
    box-shadow: var(--8bit-card-shadow);
    border: 2px solid var(--8bit-border-color);
}

.dashboard-section h3 {
    font-size: 1.4rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    color: var(--primary-color);
    /* Mantém a cor original do design */
    border-bottom: 2px solid var(--primary-color);
    /* Mantém a cor original do design */
}

body.theme-8bit .dashboard-section h3 {
    color: var(--8bit-primary-color);
    border-bottom: 2px dashed var(--8bit-primary-color);
}

/* Filter Form */
.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
    margin-bottom: 1.5rem;
}

.filter-form .form-group {
    display: flex;
    flex-direction: column;
}

.filter-form label {
    font-size: 0.85rem;
    margin-bottom: 0.3rem;
    color: var(--text-color-light);
}

body.theme-8bit .filter-form label {
    color: var(--8bit-text-color-light);
}

.filter-form select,
.filter-form input[type="date"] {
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.9rem;
    border: 1px solid var(--border-color);
    background-color: var(--card-background);
    color: var(--text-color);
}

body.theme-8bit .filter-form select,
body.theme-8bit .filter-form input[type="date"] {
    border: 1px dashed var(--8bit-border-color);
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
}

.filter-form button,
.filter-form a.clear-filter {
    padding: 0.6rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

/* Estilos de botões do filtro */
.filter-form button {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    color: var(--button-text-color);
}

.filter-form button:hover {
    background: linear-gradient(135deg, var(--primary-color-dark) 0%, #145A57 100%);
}

.filter-form a.clear-filter.button-secondary {
    background: linear-gradient(135deg, var(--accent-color) 0%, #5a6268 100%);
    color: var(--button-text-color);
}

.filter-form a.clear-filter.button-secondary:hover {
    background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
}

body.theme-8bit .filter-form button {
    background: var(--8bit-primary-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
}

body.theme-8bit .filter-form button:hover {
    background: var(--8bit-primary-color-dark);
}

body.theme-8bit .filter-form a.clear-filter.button-secondary {
    background: var(--8bit-accent-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
}

body.theme-8bit .filter-form a.clear-filter.button-secondary:hover {
    background: var(--8bit-accent-color);
    /* Pode ser um tom diferente */
}

/* Tabela de Relatórios */
.relatorios-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
    font-size: 0.9rem;
}

.relatorios-table th,
.relatorios-table td {
    padding: 0.75rem;
    text-align: left;
    border: 1px solid var(--border-color);
    color: var(--text-color);
}

body.theme-8bit .relatorios-table th,
body.theme-8bit .relatorios-table td {
    border: 1px dashed var(--8bit-border-color);
    color: var(--8bit-text-color);
}

.relatorios-table th {
    background-color: var(--background-color-offset);
    font-weight: 600;
}

body.theme-8bit .relatorios-table th {
    background-color: var(--8bit-background-color-offset);
}

.relatorios-table tr:nth-child(even) {
    background-color: var(--background-color);
}

body.theme-8bit .relatorios-table tr:nth-child(even) {
    background-color: var(--8bit-background-color);
}

.relatorios-table tr:hover {
    background-color: var(--hover-background-color);
}

body.theme-8bit .relatorios-table tr:hover {
    background-color: var(--8bit-hover-background-color);
}


.relatorios-table .actions-cell button {
    font-size: 0.8rem;
    padding: 0.3rem 0.6rem;
    margin-right: 5px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-color-dark));
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.relatorios-table .actions-cell button:hover {
    background: linear-gradient(135deg, var(--primary-color-dark), #145A57);
}

body.theme-8bit .relatorios-table .actions-cell button {
    background: var(--8bit-primary-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
}

body.theme-8bit .relatorios-table .actions-cell button:hover {
    background: var(--8bit-primary-color-dark);
}

.relatorios-table .comentario-coord-cell {
    font-style: italic;
    font-size: 0.85rem;
    color: var(--text-color-light);
}

body.theme-8bit .relatorios-table .comentario-coord-cell {
    color: var(--8bit-text-color-light);
}

.material-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: bold;
}

.material-link:hover {
    text-decoration: underline;
}

body.theme-8bit .material-link {
    color: var(--8bit-primary-color);
}


/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
    /* Corrected to always have a background */
}

.modal-content {
    background-color: var(--card-background);
    margin: 5% auto;
    padding: 20px;
    border: 1px solid var(--border-color);
    width: 90%;
    max-width: 700px;
    border-radius: 8px;
    position: relative;
    box-shadow: var(--card-shadow);
    color: var(--text-color);
}

body.theme-8bit .modal-content {
    background-color: var(--8bit-card-background);
    border: 2px solid var(--8bit-border-color);
    box-shadow: var(--8bit-card-shadow);
    color: var(--8bit-text-color);
}


.modal-close-button {
    color: var(--text-color-alt);
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.modal-close-button:hover {
    color: var(--text-color);
}

body.theme-8bit .modal-close-button {
    color: var(--8bit-text-color-alt);
}

body.theme-8bit .modal-close-button:hover {
    color: var(--8bit-text-color);
}

.modal-content h4 {
    margin-top: 0;
    font-size: 1.5rem;
    color: var(--primary-color);
}

body.theme-8bit .modal-content h4 {
    color: var(--8bit-primary-color);
}

.report-detail {
    margin-bottom: 0.8rem;
}

.report-detail strong {
    color: var(--primary-color-dark);
}

body.theme-8bit .report-detail strong {
    color: var(--8bit-primary-color-dark);
}

.report-detail p,
.report-detail div {
    white-space: pre-wrap;
    background: var(--background-color-offset);
    padding: 10px;
    border-radius: 4px;
    border: 1px solid var(--border-color-soft);
    color: var(--text-color);
}

body.theme-8bit .report-detail p,
body.theme-8bit .report-detail div {
    background: var(--8bit-background-color-offset);
    border: 1px dashed var(--8bit-border-color-soft);
    color: var(--8bit-text-color);
}

.modal-content textarea {
    width: calc(100% - 22px);
    min-height: 100px;
    padding: 10px;
    margin-top: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
    border: 1px solid var(--border-color);
    background-color: var(--card-background);
    color: var(--text-color);
}

body.theme-8bit .modal-content textarea {
    border: 1px dashed var(--8bit-border-color);
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
}

.modal-content button#salvarComentarioBtn {
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    background-color: var(--primary-color);
    color: var(--button-text-color);
}

.modal-content button#salvarComentarioBtn:hover {
    background-color: var(--primary-color-dark);
}

body.theme-8bit .modal-content button#salvarComentarioBtn {
    background-color: var(--8bit-primary-color);
    color: var(--8bit-button-text-color);
    border: 1px solid var(--8bit-border-color);
}

body.theme-8bit .modal-content button#salvarComentarioBtn:hover {
    background-color: var(--8bit-primary-color-dark);
}

#comentarioStatus {
    font-size: 0.9em;
    margin-top: 10px;
    color: var(--text-color-light);
}

.status-message {
    padding: 8px 12px;
    border-radius: 4px;
    margin-top: 10px;
}

.status-message.info-message {
    background-color: rgba(var(--info-color-rgb, 23, 162, 184), 0.1);
    border: 1px solid var(--info-color);
    color: var(--info-color);
}

.status-message.status-success {
    background-color: rgba(var(--success-color-rgb, 40, 167, 69), 0.1);
    border: 1px solid var(--success-color);
    color: var(--success-color);
}

.status-message.status-error {
    background-color: rgba(var(--danger-color-rgb, 220, 53, 69), 0.1);
    border: 1px solid var(--danger-color);
    color: var(--danger-color);
}

body.theme-8bit .status-message.info-message {
    background-color: rgba(52, 152, 219, 0.1);
    border: 1px dashed var(--8bit-info-color);
    color: var(--8bit-info-color);
}

body.theme-8bit .status-message.status-success {
    background-color: rgba(39, 174, 96, 0.1);
    border: 1px dashed var(--8bit-success-color);
    color: var(--8bit-success-color);
}

body.theme-8bit .status-message.status-error {
    background-color: rgba(231, 76, 60, 0.1);
    border: 1px dashed var(--8bit-danger-color);
    color: var(--8bit-danger-color);
}


/* Mensagens de "nenhum dado" */
.no-data-message {
    text-align: center;
    padding: 1.5rem;
    font-size: 1rem;
    color: var(--text-color-alt);
    background: rgba(var(--primary-color-rgb, 32, 138, 135), 0.05);
    border-radius: 8px;
    border: 1px dashed rgba(var(--primary-color-rgb, 32, 138, 135), 0.2);
    margin-top: 1rem;
}

body.theme-8bit .no-data-message {
    background: rgba(0, 128, 128, 0.05);
    border: 1px dashed rgba(0, 128, 128, 0.2);
    color: var(--8bit-text-color-alt);
}

/* Sidebar escondida */
.sidebar.hidden {
    transform: translateX(-100%);
    width: 0;
    padding: 0;
    opacity: 0;
}

/* --- FIX: ADJUST MAIN-CONTENT WHEN SIDEBAR IS HIDDEN TO CENTER --- */
.container.full-width .main-content {
    flex: 1 1 auto;
    /* Allows it to grow/shrink */
    max-width: 1200px;
    /* Limit content width for readability on large screens */
    margin: 0 auto;
    /* Centers the content horizontally */
    /* Padding is already defined on .main-content itself */
}


/* Responsive Design */
@media (max-width: 992px) {
    .filter-form {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-form .form-group {
        width: 100%;
    }

    .filter-form select,
    .filter-form input[type="date"] {
        width: 100%;
    }
}

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
        /* Added shadow for mobile sidebar */
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .main-content {
        padding: 1.5rem;
        max-width: unset;
        /* Remove max-width on smaller screens */
        margin: 0;
        /* Remove auto margin on smaller screens */
    }

    header {
        padding: 1rem;
    }

    header h1 {
        font-size: 1.3rem;
    }

    .relatorios-table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
        /* Impede que o conteúdo das células quebre */
    }

    .relatorios-table thead,
    .relatorios-table tbody,
    .relatorios-table th,
    .relatorios-table td,
    .relatorios-table tr {
        display: block;
        /* Força os elementos da tabela a se comportarem como blocos */
    }

    .relatorios-table thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
        /* Esconde o cabeçalho original da tabela */
    }

    .relatorios-table tr {
        border: 1px solid var(--border-color);
        margin-bottom: 1.5rem;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: var(--card-shadow);
        background-color: var(--card-background);
        position: relative;
        padding: 1rem;
        /* Adiciona padding para os "cards" */
    }

    body.theme-8bit .relatorios-table tr {
        border: 2px dashed var(--8bit-border-color);
        background-color: var(--8bit-card-background);
        box-shadow: var(--8bit-card-shadow);
    }

    .relatorios-table td {
        border: none;
        position: relative;
        padding-left: 50%;
        /* Espaço para o pseudo-elemento da label */
        text-align: right;
        padding-bottom: 0.5rem;
        /* Espaçamento entre itens */
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .relatorios-table td::before {
        content: attr(data-label);
        /* Usa o atributo data-label para mostrar o cabeçalho */
        position: absolute;
        left: 10px;
        width: 45%;
        padding-right: 10px;
        white-space: nowrap;
        text-align: left;
        font-weight: bold;
        color: var(--primary-color-dark);
        /* Cor para as labels */
    }

    body.theme-8bit .relatorios-table td::before {
        color: var(--8bit-primary-color-dark);
    }

    /* Ajustes específicos para a célula de ações */
    .relatorios-table .actions-cell {
        text-align: center;
        padding-left: 10px;
        /* Reset padding para centralizar */
        justify-content: center;
        border-top: 1px solid var(--border-color-soft);
        /* Separador visual */
        padding-top: 1rem;
        margin-top: 0.5rem;
    }

    body.theme-8bit .relatorios-table .actions-cell {
        border-top: 1px dashed var(--8bit-border-color-soft);
    }

    .relatorios-table .actions-cell button {
        width: 100%;
        /* Botão ocupa a largura total no mobile */
        margin-right: 0;
        margin-bottom: 0.5rem;
    }

    .relatorios-table .actions-cell button:last-child {
        margin-bottom: 0;
    }

    /* Esconde o material_link no mobile para evitar quebra de layout, o acesso será via modal */
    .relatorios-table td:nth-child(6) {
        /* Coluna Material */
        display: none;
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


/* Estilos específicos do chat */
.chat-widget-acad {
    position: fixed;
    bottom: 0;
    right: 20px;
    width: 320px;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    overflow: hidden;
    transition: height 0.3s ease-in-out;
    background-color: var(--background-color);
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.chat-widget-acad.chat-collapsed {
    height: 45px;
}

.chat-widget-acad.chat-expanded {
    height: 450px;
}

.chat-header-acad {
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: var(--primary-color);
    color: var(--button-text-color);
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.chat-header-acad span {
    font-weight: bold;
}

.chat-toggle-btn-acad {
    background: none;
    border: none;
    color: var(--button-text-color);
    font-size: 1.2rem;
    cursor: pointer;
    transition: transform 0.3s ease-in-out;
}

.chat-expanded .chat-toggle-btn-acad {
    transform: rotate(180deg);
}

.chat-body-acad {
    height: calc(100% - 45px);
    display: flex;
    flex-direction: column;
    background-color: var(--background-color);
    border-left: 1px solid var(--border-color);
    border-right: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
}

#chatUserListScreenAcad,
#chatConversationScreenAcad {
    display: flex;
    flex-direction: column;
    height: 100%;
    overflow: hidden;
}

.chat-search-container-acad {
    padding: 8px;
}

#chatSearchUserAcad {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid var(--border-color-soft);
    border-radius: 20px;
    box-sizing: border-box;
    font-size: 0.9em;
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
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid var(--border-color-soft);
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-color);
}

#chatUserListUlAcad li:hover {
    background-color: var(--hover-background-color);
}

#chatUserListUlAcad li img {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
}

#chatUserListUlAcad li .chat-user-name-acad {
    flex-grow: 1;
    font-size: 0.9em;
}

.chat-user-professor-acad .chat-user-name-acad {
    font-weight: bold;
}

.chat-user-coordenador-acad .chat-user-name-acad {
    font-weight: bold;
    font-style: italic;
}

.teacher-icon-acad {
    margin-left: 5px;
    color: var(--primary-color);
    font-size: 0.9em;
}

.student-icon-acad {
    margin-left: 5px;
    color: var(--accent-color);
    font-size: 0.9em;
}

.coord-icon-acad {
    margin-left: 5px;
    color: var(--info-color);
    font-size: 0.9em;
}

.chat-conversation-header-acad {
    padding: 8px 10px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid var(--border-color-soft);
    background-color: var(--background-color-offset);
    gap: 10px;
}

#chatBackToListBtnAcad {
    background: none;
    border: none;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 5px;
    color: var(--primary-color);
}

.chat-conversation-photo-acad {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
}

#chatConversationUserNameAcad {
    font-weight: bold;
    font-size: 0.95em;
    color: var(--text-color);
}

#chatMessagesContainerAcad {
    flex-grow: 1;
    padding: 10px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.message-acad {
    padding: 8px 12px;
    border-radius: 15px;
    max-width: 75%;
    word-wrap: break-word;
    font-size: 0.9em;
}

.message-acad.sent-acad {
    background-color: var(--primary-color-light);
    color: var(--text-color);
    align-self: flex-end;
    border-bottom-right-radius: 5px;
}

.message-acad.received-acad {
    background-color: var(--accent-color-extra-light);
    color: var(--text-color);
    align-self: flex-start;
    border-bottom-left-radius: 5px;
}

.message-acad.error-acad {
    background-color: var(--danger-color);
    color: var(--button-text-color);
    align-self: flex-end;
    border: 1px solid var(--danger-color);
}

.chat-message-input-area-acad {
    display: flex;
    padding: 8px 10px;
    border-top: 1px solid var(--border-color-soft);
    background-color: var(--background-color-offset);
    gap: 8px;
}

#chatMessageInputAcad {
    flex-grow: 1;
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 20px;
    resize: none;
    font-size: 0.9em;
    min-height: 20px;
    max-height: 80px;
    overflow-y: auto;
    background-color: var(--card-background);
    color: var(--text-color);
}

#chatSendMessageBtnAcad {
    background: var(--primary-color);
    color: var(--button-text-color);
    border: none;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

#chatSendMessageBtnAcad:hover {
    background: var(--primary-color-dark);
}

/* Estilos específicos para o tema 8bit no chat */
body.theme-8bit .chat-widget-acad {
    box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.5);
    border: 2px solid var(--8bit-border-color);
}

body.theme-8bit .chat-header-acad {
    background-color: var(--8bit-primary-color);
    color: var(--8bit-button-text-color);
    border: 2px solid var(--8bit-border-color);
    border-bottom: none;
}

body.theme-8bit .chat-toggle-btn-acad {
    color: var(--8bit-button-text-color);
}

body.theme-8bit .chat-body-acad {
    background-color: var(--8bit-background-color);
    border: 2px solid var(--8bit-border-color);
    border-top: none;
    border-radius: 0 0 10px 10px;
}

body.theme-8bit #chatSearchUserAcad {
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
    border: 1px dashed var(--8bit-border-color);
}

body.theme-8bit #chatUserListUlAcad li {
    color: var(--8bit-text-color);
    border-bottom: 1px dashed var(--8bit-border-color-soft);
}

body.theme-8bit #chatUserListUlAcad li:hover {
    background-color: var(--8bit-hover-background-color);
}

body.theme-8bit .chat-conversation-header-acad {
    background-color: var(--8bit-background-color-offset);
    border-bottom: 1px dashed var(--8bit-border-color-soft);
}

body.theme-8bit #chatBackToListBtnAcad {
    color: var(--8bit-primary-color);
}

body.theme-8bit #chatConversationUserNameAcad {
    color: var(--8bit-text-color);
}

body.theme-8bit #chatMessagesContainerAcad {
    scrollbar-color: var(--8bit-primary-color) var(--8bit-background-color-offset);
}

body.theme-8bit #chatMessagesContainerAcad::-webkit-scrollbar-thumb {
    background: var(--8bit-primary-color);
    border: 1px solid var(--8bit-border-color);
}

body.theme-8bit #chatMessagesContainerAcad::-webkit-scrollbar-track {
    background: var(--8bit-background-color-offset);
}


body.theme-8bit .message-acad.sent-acad {
    background-color: var(--8bit-primary-color-light);
    color: var(--8bit-text-color);
}

body.theme-8bit .message-acad.received-acad {
    background-color: var(--8bit-accent-color-extra-light);
    color: var(--8bit-text-color);
}

body.theme-8bit .chat-message-input-area-acad {
    background-color: var(--8bit-background-color-offset);
    border-top: 1px dashed var(--8bit-border-color-soft);
}

body.theme-8bit #chatMessageInputAcad {
    background-color: var(--8bit-card-background);
    color: var(--8bit-text-color);
    border: 1px dashed var(--8bit-border-color);
}

body.theme-8bit #chatSendMessageBtnAcad {
    background: var(--8bit-primary-color);
    color: var(--8bit-button-text-color);
    border: 1px solid var(--8bit-border-color);
}

body.theme-8bit #chatSendMessageBtnAcad:hover {
    background: var(--8bit-primary-color-dark);
}
        /* Estilos da página visualizar_relatorios_coordenacao.php */
        .dashboard-section { /* background-color, box-shadow virão do tema com .card */ padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .dashboard-section h3 { font-size: 1.4rem; margin-bottom: 1rem; padding-bottom: 0.5rem; /* color e border-bottom virão do tema */ }
        .filter-form { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; margin-bottom: 1.5rem; }
        .filter-form .form-group { display: flex; flex-direction: column; }
        .filter-form label { font-size: 0.85rem; margin-bottom: 0.3rem; /* color virá do tema */ }
        .filter-form select, .filter-form input[type="date"] { padding: 0.5rem; border-radius: 4px; font-size:0.9rem; /* border virá do tema */ }
        .filter-form button, .filter-form a.clear-filter { padding: 0.6rem 1rem; border: none; border-radius: 4px; cursor: pointer; font-size:0.9rem; text-decoration:none; }
        .relatorios-table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9rem; }
        .relatorios-table th, .relatorios-table td { padding: 0.75rem; text-align: left; /* border virá do tema */ }
        .relatorios-table .actions-cell button { font-size: 0.8rem; padding: 0.3rem 0.6rem; margin-right: 5px; }
        .relatorios-table .comentario-coord-cell { font-style: italic; font-size: 0.85rem; }
        .material-link { /* color virá do tema para links */ }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
        .modal-content { /* background-color e box-shadow virão do tema com .card */ margin: 5% auto; padding: 20px; border: 1px solid var(--border-color, #888); width: 90%; max-width: 700px; border-radius: 8px; position: relative; }
        .modal-close-button { color: var(--text-color-muted, #aaa); float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-close-button:hover { color: var(--text-color, black); }
        .modal-content h4 { margin-top: 0; font-size: 1.5rem; /* color virá do tema */ }
        .report-detail { margin-bottom: 0.8rem; }
        .report-detail strong { /* color virá do tema */ }
        .report-detail p, .report-detail div { white-space: pre-wrap; background: var(--background-color-offset, #f9f9f9); padding: 10px; border-radius:4px; border:1px solid var(--border-color-soft, #eee); }
        .modal-content textarea { width: calc(100% - 22px); min-height: 100px; padding: 10px; margin-top:10px; margin-bottom:10px; border-radius:4px; /* border, background-color, color virão do tema */ }
        .modal-content button#salvarComentarioBtn { padding:10px 15px; border:none; border-radius:4px; cursor:pointer; /* background-color, color virão do tema */ }
        #comentarioStatus { font-size:0.9em; margin-top:10px; }

        /* --- CSS NOVO CHAT ACADÊMICO --- */
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
        <h1>ACADMIX - Relatórios de Aula (Coordenação)</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>
    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?>
        </nav>
        <main class="main-content">
            <h2 class="page-title">Visualizar Relatórios de Aula</h2>
            
            <section class="dashboard-section card">
                <h3>Filtrar Relatórios</h3>
                <form method="GET" action="visualizar_relatorios_coordenacao.php" class="filter-form">
                    <div class="form-group">
                        <label for="professor_id">Professor:</label>
                        <select name="professor_id" id="professor_id" class="input-field"><option value="">Todos</option>
                            <?php if($professores_query_result) while($p = mysqli_fetch_assoc($professores_query_result)): ?>
                                <option value="<?php echo $p['id']; ?>" <?php if(isset($_GET['professor_id']) && $_GET['professor_id'] == $p['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($p['nome']); ?>
                                </option>
                            <?php endwhile; if($professores_query_result) mysqli_data_seek($professores_query_result,0); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="turma_id">Turma:</label>
                        <select name="turma_id" id="turma_id" class="input-field"><option value="">Todas</option>
                            <?php if($turmas_query_result) while($tu = mysqli_fetch_assoc($turmas_query_result)): ?>
                                <option value="<?php echo $tu['id']; ?>" <?php if(isset($_GET['turma_id']) && $_GET['turma_id'] == $tu['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($tu['nome_turma']); ?>
                                </option>
                            <?php endwhile; if($turmas_query_result) mysqli_data_seek($turmas_query_result,0); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="disciplina_id">Disciplina:</label>
                        <select name="disciplina_id" id="disciplina_id" class="input-field"><option value="">Todas</option>
                            <?php if($disciplinas_query_result) while($d = mysqli_fetch_assoc($disciplinas_query_result)): ?>
                                <option value="<?php echo $d['id']; ?>" <?php if(isset($_GET['disciplina_id']) && $_GET['disciplina_id'] == $d['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($d['nome_disciplina']); ?>
                                </option>
                            <?php endwhile; if($disciplinas_query_result) mysqli_data_seek($disciplinas_query_result,0); ?>
                        </select>
                    </div>
                    <div class="form-group"><label for="data_de">De:</label><input type="date" name="data_de" id="data_de" class="input-field" value="<?php echo isset($_GET['data_de']) ? htmlspecialchars($_GET['data_de']) : ''; ?>"></div>
                    <div class="form-group"><label for="data_ate">Até:</label><input type="date" name="data_ate" id="data_ate" class="input-field" value="<?php echo isset($_GET['data_ate']) ? htmlspecialchars($_GET['data_ate']) : ''; ?>"></div>
                    <button type="submit" class="button"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="visualizar_relatorios_coordenacao.php" class="clear-filter button button-secondary"><i class="fas fa-times"></i> Limpar</a>
                </form>
            </section>

            <section class="dashboard-section card">
                <h3>Relatórios Recebidos</h3>
                <?php if($relatorios_result_data && mysqli_num_rows($relatorios_result_data) > 0): ?>
                <table class="relatorios-table table"> <thead><tr><th>Data Aula</th><th>Professor</th><th>Turma</th><th>Disciplina</th><th>Conteúdo (Início)</th><th>Material</th><th>Comentário Coord.</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php while($rel = mysqli_fetch_assoc($relatorios_result_data)): ?>
                        <tr>
                            <td><?php echo date("d/m/Y", strtotime($rel['data_aula'])); ?></td>
                            <td><?php echo htmlspecialchars($rel['nome_professor']); ?></td>
                            <td><?php echo htmlspecialchars($rel['nome_turma']); ?></td>
                            <td><?php echo htmlspecialchars($rel['nome_disciplina']); ?></td>
                            <td><?php echo htmlspecialchars(mb_substr($rel['conteudo_lecionado'], 0, 40)) . (mb_strlen($rel['conteudo_lecionado']) > 40 ? '...' : ''); ?></td>
                            <td><?php if(!empty($rel['material_aula_path'])): ?><a href="<?php echo htmlspecialchars($rel['material_aula_path']); ?>" target="_blank" class="material-link"><i class="fas fa-paperclip"></i> Ver</a><?php else: ?>-<?php endif; ?></td>
                            <td class="comentario-coord-cell"><?php echo !empty($rel['comentario_coordenacao']) ? (htmlspecialchars(mb_substr($rel['comentario_coordenacao'], 0, 30)) . (mb_strlen($rel['comentario_coordenacao']) > 30 ? '...' : '')) : 'Nenhum'; ?></td>
                            <td class="actions-cell">
                                <button onclick='abrirModalRelatorio(<?php echo json_encode($rel, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' class="button button-small"><i class="fas fa-eye"></i> Detalhes/Comentar</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?><p class="no-data-message info-message">Nenhum relatório encontrado com os filtros aplicados ou nenhum relatório enviado.</p><?php endif; ?>
            </section>
        </main>
    </div>

    <div id="relatorioModal" class="modal">
        <div class="modal-content card"> <span class="modal-close-button" onclick="document.getElementById('relatorioModal').style.display='none'">&times;</span>
            <h4>Detalhes do Relatório de Aula</h4>
            <div id="modalReportDetails">
                <p class="report-detail"><strong>Professor:</strong> <span id="modalProfNome"></span></p>
                <p class="report-detail"><strong>Turma:</strong> <span id="modalTurmaNome"></span></p>
                <p class="report-detail"><strong>Disciplina:</strong> <span id="modalDisciplinaNome"></span></p>
                <p class="report-detail"><strong>Data da Aula:</strong> <span id="modalDataAula"></span></p>
                <p class="report-detail"><strong>Conteúdo Lecionado:</strong></p>
                <div id="modalConteudo"></div>
                <p class="report-detail"><strong>Observações do Professor:</strong></p>
                <div id="modalObsProfessor"></div>
                <p class="report-detail"><strong>Material Anexado:</strong> <span id="modalMaterialLink"></span></p>
            </div>
            <hr>
            <h4>Comentário da Coordenação</h4>
            <form id="comentarioForm">
                <input type="hidden" name="relatorio_id_comentario" id="relatorio_id_comentario">
                <textarea name="comentario_coordenacao_texto" id="comentario_coordenacao_texto" placeholder="Adicione seu comentário aqui..." class="input-field"></textarea>
                <button type="button" id="salvarComentarioBtn" onclick="salvarComentario()" class="button button-primary">Salvar Comentário</button>
                <div id="comentarioStatus"></div>
            </form>
        </div>
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

        // Script do Modal (existente)
        const relatorioModal = document.getElementById('relatorioModal');
        if (relatorioModal) { // Adicionada verificação para evitar erro se o modal não existir
            const spanClose = relatorioModal.querySelector('.modal-close-button');

            function abrirModalRelatorio(relatorioData) {
                document.getElementById('modalProfNome').textContent = relatorioData.nome_professor;
                document.getElementById('modalTurmaNome').textContent = relatorioData.nome_turma;
                document.getElementById('modalDisciplinaNome').textContent = relatorioData.nome_disciplina;
                // Ajuste para data da aula, considerando que ela pode vir como YYYY-MM-DD
                const dataAulaObj = new Date(relatorioData.data_aula + 'T00:00:00'); // Adiciona T00:00:00 para evitar problemas de fuso na conversão
                document.getElementById('modalDataAula').textContent = dataAulaObj.toLocaleDateString('pt-BR');
                
                document.getElementById('modalConteudo').textContent = relatorioData.conteudo_lecionado;
                document.getElementById('modalObsProfessor').textContent = relatorioData.observacoes || 'Nenhuma.';
                
                const materialLinkEl = document.getElementById('modalMaterialLink');
                if (relatorioData.material_aula_path) {
                    materialLinkEl.innerHTML = `<a href="${relatorioData.material_aula_path}" target="_blank" class="material-link"><i class="fas fa-paperclip"></i> Visualizar Material</a>`;
                } else {
                    materialLinkEl.textContent = 'Nenhum material anexado.';
                }

                document.getElementById('relatorio_id_comentario').value = relatorioData.relatorio_id;
                document.getElementById('comentario_coordenacao_texto').value = relatorioData.comentario_coordenacao || '';
                document.getElementById('comentarioStatus').textContent = ''; 
                if(relatorioData.data_comentario_coordenacao && relatorioData.nome_coordenador_comentario) {
                     document.getElementById('comentarioStatus').textContent = `Último comentário por ${relatorioData.nome_coordenador_comentario} em ${new Date(relatorioData.data_comentario_coordenacao).toLocaleString('pt-BR')}.`;
                }
                relatorioModal.style.display = 'block';
            }

            if (spanClose) spanClose.onclick = function() { relatorioModal.style.display = 'none'; }
            window.onclick = function(event) { if (event.target == relatorioModal) { relatorioModal.style.display = 'none'; } }

            function salvarComentario() {
                const form = document.getElementById('comentarioForm');
                const formData = new FormData(form);
                const statusDiv = document.getElementById('comentarioStatus');
                statusDiv.textContent = 'Salvando...';
                statusDiv.className = 'status-message info-message'; // Classe para feedback

                fetch('salvar_comentario_relatorio_coordenacao.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.textContent = 'Comentário salvo com sucesso! Recarregando a página...';
                        statusDiv.className = 'status-message status-success';
                        setTimeout(() => { window.location.reload(); }, 1500);
                    } else {
                        statusDiv.textContent = 'Erro: ' + (data.message || 'Não foi possível salvar o comentário.');
                        statusDiv.className = 'status-message status-error';
                    }
                })
                .catch(error => {
                    console.error('Erro no fetch:', error);
                    statusDiv.textContent = 'Erro de comunicação ao salvar o comentário.';
                    statusDiv.className = 'status-message status-error';
                });
            }
            // Tornar salvarComentario global ou anexar ao botão de forma diferente se o onclick não funcionar bem
            // window.salvarComentario = salvarComentario; // Se necessário
            const salvarBtn = document.getElementById('salvarComentarioBtn');
            if(salvarBtn) salvarBtn.addEventListener('click', salvarComentario);

        } // Fim da verificação if (relatorioModal)
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const currentUserId = <?php echo json_encode($coordenador_id); ?>;
        const currentUserSessionRole = <?php echo json_encode($_SESSION['role']); ?>; 
        let currentUserChatRole = '';

        if (currentUserSessionRole === 'aluno') {
            currentUserChatRole = 'aluno'; 
        } else if (currentUserSessionRole === 'docente') {
            currentUserChatRole = 'professor'; 
        } else if (currentUserSessionRole === 'coordenacao') {
            currentUserChatRole = 'coordenador';
        } else {
            console.warn("Chat: Papel do usuário não reconhecido:", currentUserSessionRole);
        }
        
        const currentUserTurmaIdForStudent = 0; 

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
            const isCollapsed = chatWidget.classList.contains('chat-collapsed');
            if (isCollapsed) {
                chatWidget.classList.remove('chat-collapsed');
                chatWidget.classList.add('chat-expanded');
                chatBody.style.display = 'flex';
                chatToggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i>';
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
                chatBody.style.display = 'none';
                chatToggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
            }
        }

        function showUserListScreen() {
            userListScreen.style.display = 'flex';
            conversationScreen.style.display = 'none';
        }

        function showConversationScreen(contact, shouldFetchMessages = true) {
            currentConversationWith = contact; 
            userListScreen.style.display = 'none';
            conversationScreen.style.display = 'flex';
            conversationUserName.textContent = contact.nome;
            
            let photoToUse = defaultUserPhoto;
            if (contact.role === 'professor') photoToUse = defaultProfessorPhoto;
            else if (contact.role === 'coordenador') photoToUse = defaultCoordenadorPhoto;
            if (contact.foto_url) photoToUse = contact.foto_url;
            conversationUserPhoto.src = photoToUse;
            
            if (shouldFetchMessages) {
                messagesContainer.innerHTML = ''; 
                fetchAndDisplayMessages(contact.id, contact.role);
            }
            messageInput.focus();
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
                userListUl.innerHTML = '<li>Lista de contatos não disponível.</li>';
                return;
            }

            userListUl.innerHTML = '<li><i class="fas fa-spinner fa-spin"></i> Carregando contatos...</li>';
            
            try {
                const response = await fetch(`chat_api.php?action=${actionApi}`); 
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error ${response.status}: ${errorText}`);
                }
                const users = await response.json();

                if (users.error) {
                    console.error('API Erro ('+actionApi+'):', users.error);
                    userListUl.innerHTML = `<li>Erro: ${users.error}</li>`;
                    return;
                }
                allContacts = users; 
                renderUserList(allContacts);

            } catch (error) {
                console.error('Falha ao buscar contatos ('+actionApi+'):', error);
                userListUl.innerHTML = '<li>Falha ao carregar contatos.</li>';
            }
        }

        function renderUserList(usersToRender) {
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
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message-acad', type);
            messageDiv.textContent = text; 
            messagesContainer.appendChild(messageDiv);
            if (messagesContainer.scrollHeight > messagesContainer.clientHeight) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }

        async function handleSendMessage() {
            const text = messageInput.value.trim();
            if (text === '' || !currentConversationWith) return;

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
if(isset($stmt_relatorios_prepare)) mysqli_stmt_close($stmt_relatorios_prepare); // Corrigido
if(isset($conn) && $conn) mysqli_close($conn); 
?>