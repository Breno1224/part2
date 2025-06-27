<?php
session_start(); // GARANTIR que está no topo absoluto

// Verifica se o usuário é um aluno logado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    header("Location: index.html");
    exit();
}
// include 'db.php'; // O chat_api.php já incluirá db.php.

$nome_aluno = $_SESSION['usuario_nome'];
$aluno_id = $_SESSION['usuario_id']; 
$turma_id_aluno = isset($_SESSION['turma_id']) ? intval($_SESSION['turma_id']) : 0; // Essencial para o chat

$currentPageIdentifier = 'inicio_noticias'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Dados Estáticos (substitua por buscas no banco de dados em uma aplicação real)
$acesso_rapido_aluno = [
    ["titulo" => "Meu Boletim", "icone" => "fas fa-graduation-cap", "link" => "boletim.php", "cor" => "#208A87"],
    ["titulo" => "Calendário", "icone" => "fas fa-calendar-alt", "link" => "calendario.php", "cor" => "#D69D2A"],
    ["titulo" => "Materiais", "icone" => "fas fa-book-open", "link" => "materiais.php", "cor" => "#5D3A9A"],
    ["titulo" => "Comunicados", "icone" => "fas fa-bell", "link" => "comunicados_aluno.php", "cor" => "#C54B6C"]
];
$proximas_atividades_static = [
    ["data" => "2025-06-10", "descricao" => "Entrega do Trabalho de História - Revolução Industrial.", "tipo" => "trabalho", "disciplina" => "História"],
    ["data" => "2025-06-15", "descricao" => "Prova Bimestral de Matemática - Unidades 3 e 4.", "tipo" => "prova", "disciplina" => "Matemática"],
    ["data" => "2025-06-20", "descricao" => "Apresentação do Seminário de Ciências.", "tipo" => "evento", "disciplina" => "Ciências"]
];
$noticias_static = [
    ["titulo" => "Inscrições para o ENEM 2025 Abertas!", "resumo" => "O período de inscrição para o Exame Nacional do Ensino Médio (ENEM) de 2025 já começou...", "link_externo" => "https://www.gov.br/inep/pt-br/areas-de-atuacao/avaliacao-e-exames-educacionais/enem", "imagem_url" => "img/noticias/enem_2025.jpg", "data_publicacao" => "2025-05-28", "categoria" => "ENEM"],
    ["titulo" => "Dicas Essenciais para Organizar sua Rotina de Estudos", "resumo" => "Manter uma rotina de estudos organizada é fundamental para o sucesso acadêmico...", "link_externo" => "#", "imagem_url" => "img/noticias/rotina_estudos.jpg", "data_publicacao" => "2025-05-25", "categoria" => "Dicas de Estudo"],
    ["titulo" => "Novos Materiais de Matemática Adicionados!", "resumo" => "Professores adicionaram novas videoaulas e listas de exercícios de Matemática...", "link_externo" => "materiais.php", "imagem_url" => "img/noticias/novos_materiais.jpg", "data_publicacao" => "2025-05-22", "categoria" => "Materiais Didáticos"],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal do Aluno - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css">
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
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

/* Welcome Message */
.welcome-message-aluno {
    background: linear-gradient(135deg, rgba(32, 138, 135, 0.1) 0%, rgba(214, 157, 42, 0.1) 100%);
    padding: 2rem;
    border-radius: 20px;
    text-align: center;
    margin-bottom: 3rem;
    border: 1px solid rgba(32, 138, 135, 0.1);
    position: relative;
    overflow: hidden;
}

.welcome-message-aluno::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(32, 138, 135, 0.05) 0%, transparent 70%);
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.3; }
    50% { transform: scale(1.1); opacity: 0.6; }
}

.welcome-message-aluno h3 {
    font-size: 1.6rem;
    color: #208A87;
    font-weight: 600;
    position: relative;
    z-index: 1;
}

/* Cards */
.card {
    margin-top: 1.5rem;
    padding: 2rem;
    background: white;
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
    background: linear-gradient(90deg, #208A87, #D69D2A);
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
}

.card h3 {
    margin-bottom: 1rem;
    color: #208A87;
    font-weight: 600;
    font-size: 1.3rem;
}

.card ul {
    list-style: none;
    padding-left: 0;
}

.card ul li {
    margin-bottom: 1rem;
    padding: 0.8rem 1rem;
    background: rgba(32, 138, 135, 0.03);
    border-radius: 8px;
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
}

.card ul li:hover {
    background: rgba(32, 138, 135, 0.06);
    border-left-color: #208A87;
    transform: translateX(4px);
}

/* Quick Access Grid */
.quick-access-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.quick-access-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.5rem;
    border-radius: 20px;
    text-decoration: none;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    min-height: 140px;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

.quick-access-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
    border-radius: 20px;
    transition: all 0.3s ease;
}

.quick-access-card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: 0 20px 48px rgba(0, 0, 0, 0.25);
}

.quick-access-card:hover::before {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, transparent 50%);
}

.quick-access-card i {
    font-size: 2.8rem;
    margin-bottom: 1rem;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    transition: transform 0.3s ease;
}

.quick-access-card:hover i {
    transform: scale(1.1);
}

.quick-access-card span {
    font-size: 1rem;
    font-weight: 600;
    text-align: center;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

/* News Feed */
.news-feed {
    display: grid;
    gap: 2rem;
}

.news-item {
    border-radius: 20px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(32, 138, 135, 0.1);
}

.news-item:hover {
    transform: translateY(-12px);
    box-shadow: 0 20px 48px rgba(0, 0, 0, 0.15);
}

.news-image-container {
    width: 100%;
    max-height: 240px;
    overflow: hidden;
    position: relative;
}

.news-image-container::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 40px;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.1), transparent);
}

.news-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}

.news-item:hover .news-image {
    transform: scale(1.05);
}

.news-content {
    padding: 2rem;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.news-title {
    font-size: 1.4rem;
    margin-bottom: 1rem;
    color: #2C1B17;
    font-weight: 600;
    line-height: 1.4;
}

.news-meta {
    font-size: 0.85rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #666;
}

.news-meta .news-date {
    font-weight: 600;
    color: #208A87;
}

.news-meta .news-category {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    background: linear-gradient(135deg, #208A87, #186D6A);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.news-summary {
    font-size: 1rem;
    line-height: 1.7;
    margin-bottom: 2rem;
    flex-grow: 1;
    color: #555;
}
.chat-header-acad {
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #208A87 0%, #186D6A 100%);
    color: white;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    box-shadow: 0 2px 8px rgba(32, 138, 135, 0.3);
}
.button, .btn-news-readmore {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.8rem 1.5rem;
    background: linear-gradient(135deg, #208A87 0%, #186D6A 100%);
    color: white;
    text-decoration: none;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
    text-align: center;
    align-self: flex-start;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(32, 138, 135, 0.3);
    position: relative;
    overflow: hidden;
}

.button::before, .btn-news-readmore::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.button:hover, .btn-news-readmore:hover {
    background: linear-gradient(135deg, #186D6A 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(32, 138, 135, 0.4);
}

.button:hover::before, .btn-news-readmore:hover::before {
    left: 100%;
}

.button:active, .btn-news-readmore:active {
    transform: translateY(0);
}

.button-logout {
    background: linear-gradient(135deg, #D69D2A 0%, #C58624 100%) !important;
    box-shadow: 0 4px 15px rgba(214, 157, 42, 0.3) !important;
}

.button-logout:hover {
    background: linear-gradient(135deg, #C58624 0%, #B07420 100%) !important;
    box-shadow: 0 8px 25px rgba(214, 157, 42, 0.4) !important;
}

/* Activity Types */
.activity-tipo-trabalho {
    border-left: 4px solid #17a2b8 !important;
    background: linear-gradient(90deg, rgba(23, 162, 184, 0.05), transparent) !important;
}

.activity-tipo-prova {
    border-left: 4px solid #dc3545 !important;
    background: linear-gradient(90deg, rgba(220, 53, 69, 0.05), transparent) !important;
}

.activity-tipo-evento {
    border-left: 4px solid #ffc107 !important;
    background: linear-gradient(90deg, rgba(255, 193, 7, 0.05), transparent) !important;
}

/* No content messages */
.no-news, .no-activities {
    text-align: center;
    padding: 3rem;
    font-size: 1.1rem;
    color: #666;
    background: rgba(32, 138, 135, 0.03);
    border-radius: 12px;
    border: 2px dashed rgba(32, 138, 135, 0.2);
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
    
    .quick-access-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .quick-access-card {
        padding: 1.5rem 1rem;
        min-height: 120px;
    }
    
    .quick-access-card i {
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

        /* Estilos estruturais. Cores/fontes de temas_globais.css e aluno.css */
        .main-content h2.section-title { font-size: 1.6rem; margin-top: 2rem; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .main-content h2.page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; padding-bottom: 0.5rem; display: inline-block; }
        .welcome-message-aluno { text-align: center; font-size: 1.5rem; margin-bottom: 2rem; font-weight: 500; }
        
        .quick-access-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .quick-access-card { 
            display: flex; flex-direction: column; align-items: center; justify-content: center; 
            padding: 1.2rem 1rem; border-radius: 8px; text-decoration: none; 
            transition: transform 0.2s, box-shadow 0.2s; min-height: 110px; 
            color: white; /* Cor do texto específica para estes cards com fundo colorido */
        }
        .quick-access-card:hover { transform: translateY(-5px); }
        .quick-access-card i { font-size: 2.2rem; margin-bottom: 0.7rem; }
        .quick-access-card span { font-size: 0.95rem; font-weight: bold; text-align: center; }
        
        .upcoming-activities ul { list-style: none; padding-left: 0; }
        .upcoming-activities li { 
            padding: 0.8rem 1rem; margin-bottom: 0.7rem; border-radius: 4px; font-size: 0.95rem; 
        }
        .upcoming-activities li .activity-date { font-weight: bold; }
        .upcoming-activities li.activity-tipo-trabalho { border-left: 3px solid #17a2b8; } 
        .upcoming-activities li.activity-tipo-prova { border-left: 3px solid #dc3545; } 
        .upcoming-activities li.activity-tipo-evento { border-left: 3px solid #ffc107; } 
        
        .news-feed { display: grid; gap: 1.5rem; }
        .news-item { 
            border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; 
            transition: transform 0.2s ease-in-out; 
        }
        .news-item:hover { transform: translateY(-5px); }
        .news-image-container { width: 100%; max-height: 200px; overflow: hidden; }
        .news-image { width: 100%; height: 100%; object-fit: cover; display: block; }
        .news-content { padding: 1rem 1.5rem 1.5rem 1.5rem; flex-grow: 1; display: flex; flex-direction: column; }
        .news-title { font-size: 1.3rem; margin-bottom: 0.5rem; }
        .news-meta { font-size: 0.8rem; margin-bottom: 0.75rem; }
        .news-meta .news-date { font-weight: bold; }
        .news-meta .news-category { 
            padding: 0.2rem 0.5rem; border-radius: 4px; 
        }
        .news-summary { font-size: 0.95rem; line-height: 1.6; margin-bottom: 1rem; flex-grow: 1; }
        .btn-news-readmore { 
            display: inline-block; padding: 0.5rem 1rem; 
            text-decoration: none; border-radius: 4px; font-size: 0.9rem; 
            text-align: center; align-self: flex-start; transition: background-color 0.3s; 
        }
        .btn-news-readmore i { margin-left: 5px; }
        .no-news, .no-activities { text-align: center; padding: 30px; font-size: 1.2rem; }

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
        <h1>ACADMIX - Portal do Aluno</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"> <i class="fas fa-sign-out-alt"></i> Sair
            </button>
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
            <div class="welcome-message-aluno">
                <h3>Bem-vindo(a), <?php echo htmlspecialchars($nome_aluno); ?>!</h3>
            </div>

            <section class="dashboard-section quick-access">
                <h2 class="section-title" style="text-align:left; display:block;">Acesso Rápido</h2>
                <div class="quick-access-grid">
                    <?php foreach ($acesso_rapido_aluno as $item): ?>
                        <a href="<?php echo htmlspecialchars($item['link']); ?>" class="quick-access-card card" style="background-color: <?php echo htmlspecialchars($item['cor']); ?>;">
                            <i class="<?php echo htmlspecialchars($item['icone']); ?>"></i>
                            <span><?php echo htmlspecialchars($item['titulo']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="dashboard-section upcoming-activities card">
                <h2 class="section-title" style="text-align:left; display:block;">Próximas Atividades e Prazos</h2>
                <?php if (empty($proximas_atividades_static)): ?>
                    <p class="no-activities">Nenhuma atividade programada por enquanto.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($proximas_atividades_static as $atividade): ?>
                            <li class="activity-tipo-<?php echo htmlspecialchars($atividade['tipo']); ?> card-item"> 
                                <span class="activity-date"><i class="fas fa-calendar-day"></i> <?php echo date("d/m/Y", strtotime($atividade['data'])); ?>:</span>
                                <?php echo htmlspecialchars($atividade['descricao']); ?>
                                <?php if(!empty($atividade['disciplina'])): ?>
                                    <span style="font-size:0.8em; opacity:0.7;"> (<?php echo htmlspecialchars($atividade['disciplina']); ?>)</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <div style="text-align: center; margin-top: 2rem;"> 
                <h2 class="page-title">Fique por Dentro!</h2>
            </div>
            <div class="news-feed">
                <?php if (empty($noticias_static)): ?>
                    <p class="no-news">Nenhuma notícia ou atualização no momento.</p>
                <?php else: ?>
                    <?php foreach ($noticias_static as $noticia): ?>
                        <article class="news-item card">
                            <?php if (!empty($noticia['imagem_url']) && file_exists($noticia['imagem_url'])): ?>
                            <div class="news-image-container">
                                <img src="<?php echo htmlspecialchars($noticia['imagem_url']); ?>" alt="Imagem para <?php echo htmlspecialchars($noticia['titulo']); ?>" class="news-image">
                            </div>
                            <?php endif; ?>
                            <div class="news-content">
                                <h3 class="news-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h3>
                                <p class="news-meta">
                                    <span class="news-date"><i class="fas fa-calendar-alt"></i> <?php echo date("d/m/Y", strtotime($noticia['data_publicacao'])); ?></span>
                                    <?php if(!empty($noticia['categoria'])): ?>
                                        | <span class="news-category tag"><?php echo htmlspecialchars($noticia['categoria']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="news-summary"><?php echo htmlspecialchars($noticia['resumo']); ?></p>
                                <?php if (!empty($noticia['link_externo']) && $noticia['link_externo'] !== '#'): ?>
                                <a href="<?php echo htmlspecialchars($noticia['link_externo']); ?>" class="btn-news-readmore button" target="_blank">
                                    Saiba Mais <i class="fas fa-external-link-alt"></i>
                                </a>
                                <?php elseif ($noticia['link_externo'] === 'materiais.php'): ?>
                                 <a href="<?php echo htmlspecialchars($noticia['link_externo']); ?>" class="btn-news-readmore button">
                                    Ver Materiais <i class="fas fa-arrow-right"></i>
                                 </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
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
        const currentUserId = <?php echo json_encode($aluno_id); ?>;
        const currentUserSessionRole = <?php echo json_encode($_SESSION['role']); ?>; 
        const currentUserTurmaIdForStudent = <?php echo json_encode($turma_id_aluno); ?>; 

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
                if (currentUserTurmaIdForStudent === 0) {
                    if(userListUl) userListUl.innerHTML = '<li>Turma não definida para carregar contatos.</li>';
                    return;
                }
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
            console.log("fetchAndDisplayMessages - Enviando para API:", { action: 'get_messages', contact_id: contactId, contact_role: contactRole, current_user_id: currentUserId, current_user_role: currentUserChatRole });
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
            const filteredUsers = allContacts.filter(user => // Alterado de allTurmaUsers para allContacts
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
<?php // if(isset($conn) && $conn) mysqli_close($conn); // A conexão do banco de dados principal é fechada no final do script, se aberta. ?>