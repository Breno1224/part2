<?php
session_start(); // Deve ser a primeira linha
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: index.html");
    exit();
}
include 'db.php';

// Dados do PERFIL SENDO VISUALIZADO
$perfil_id_para_exibir = isset($_GET['id']) ? intval($_GET['id']) : (isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0); // Default para próprio perfil se ID não passado

// Dados do USUÁRIO LOGADO (VISUALIZADOR)
$viewer_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$viewer_id = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
$viewer_nome = $_SESSION['usuario_nome'] ?? 'Visitante';
// Turma do visualizador, relevante se o visualizador for um aluno
$viewer_turma_id = ($viewer_role === 'aluno' && isset($_SESSION['turma_id'])) ? intval($_SESSION['turma_id']) : 0; 

$is_own_profile = ($viewer_role === 'coordenacao' && $viewer_id == $perfil_id_para_exibir);
$currentPageIdentifier = $is_own_profile ? 'meu_perfil_coord' : null; 

// Tema global é SEMPRE do USUÁRIO LOGADO (VISUALIZADOR)
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

$coordenador_info = null; // Informações do perfil que está sendo visualizado
if ($perfil_id_para_exibir > 0) {
    $sql_coordenador = "SELECT id, nome, email, foto_url, data_criacao, tema_perfil 
                        FROM coordenadores 
                        WHERE id = ?";
    $stmt_coordenador_prepare = mysqli_prepare($conn, $sql_coordenador);
    if ($stmt_coordenador_prepare) {
        mysqli_stmt_bind_param($stmt_coordenador_prepare, "i", $perfil_id_para_exibir);
        mysqli_stmt_execute($stmt_coordenador_prepare);
        $result_coordenador = mysqli_stmt_get_result($stmt_coordenador_prepare);
        $coordenador_info = mysqli_fetch_assoc($result_coordenador);
        mysqli_stmt_close($stmt_coordenador_prepare);
    } else {
        error_log("Erro ao preparar statement para perfil do coordenador: " . mysqli_error($conn));
    }
}

$ano_inicio = $coordenador_info ? date("Y", strtotime($coordenador_info['data_criacao'])) : 'N/A';
// Tema que o DONO deste perfil escolheu (usado para pré-selecionar o <select> de temas SE for o próprio perfil)
$tema_escolhido_pelo_dono_do_perfil = $coordenador_info && !empty($coordenador_info['tema_perfil']) ? $coordenador_info['tema_perfil'] : 'padrao';

$temas_disponiveis = [
    'padrao' => 'Padrão do Sistema', '8bit' => '8-Bit Retrô',
    'natureza' => 'Natureza Calma', 'academico' => 'Acadêmico Clássico',
    'darkmode' => 'Modo Escuro Simples'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?php echo $coordenador_info ? htmlspecialchars($coordenador_info['nome']) : 'Coordenação'; ?> - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/coordenacao.css"> 
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
body.theme-8bit .main-content h2,
body.theme-8bit .profile-header h2,
body.theme-8bit .profile-section h3 {
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

/* Profile Container */
.profile-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1rem;
}

.profile-header {
    text-align: center;
    margin-bottom: 1.5rem;
    width: 100%;
}

.profile-photo-wrapper {
    position: relative;
    margin-bottom: 1rem;
    display: inline-block;
}

.profile-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid var(--primary-color-dark); /* Adiciona borda colorida */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Sombra para destacar a foto */
}

body.theme-8bit .profile-photo {
    border: 5px dashed var(--8bit-primary-color-dark);
    box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.7);
}

.profile-header h2 {
    font-size: 2rem;
    margin-bottom: 0.25rem;
    color: var(--text-color);
}

body.theme-8bit .profile-header h2 {
    color: var(--8bit-text-color);
}

.profile-header .member-since {
    font-size: 1rem;
    margin-bottom: 1rem;
    color: var(--text-color-light);
}

body.theme-8bit .profile-header .member-since {
    color: var(--8bit-text-color-light);
}

.upload-form-container {
    margin-top: 10px;
    text-align: center;
}

.upload-form-container .input-field {
    width: auto; /* Permite que o input de arquivo não ocupe 100% da largura */
    display: inline-block;
    margin-right: 10px;
}

.status-message-profile {
    padding: 0.8rem;
    margin-top: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    text-align: center;
    font-size: 0.9rem;
}

.status-message-profile.status-success {
    background-color: rgba(var(--success-color-rgb, 40, 167, 69), 0.1);
    border: 1px solid var(--success-color);
    color: var(--success-color);
}

.status-message-profile.status-error {
    background-color: rgba(var(--danger-color-rgb, 220, 53, 69), 0.1);
    border: 1px solid var(--danger-color);
    color: var(--danger-color);
}

body.theme-8bit .status-message-profile.status-success {
    background-color: rgba(39, 174, 96, 0.1);
    border: 1px dashed var(--8bit-success-color);
    color: var(--8bit-success-color);
}
body.theme-8bit .status-message-profile.status-error {
    background-color: rgba(231, 76, 60, 0.1);
    border: 1px dashed var(--8bit-danger-color);
    color: var(--8bit-danger-color);
}


.profile-details {
    width: 100%;
    max-width: 700px;
}

.profile-section {
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    background-color: var(--card-background);
    box-shadow: var(--card-shadow);
}

body.theme-8bit .profile-section {
    background-color: var(--8bit-card-background);
    box-shadow: var(--8bit-card-shadow);
    border: 2px solid var(--8bit-border-color);
}

.profile-section h3 {
    font-size: 1.3rem;
    margin-top: 0;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
}

body.theme-8bit .profile-section h3 {
    color: var(--8bit-primary-color);
    border-bottom: 2px dashed var(--8bit-primary-color);
}


.profile-section p {
    font-size: 1rem;
    line-height: 1.6;
    color: var(--text-color);
}

body.theme-8bit .profile-section p {
    color: var(--8bit-text-color);
}


/* Edit Section */
.edit-section details {
    margin-bottom: 10px;
}

.edit-section summary {
    cursor: pointer;
    font-weight: bold;
    padding: 0.5rem;
    border-radius: 4px;
    display: inline-block;
    background-color: var(--background-color-offset);
    color: var(--text-color);
    transition: background-color 0.2s ease;
}

body.theme-8bit .edit-section summary {
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
    border: 1px dashed var(--8bit-border-color-soft);
}

.edit-section summary:hover {
    background-color: var(--hover-background-color);
}

body.theme-8bit .edit-section summary:hover {
    background-color: var(--8bit-hover-background-color);
}

.edit-section form {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px solid var(--border-color-soft);
    border-radius: 4px;
    background-color: var(--background-color);
}

body.theme-8bit .edit-section form {
    border: 1px dashed var(--8bit-border-color-soft);
    background-color: var(--8bit-background-color);
}

.edit-section label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-color);
}

body.theme-8bit .edit-section label {
    color: var(--8bit-text-color);
}

.edit-section textarea,
.edit-section select {
    width: 100%;
    padding: 0.75rem;
    border-radius: 4px;
    box-sizing: border-box;
    margin-bottom: 1rem;
    border: 1px solid var(--border-color);
    background-color: var(--background-color-offset);
    color: var(--text-color);
}

body.theme-8bit .edit-section textarea,
body.theme-8bit .edit-section select {
    border: 1px dashed var(--8bit-border-color);
    background-color: var(--8bit-background-color-offset);
    color: var(--8bit-text-color);
}

.edit-section button[type="submit"] {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
}

/* Button general styles */
.button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    border-radius: 25px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Primary Button */
.button-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
    color: var(--button-text-color);
    box-shadow: 0 4px 15px rgba(32, 138, 135, 0.3);
}

.button-primary:hover {
    background: linear-gradient(135deg, var(--primary-color-dark) 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(32, 138, 135, 0.4);
}

body.theme-8bit .button-primary {
    background: var(--8bit-primary-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
    box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.5);
}

body.theme-8bit .button-primary:hover {
    background: var(--8bit-primary-color-dark);
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.7);
}

/* Logout Button */
.button-logout {
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-color-dark) 100%);
    color: var(--button-text-color);
    box-shadow: 0 4px 15px rgba(214, 157, 42, 0.3);
}

.button-logout:hover {
    background: linear-gradient(135deg, var(--secondary-color-dark) 0%, #B07420 100%);
    box-shadow: 0 6px 20px rgba(214, 157, 42, 0.4);
}

body.theme-8bit .button-logout {
    background: var(--8bit-secondary-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
    box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.5);
}

body.theme-8bit .button-logout:hover {
    background: var(--8bit-secondary-color-dark);
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.7);
}

/* Small button */
.button-small {
    padding: 0.6rem 1.2rem;
    font-size: 0.85rem;
}

/* No data message */
.no-data {
    font-style: italic;
    color: var(--text-color-light);
}

body.theme-8bit .no-data {
    color: var(--8bit-text-color-light);
}

/* Error message */
.error-message {
    text-align: center;
    color: var(--danger-color);
    font-size: 1.2rem;
    padding: 2rem;
    background-color: rgba(var(--danger-color-rgb, 220, 53, 69), 0.1);
    border: 1px dashed var(--danger-color);
    border-radius: 8px;
}

body.theme-8bit .error-message {
    background-color: rgba(231, 76, 60, 0.1);
    border: 1px dashed var(--8bit-danger-color);
    color: var(--8bit-danger-color);
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

    .profile-container {
        padding: 0.5rem;
    }

    .profile-photo {
        width: 120px;
        height: 120px;
    }

    .profile-header h2 {
        font-size: 1.6rem;
    }

    .profile-header .member-since {
        font-size: 0.9rem;
    }

    .upload-form-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    .upload-form-container .input-field {
        width: 80%; /* Ajuste para melhor visualização em mobile */
        margin-right: 0;
    }
    .upload-form-container button {
        width: 80%;
    }


    .profile-details {
        padding: 0 1rem;
    }

    .profile-section {
        padding: 1rem;
    }

    .profile-section h3 {
        font-size: 1.1rem;
    }

    .profile-section p {
        font-size: 0.9rem;
    }

    .edit-section form {
        padding: 0.8rem;
    }

    .edit-section label {
        font-size: 0.9rem;
    }

    .edit-section textarea,
    .edit-section select {
        padding: 0.6rem;
        font-size: 0.9rem;
    }

    .edit-section button[type="submit"] {
        width: 100%;
        padding: 0.8rem;
        font-size: 0.95rem;
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


/* --- CHAT ACADÊMICO --- */
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
        /* Estilos ESTRUTURAIS. Cores/fontes virão dos temas via .card, .button, etc. */
        .profile-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; padding:1rem; }
        .profile-header { text-align: center; margin-bottom: 1.5rem; width:100%;}
        .profile-photo-wrapper { position: relative; margin-bottom: 1rem; display:inline-block; }
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; }
        .profile-header h2 { font-size: 2rem; margin-bottom: 0.25rem; }
        .profile-header .member-since { font-size: 1rem; margin-bottom: 1rem; }
        .upload-form-container { margin-top: 10px; text-align:center; }
        .status-message-profile { padding: 0.8rem; margin-top:1rem; margin-bottom: 1rem; border-radius: 4px; text-align:center; font-size:0.9rem; }
        .profile-details { width: 100%; max-width: 700px; }
        .profile-section { padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .profile-section h3 { font-size: 1.3rem; margin-top: 0; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .profile-section p { font-size: 1rem; line-height: 1.6; }
        .edit-section details { margin-bottom: 10px; }
        .edit-section summary { cursor: pointer; font-weight: bold; padding: 0.5rem; border-radius:4px; display: inline-block;}
        .edit-section form { margin-top: 1rem; padding:1rem; border:1px solid var(--border-color-soft, #eee); border-radius:4px;}
        .edit-section label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .edit-section textarea, .edit-section select { width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box; margin-bottom:1rem; }
        .edit-section button[type="submit"] { padding: 0.6rem 1.2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        .no-data { font-style: italic; }
        .error-message { text-align: center; color: var(--danger-color, red); font-size: 1.2rem; padding: 2rem; }

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
        <h1>ACADMIX - Perfil da Coordenação</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_include_path = __DIR__ . '/includes/sidebar_coordenacao.php';
            if (!empty($sidebar_include_path) && file_exists($sidebar_include_path)) { 
                include $sidebar_include_path; 
            } else { 
                echo "<p style='padding:1rem; color:white;'>Menu não disponível.</p>"; 
            }
            ?>
        </nav>

        <main class="main-content">
            <?php if ($coordenador_info): ?>
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-photo-wrapper">
                            <img src="<?php echo htmlspecialchars(!empty($coordenador_info['foto_url']) ? $coordenador_info['foto_url'] : 'img/coordenadores/default_avatar.png'); ?>"
                                 alt="Foto de <?php echo htmlspecialchars($coordenador_info['nome']); ?>"
                                 class="profile-photo"
                                 onerror="this.onerror=null; this.src='img/coordenadores/default_avatar.png';">
                        </div>

                        <?php if ($is_own_profile): ?>
                        <div class="upload-form-container">
                            <form action="upload_foto_coordenador.php" method="post" enctype="multipart/form-data">
                                <input type="file" name="foto_perfil" accept="image/jpeg, image/png, image/gif" required class="input-field">
                                <button type="submit" class="button button-small"><i class="fas fa-upload"></i> Alterar Foto</button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <?php if(isset($_SESSION['upload_status_message'])): ?>
                            <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['upload_status_type']); ?>">
                                <?php echo htmlspecialchars($_SESSION['upload_status_message']); ?>
                            </div>
                            <?php unset($_SESSION['upload_status_message']); unset($_SESSION['upload_status_type']); ?>
                        <?php endif; ?>

                        <h2><?php echo htmlspecialchars($coordenador_info['nome']); ?></h2>
                        <p class="member-since">Membro da equipe desde <?php echo $ano_inicio; ?></p>
                    </div>

                    <div class="profile-details">
                        <?php if ($is_own_profile): ?>
                        <section class="profile-section edit-section card">
                            <details open> <summary><i class="fas fa-palette"></i> Editar Tema Visual do Sistema</summary>
                                <form action="salvar_tema_coordenador.php" method="POST" style="margin-top:1rem;">
                                    <label for="tema_perfil_select_coord">Escolha um Tema para o Sistema:</label>
                                    <select id="tema_perfil_select_coord" name="tema_perfil_coordenador" class="input-field">
                                        <?php 
                                        foreach($temas_disponiveis as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>" <?php if($tema_global_usuario == $value) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="button button-primary"><i class="fas fa-save"></i> Aplicar Tema</button>
                                </form>
                                <?php if(isset($_SESSION['tema_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['tema_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['tema_status_message']); ?></div>
                                <?php unset($_SESSION['tema_status_message']); unset($_SESSION['tema_status_type']); ?>
                                <?php endif; ?>
                            </details>
                        </section>
                        <?php endif; ?>

                        <section class="profile-section card">
                            <h3><i class="fas fa-info-circle"></i> Informações de Contato</h3>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($coordenador_info['email']); ?></p>
                        </section>
                        
                    </div>
                </div>
            <?php else: ?>
                <p class="error-message">Perfil da coordenação não encontrado.</p>
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
        const currentUserId = <?php echo json_encode($viewer_id); ?>; 
        const currentUserSessionRole = <?php echo json_encode($viewer_role); ?>; 
        const currentUserTurmaIdForStudent = <?php echo json_encode($viewer_turma_id); ?>;

        let currentUserChatRole = '';
        if (currentUserSessionRole === 'aluno') {
            currentUserChatRole = 'aluno'; 
        } else if (currentUserSessionRole === 'docente') {
            currentUserChatRole = 'professor'; 
        } else if (currentUserSessionRole === 'coordenacao') {
            currentUserChatRole = 'coordenador';
        } else {
            // O chat não será inicializado com uma lista de contatos se o papel não for reconhecido
            console.warn("Chat: Papel do visualizador não explicitamente suportado para lista de contatos:", currentUserSessionRole);
        }
        
        const defaultUserPhoto = 'img/alunos/default_avatar.png';
        const defaultProfessorPhoto = 'img/professores/default_avatar.png'; 
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
            if (!chatWidget) return; // Se o widget não existir, não faz nada
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
                    if(userListUl) userListUl.innerHTML = '<li>Turma não definida para aluno.</li>';
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
        } else {
            console.warn("Elemento de cabeçalho do Chat não encontrado.");
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
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>