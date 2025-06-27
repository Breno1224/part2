<?php
session_start(); // GARANTIR que está no topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id']; // Essencial para o chat
// $_SESSION['role'] é 'docente', será usado no JS do chat

// Define o identificador da página atual para a sidebar
$currentPageIdentifier = 'enviar_materiais';

// PEGAR TEMA DA SESSÃO
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Buscar disciplinas e turmas para os selects
$disciplinas_result_query = mysqli_query($conn, "SELECT id, nome_disciplina FROM disciplinas ORDER BY nome_disciplina");
$turmas_result_query = mysqli_query($conn, "SELECT id, nome_turma FROM turmas ORDER BY nome_turma");

// Buscar materiais já enviados por este professor (para listagem)
$materiais_enviados_sql = "
    SELECT m.id, m.titulo, m.tipo_material, d.nome_disciplina, t.nome_turma, m.data_upload
    FROM materiais_didaticos m
    LEFT JOIN disciplinas d ON m.disciplina_id = d.id
    LEFT JOIN turmas t ON m.turma_id = t.id
    WHERE m.professor_id = ?
    ORDER BY m.data_upload DESC";
$stmt_materiais = mysqli_prepare($conn, $materiais_enviados_sql);
$materiais_enviados_result = null; 
if ($stmt_materiais) { 
    mysqli_stmt_bind_param($stmt_materiais, "i", $professor_id);
    mysqli_stmt_execute($stmt_materiais);
    $materiais_enviados_result = mysqli_stmt_get_result($stmt_materiais);
} else {
    error_log("Erro ao preparar statement para buscar materiais (gerenciar_materiais.php): " . mysqli_error($conn));
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Materiais Didáticos - ACADMIX</title>
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

    /* Cores de status */
    --status-presente: #28a745; /* Verde */
    --status-presente-rgb: 40, 167, 69;
    --status-falta: #dc3545;    /* Vermelho */
    --status-falta-rgb: 220, 53, 69;
    --status-atestado: #ffc107; /* Amarelo */
    --status-atestado-rgb: 255, 193, 7;
    --status-justificada: #17a2b8; /* Azul claro */
    --status-justificada-rgb: 23, 162, 184;
    --status-info: #17a2b8; /* Usado para info message */
    --status-error: #dc3545; /* Usado para error message */
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
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    color: var(--button-text-color);
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(var(--primary-color-rgb), 0.3);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap; /* Impede que o texto quebre */
    position: relative;
    overflow: hidden;
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

.button:hover::before {
    left: 100%;
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
    margin-bottom: 1.5rem;
    position: relative; /* Necessário para o ::after */
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    border-radius: 2px;
}

/* Cards */
.card {
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

.card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
}

.card h3 {
    margin-bottom: 1rem;
    color: var(--primary-color);
    font-weight: 600;
    font-size: 1.3rem;
}

/* Formulários */
.form-section, .list-section { 
    margin-bottom: 2rem;
    padding: 2rem; /* Aumentado padding para consistência com .card */
    border-radius: 16px; /* Aumentado border-radius para consistência com .card */
    background: var(--card-background); /* Definido background */
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08); /* Adicionado shadow */
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); /* Adicionado transição */
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
    margin-bottom: 0.5rem;
    font-weight: bold;
    color: var(--text-color);
}

.input-field {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 8px; /* Mais arredondado */
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
    min-height: 120px; /* Aumentado min-height */
    resize: vertical;
}

.form-section .radio-group {
    display: flex;
    gap: 20px;
    margin-top: 1rem;
    margin-bottom: 1.5rem;
}

.form-section .radio-group label {
    font-weight: normal;
    margin-top: 0;
    margin-bottom: 0;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    color: var(--text-color);
}

.form-section .radio-group input[type="radio"] {
    margin-right: 8px; /* Espaçamento melhor */
    vertical-align: middle;
    transform: scale(1.2); /* Aumentar o radio button */
    accent-color: var(--primary-color); /* Cor do radio button */
}

.form-section button[type="submit"] { 
    padding: 0.85rem 2rem; /* Mais padding */
    border: none;
    border-radius: 25px; /* Mais arredondado */
    cursor: pointer; 
    font-size: 1.05rem; /* Fonte um pouco maior */
    margin-top: 2rem; /* Mais margem superior */
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    color: var(--button-text-color);
    box-shadow: 0 6px 15px rgba(var(--primary-color-rgb), 0.3);
    transition: all 0.3s ease;
}

.form-section button[type="submit"]:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(var(--primary-color-rgb), 0.4);
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
    background-color: rgba(var(--status-presente-rgb), 0.1);
    color: var(--status-presente);
    border: 1px solid var(--status-presente);
}

.status-message.status-error {
    background-color: rgba(var(--status-falta-rgb), 0.1);
    color: var(--status-falta);
    border: 1px solid var(--status-falta);
}

.info-message {
    background-color: rgba(var(--status-info-rgb), 0.1);
    color: var(--status-info);
    border: 1px solid var(--status-info);
}

.no-data-message {
    text-align: center;
    padding: 2rem;
    font-size: 1.1rem;
    color: var(--text-color-muted);
    background: rgba(var(--primary-color-rgb), 0.03);
    border-radius: 12px;
    border: 2px dashed rgba(var(--primary-color-rgb), 0.2);
}

/* Tabela de Materiais */
.list-section table {
    width: 100%;
    border-collapse: separate; /* Para border-radius nas células */
    border-spacing: 0; /* Para remover espaços entre as células */
    margin-top: 1.5rem;
    background-color: var(--card-background);
    border-radius: 8px;
    overflow: hidden; /* Garante que bordas arredondadas sejam visíveis */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.list-section th, .list-section td {
    padding: 0.9rem 1.2rem;
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color-soft);
    color: var(--text-color);
}

.list-section th {
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

.list-section tbody tr:last-child td {
    border-bottom: none;
}

.list-section tbody tr:hover {
    background-color: var(--hover-background-color);
}

.btn-delete {
    color: var(--status-falta); /* Cor para o botão de deletar */
    text-decoration: none;
    font-weight: bold;
    transition: color 0.2s ease;
}

.btn-delete:hover {
    color: darken(var(--status-falta), 10%); /* Escurecer ao passar o mouse */
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
    color: var(--status-justificada);
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
    color: var(--text-color); /* Mudar para uma cor de texto que contraste melhor */
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

    .form-section, .list-section {
        padding: 1.5rem;
    }

    .form-section button[type="submit"] {
        width: 100%;
    }

    .list-section th, .list-section td {
        padding: 0.7rem;
        font-size: 0.9em;
    }

    .chat-widget-acad {
        width: 100%;
        right: 0;
        border-radius: 0;
    }
    .chat-widget-acad.chat-expanded {
        height: 80vh; /* Ocupa mais da tela em mobile */
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
        /* Estilos da página gerenciar_materiais.php */
        .form-section, .list-section { 
            margin-bottom: 2rem; padding: 1.5rem; border-radius: 8px; /* Ajustado border-radius */
        }
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.5rem; font-weight: bold; }
        .form-section input[type="text"],
        .form-section input[type="url"],
        .form-section input[type="file"],
        .form-section textarea,
        .form-section select {
            width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box;
        }
        .form-section textarea { min-height: 100px; }
        .form-section .radio-group label { font-weight: normal; margin-right: 15px; }
        .form-section .radio-group input[type="radio"] { margin-right: 5px; vertical-align: middle;}
        .form-section button[type="submit"] { 
            padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; 
            font-size: 1rem; margin-top: 1.5rem;
        }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .list-section table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .list-section th, .list-section td { padding: 0.75rem; text-align: left; }
        .btn-delete { color: red; text-decoration: none; } 

        /* --- INÍCIO CSS NOVO CHAT ACADÊMICO --- */
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
        .teacher-icon-acad { margin-left: 5px; color: var(--primary-color, #007bff); font-size: 0.9em; }
        .student-icon-acad { margin-left: 5px; color: var(--accent-color, #6c757d); font-size: 0.9em; } 
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
        /* --- FIM CSS NOVO CHAT ACADÊMICO --- */
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Gerenciar Materiais (Prof. <?php echo htmlspecialchars($nome_professor); ?>)</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_professor.php';
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Enviar Novo Material Didático</h2>

            <?php if(isset($_SESSION['status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['status_message']); ?>
                </div>
                <?php unset($_SESSION['status_message']); unset($_SESSION['status_type']); ?>
            <?php endif; ?>

            <section class="form-section dashboard-section card">
                <form action="salvar_material.php" method="POST" enctype="multipart/form-data">
                    <label for="titulo">Título do Material:</label>
                    <input type="text" id="titulo" name="titulo" required class="input-field">

                    <label for="descricao">Descrição:</label>
                    <textarea id="descricao" name="descricao" class="input-field"></textarea>

                    <label for="disciplina_id">Disciplina:</label>
                    <select id="disciplina_id" name="disciplina_id" required class="input-field">
                        <option value="">Selecione a Disciplina</option>
                        <?php mysqli_data_seek($disciplinas_result_query, 0); // Resetar ponteiro
                              if($disciplinas_result_query) while($disciplina = mysqli_fetch_assoc($disciplinas_result_query)): ?>
                            <option value="<?php echo $disciplina['id']; ?>"><?php echo htmlspecialchars($disciplina['nome_disciplina']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label for="turma_id">Turma (Opcional - deixe em branco para global da disciplina):</label>
                    <select id="turma_id" name="turma_id" class="input-field">
                        <option value="">Todas as Turmas / Global</option>
                        <?php mysqli_data_seek($turmas_result_query, 0); // Resetar ponteiro
                              if($turmas_result_query) while($turma = mysqli_fetch_assoc($turmas_result_query)): ?>
                            <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome_turma']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Tipo de Envio:</label>
                    <div class="radio-group">
                        <input type="radio" id="tipo_arquivo" name="tipo_envio" value="arquivo" checked onchange="toggleEnvioFields()"> <label for="tipo_arquivo">Arquivo</label>
                        <input type="radio" id="tipo_link" name="tipo_envio" value="link" onchange="toggleEnvioFields()"> <label for="tipo_link">Link Externo</label>
                    </div>

                    <div id="campo_arquivo">
                        <label for="arquivo_material">Selecione o Arquivo:</label>
                        <input type="file" id="arquivo_material" name="arquivo_material" class="input-field">
                    </div>

                    <div id="campo_link" style="display:none;">
                        <label for="link_material">URL do Material (Ex: link do YouTube, Google Drive, artigo):</label>
                        <input type="url" id="link_material" name="link_material" placeholder="https://www.example.com/material" class="input-field">
                    </div>
                    
                    <label for="tipo_material">Tipo do Material (Ex: PDF, Vídeo, Apresentação):</label>
                    <input type="text" id="tipo_material" name="tipo_material" required placeholder="Descreva o tipo do material" class="input-field">

                    <button type="submit" class="button">Enviar Material</button>
                </form>
            </section>

            <section class="list-section dashboard-section card">
                <h2>Materiais Enviados</h2>
                <?php if($materiais_enviados_result && mysqli_num_rows($materiais_enviados_result) > 0): ?>
                <table class="table"> <thead>
                        <tr>
                            <th>Título</th>
                            <th>Disciplina</th>
                            <th>Turma</th>
                            <th>Tipo</th>
                            <th>Data Envio</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php while($material = mysqli_fetch_assoc($materiais_enviados_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($material['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($material['nome_disciplina'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($material['nome_turma'] ?? 'Global'); ?></td>
                            <td><?php echo htmlspecialchars($material['tipo_material']); ?></td>
                            <td><?php echo date("d/m/Y H:i", strtotime($material['data_upload'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-data-message info-message">Nenhum material enviado por você ainda.</p>
                <?php endif; ?>
            </section>
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
                    <input type="text" id="chatSearchUserAcad" placeholder="Pesquisar Alunos/Professores...">
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

        // Script específico da página para toggle dos campos de envio de material
        function toggleEnvioFields() {
            if (document.getElementById('tipo_arquivo').checked) {
                document.getElementById('campo_arquivo').style.display = 'block';
                document.getElementById('arquivo_material').required = true; 
                document.getElementById('campo_link').style.display = 'none';
                document.getElementById('link_material').required = false;
            } else { 
                document.getElementById('campo_arquivo').style.display = 'none';
                document.getElementById('arquivo_material').required = false;
                document.getElementById('campo_link').style.display = 'block';
                document.getElementById('link_material').required = true; 
            }
        }
        // Chama a função na carga da página para garantir o estado correto dos campos
        document.addEventListener('DOMContentLoaded', toggleEnvioFields);
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
                if (!isChatInitiallyLoaded) { 
                    fetchContactsForProfessor();
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
            if (contact.foto_url) photoToUse = contact.foto_url;
            conversationUserPhoto.src = photoToUse;
            
            if (shouldFetchMessages) {
                messagesContainer.innerHTML = ''; 
                fetchAndDisplayMessages(contact.id, contact.role);
            }
            messageInput.focus();
        }
        
        async function fetchContactsForProfessor() { 
            userListUl.innerHTML = '<li><i class="fas fa-spinner fa-spin"></i> Carregando contatos...</li>';
            
            try {
                const response = await fetch(`chat_api.php?action=get_professor_contacts`); 
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error ${response.status}: ${errorText}`);
                }
                const users = await response.json();

                if (users.error) {
                    console.error('API Erro (get_professor_contacts):', users.error);
                    userListUl.innerHTML = `<li>Erro: ${users.error}</li>`;
                    return;
                }
                allContacts = users; 
                renderUserList(allContacts);

            } catch (error) {
                console.error('Falha ao buscar contatos do professor:', error);
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

        chatHeader.addEventListener('click', (event) => {
            if (event.target.closest('#chatToggleBtnAcad') || event.target.id === 'chatToggleBtnAcad') {
                 toggleChat();
            } else if (event.target === chatHeader || chatHeader.contains(event.target)) {
                toggleChat();
            }
        });

        backToListBtn.addEventListener('click', () => {
            showUserListScreen(); 
        });

        searchUserInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const filteredUsers = allContacts.filter(user => 
                user.nome.toLowerCase().includes(searchTerm)
            );
            renderUserList(filteredUsers);
        });

        sendMessageBtn.addEventListener('click', handleSendMessage);
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
    });
    </script>

</body>
</html>
<?php 
if(isset($stmt_materiais)) mysqli_stmt_close($stmt_materiais); 
if(isset($conn) && $conn) mysqli_close($conn); 
?>