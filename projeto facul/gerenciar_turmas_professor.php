<?php
session_start(); // GARANTIR QUE ESTÁ NO TOPO ABSOLUTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php'; // Conexão com o banco

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id']; // Essencial para o chat
// $_SESSION['role'] é 'docente', será usado no JS do chat

$currentPageIdentifier = 'minhas_turmas'; // Para a sidebar

// PEGAR TEMA DA SESSÃO
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// 1. Buscar as turmas associadas a este professor
$turmas_professor = [];
$sql_turmas = "SELECT DISTINCT t.id, t.nome_turma 
               FROM turmas t
               JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id
               WHERE ptd.professor_id = ?
               ORDER BY t.nome_turma";
$stmt_turmas_prepare = mysqli_prepare($conn, $sql_turmas);
if ($stmt_turmas_prepare) {
    mysqli_stmt_bind_param($stmt_turmas_prepare, "i", $professor_id);
    mysqli_stmt_execute($stmt_turmas_prepare);
    $result_turmas = mysqli_stmt_get_result($stmt_turmas_prepare);
    while ($row = mysqli_fetch_assoc($result_turmas)) {
        $turmas_professor[] = $row;
    }
    mysqli_stmt_close($stmt_turmas_prepare);
} else {
    error_log("Erro ao buscar turmas do professor (gerenciar_turmas_professor.php): " . mysqli_error($conn));
}

// 2. Verificar se uma turma foi selecionada para listar os alunos
$alunos_da_turma = [];
$turma_selecionada_id = null;
$nome_turma_selecionada = "";
$professor_tem_acesso_turma = false; 

if (isset($_GET['turma_id']) && !empty($_GET['turma_id'])) {
    $turma_selecionada_id = intval($_GET['turma_id']);

    foreach ($turmas_professor as $turma_p) {
        if ($turma_p['id'] == $turma_selecionada_id) {
            $professor_tem_acesso_turma = true;
            $nome_turma_selecionada = $turma_p['nome_turma'];
            break;
        }
    }

    if ($professor_tem_acesso_turma) {
        $sql_alunos = "SELECT id, nome, email, foto_url 
                       FROM alunos 
                       WHERE turma_id = ? 
                       ORDER BY nome";
        $stmt_alunos_prepare = mysqli_prepare($conn, $sql_alunos);
        if ($stmt_alunos_prepare) {
            mysqli_stmt_bind_param($stmt_alunos_prepare, "i", $turma_selecionada_id);
            mysqli_stmt_execute($stmt_alunos_prepare);
            $result_alunos = mysqli_stmt_get_result($stmt_alunos_prepare);
            while ($row = mysqli_fetch_assoc($result_alunos)) {
                $alunos_da_turma[] = $row;
            }
            mysqli_stmt_close($stmt_alunos_prepare);
        } else {
            error_log("Erro ao buscar alunos da turma (gerenciar_turmas_professor.php): " . mysqli_error($conn));
        }
    } else {
      $turma_selecionada_id = null; 
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Turmas - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> 
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

/* Estilos específicos da página gerenciar_turmas_professor.php */
.dashboard-section {
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    background: white;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(32, 138, 135, 0.1);
}

.dashboard-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #208A87, #D69D2A);
}

.dashboard-section:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
}

.dashboard-section h3 {
    font-size: 1.4rem;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    color: #208A87;
    font-weight: 600;
    position: relative;
}

.dashboard-section h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(90deg, #208A87, #D69D2A);
    border-radius: 2px;
}

.turma-select-form {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.turma-select-form label {
    font-weight: 600;
    color: #2C1B17;
    font-size: 1rem;
}

.turma-select-form select {
    padding: 0.8rem 1rem;
    border: 2px solid rgba(32, 138, 135, 0.2);
    border-radius: 12px;
    min-width: 250px;
    font-size: 0.95rem;
    background: white;
    color: #2C1B17;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.turma-select-form select:focus {
    outline: none;
    border-color: #208A87;
    box-shadow: 0 0 0 3px rgba(32, 138, 135, 0.1);
    transform: translateY(-1px);
}

.student-list-container {
    margin-top: 2rem;
}

.student-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 2rem;
    margin-top: 1.5rem;
}

.student-card {
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    background: white;
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(32, 138, 135, 0.1);
    position: relative;
    overflow: hidden;
}

.student-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #208A87, #D69D2A);
    transition: width 0.3s ease;
}

.student-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.15);
}

.student-card:hover::before {
    width: 8px;
}

.student-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 1.5rem;
    border: 3px solid rgba(32, 138, 135, 0.2);
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.student-card:hover .student-photo {
    border-color: #208A87;
    transform: scale(1.05);
}

.student-info {
    flex: 1;
}

.student-info h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.2rem;
    color: #2C1B17;
    font-weight: 600;
}

.student-info p {
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
    color: #666;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.student-info p i {
    color: #208A87;
    width: 16px;
}

.student-info .btn-profile {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    padding: 0.6rem 1.2rem;
    text-decoration: none;
    border-radius: 20px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: linear-gradient(135deg, #208A87 0%, #186D6A 100%);
    color: white;
    font-weight: 600;
    box-shadow: 0 3px 12px rgba(32, 138, 135, 0.3);
    position: relative;
    overflow: hidden;
}

.student-info .btn-profile::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.student-info .btn-profile:hover {
    background: linear-gradient(135deg, #186D6A 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(32, 138, 135, 0.4);
}

.student-info .btn-profile:hover::before {
    left: 100%;
}

.no-data-message {
    padding: 2rem;
    text-align: center;
    border-radius: 12px;
    font-size: 1.1rem;
    margin: 1rem 0;
}

.info-message {
    background: rgba(32, 138, 135, 0.05);
    color: #208A87;
    border: 2px dashed rgba(32, 138, 135, 0.2);
}

.error-message {
    background: rgba(220, 53, 69, 0.05);
    color: #dc3545;
    border: 2px dashed rgba(220, 53, 69, 0.2);
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

.card-item {
    background: white;
    border-radius: 16px;
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

/* Botões */
.button {
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
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(32, 138, 135, 0.3);
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
    background: linear-gradient(135deg, #186D6A 0%, #145A57 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(32, 138, 135, 0.4);
}

.button:hover::before {
    left: 100%;
}

.button:active {
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

.button-small {
    padding: 0.6rem 1.2rem;
    font-size: 0.85rem;
}

.input-field {
    padding: 0.8rem 1rem;
    border: 2px solid rgba(32, 138, 135, 0.2);
    border-radius: 12px;
    font-size: 0.95rem;
    background: white;
    color: #2C1B17;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.input-field:focus {
    outline: none;
    border-color: #208A87;
    box-shadow: 0 0 0 3px rgba(32, 138, 135, 0.1);
    transform: translateY(-1px);
}

/* CSS do Chat Acadêmico */
.chat-widget-acad {
    position: fixed;
    bottom: 0;
    right: 20px;
    width: 320px;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.15);
    z-index: 1000;
    overflow: hidden;
    transition: height 0.3s ease-in-out;
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
    background: linear-gradient(135deg, #208A87 0%, #186D6A 100%);
    color: white;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}

.chat-header-acad span {
    font-weight: bold;
}

.chat-toggle-btn-acad {
    background: none;
    border: none;
    color: white;
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
    background-color: white;
    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
}

#chatUserListScreenAcad, #chatConversationScreenAcad {
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
    border: 1px solid #eee;
    border-radius: 20px;
    box-sizing: border-box;
    font-size: 0.9em;
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
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #333;
}

#chatUserListUlAcad li:hover {
    background-color: #f0f0f0;
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

.teacher-icon-acad {
    margin-left: 5px;
    color: #208A87;
    font-size: 0.9em;
}

.student-icon-acad {
    margin-left: 5px;
    color: #6c757d;
    font-size: 0.9em;
}

.chat-conversation-header-acad {
    padding: 8px 10px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #eee;
    background-color: #f9f9f9;
    gap: 10px;
}

#chatBackToListBtnAcad {
    background: none;
    border: none;
    font-size: 1.1rem;
    cursor: pointer;
    padding: 5px;
    color: #208A87;
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
    color: #333;
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
    background-color: #dcf8c6;
    color: #333;
    align-self: flex-end;
    border-bottom-right-radius: 5px;
}

.message-acad.received-acad {
    background-color: #f1f0f0;
    color: #333;
    align-self: flex-start;
    border-bottom-left-radius: 5px;
}

.message-acad.error-acad {
    background-color: #f8d7da;
    color: #721c24;
    align-self: flex-end;
    border: 1px solid #f5c6cb;
}

.chat-message-input-area-acad {
    display: flex;
    padding: 8px 10px;
    border-top: 1px solid #eee;
    background-color: #f9f9f9;
    gap: 8px;
}

#chatMessageInputAcad {
    flex-grow: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 20px;
    resize: none;
    font-size: 0.9em;
    min-height: 20px;
    max-height: 80px;
    overflow-y: auto;
}

#chatSendMessageBtnAcad {
    background: #208A87;
    color: white;
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
    background: #186D6A;
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
    
    .student-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .student-card {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem;
    }
    
    .student-photo {
        margin: 0 0 1rem 0;
    }
    
    .turma-select-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .turma-select-form select {
        min-width: 100%;
    }
    
    header {
        padding: 1rem;
    }
    
    header h1 {
        font-size: 1.3rem;
    }
    
    .chat-widget-acad {
        width: 280px;
        right: 10px;
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
        /* Estilos da página gerenciar_turmas_professor.php */
        .dashboard-section { padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .dashboard-section h3 { font-size: 1.4rem; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .turma-select-form label { font-weight: bold; margin-right: 10px; }
        .turma-select-form select { padding: 0.5rem; border-radius: 4px; margin-right: 10px; min-width: 200px; }
        .student-list-container { margin-top: 2rem; }
        .student-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        .student-card { border-radius: 8px; padding: 1rem; display: flex; align-items: center; }
        .student-photo { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-right: 1rem; border: 2px solid var(--border-color-soft, #ddd); }
        .student-info h4 { margin: 0 0 0.3rem 0; font-size: 1.1rem; }
        .student-info p { margin: 0 0 0.5rem 0; font-size: 0.85rem; }
        .student-info .btn-profile { font-size: 0.8rem; padding: 0.3rem 0.7rem; text-decoration: none; border-radius: 4px; transition: background-color 0.2s;}
        .no-data-message { padding: 1rem; text-align: center; border-radius: 4px; }

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
        <h1>ACADMIX - Gerenciar Turmas (Prof. <?php echo htmlspecialchars($nome_professor); ?>)</h1>
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
            <section class="dashboard-section card">
                <h3>Selecione uma Turma para Visualizar Alunos</h3>
                <?php if (!empty($turmas_professor)): ?>
                    <form method="GET" action="gerenciar_turmas_professor.php" class="turma-select-form">
                        <label for="turma_id_select">Turma:</label>
                        <select name="turma_id" id="turma_id_select" onchange="this.form.submit()" class="input-field"> 
                            <option value="">-- Selecione uma Turma --</option>
                            <?php foreach ($turmas_professor as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>" <?php echo ($turma_selecionada_id == $turma['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($turma['nome_turma']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php else: ?>
                    <p class="no-data-message info-message">Você não está associado a nenhuma turma no momento ou nenhuma turma foi cadastrada.</p>
                <?php endif; ?>
            </section>

            <?php if ($turma_selecionada_id && $professor_tem_acesso_turma): ?>
                <section class="dashboard-section student-list-container card">
                    <h3>Alunos da Turma: <?php echo htmlspecialchars($nome_turma_selecionada); ?></h3>
                    <?php if (!empty($alunos_da_turma)): ?>
                        <div class="student-grid">
                            <?php foreach ($alunos_da_turma as $aluno): ?>
                                <div class="student-card card-item">
                                    <img src="<?php echo htmlspecialchars(!empty($aluno['foto_url']) ? $aluno['foto_url'] : 'img/alunos/default_avatar.png'); ?>" 
                                         alt="Foto de <?php echo htmlspecialchars($aluno['nome']); ?>" 
                                         class="student-photo"
                                         onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                                    <div class="student-info">
                                        <h4><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                                        <?php if(!empty($aluno['email'])): ?>
                                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($aluno['email']); ?></p>
                                        <?php endif; ?>
                                        <a href="perfil_aluno_detalhado.php?aluno_id=<?php echo $aluno['id']; ?>" class="btn-profile button button-small">
                                            <i class="fas fa-user-circle"></i> Ver Perfil
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data-message info-message">Nenhum aluno encontrado para esta turma.</p>
                    <?php endif; ?>
                </section>
            <?php elseif (isset($_GET['turma_id']) && !$professor_tem_acesso_turma && !empty($_GET['turma_id'])): ?>
                 <section class="dashboard-section student-list-container card">
                    <h3>Alunos da Turma</h3>
                     <p class="no-data-message error-message">Você não tem permissão para visualizar esta turma ou a turma selecionada é inválida.</p>
                 </section>
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
        const currentUserId = <?php echo json_encode($professor_id); ?>;
        const currentUserSessionRole = <?php echo json_encode($_SESSION['role']); ?>; // 'docente'
        let currentUserChatRole = '';
        if (currentUserSessionRole === 'docente') {
            currentUserChatRole = 'professor'; // Normaliza para 'professor' para consistência com a API e JS
        } else {
            currentUserChatRole = currentUserSessionRole; // Mantém outros papéis se houver
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
            try {
                const response = await fetch(`chat_api.php?action=get_messages&contact_id=${contactId}&contact_role=${encodeURIComponent(contactRole)}`);
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
                        const messageType = (parseInt(msg.sender_id) === currentUserId && msg.sender_role === currentUserChatRole) ? 'sent-acad' : 'received-acad';
                        appendMessageToChat(msg.message_text, messageType);
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
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>