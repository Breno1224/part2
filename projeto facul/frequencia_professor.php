<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php';

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];

$currentPageIdentifier = 'frequencia'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

$turmas_professor = [];
$sql_turmas = "SELECT DISTINCT t.id, t.nome_turma FROM turmas t JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id WHERE ptd.professor_id = ? ORDER BY t.nome_turma";
$stmt_turmas_fetch = mysqli_prepare($conn, $sql_turmas);
if ($stmt_turmas_fetch) {
    mysqli_stmt_bind_param($stmt_turmas_fetch, "i", $professor_id);
    mysqli_stmt_execute($stmt_turmas_fetch);
    $result_turmas = mysqli_stmt_get_result($stmt_turmas_fetch);
    while ($row = mysqli_fetch_assoc($result_turmas)) {
        $turmas_professor[] = $row;
    }
    mysqli_stmt_close($stmt_turmas_fetch);
} else {
    error_log("Erro ao buscar turmas do professor (frequencia_professor.php): " . mysqli_error($conn));
}

$turma_selecionada_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
$data_aula_selecionada = isset($_GET['data_aula']) ? $_GET['data_aula'] : date('Y-m-d');
$nome_turma_selecionada = "";
$alunos_com_frequencia = [];
$professor_tem_acesso_turma = false; 

if ($turma_selecionada_id && !empty($data_aula_selecionada)) {
    foreach ($turmas_professor as $turma_p) {
        if ($turma_p['id'] == $turma_selecionada_id) {
            $professor_tem_acesso_turma = true;
            $nome_turma_selecionada = $turma_p['nome_turma'];
            break;
        }
    }
    
    if ($professor_tem_acesso_turma) {
        $sql_alunos_frequencia = "
            SELECT a.id as aluno_id, a.nome as aluno_nome, a.foto_url, f.status, f.observacao
            FROM alunos a
            LEFT JOIN frequencia f ON a.id = f.aluno_id AND f.turma_id = ? AND f.data_aula = ?
            WHERE a.turma_id = ?
            ORDER BY a.nome";
        $stmt_alunos = mysqli_prepare($conn, $sql_alunos_frequencia);
        if ($stmt_alunos) {
            mysqli_stmt_bind_param($stmt_alunos, "isi", $turma_selecionada_id, $data_aula_selecionada, $turma_selecionada_id);
            mysqli_stmt_execute($stmt_alunos);
            $result_alunos_frequencia = mysqli_stmt_get_result($stmt_alunos);
            while ($row = mysqli_fetch_assoc($result_alunos_frequencia)) {
                if (is_null($row['status'])) {
                    $row['status'] = 'P';
                }
                $alunos_com_frequencia[] = $row;
            }
            mysqli_stmt_close($stmt_alunos);
        }
    } else {
        $turma_selecionada_id = null; 
        if (isset($_GET['turma_id']) && !empty($_GET['turma_id'])) {
         $_SESSION['frequencia_status_message'] = "Você não tem acesso à turma selecionada ou ela não existe.";
         $_SESSION['frequencia_status_type'] = "status-error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registro de Frequência - ACADMIX</title>
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
body.theme-8bit .main-content h2.page-title,
body.theme-8bit .dashboard-section h3,
body.theme-8bit #modalAlunoNome {
    font-family: 'Press Start 2P', cursive;
    text-shadow: 3px 3px 0px var(--8bit-primary-color-dark);
}

body.theme-8bit .card {
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

/* Dashboard Section */
.dashboard-section {
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    background-color: var(--card-background);
    box-shadow: var(--card-shadow);
    color: var(--text-color);
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
    border-bottom: 2px solid var(--primary-color);
}

body.theme-8bit .dashboard-section h3 {
    color: var(--8bit-primary-color);
    border-bottom: 2px dashed var(--8bit-primary-color);
}

/* Form Inline */
.form-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-end;
}

.form-inline label {
    margin-right: 0.5rem;
    font-weight: bold;
    color: var(--text-color);
}

body.theme-8bit .form-inline label {
    color: var(--8bit-text-color);
}

.form-inline .input-field {
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.9rem;
    border: 1px solid var(--border-color);
    background-color: var(--background-color-offset);
    color: var(--text-color);
}

body.theme-8bit .form-inline .input-field {
    border: 1px dashed var(--8bit-border-color);
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
}


/* Chamada Table */
.chamada-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 1.5rem;
    font-size: 0.95rem;
}

.chamada-table th,
.chamada-table td {
    padding: 0.9rem;
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color-soft);
    color: var(--text-color);
}

body.theme-8bit .chamada-table th,
body.theme-8bit .chamada-table td {
    border-bottom: 1px dashed var(--8bit-border-color-soft);
    color: var(--8bit-text-color);
}

.chamada-table th {
    background-color: var(--background-color-offset);
    font-weight: 600;
}

body.theme-8bit .chamada-table th {
    background-color: var(--8bit-background-color-offset);
}

.chamada-table tbody tr:hover {
    background-color: var(--hover-background-color);
}

body.theme-8bit .chamada-table tbody tr:hover {
    background-color: var(--8bit-hover-background-color);
}


.chamada-table .aluno-nome-clickable {
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-color);
}

body.theme-8bit .chamada-table .aluno-nome-clickable {
    color: var(--8bit-text-color);
}

.chamada-table .aluno-nome-clickable img {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--border-color-soft);
}

body.theme-8bit .chamada-table .aluno-nome-clickable img {
    border: 2px dashed var(--8bit-border-color-soft);
}


.chamada-table .aluno-nome-clickable:hover span {
    text-decoration: underline;
    color: var(--primary-color);
}

body.theme-8bit .chamada-table .aluno-nome-clickable:hover span {
    color: var(--8bit-primary-color);
}


/* Status Buttons (P, F, A, FJ) */
.status-buttons {
    display: flex;
    gap: 8px;
}

.status-buttons input[type="radio"] {
    display: none;
}

.status-buttons label {
    padding: 8px 12px;
    border-radius: 20px;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: bold;
    transition: all 0.2s ease;
    min-width: 40px;
    text-align: center;
    border: 1px solid var(--border-color);
    color: var(--text-color-alt);
}

body.theme-8bit .status-buttons label {
    border: 1px dashed var(--8bit-border-color);
    color: var(--8bit-text-color-alt);
}


.status-buttons label:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.status-buttons input[type="radio"]:checked + label {
    color: white;
    border-color: transparent;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.status-buttons .status-P input[type="radio"]:checked + label {
    background-color: var(--success-color);
}

.status-buttons .status-F input[type="radio"]:checked + label {
    background-color: var(--danger-color);
}

.status-buttons .status-A input[type="radio"]:checked + label {
    background-color: var(--warning-color);
    color: var(--text-color) !important;
}

.status-buttons .status-FJ input[type="radio"]:checked + label {
    background-color: var(--info-color);
}

body.theme-8bit .status-buttons .status-P input[type="radio"]:checked + label {
    background-color: var(--8bit-success-color);
}

body.theme-8bit .status-buttons .status-F input[type="radio"]:checked + label {
    background-color: var(--8bit-danger-color);
}

body.theme-8bit .status-buttons .status-A input[type="radio"]:checked + label {
    background-color: var(--8bit-warning-color);
    color: var(--8bit-text-color) !important;
}

body.theme-8bit .status-buttons .status-FJ input[type="radio"]:checked + label {
    background-color: var(--8bit-info-color);
}


.chamada-table input[type="text"].observacao-input {
    width: 98%;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    background-color: var(--background-color-offset);
    color: var(--text-color);
}

body.theme-8bit .chamada-table input[type="text"].observacao-input {
    border: 1px dashed var(--8bit-border-color);
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
}


/* Botão Salvar Chamada */
.btn-salvar-chamada {
    display: block;
    width: auto;
    padding: 0.75rem 1.8rem;
    font-size: 1rem;
    margin: 2rem auto 0 auto;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    color: var(--button-text-color);
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(32, 138, 135, 0.3);
}

.btn-salvar-chamada:hover {
    background: linear-gradient(135deg, var(--primary-color-dark) 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(32, 138, 135, 0.4);
}

body.theme-8bit .btn-salvar-chamada {
    background: var(--8bit-primary-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
    box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.5);
}

body.theme-8bit .btn-salvar-chamada:hover {
    background: var(--8bit-primary-color-dark);
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.7);
}


/* Status Message */
.status-message {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
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


/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1001;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.6);
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.show {
    display: flex;
    opacity: 1;
}

.modal-content {
    margin: auto;
    padding: 25px;
    width: 90%;
    max-width: 550px;
    border-radius: 12px;
    position: relative;
    animation: slide-down 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background-color: var(--card-background); /* Adicionado background */
    border: 1px solid var(--border-color); /* Adicionado borda */
    box-shadow: var(--card-shadow); /* Adicionado sombra */
    color: var(--text-color); /* Adicionado cor do texto */
}

body.theme-8bit .modal-content {
    background-color: var(--8bit-card-background);
    border: 2px solid var(--8bit-border-color);
    box-shadow: var(--8bit-card-shadow);
    color: var(--8bit-text-color);
}


@keyframes slide-down {
    from {
        transform: translateY(-30px);
        opacity: 0;
    }

    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-close-button {
    color: var(--text-color-alt);
    position: absolute;
    top: 10px;
    right: 20px;
    font-size: 30px;
    font-weight: bold;
    cursor: pointer;
}

.modal-close-button:hover,
.modal-close-button:focus {
    color: var(--text-color);
}

body.theme-8bit .modal-close-button {
    color: var(--8bit-text-color-alt);
}

body.theme-8bit .modal-close-button:hover,
body.theme-8bit .modal-close-button:focus {
    color: var(--8bit-text-color);
}


#modalAlunoNome {
    margin-top: 0;
    font-size: 1.6rem;
    border-bottom: 1px solid var(--border-color-soft);
    padding-bottom: 0.8rem;
    margin-bottom: 1.5rem;
    text-align: center;
    color: var(--primary-color-dark);
}

body.theme-8bit #modalAlunoNome {
    border-bottom: 1px dashed var(--8bit-border-color-soft);
    color: var(--8bit-primary-color-dark);
}

#modalAlunoStats p {
    font-size: 1.05rem;
    line-height: 1.8;
    margin-bottom: 0.5rem;
    color: var(--text-color);
}

body.theme-8bit #modalAlunoStats p {
    color: var(--8bit-text-color);
}

#modalAlunoStats .highlight {
    font-weight: bold;
    color: var(--primary-color-dark);
}

body.theme-8bit #modalAlunoStats .highlight {
    color: var(--8bit-primary-color-dark);
}


#modalAlunoStats hr {
    border: none;
    border-top: 1px dashed var(--border-color-soft);
    margin: 1.5rem 0;
}

body.theme-8bit #modalAlunoStats hr {
    border-top: 1px dashed var(--8bit-border-color-soft);
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
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .dashboard-section { padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .dashboard-section h3 { font-size: 1.4rem; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .form-inline { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
        .form-inline label { margin-right: 0.5rem; font-weight:bold; }
        
        .chamada-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 1.5rem; }
        .chamada-table th, .chamada-table td { padding: 0.9rem; text-align: left; vertical-align: middle; border-bottom: 1px solid var(--border-color-soft); }
        .chamada-table th { background-color: var(--background-color-offset); font-weight: 600; }
        .chamada-table tbody tr:hover { background-color: var(--hover-background-color); }
        .chamada-table .aluno-nome-clickable { cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .chamada-table .aluno-nome-clickable img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; }
        .chamada-table .aluno-nome-clickable:hover span { text-decoration: underline; color: var(--primary-color); }
        
        .status-buttons { display: flex; gap: 8px; }
        .status-buttons input[type="radio"] { display: none; }
        .status-buttons label { padding: 8px 12px; border-radius: 20px; cursor: pointer; font-size: 0.9rem; font-weight: bold; transition: all 0.2s ease; min-width: 40px; text-align: center; border: 1px solid var(--border-color); color: var(--text-color-muted); }
        .status-buttons label:hover { transform: translateY(-2px); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .status-buttons input[type="radio"]:checked + label { color: white; border-color: transparent; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .status-buttons .status-P input[type="radio"]:checked + label { background-color: #28a745; } 
        .status-buttons .status-F input[type="radio"]:checked + label { background-color: #dc3545; } 
        .status-buttons .status-A input[type="radio"]:checked + label { background-color: #ffc107; color: #333 !important; } 
        .status-buttons .status-FJ input[type="radio"]:checked + label { background-color: #17a2b8; } 
        
        .chamada-table input[type="text"].observacao-input { width: 98%; }
        .btn-salvar-chamada { display: block; width: auto; padding: 0.75rem 1.8rem; font-size: 1rem; margin: 2rem auto 0 auto; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 8px; text-align:center; }
        
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
        .modal.show { display: flex; opacity: 1; }
        .modal-content { margin: auto; padding: 25px; width: 90%; max-width: 550px; border-radius: 12px; position: relative; animation: slide-down 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes slide-down { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-close-button { color: var(--text-color-muted); position: absolute; top: 10px; right: 20px; font-size: 30px; font-weight: bold; cursor: pointer; }
        .modal-close-button:hover, .modal-close-button:focus { color: var(--text-color); }
        #modalAlunoNome { margin-top: 0; font-size: 1.6rem; border-bottom: 1px solid var(--border-color-soft); padding-bottom: 0.8rem; margin-bottom: 1.5rem; text-align: center; }
        #modalAlunoStats p { font-size: 1.05rem; line-height: 1.8; margin-bottom: 0.5rem; }
        #modalAlunoStats .highlight { font-weight: bold; color: var(--primary-color-dark); }
        #modalAlunoStats hr { border: none; border-top: 1px dashed var(--border-color-soft); margin: 1.5rem 0; }

        /* ... Cole o CSS do Chat aqui ... */
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Registro de Frequência</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Registro de Frequência</h2>
            <section class="dashboard-section card">
                <h3>Selecionar Turma e Data</h3>
                <form method="GET" action="frequencia_professor.php" class="form-inline">
                     <label for="turma_id_select">Turma:</label>
                    <select name="turma_id" id="turma_id_select" required class="input-field">
                        <option value="">-- Selecione --</option>
                        <?php foreach ($turmas_professor as $turma): ?>
                            <option value="<?php echo $turma['id']; ?>" <?php echo ($turma_selecionada_id == $turma['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($turma['nome_turma']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="data_aula_select">Data:</label>
                    <input type="date" name="data_aula" id="data_aula_select" value="<?php echo htmlspecialchars($data_aula_selecionada); ?>" required class="input-field">
                    <button type="submit" class="button"><i class="fas fa-list-alt"></i> Carregar Alunos</button>
                </form>
            </section>

            <?php if(isset($_SESSION['frequencia_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['frequencia_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['frequencia_status_message']); ?>
                </div>
                <?php unset($_SESSION['frequencia_status_message']); unset($_SESSION['frequencia_status_type']); ?>
            <?php endif; ?>

            <?php if ($turma_selecionada_id && $professor_tem_acesso_turma): ?>
            <section class="dashboard-section card">
                <h3>Chamada para: <?php echo htmlspecialchars($nome_turma_selecionada); ?> - Data: <?php echo date("d/m/Y", strtotime($data_aula_selecionada)); ?></h3>
                <?php if (!empty($alunos_com_frequencia)): ?>
                <form action="salvar_frequencia.php" method="POST">
                    <input type="hidden" name="turma_id" value="<?php echo $turma_selecionada_id; ?>">
                    <input type="hidden" name="data_aula" value="<?php echo $data_aula_selecionada; ?>">
                    <div style="overflow-x:auto;">
                        <table class="chamada-table table"> 
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th width="35%">Status (P, F, A, FJ)</th>
                                    <th width="35%">Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alunos_com_frequencia as $aluno): ?>
                                <tr>
                                    <td>
                                        <span class="aluno-nome-clickable" data-aluno-id="<?php echo $aluno['aluno_id']; ?>" data-aluno-nome="<?php echo htmlspecialchars($aluno['aluno_nome']); ?>" data-turma-id="<?php echo $turma_selecionada_id; ?>">
                                            <img src="<?php echo htmlspecialchars(!empty($aluno['foto_url']) ? $aluno['foto_url'] : 'img/alunos/default_avatar.png'); ?>" alt="Foto" onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                                            <span><?php echo htmlspecialchars($aluno['aluno_nome']); ?></span>
                                        </span>
                                    </td>
                                    <td class="status-buttons">
                                        <?php $aluno_id_input = $aluno['aluno_id']; ?>
                                        <div class="status-P">
                                            <input type="radio" id="p_<?php echo $aluno_id_input; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="P" <?php echo ($aluno['status'] == 'P') ? 'checked' : ''; ?>>
                                            <label for="p_<?php echo $aluno_id_input; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>">P</label>
                                        </div>
                                        <div class="status-F">
                                            <input type="radio" id="f_<?php echo $aluno_id_input; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="F" <?php echo ($aluno['status'] == 'F') ? 'checked' : ''; ?>>
                                            <label for="f_<?php echo $aluno_id_input; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>">F</label>
                                        </div>
                                        <div class="status-A">
                                            <input type="radio" id="a_<?php echo $aluno_id_input; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="A" <?php echo ($aluno['status'] == 'A') ? 'checked' : ''; ?>>
                                            <label for="a_<?php echo $aluno_id_input; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>">A</label>
                                        </div>
                                        <div class="status-FJ">
                                            <input type="radio" id="fj_<?php echo $aluno_id_input; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="FJ" <?php echo ($aluno['status'] == 'FJ') ? 'checked' : ''; ?>>
                                            <label for="fj_<?php echo $aluno_id_input; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>">FJ</label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="frequencia[<?php echo $aluno_id_input; ?>][observacao]" class="observacao-input input-field" value="<?php echo htmlspecialchars($aluno['observacao'] ?? ''); ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn-salvar-chamada button button-primary"><i class="fas fa-save"></i> Salvar Chamada</button>
                </form>
                <?php else: ?>
                    <p class="no-data-message info-message">Nenhum aluno encontrado nesta turma para a data selecionada.</p>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </main>
    </div>

    <div id="frequenciaModal" class="modal">
        <div class="modal-content card">
            <span class="modal-close-button">&times;</span>
            <h3 id="modalAlunoNome"></h3>
            <div id="modalAlunoStats"><p>Carregando estatísticas...</p></div>
        </div>
    </div>

    <div id="academicChatWidget" class="chat-widget-acad chat-collapsed">
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

        // Script do Modal de Estatísticas
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('frequenciaModal');
            if (!modal) return;
            
            const modalCloseButton = modal.querySelector('.modal-close-button');
            const modalAlunoNomeEl = document.getElementById('modalAlunoNome');
            const modalAlunoStatsEl = document.getElementById('modalAlunoStats');

            document.querySelectorAll('.aluno-nome-clickable').forEach(item => {
                item.addEventListener('click', function() {
                    const alunoId = this.dataset.alunoId;
                    const alunoNome = this.dataset.alunoNome;
                    const turmaId = this.dataset.turmaId; 
                    
                    if (!alunoId || !turmaId) {
                        console.error("Dados do aluno ou turma faltando no elemento clicado.");
                        return;
                    }
                    
                    modalAlunoNomeEl.textContent = "Estatísticas de: " + alunoNome;
                    modalAlunoStatsEl.innerHTML = "<p><i class='fas fa-spinner fa-spin'></i> Buscando dados...</p>";
                    modal.classList.add('show');

                    fetch(`ajax_busca_stats_frequencia.php?aluno_id=${alunoId}&turma_id=${turmaId}`)
                    .then(response => {
                        if (!response.ok) { throw new Error('Erro de rede ou servidor: ' + response.status); }
                        return response.json();
                    })
                    .then(data => {
                        if(data.error){
                            modalAlunoStatsEl.innerHTML = `<p class="error-message">${data.error}</p>`;
                        } else {
                            let totalAulasValidas = (data.total_aulas || 0) - (data.atestados || 0) - (data.faltas_justificadas || 0);
                            if (totalAulasValidas < 0) totalAulasValidas = 0;

                            let percentualPresenca = 0;
                            if (totalAulasValidas > 0) {
                                percentualPresenca = ((data.presencas || 0) / totalAulasValidas) * 100;
                            } else if ((data.presencas || 0) > 0) {
                                percentualPresenca = 100;
                            }

                            let statsHtml = `<p><span class="highlight">Total de Aulas Registradas:</span> ${data.total_aulas || 0}</p>`;
                            statsHtml += `<p><span class="highlight">Presenças (P):</span> ${data.presencas || 0}</p>`;
                            statsHtml += `<p><span class="highlight">Faltas (F):</span> ${data.faltas || 0}</p>`;
                            statsHtml += `<p><span class="highlight">Atestados (A):</span> ${data.atestados || 0}</p>`;
                            statsHtml += `<p><span class="highlight">Faltas Justificadas (FJ):</span> ${data.faltas_justificadas || 0}</p><hr>`;
                            statsHtml += `<p><span class="highlight">Percentual de Presença (sobre aulas válidas):</span> <strong>${percentualPresenca.toFixed(1)}%</strong></p>`;
                            modalAlunoStatsEl.innerHTML = statsHtml;
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar estatísticas:', error);
                        modalAlunoStatsEl.innerHTML = "<p class='error-message'>Erro de comunicação ao carregar dados.</p>";
                    });
                });
            });

            if (modalCloseButton) {
                 modalCloseButton.onclick = function() { modal.classList.remove('show'); }
            }
            window.onclick = function(event) { if (event.target == modal) { modal.classList.remove('show'); } }
        });
    </script>
    
    </body>
</html>
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>