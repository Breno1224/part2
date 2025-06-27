<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
// include 'db.php'; // O chat_api.php já incluirá db.php, se necessário para outras operações.

$nome_coordenador = $_SESSION['usuario_nome'];
$coordenador_id = $_SESSION['usuario_id']; // Essencial para o chat

$currentPageIdentifier = 'painel_coordenacao';
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao'; 
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel da Coordenação - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/coordenacao.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Importação de fontes para temas */
@import url('https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap'); /* Para o tema 8bit */

/* Variáveis de Cores e Temas */
/* Variáveis de Cores e Temas */
:root {
    /* Cores Padrão (Tema Claro) */
    --primary-color: #208A87; /* Cor principal (verde água) */
    --primary-color-dark: #186D6A; /* Verde mais escuro */
    --primary-color-light: #e0f2f2; /* Verde muito claro para chat */
    --secondary-color: #D69D2A; /* Cor secundária (amarelo/dourado) */
    --secondary-color-dark: #C58624; /* Amarelo/dourado mais escuro */
    --background-color: #F8F9FA; /* Fundo geral claro */
    --background-color-offset: #E9ECEF; /* Fundo para elementos ligeiramente diferentes */
    --text-color: #2C1B17; /* Cor do texto principal (quase preto) */
    --text-color-light: #555; /* Texto mais claro */
    --text-color-alt: #666; /* Texto alternativo */
    --border-color: #ddd; /* Cor de borda */
    --border-color-soft: #eee; /* Cor de borda mais suave */
    --card-background: white; /* Fundo dos cards */
    --card-shadow: 0 8px 32px rgba(0, 0, 0, 0.08); /* Sombra dos cards */
    --card-hover-shadow: 0 16px 48px rgba(0, 0, 0, 0.12); /* Sombra dos cards no hover */
    --button-text-color: white; /* Cor do texto em botões */
    --info-color: #17a2b8; /* Cor para informações (azul) */
    --success-color: #28a745; /* Cor para sucesso (verde) */
    --warning-color: #ffc107; /* Cor para aviso (amarelo) */
    --danger-color: #dc3545; /* Cor para perigo (vermelho) */
    --accent-color: #6c757d; /* Cor de destaque (cinza) */
    --accent-color-extra-light: #f1f0f0; /* Cinza muito claro para chat */
    --hover-background-color: #f0f0f0; /* Fundo para hover em listas */

    /* Cores para o tema "8bit" */
    --8bit-primary-color: #008080; /* Teal escuro */
    --8bit-primary-color-dark: #005f5f;
    --8bit-primary-color-light: #d0f0f0;
    --8bit-secondary-color: #FFD700; /* Dourado */
    --8bit-secondary-color-dark: #ccaa00;
    --8bit-background-color: #2c3e50; /* Azul escuro */
    --8bit-background-color-offset: #34495e; /* Azul escuro mais claro */
    --8bit-text-color: #ecf0f1; /* Branco/cinza claro */
    --8bit-text-color-light: #bdc3c7;
    --8bit-text-color-alt: #95a5a6;
    --8bit-border-color: #7f8c8d;
    --8bit-border-color-soft: #95a5a6;
    --8bit-card-background: #34495e;
    --8bit-card-shadow: 8px 8px 0px rgba(0, 0, 0, 0.5); /* Sombra estilo 8bit */
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
body.theme-8bit .welcome-message-coordenador {
    font-family: 'Press Start 2P', cursive;
    text-shadow: 3px 3px 0px var(--8bit-primary-color-dark);
}

body.theme-8bit .card,
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
    box-shadow: 0 4px 20px rgba(var(--primary-color-rgb, 32, 138, 135), 0.3);
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
    background: linear-gradient(to bottom, rgba(var(--primary-color-rgb, 32, 138, 135), 0.1), transparent);
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
    box-shadow: 0 4px 15px rgba(var(--secondary-color-rgb, 214, 157, 42), 0.3);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

header button:hover {
    background: linear-gradient(135deg, var(--secondary-color-dark) 0%, #B07420 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(var(--secondary-color-rgb, 214, 157, 42), 0.4);
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
    box-shadow: 4px 0 20px rgba(var(--primary-color-rgb, 32, 138, 135), 0.15);
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
    background: linear-gradient(135deg, rgba(var(--primary-color-rgb, 32, 138, 135), 0.05) 0%, rgba(var(--secondary-color-rgb, 214, 157, 42), 0.05) 100%);
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

/* Welcome Message para Coordenador */
.welcome-message-coordenador {
    text-align: left;
    font-size: 1.6rem;
    margin-bottom: 1.5rem;
    font-weight: 500;
    color: var(--text-color);
    background: linear-gradient(135deg, rgba(var(--primary-color-rgb, 32, 138, 135), 0.1) 0%, rgba(var(--secondary-color-rgb, 214, 157, 42), 0.1) 100%);
    padding: 2rem;
    border-radius: 20px;
    border: 1px solid rgba(var(--primary-color-rgb, 32, 138, 135), 0.1);
    position: relative;
    overflow: hidden;
}

.welcome-message-coordenador::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(var(--primary-color-rgb, 32, 138, 135), 0.05) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.3; }
    50% { transform: scale(1.1); opacity: 0.6; }
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

/* Dashboard Cards */
.dashboard-card {
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    /* Cores de fundo, sombra e texto virão de .card em temas_globais.css */
}

/* Aplicação das variáveis do tema para .card */
.card {
    background: var(--card-background);
    border: none;
    box-shadow: var(--card-shadow);
    color: var(--text-color);
    /* Garante que o texto dentro do card use a cor do tema */
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: var(--card-hover-shadow);
}

body.theme-8bit .card {
    background: var(--8bit-card-background);
    box-shadow: var(--8bit-card-shadow);
    color: var(--8bit-text-color);
    border: 2px solid var(--8bit-border-color);
}

body.theme-8bit .card:hover {
    transform: translate(-5px, -5px);
    /* Efeito 8bit */
    box-shadow: var(--8bit-card-hover-shadow);
}


.dashboard-card i {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

.dashboard-card span {
    font-size: 1.1rem;
    font-weight: bold;
    display: block;
    color: var(--text-color);
    /* A cor do span virá da cor do texto do .card, definida pelo tema */
}

body.theme-8bit .dashboard-card span {
    color: var(--8bit-text-color);
}

/* Cores específicas dos ÍCONES dos cards (mantidas como design específico do card) */
.card-aluno i {
    color: #208A87;
}

/* Verde Água */
.card-professor i {
    color: #D69D2A;
}

/* Amarelo/Dourado */
.card-comunicado i {
    color: #5D3A9A;
}

/* Roxo */
.card-turma i {
    color: #C54B6C;
}

/* Rosa avermelhado */
.card-disciplina i {
    color: #28a745;
}

/* Verde escuro */

/* Botões */
.button,
.btn-news-readmore {
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
    align-self: flex-start;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(var(--primary-color-rgb, 32, 138, 135), 0.3);
    position: relative;
    overflow: hidden;
}

.button::before,
.btn-news-readmore::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.button:hover,
.btn-news-readmore:hover {
    background: linear-gradient(135deg, var(--primary-color-dark) 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(var(--primary-color-rgb, 32, 138, 135), 0.4);
}

.button:hover::before,
.btn-news-readmore:hover::before {
    left: 100%;
}

.button:active,
.btn-news-readmore:active {
    transform: translateY(0);
}

.button-logout {
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color-dark) 100%) !important;
    box-shadow: 0 4px 15px rgba(var(--secondary-color-rgb, 214, 157, 42), 0.3) !important;
}

.button-logout:hover {
    background: linear-gradient(135deg, var(--secondary-color-dark) 0%, #B07420 100%) !important;
    box-shadow: 0 8px 25px rgba(var(--secondary-color-rgb, 214, 157, 42), 0.4) !important;
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
    flex: 1 1 auto; /* Allows it to grow/shrink */
    max-width: 1200px; /* Limit content width for readability on large screens */
    margin: 0 auto; /* Centers the content horizontally */
    padding: 2.5rem; /* Keep existing padding */
    /* Remove width: 100% as flex:auto and max-width + margin:auto will handle sizing */
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
        /* Added shadow for mobile sidebar */
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .main-content {
        padding: 1.5rem;
        max-width: unset; /* Remove max-width on smaller screens */
        margin: 0; /* Remove auto margin on smaller screens */
    }

    .dashboard-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }

    .dashboard-card {
        padding: 1.5rem 1rem;
        min-height: 120px;
    }

    .dashboard-card i {
        font-size: 2.2rem;
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
        /* Estilos ESTRUTURAIS e específicos do painel da coordenação. 
           Cores de fundo, texto e sombras dos cards devem vir de .card em temas_globais.css */

        .main-content .welcome-message-coordenador { 
            text-align: left; 
            font-size: 1.6rem; 
            margin-bottom: 1.5rem; 
            font-weight: 500; 
            /* color: var(--text-color); /* Cor do texto virá do tema */
        }
        .dashboard-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 1.5rem; 
        }
        .dashboard-card { 
            /* background-color, box-shadow, color e border devem ser definidos pela classe .card em temas_globais.css */
            border-radius: 8px; 
            padding: 1.5rem; 
            text-align: center; 
            text-decoration: none; 
            transition: transform 0.2s, box-shadow 0.2s; 
        }
        .dashboard-card:hover { 
            transform: translateY(-5px); 
            /* box-shadow no hover também deve ser definido por .card:hover no tema */
        }
        .dashboard-card i { 
            font-size: 3rem; 
            margin-bottom: 1rem; 
            display: block; 
        }
        .dashboard-card span { 
            font-size: 1.1rem; 
            font-weight: bold; 
            display: block; /* Garante que o span ocupe a largura para centralização, se necessário */
             /* A cor do span virá da cor do texto do .card, definida pelo tema */
        }

        /* Cores específicas dos ÍCONES dos cards (mantidas como design específico do card) */
        .card-aluno i { color: #208A87; } 
        .card-professor i { color: #D69D2A; } 
        .card-comunicado i { color: #5D3A9A; } 
        .card-turma i { color: #C54B6C; } 
        .card-disciplina i { color: #28a745; } 

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
        /* --- FIM CSS NOVO CHAT ACADÊMICO --- */
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Painel da Coordenação</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>
    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?>
        </nav>
        <main class="main-content">
            <div class="welcome-message-coordenador">
                Bem-vindo(a), Coordenador(a) <?php echo htmlspecialchars($nome_coordenador); ?>!
            </div>
            <div class="dashboard-grid">
                <a href="coordenacao_add_aluno.php" class="dashboard-card card">
                    <i class="fas fa-user-plus card-aluno"></i>
                    <span>Adicionar Aluno</span>
                </a>
                <a href="coordenacao_add_professor.php" class="dashboard-card card">
                    <i class="fas fa-chalkboard-teacher card-professor"></i>
                    <span>Adicionar Professor</span>
                </a>
                <a href="coordenacao_ver_alunos.php" class="dashboard-card card">
                    <i class="fas fa-users card-aluno"></i>
                    <span>Visualizar Alunos</span>
                </a>
                <a href="coordenacao_ver_professores.php" class="dashboard-card card">
                    <i class="fas fa-user-tie card-professor"></i>
                    <span>Visualizar Professores</span>
                </a>
                <a href="coordenacao_lancar_comunicado.php" class="dashboard-card card">
                    <i class="fas fa-bullhorn card-comunicado"></i>
                    <span>Enviar Comunicado</span>
                </a>
                <a href="gerenciar_turmas_coordenacao.php" class="dashboard-card card">
                    <i class="fas fa-sitemap card-turma"></i>
                    <span>Gerenciar Turmas</span>
                </a>
                <a href="coordenacao_gerenciar_disciplinas.php" class="dashboard-card card">
                    <i class="fas fa-book card-disciplina"></i>
                    <span>Gerenciar Disciplinas</span>
                </a>
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