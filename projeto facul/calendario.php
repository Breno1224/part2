<?php
session_start(); 

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    header("Location: index.html");
    exit();
}

// Variáveis da sessão para a página e para o chat
$nome_aluno = $_SESSION['usuario_nome'] ?? 'Aluno(a)';
$aluno_id = $_SESSION['usuario_id']; // Essencial para o chat
$turma_id_aluno = isset($_SESSION['turma_id']) ? intval($_SESSION['turma_id']) : 0; // Essencial para o chat

$currentPageIdentifier = 'calendario'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Calendário Pessoal - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css"> 
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="css/calendario_novo.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* css/calendario_novo.css */

/* Aplicando estilos base do tema aluno */
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
    background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
    color: #2C1B17;
    line-height: 1.6;
}

/* Cabeçalho */
header {
    background: linear-gradient(135deg, #208A87 0%, #186D6A 100%);
    color: white;
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
    background: linear-gradient(135deg, #D69D2A 0%, #C58624 100%);
    color: white;
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
    background: linear-gradient(135deg, #C58624 0%, #B07420 100%);
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
    background: linear-gradient(180deg, #208A87 0%, #186D6A 100%);
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
    color: white;
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
    color: white;
    font-weight: 600;
    border-left: 4px solid #D69D2A;
}

/* Conteúdo principal */
.main-content {
    flex: 1;
    padding: 2.5rem;
    background: white;
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

.main-content > * {
    position: relative;
    z-index: 1;
}

.main-content h2 {
    margin-bottom: 1.5rem;
    color: #2C1B17;
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
    background: linear-gradient(90deg, #208A87, #D69D2A);
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
    background: linear-gradient(90deg, #208A87, #D69D2A);
    border-radius: 2px;
}

/* Título específico do calendário */
.main-content h2.page-title-calendar {
    text-align: center;
    font-size: 1.8rem;
    margin-bottom: 2rem;
    color: #2C1B17;
    font-weight: 600;
    position: relative;
    padding-bottom: 1rem;
}

.main-content h2.page-title-calendar::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #208A87, #D69D2A);
    border-radius: 2px;
}

/* Container do calendário */
.calendar-view-container {
    margin-bottom: 2rem;
    padding: 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
}

.calendar-view-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #208A87, #D69D2A);
}

/* Navegação do calendário */
.calendar-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0 1.5rem 0;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid rgba(32, 138, 135, 0.1);
}

.calendar-navigation h3 { 
    font-size: 1.4rem; 
    margin: 0 1rem;
    min-width: 150px; 
    text-align: center;
    color: #208A87;
    font-weight: 600;
}

.calendar-navigation button {
    background: linear-gradient(135deg, #208A87 0%, #186D6A 100%);
    color: white;
    border: none; 
    padding: 0.8rem 1.2rem;
    cursor: pointer;
    font-size: 1.2rem; 
    font-weight: bold;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(32, 138, 135, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 45px;
    height: 45px;
}

.calendar-navigation button:hover {
    background: linear-gradient(135deg, #186D6A 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(32, 138, 135, 0.4);
}

.calendar-navigation button:active {
    transform: translateY(0);
}

/* Tabela do calendário */
.calendar-table {
    width: 100%;
    border-collapse: separate; 
    border-spacing: 8px; 
    table-layout: fixed;
    margin-top: 1rem;
}

.calendar-table th { 
    padding: 1rem 0.5rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
    color: #208A87;
    background: rgba(32, 138, 135, 0.1);
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.calendar-table td.day-cell {
    height: 90px; 
    padding: 8px; 
    text-align: right;
    vertical-align: top;
    border: 2px solid rgba(32, 138, 135, 0.1);
    border-radius: 12px;
    cursor: pointer;
    position: relative; 
    font-size: 0.9rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.calendar-table td.day-cell .day-number { 
    display: inline-block;
    padding: 6px 8px;
    line-height: 1;
    border-radius: 6px;
    font-weight: 500;
    color: #2C1B17;
}

.calendar-table td.day-cell.empty {
    background-color: transparent !important;
    border-color: transparent !important;
    cursor: default;
    box-shadow: none !important;
}

.calendar-table td.day-cell:not(.empty):hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 8px 25px rgba(32, 138, 135, 0.15);
    border-color: rgba(32, 138, 135, 0.3);
}

/* Dia atual (hoje) */
.calendar-table td.day-cell.today {
    background: linear-gradient(135deg, rgba(214, 157, 42, 0.1) 0%, rgba(214, 157, 42, 0.05) 100%);
    border-color: #D69D2A;
    box-shadow: 0 4px 15px rgba(214, 157, 42, 0.2);
}

.calendar-table td.day-cell.today .day-number {
    background: linear-gradient(135deg, #D69D2A 0%, #C58624 100%);
    color: white !important;
    font-weight: bold;
    border-radius: 50%; 
    padding: 8px 10px;
    box-shadow: 0 2px 8px rgba(214, 157, 42, 0.3);
}

/* Dia selecionado */
.calendar-table td.day-cell.selected {
    border-width: 3px !important;
    border-color: #208A87 !important;
    background: linear-gradient(135deg, rgba(32, 138, 135, 0.1) 0%, rgba(32, 138, 135, 0.05) 100%);
    font-weight: bold;
    box-shadow: 0 6px 20px rgba(32, 138, 135, 0.2);
}

.calendar-table td.day-cell.selected .day-number {
    color: #186D6A;
    font-weight: 700;
}

/* Dia atual e selecionado */
.calendar-table td.day-cell.today.selected .day-number {
    background: linear-gradient(135deg, #D69D2A 0%, #C58624 100%);
    color: white !important;
}

/* Indicador de evento */
.calendar-table td.day-cell.has-event::after {
    content: '';
    display: block;
    width: 12px; 
    height: 12px;
    background: linear-gradient(135deg, #FFD700 0%, #DAA520 100%);
    border: 2px solid #DAA520; 
    border-radius: 50%;
    position: absolute;
    bottom: 8px;
    left: 8px;
    box-shadow: 0 2px 6px rgba(218, 165, 32, 0.4);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

/* Seção de gerenciamento de eventos */
.event-section-container {
    margin-top: 2rem;
    padding: 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
}

.event-section-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #208A87, #D69D2A);
}

.event-section-container h3 { 
    font-size: 1.4rem;
    margin-bottom: 1.5rem;
    color: #208A87;
    font-weight: 600;
}

.event-form-area {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid rgba(32, 138, 135, 0.1);
}

.event-form-area label {
    display: block;
    font-size: 0.95rem;
    margin: 1rem 0 0.5rem 0;
    color: #2C1B17;
    font-weight: 500;
}

.event-form-area label strong { 
    font-size: 1.1rem;
    color: #208A87;
}

.event-form-area input[type="text"] {
    width: 100%;
    padding: 0.8rem 1rem;
    margin-bottom: 1rem;
    box-sizing: border-box;
    font-size: 1rem;
    border: 2px solid rgba(32, 138, 135, 0.2);
    border-radius: 8px;
    transition: all 0.3s ease;
    background: white;
}

.event-form-area input[type="text"]:focus {
    outline: none;
    border-color: #208A87;
    box-shadow: 0 0 0 3px rgba(32, 138, 135, 0.1);
}

.event-form-area button {
    background: linear-gradient(135deg, #208A87 0%, #186D6A 100%);
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    cursor: pointer;
    border-radius: 25px;
    font-size: 0.95rem;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(32, 138, 135, 0.3);
}

.event-form-area button:hover {
    background: linear-gradient(135deg, #186D6A 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(32, 138, 135, 0.4);
}

.event-form-area button:active {
    transform: translateY(0);
}

.event-display-area h4 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #208A87;
    font-weight: 600;
}

.event-display-area ul { 
    list-style: none; 
    padding: 0; 
    max-height: 200px; 
    overflow-y: auto; 
    margin-top: 1rem;
}

.event-display-area li { 
    padding: 1rem;
    border-bottom: 1px solid rgba(32, 138, 135, 0.1);
    font-size: 0.95rem;
    background: rgba(32, 138, 135, 0.03);
    margin-bottom: 0.5rem;
    border-radius: 8px;
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
}

.event-display-area li:hover {
    background: rgba(32, 138, 135, 0.06);
    border-left-color: #208A87;
    transform: translateX(4px);
}

.event-display-area li:last-child { 
    border-bottom: none; 
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
    
    .calendar-view-container {
        padding: 1rem;
    }
    
    .calendar-navigation {
        padding: 0.5rem 0 1rem 0;
    }
    
    .calendar-navigation h3 {
        font-size: 1.2rem;
        min-width: 120px;
    }
    
    .calendar-navigation button {
        padding: 0.6rem 1rem;
        font-size: 1rem;
        min-width: 40px;
        height: 40px;
    }
    
    .calendar-table {
        border-spacing: 4px;
    }
    
    .calendar-table td.day-cell {
        height: 70px;
        padding: 4px;
    }
    
    .calendar-table th {
        padding: 0.5rem 0.2rem;
        font-size: 0.8rem;
    }
    
    .event-section-container {
        padding: 1rem;
    }
    
    header {
        padding: 1rem;
    }
    
    header h1 {
        font-size: 1.3rem;
    }
}

/* Animações suaves */
.sidebar,
.main-content,
.calendar-view-container,
.event-section-container {
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
    background: linear-gradient(135deg, #208A87, #186D6A);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #186D6A, #145A57);
}
        /* Estilos existentes da página calendario_novo.css são referenciados. */
        /* Se houver estilos inline específicos aqui, mantenha-os ou mova-os para o CSS externo. */
        
        /* --- INÍCIO CSS NOVO CHAT ACADÊMICO --- */
        /* (Mova para um arquivo CSS externo como chat_academico.css para todas as páginas) */
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
        <h1>ACADMIX - Calendário Pessoal</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer"> 
        <nav class="sidebar" id="sidebar"> 
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php';
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <div style="text-align:center; margin-bottom:1.5rem;">
                <h2 class="page-title-calendar">Meu Calendário</h2>
            </div>

            <div class="calendar-view-container dashboard-section card"> <div class="calendar-navigation">
                    <button id="prevMonthCalendarBtn" aria-label="Mês anterior" title="Mês anterior"><i class="fas fa-chevron-left"></i></button>
                    <h3 id="currentMonthYearDisplay">Carregando...</h3>
                    <button id="nextMonthCalendarBtn" aria-label="Próximo mês" title="Próximo mês"><i class="fas fa-chevron-right"></i></button>
                </div>
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>Dom</th><th>Seg</th><th>Ter</th><th>Qua</th><th>Qui</th><th>Sex</th><th>Sáb</th>
                        </tr>
                    </thead>
                    <tbody id="calendarTableBody">
                        </tbody>
                </table>
            </div>

            <div class="event-section-container dashboard-section card"> <h3>Lembretes e Eventos</h3>
                <div class="event-form-area">
                    <label for="eventDateSelectedDisplay">Data selecionada: <strong id="eventDateSelectedDisplay">-</strong></label>
                    <input type="text" id="newEventTitleInput" placeholder="Adicionar lembrete (ex: Prova de Álgebra)">
                    <button type="button" id="addEventToCalendarBtn" class="button"><i class="fas fa-plus"></i> Adicionar</button> </div>
                <div class="event-display-area">
                    <h4>Eventos para <span id="eventDisplayDateSpan">-</span>:</h4>
                    <ul id="eventListForDayUl">
                        <li>Nenhum evento ou dia não selecionado.</li>
                    </ul>
                </div>
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
        // JAVASCRIPT DO CALENDÁRIO (existente)
        document.addEventListener('DOMContentLoaded', function () {
            const calendarTableBody = document.getElementById('calendarTableBody');
            const currentMonthYearDisplay = document.getElementById('currentMonthYearDisplay');
            const prevMonthBtn = document.getElementById('prevMonthCalendarBtn');
            const nextMonthBtn = document.getElementById('nextMonthCalendarBtn');
            
            const eventDateSelectedDisplay = document.getElementById('eventDateSelectedDisplay');
            const newEventTitleInput = document.getElementById('newEventTitleInput');
            const addEventBtn = document.getElementById('addEventToCalendarBtn');
            const eventListForDayUl = document.getElementById('eventListForDayUl');
            const eventDisplayDateSpan = document.getElementById('eventDisplayDateSpan');

            let currentDateInternal = new Date(); 
            let selectedDateKeyInternal = null;   
            let userEventsInternal = JSON.parse(localStorage.getItem('acadmixAlunoCalendarEvents_<?php echo $aluno_id; ?>')) || {}; // Chave única por aluno

            function formatDateAsKeyInternal(date) {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }
            
            function formatKeyForDisplayInternal(dateKey) {
                if (!dateKey) return '-';
                const [y, m, d] = dateKey.split('-');
                return `${d}/${m}/${y}`;
            }

            function displayEventsForSelectedDateInternal() {
                if (!eventListForDayUl) return;
                eventListForDayUl.innerHTML = '';
                
                const displayDateStr = formatKeyForDisplayInternal(selectedDateKeyInternal) || '-';
                if (eventDateSelectedDisplay) eventDateSelectedDisplay.textContent = displayDateStr;
                if (eventDisplayDateSpan) eventDisplayDateSpan.textContent = displayDateStr;

                const eventsOnDate = userEventsInternal[selectedDateKeyInternal] || [];
                if (eventsOnDate.length === 0) {
                    eventListForDayUl.innerHTML = '<li>Nenhum lembrete para este dia.</li>';
                } else {
                    eventsOnDate.forEach((eventObj, index) => { // Alterado para objeto
                        const li = document.createElement('li');
                        li.textContent = eventObj.title; // Acessa a propriedade title
                        
                        // Botão de remover (opcional)
                        const removeBtn = document.createElement('button');
                        removeBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
                        removeBtn.classList.add('remove-event-btn'); // Adicione estilos para este botão
                        removeBtn.title = "Remover lembrete";
                        removeBtn.onclick = function() {
                            userEventsInternal[selectedDateKeyInternal].splice(index, 1);
                            if (userEventsInternal[selectedDateKeyInternal].length === 0) {
                                delete userEventsInternal[selectedDateKeyInternal];
                            }
                            saveEventsToLocalStorageInternal();
                            renderNewCalendarInternal();
                            displayEventsForSelectedDateInternal();
                        };
                        li.appendChild(removeBtn);
                        eventListForDayUl.appendChild(li);
                    });
                }
            }

            function renderNewCalendarInternal() {
                if (!calendarTableBody || !currentMonthYearDisplay) {
                    return;
                }
                calendarTableBody.innerHTML = ''; 
                const year = currentDateInternal.getFullYear();
                const month = currentDateInternal.getMonth();

                currentMonthYearDisplay.textContent = currentDateInternal.toLocaleDateString('pt-BR', {
                    month: 'long', year: 'numeric'
                }).replace(/^\w/, c => c.toUpperCase());

                const firstDayOfMonth = new Date(year, month, 1).getDay(); 
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                
                let dateCounter = 1;
                for (let i = 0; i < 6; i++) { 
                    const row = document.createElement('tr');
                    for (let j = 0; j < 7; j++) {
                        const cell = document.createElement('td');
                        cell.classList.add('day-cell');
                        if (i === 0 && j < firstDayOfMonth) {
                            cell.classList.add('empty');
                        } else if (dateCounter > daysInMonth) {
                            cell.classList.add('empty');
                        } else {
                            const dayNumberSpan = document.createElement('span');
                            dayNumberSpan.classList.add('day-number');
                            dayNumberSpan.textContent = dateCounter;
                            cell.appendChild(dayNumberSpan);
                            
                            const cellDate = new Date(year, month, dateCounter);
                            const cellDateKey = formatDateAsKeyInternal(cellDate);

                            const today = new Date();
                            if (dateCounter === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                                cell.classList.add('today');
                            }
                            if (cellDateKey === selectedDateKeyInternal) {
                                cell.classList.add('selected');
                            }
                            if (userEventsInternal[cellDateKey] && userEventsInternal[cellDateKey].length > 0) {
                                cell.classList.add('has-event');
                                cell.title = "Eventos: \n• " + userEventsInternal[cellDateKey].map(e => e.title).join('\n• ');
                            }

                            cell.addEventListener('click', function() {
                                selectedDateKeyInternal = cellDateKey;
                                renderNewCalendarInternal(); 
                                displayEventsForSelectedDateInternal();
                            });
                            dateCounter++;
                        }
                        row.appendChild(cell);
                    }
                    calendarTableBody.appendChild(row);
                    if (dateCounter > daysInMonth && i >=3 ) { 
                        if ( (i === 3 && daysInMonth + firstDayOfMonth <= 28 ) || 
                             (i === 4 && daysInMonth + firstDayOfMonth <= 35) ) { 
                        } else if (i >= 4) { 
                             break;
                        }
                    }
                }
            }
            
            function saveEventsToLocalStorageInternal() {
                localStorage.setItem('acadmixAlunoCalendarEvents_<?php echo $aluno_id; ?>', JSON.stringify(userEventsInternal));
            }

            if (prevMonthBtn) {
                prevMonthBtn.addEventListener('click', function() {
                    currentDateInternal.setMonth(currentDateInternal.getMonth() - 1);
                    renderNewCalendarInternal();
                    displayEventsForSelectedDateInternal();
                });
            }
            if (nextMonthBtn) {
                nextMonthBtn.addEventListener('click', function() {
                    currentDateInternal.setMonth(currentDateInternal.getMonth() + 1);
                    renderNewCalendarInternal();
                    displayEventsForSelectedDateInternal();
                });
            }

            if (addEventBtn) {
                addEventBtn.addEventListener('click', function() {
                    if (!newEventTitleInput) return;
                    const title = newEventTitleInput.value.trim();
                    if (!title) {
                        alert('Por favor, digite o título do lembrete.');
                        return;
                    }
                    if (!selectedDateKeyInternal) {
                        alert('Por favor, selecione um dia no calendário primeiro.');
                        return;
                    }
                    if (!userEventsInternal[selectedDateKeyInternal]) {
                        userEventsInternal[selectedDateKeyInternal] = [];
                    }
                    userEventsInternal[selectedDateKeyInternal].push({ title: title }); // Salva como objeto
                    saveEventsToLocalStorageInternal();
                    newEventTitleInput.value = '';
                    renderNewCalendarInternal(); 
                    displayEventsForSelectedDateInternal(); 
                });
            }
            
            renderNewCalendarInternal();
            displayEventsForSelectedDateInternal();
        });

        // Script do menu lateral (Toggle) - PADRONIZADO
        const menuToggleButton = document.getElementById('menu-toggle'); // ID padronizado
        const sidebarNavigation = document.getElementById('sidebar'); // ID padronizado
        const mainContainer = document.getElementById('pageContainer'); // ID padronizado

        if (menuToggleButton && sidebarNavigation && mainContainer) {
            menuToggleButton.addEventListener('click', function () {
                sidebarNavigation.classList.toggle('hidden'); 
                mainContainer.classList.toggle('full-width'); 
            });
        }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const currentUserId = <?php echo json_encode($aluno_id); ?>;
        const currentUserTurmaId = <?php echo json_encode($turma_id_aluno); ?>; 
        const defaultUserPhoto = 'img/alunos/default_avatar.png'; 

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

        let allTurmaUsers = []; 
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
                    fetchAndDisplayTurmaUsers();
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
            conversationUserPhoto.src = contact.foto_url || defaultUserPhoto;
            
            if (shouldFetchMessages) {
                messagesContainer.innerHTML = ''; 
                fetchAndDisplayMessages(contact.id);
            }
            messageInput.focus();
        }
        
        async function fetchAndDisplayTurmaUsers() {
            if (currentUserTurmaId === 0) {
                userListUl.innerHTML = '<li>Turma não definida.</li>';
                return;
            }
            userListUl.innerHTML = '<li><i class="fas fa-spinner fa-spin"></i> Carregando usuários...</li>';
            
            try {
                const response = await fetch(`chat_api.php?action=get_turma_users`);
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error ${response.status}: ${errorText}`);
                }
                const users = await response.json();

                if (users.error) {
                    console.error('API Erro (get_turma_users):', users.error);
                    userListUl.innerHTML = `<li>Erro: ${users.error}</li>`;
                    return;
                }
                allTurmaUsers = users;
                renderUserList(allTurmaUsers);

            } catch (error) {
                console.error('Falha ao buscar usuários:', error);
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
                
                const img = document.createElement('img');
                img.src = user.foto_url || defaultUserPhoto;
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
                }
                
                li.addEventListener('click', () => {
                    showConversationScreen(user, true); 
                });
                userListUl.appendChild(li);
            });
        }

        async function fetchAndDisplayMessages(contactId) {
            messagesContainer.innerHTML = '<p style="text-align:center;font-size:0.8em;"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';
            try {
                const response = await fetch(`chat_api.php?action=get_messages&contact_id=${contactId}`);
                if (!response.ok) {
                    const errorText = await response.text();
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
                        appendMessageToChat(msg.message_text, parseInt(msg.sender_id) === currentUserId ? 'sent-acad' : 'received-acad');
                    });
                }
            } catch (error) {
                console.error('Falha ao buscar mensagens:', error);
                messagesContainer.innerHTML = '<p style="text-align:center;color:red;">Falha ao carregar.</p>';
            }
        }

        function appendMessageToChat(text, type) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message-acad', type);
            messageDiv.textContent = text; 
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
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
            const filteredUsers = allTurmaUsers.filter(user => 
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
// Fechamento da conexão do banco de dados principal da página, se 'db.php' foi incluído no topo.
// if(isset($conn) && $conn) mysqli_close($conn); 
?>