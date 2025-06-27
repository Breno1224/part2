<?php
session_start(); // GARANTIR que está no topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';

// Renomeando para consistência com o que o chat JS espera
$nome_professor = $_SESSION['usuario_nome']; 
$professor_id = $_SESSION['usuario_id']; // Essencial para o chat

// Define o identificador da página atual para a sidebar
$currentPageIdentifier = 'ver_comunicados_prof'; 

// PEGAR TEMA DA SESSÃO
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Subquery para pegar as turmas do professor logado
$turmas_do_professor_ids = [];
$sql_minhas_turmas = "SELECT DISTINCT turma_id FROM professores_turmas_disciplinas WHERE professor_id = ?";
$stmt_minhas_turmas_prepare = mysqli_prepare($conn, $sql_minhas_turmas); // Nome de variável ajustado
$turmas_ids_string = '0'; // Default para evitar erro SQL se não houver turmas

if ($stmt_minhas_turmas_prepare) {
    mysqli_stmt_bind_param($stmt_minhas_turmas_prepare, "i", $professor_id); // Usando $professor_id
    mysqli_stmt_execute($stmt_minhas_turmas_prepare);
    $result_minhas_turmas = mysqli_stmt_get_result($stmt_minhas_turmas_prepare);
    while ($row_turma = mysqli_fetch_assoc($result_minhas_turmas)) {
        $turmas_do_professor_ids[] = $row_turma['turma_id'];
    }
    mysqli_stmt_close($stmt_minhas_turmas_prepare);
    if (!empty($turmas_do_professor_ids)) {
        $turmas_ids_string = implode(',', array_map('intval', $turmas_do_professor_ids));
    }
} else {
    error_log("Erro ao buscar turmas do professor (comunicados_professor_ver.php): " . mysqli_error($conn));
}

$sql_comunicados_prof = "
    SELECT 
        c.titulo, c.conteudo, c.data_publicacao, 
        p_remetente.nome as nome_professor_remetente, 
        coord.nome as nome_coordenador_remetente,
        t.nome_turma,
        c.publico_alvo, c.professor_id AS comunicado_professor_id, c.coordenador_id AS comunicado_coordenador_id
    FROM comunicados c
    LEFT JOIN professores p_remetente ON c.professor_id = p_remetente.id
    LEFT JOIN coordenadores coord ON c.coordenador_id = coord.id
    LEFT JOIN turmas t ON c.turma_id = t.id 
    WHERE 
        (c.coordenador_id IS NOT NULL AND c.publico_alvo = 'TODOS_PROFESSORES') OR 
        (c.professor_id = ?) OR 
        (c.publico_alvo = 'TURMA_ESPECIFICA' AND c.turma_id IN (" . $turmas_ids_string . ") AND (c.professor_id != ? OR c.professor_id IS NULL) )
    ORDER BY c.data_publicacao DESC";

$stmt_prof_com_prepare = mysqli_prepare($conn, $sql_comunicados_prof); // Nome de variável ajustado
$result_comunicados_prof_data = null; // Nome de variável ajustado
if($stmt_prof_com_prepare){
    mysqli_stmt_bind_param($stmt_prof_com_prepare, "ii", $professor_id, $professor_id); // Usando $professor_id
    mysqli_stmt_execute($stmt_prof_com_prepare);
    $result_comunicados_prof_data = mysqli_stmt_get_result($stmt_prof_com_prepare);
} else {
    error_log("Erro ao preparar statement para buscar comunicados do professor (comunicados_professor_ver.php): " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Comunicados - Professor ACADMIX</title>
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

    /* Cores específicas para comunicados */
    --comunicado-border-color: var(--primary-color); /* Borda padrão para comunicados */
    --comunicado-coord-border-color: var(--accent-color); /* Borda para comunicados da coordenação */
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
    margin-bottom: 2rem; /* Aumentada margem inferior */
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

/* Estilos específicos da página comunicados_professor_ver.php */
.comunicado-item {
    border-left-width: 5px;
    border-left-style: solid;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: 0 5px 5px 0;
    background-color: var(--card-background); /* Fundo branco */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); /* Sombra mais suave */
    transition: all 0.3s ease;
    border-color: var(--comunicado-border-color); /* Borda padrão */
}

.comunicado-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.comunicado-item h3 {
    font-size: 1.4rem; /* Um pouco maior */
    margin-top: 0;
    margin-bottom: 0.8rem; /* Mais espaço abaixo do título */
    color: var(--text-color);
    font-weight: 700; /* Mais forte */
}

.comunicado-meta {
    font-size: 0.9rem; /* Um pouco maior */
    margin-bottom: 1.2rem; /* Mais espaço abaixo da meta */
    color: var(--text-color-muted);
}

.comunicado-meta .author {
    font-weight: bold;
    color: var(--primary-color-dark); /* Cor para o autor professor */
}

.comunicado-meta .author-coord {
    font-weight: bold;
    color: var(--accent-color-dark); /* Cor para o autor coordenador */
}

.comunicado-meta .target-turma {
    font-weight: 600;
    color: var(--primary-color);
}

.comunicado-conteudo {
    font-size: 1rem;
    line-height: 1.7; /* Mais espaçamento entre linhas */
    white-space: pre-wrap;
    color: var(--text-color);
}

.no-comunicados {
    text-align: center;
    padding: 2rem; /* Mais padding */
    font-size: 1.1rem;
    color: var(--text-color-muted);
    background: rgba(var(--primary-color-rgb), 0.03);
    border-radius: 12px;
    border: 2px dashed rgba(var(--primary-color-rgb), 0.2);
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

    .comunicado-item {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .comunicado-item h3 {
        font-size: 1.2rem;
    }
    .comunicado-meta {
        font-size: 0.8rem;
    }
    .comunicado-conteudo {
        font-size: 0.9rem;
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
        /* Estilos da página comunicados_professor_ver.php */
        .main-content h2.page-title { 
            text-align: center; font-size: 1.8rem; margin-bottom: 2rem; 
            padding-bottom: 0.5rem; display: inline-block;
        }
        .comunicado-item { 
            border-left-width: 5px; border-left-style: solid; 
            padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 0 5px 5px 0; 
        }
        .comunicado-item h3 { 
            font-size: 1.3rem; margin-top: 0; margin-bottom: 0.5rem; 
        }
        .comunicado-meta { 
            font-size: 0.85rem; margin-bottom: 1rem; 
        }
        .comunicado-meta .author, .comunicado-meta .author-coord { font-weight: bold; }
        .comunicado-conteudo { 
            font-size: 1rem; line-height: 1.6; white-space: pre-wrap; 
        }
        .no-comunicados { text-align: center; padding: 20px; font-size: 1.1rem; }

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
        <h1>ACADMIX - Quadro de Avisos</h1>
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
            <div style="text-align: center;">
                <h2 class="page-title">Comunicados Importantes</h2>
            </div>
            <?php if($result_comunicados_prof_data && mysqli_num_rows($result_comunicados_prof_data) > 0): ?>
                <?php while($com = mysqli_fetch_assoc($result_comunicados_prof_data)): ?>
                    <?php
                    $remetente_display = ""; 
                    $classe_css_remetente = ""; 
                    
                    if (!empty($com['comunicado_coordenador_id'])) {
                        $remetente_display = "Coordenação (" . htmlspecialchars($com['nome_coordenador_remetente'] ?? 'N/A') . ")";
                        $classe_css_remetente = "coord"; 
                    } elseif (!empty($com['comunicado_professor_id'])) {
                        if ($com['comunicado_professor_id'] == $professor_id) { // Usando $professor_id
                             $remetente_display = "Você";
                        } else {
                             $remetente_display = "Prof. " . htmlspecialchars($com['nome_professor_remetente'] ?? 'N/A');
                        }
                    } else {
                        $remetente_display = "Sistema";
                    }

                    $publico_display = "";
                     if ($com['publico_alvo'] === 'TODOS_PROFESSORES') {
                        $publico_display = "Todos os Professores";
                    } elseif ($com['publico_alvo'] === 'TURMA_ESPECIFICA' && !empty($com['nome_turma'])) {
                        $publico_display = htmlspecialchars($com['nome_turma']);
                    } elseif ($com['publico_alvo'] === 'PROFESSOR_GERAL_ALUNOS') {
                         $publico_display = "Alunos (Geral do Remetente)";
                    } elseif ($com['publico_alvo'] === 'TODOS_ALUNOS') { 
                        $publico_display = "Alunos (Geral da Escola)";
                    }
                    ?>
                    <article class="comunicado-item card <?php echo $classe_css_remetente; ?>"> <h3><?php echo htmlspecialchars($com['titulo']); ?></h3>
                        <p class="comunicado-meta">
                            Publicado por: <span class="author <?php if($classe_css_remetente === 'coord') echo 'author-coord'; ?>"><?php echo $remetente_display; ?></span> |
                            Em: <?php echo date("d/m/Y H:i", strtotime($com['data_publicacao'])); ?>
                            <?php if(!empty($publico_display)): ?> | Para: <span class="target-turma"><?php echo $publico_display; ?></span><?php endif; ?>
                        </p>
                        <div class="comunicado-conteudo"><?php echo nl2br(htmlspecialchars($com['conteudo'])); ?></div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-comunicados info-message">Nenhum comunicado para visualizar no momento.</p>
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
        const currentUserId = <?php echo json_encode($professor_id); ?>; // Usando $professor_id
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
if(isset($stmt_prof_com_prepare)) mysqli_stmt_close($stmt_prof_com_prepare); // Corrigido para usar o nome da variável do statement
if(isset($conn) && $conn) mysqli_close($conn); 
?>