<?php
session_start(); // GARANTIR QUE ESTÁ NO TOPO ABSOLUTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
include 'db.php'; // Conexão com o banco

$nome_coordenador = $_SESSION['usuario_nome'];
$coordenador_id = $_SESSION['usuario_id']; 

$currentPageIdentifier = 'gerenciar_turmas_coord'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// --- INÍCIO LÓGICA DE PROCESSAMENTO DE FORMULÁRIOS (AÇÕES POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    mysqli_autocommit($conn, FALSE); 

    try {
        if ($_POST['action'] === 'add_turma' && isset($_POST['nome_nova_turma'], $_POST['ano_letivo_nova_turma'], $_POST['periodo_nova_turma'])) {
            $nome_nova_turma = trim($_POST['nome_nova_turma']);
            $ano_letivo = intval($_POST['ano_letivo_nova_turma']);
            $periodo = trim($_POST['periodo_nova_turma']);

            if (!empty($nome_nova_turma) && $ano_letivo > 2000 && !empty($periodo)) {
                $sql_insert_turma = "INSERT INTO turmas (nome_turma, ano_letivo, periodo) VALUES (?, ?, ?)";
                $stmt_insert = mysqli_prepare($conn, $sql_insert_turma);
                mysqli_stmt_bind_param($stmt_insert, "sis", $nome_nova_turma, $ano_letivo, $periodo);
                if (mysqli_stmt_execute($stmt_insert)) {
                    $_SESSION['manage_turma_status_message'] = "Turma '" . htmlspecialchars($nome_nova_turma) . "' adicionada com sucesso!";
                    $_SESSION['manage_turma_status_type'] = "status-success";
                } else {
                    throw new Exception("Erro ao adicionar turma: " . mysqli_stmt_error($stmt_insert));
                }
                mysqli_stmt_close($stmt_insert);
            } else {
                throw new Exception("Dados inválidos para adicionar turma.");
            }
        } elseif ($_POST['action'] === 'delete_turma' && isset($_POST['turma_id_delete'])) {
            $turma_id_del = intval($_POST['turma_id_delete']);
            
            $sql_desvincular_alunos = "UPDATE alunos SET turma_id = NULL WHERE turma_id = ?";
            $stmt_desv_alunos = mysqli_prepare($conn, $sql_desvincular_alunos);
            mysqli_stmt_bind_param($stmt_desv_alunos, "i", $turma_id_del);
            mysqli_stmt_execute($stmt_desv_alunos); 
            mysqli_stmt_close($stmt_desv_alunos);

            $sql_del_prof_assoc = "DELETE FROM professores_turmas_disciplinas WHERE turma_id = ?";
            $stmt_del_prof = mysqli_prepare($conn, $sql_del_prof_assoc);
            mysqli_stmt_bind_param($stmt_del_prof, "i", $turma_id_del);
            mysqli_stmt_execute($stmt_del_prof);
            mysqli_stmt_close($stmt_del_prof);

            $sql_delete_turma = "DELETE FROM turmas WHERE id = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete_turma);
            mysqli_stmt_bind_param($stmt_delete, "i", $turma_id_del);
            if (mysqli_stmt_execute($stmt_delete)) {
                if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                    $_SESSION['manage_turma_status_message'] = "Turma excluída com sucesso!";
                    $_SESSION['manage_turma_status_type'] = "status-success";
                } else {
                    throw new Exception("Nenhuma turma encontrada com o ID fornecido para exclusão.");
                }
            } else {
                throw new Exception("Erro ao excluir turma: " . mysqli_stmt_error($stmt_delete));
            }
            mysqli_stmt_close($stmt_delete);

        } elseif ($_POST['action'] === 'add_aluno_turma' && isset($_POST['turma_id'], $_POST['aluno_id'])) {
            $turma_id_add_aluno = intval($_POST['turma_id']);
            $aluno_id_add = intval($_POST['aluno_id']);
            
            $sql_add_aluno = "UPDATE alunos SET turma_id = ? WHERE id = ?";
            $stmt_add_aluno = mysqli_prepare($conn, $sql_add_aluno);
            mysqli_stmt_bind_param($stmt_add_aluno, "ii", $turma_id_add_aluno, $aluno_id_add);
            if(mysqli_stmt_execute($stmt_add_aluno)){
                $_SESSION['manage_turma_status_message'] = "Aluno adicionado à turma com sucesso!";
                $_SESSION['manage_turma_status_type'] = "status-success";
            } else {
                throw new Exception("Erro ao adicionar aluno à turma: " . mysqli_stmt_error($stmt_add_aluno));
            }
            mysqli_stmt_close($stmt_add_aluno);

        } elseif ($_POST['action'] === 'add_professor_disciplina_turma' && isset($_POST['turma_id'], $_POST['professor_id'], $_POST['disciplina_id'])) {
            $turma_id_assoc = intval($_POST['turma_id']);
            $professor_id_assoc = intval($_POST['professor_id']);
            $disciplina_id_assoc = intval($_POST['disciplina_id']);

            $sql_assoc_prof = "INSERT INTO professores_turmas_disciplinas (turma_id, professor_id, disciplina_id) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE professor_id = VALUES(professor_id)"; 
            $stmt_assoc_prof = mysqli_prepare($conn, $sql_assoc_prof);
            mysqli_stmt_bind_param($stmt_assoc_prof, "iii", $turma_id_assoc, $professor_id_assoc, $disciplina_id_assoc);
             if(mysqli_stmt_execute($stmt_assoc_prof)){
                $_SESSION['manage_turma_status_message'] = "Professor/Disciplina associado à turma com sucesso!";
                $_SESSION['manage_turma_status_type'] = "status-success";
            } else {
                throw new Exception("Erro ao associar professor/disciplina à turma: " . mysqli_stmt_error($stmt_assoc_prof));
            }
            mysqli_stmt_close($stmt_assoc_prof);
        }
        mysqli_commit($conn);
        $redirect_url = "gerenciar_turmas_coordenacao.php"; // Nome correto do arquivo
        if (isset($turma_id_add_aluno) || isset($turma_id_assoc) || (isset($_POST['action']) && ($_POST['action'] === 'add_aluno_turma' || $_POST['action'] === 'add_professor_disciplina_turma') && isset($_POST['turma_id']))) {
            $turma_view_id = $_POST['turma_id'] ?? $turma_id_add_aluno ?? $turma_id_assoc;
             $redirect_url .= "?turma_id_view=" . $turma_view_id;
        }
        header("Location: " . $redirect_url);
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn); 
        $_SESSION['manage_turma_status_message'] = "Erro: " . $e->getMessage();
        $_SESSION['manage_turma_status_type'] = "status-error";
        error_log("Erro em gerenciar_turmas_coordenacao.php: " . $e->getMessage());
        $redirect_url = "gerenciar_turmas_coordenacao.php"; // Nome correto do arquivo
         if (isset($_POST['turma_id'])) { // Se a ação envolvia uma turma específica, tenta voltar para ela
            $redirect_url .= "?turma_id_view=".$_POST['turma_id'];
        } elseif (isset($_GET['turma_id_view'])) { // Se estava visualizando uma turma
             $redirect_url .= "?turma_id_view=".$_GET['turma_id_view'];
        }
        header("Location: " . $redirect_url);
        exit();
    }
    mysqli_autocommit($conn, TRUE); 
}
// --- FIM LÓGICA DE PROCESSAMENTO DE FORMULÁRIOS ---

$todas_as_turmas = [];
$sql_todas_turmas = "SELECT id, nome_turma, ano_letivo, periodo FROM turmas ORDER BY ano_letivo DESC, nome_turma ASC";
$result_todas_turmas = mysqli_query($conn, $sql_todas_turmas);
if ($result_todas_turmas) {
    while ($row = mysqli_fetch_assoc($result_todas_turmas)) {
        $todas_as_turmas[] = $row;
    }
} else {
    error_log("Erro ao buscar todas as turmas (gerenciar_turmas_coordenacao.php): " . mysqli_error($conn));
}

$alunos_da_turma_selecionada = [];
$professores_da_turma_selecionada = [];
$turma_selecionada_id_view = null; 
$nome_turma_selecionada_display = "";

if (isset($_GET['turma_id_view']) && !empty($_GET['turma_id_view'])) {
    $turma_selecionada_id_view = intval($_GET['turma_id_view']);

    $sql_valida_turma = "SELECT nome_turma FROM turmas WHERE id = ?";
    $stmt_valida = mysqli_prepare($conn, $sql_valida_turma);
    if($stmt_valida){
        mysqli_stmt_bind_param($stmt_valida, "i", $turma_selecionada_id_view);
        mysqli_stmt_execute($stmt_valida);
        $result_valida = mysqli_stmt_get_result($stmt_valida);
        if($turma_valida_data = mysqli_fetch_assoc($result_valida)){
            $nome_turma_selecionada_display = $turma_valida_data['nome_turma'];

            $sql_alunos = "SELECT id, nome, email, foto_url FROM alunos WHERE turma_id = ? ORDER BY nome";
            $stmt_alunos = mysqli_prepare($conn, $sql_alunos);
            if ($stmt_alunos) {
                mysqli_stmt_bind_param($stmt_alunos, "i", $turma_selecionada_id_view);
                mysqli_stmt_execute($stmt_alunos);
                $result_alunos = mysqli_stmt_get_result($stmt_alunos);
                while ($row = mysqli_fetch_assoc($result_alunos)) { $alunos_da_turma_selecionada[] = $row; }
                mysqli_stmt_close($stmt_alunos);
            } else { error_log("Erro ao buscar alunos: " . mysqli_error($conn)); }

            $sql_professores_turma = "SELECT DISTINCT p.id, p.nome, p.foto_url, d.nome_disciplina, d.id as disciplina_id
                                      FROM professores p
                                      JOIN professores_turmas_disciplinas ptd ON p.id = ptd.professor_id
                                      JOIN disciplinas d ON ptd.disciplina_id = d.id
                                      WHERE ptd.turma_id = ? ORDER BY p.nome, d.nome_disciplina";
            $stmt_prof_turma = mysqli_prepare($conn, $sql_professores_turma);
            if ($stmt_prof_turma) {
                mysqli_stmt_bind_param($stmt_prof_turma, "i", $turma_selecionada_id_view);
                mysqli_stmt_execute($stmt_prof_turma);
                $result_prof_turma = mysqli_stmt_get_result($stmt_prof_turma);
                while ($row = mysqli_fetch_assoc($result_prof_turma)) { $professores_da_turma_selecionada[] = $row; }
                mysqli_stmt_close($stmt_prof_turma);
            } else { error_log("Erro ao buscar professores da turma: " . mysqli_error($conn)); }
        } else {
            if(!isset($_SESSION['manage_turma_status_message'])) { // Só define se não houver outra mensagem de ação
                 $_SESSION['manage_turma_status_message'] = "Turma selecionada para visualização é inválida.";
                 $_SESSION['manage_turma_status_type'] = "status-error";
            }
            $turma_selecionada_id_view = null;
        }
        mysqli_stmt_close($stmt_valida);
    } else {
        error_log("Erro ao preparar validação de turma: " . mysqli_error($conn));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Turmas - Coordenação ACADMIX</title>
    <link rel="stylesheet" href="css/coordenacao.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
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
    --8bit-border-color: #7f8c8d; /* Borda 8bit */
    --8bit-border-color-soft: #95a5a6; /* Borda suave 8bit */
    --8bit-card-background: #34495e; /* Fundo dos cards 8bit */
    --8bit-card-shadow: 8px 8px 0px rgba(0, 0, 0, 0.5); /* Sombra estilo 8bit */
    --8bit-card-hover-shadow: 12px 12px 0px rgba(0, 0, 0, 0.7); /* Sombra hover 8bit */
    --8bit-button-text-color: #ecf0f1; /* Cor do texto em botões 8bit */
    --8bit-info-color: #3498db; /* Cor info 8bit */
    --8bit-success-color: #27ae60; /* Cor sucesso 8bit */
    --8bit-warning-color: #f39c12; /* Cor aviso 8bit */
    --8bit-danger-color: #e74c3c; /* Cor perigo 8bit */
    --8bit-accent-color: #7f8c8d; /* Cor destaque 8bit */
    --8bit-accent-color-extra-light: #505d6b; /* Cinza muito claro 8bit */
    --8bit-hover-background-color: #4a627a; /* Fundo hover 8bit */
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
    flex: 1 1 auto;
    /* Permite que o conteúdo cresça/encolha */
    max-width: 1200px;
    /* Limita a largura do conteúdo para melhor leitura em telas grandes */
    margin: 0 auto;
    /* Centraliza o conteúdo horizontalmente */
    /* O padding já está definido em .main-content */
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
        max-width: unset;
        /* Remove o max-width em telas menores */
        margin: 0;
        /* Remove a margem automática em telas menores */
    }

    header {
        padding: 1rem;
    }

    header h1 {
        font-size: 1.3rem;
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
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .action-section { margin-bottom: 2rem; }
        .turma-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; }
        .turma-card { padding: 1rem; border-radius: 8px; }
        .turma-card h4 { margin-top: 0; margin-bottom: 0.5rem; }
        .turma-card p { font-size: 0.9em; margin-bottom: 1rem; }
        .turma-card .actions a, .turma-card .actions button { margin-right: 5px; text-decoration: none; padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 4px; margin-top: 5px;}
        .details-section h4 { font-size: 1.2rem; margin-top: 0; margin-bottom: 0.8rem; }
        .details-list { list-style: none; padding-left: 0; }
        .details-list li { padding: 0.5rem; border-bottom: 1px solid var(--border-color-soft, #eee); display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .details-list li:last-child { border-bottom: none; }
        .details-list img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right:10px; }
        .details-list .user-info-span { flex-grow: 1; }
        .form-container { max-width: 600px; margin: 0 auto 2rem auto; }
        .form-container label { display: block; margin-top: 1rem; margin-bottom: 0.3rem; font-weight: bold;}
        .form-container input[type="text"], .form-container input[type="number"], .form-container select { width: 100%; padding: 0.6rem; margin-bottom: 0.8rem; box-sizing: border-box; border-radius: 4px;}
        .form-container button { display: block; width: auto; margin-top: 1rem; padding: 0.7rem 1.5rem; }
        .no-data-message { padding: 1rem; text-align: center; border-radius: 4px; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }

        /* --- CSS CHAT ACADÊMICO --- */
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
        <h1>ACADMIX - Gerenciar Turmas (Coord. <?php echo htmlspecialchars($nome_coordenador); ?>)</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Gerenciamento de Turmas</h2>

            <?php if(isset($_SESSION['manage_turma_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['manage_turma_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['manage_turma_status_message']); ?>
                </div>
                <?php unset($_SESSION['manage_turma_status_message']); unset($_SESSION['manage_turma_status_type']); ?>
            <?php endif; ?>

            <section class="dashboard-section card action-section">
                <h3><i class="fas fa-plus-square"></i> Adicionar Nova Turma</h3>
                <form action="gerenciar_turmas_coordenacao.php" method="POST" class="form-container">
                    <input type="hidden" name="action" value="add_turma">
                    <label for="nome_nova_turma">Nome da Turma:</label>
                    <input type="text" id="nome_nova_turma" name="nome_nova_turma" class="input-field" required placeholder="Ex: 1º Ano A, Terceirão Alpha">
                    
                    <label for="ano_letivo_nova_turma">Ano Letivo:</label>
                    <input type="number" id="ano_letivo_nova_turma" name="ano_letivo_nova_turma" class="input-field" value="<?php echo date('Y'); ?>" required min="2020" max="2099">
                    
                    <label for="periodo_nova_turma">Período:</label>
                    <select id="periodo_nova_turma" name="periodo_nova_turma" class="input-field" required>
                        <option value="">Selecione o Período</option>
                        <option value="Manhã">Manhã</option>
                        <option value="Tarde">Tarde</option>
                        <option value="Noite">Noite</option>
                        <option value="Integral">Integral</option>
                    </select>
                    <button type="submit" class="button button-primary"><i class="fas fa-plus-circle"></i> Adicionar Turma</button>
                </form>
            </section>

            <section class="dashboard-section card">
                <h3><i class="fas fa-layer-group"></i> Turmas Existentes</h3>
                <?php if (!empty($todas_as_turmas)): ?>
                    <form method="GET" action="gerenciar_turmas_coordenacao.php" class="turma-select-form" style="margin-bottom: 20px;">
                        <label for="turma_id_view_select">Visualizar Detalhes da Turma:</label>
                        <select name="turma_id_view" id="turma_id_view_select" onchange="this.form.submit()" class="input-field">
                            <option value="">-- Selecione uma Turma para Ver Detalhes --</option>
                            <?php foreach ($todas_as_turmas as $turma_item): ?>
                                <option value="<?php echo $turma_item['id']; ?>" <?php echo ($turma_selecionada_id_view == $turma_item['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($turma_item['nome_turma']); ?> (<?php echo htmlspecialchars($turma_item['ano_letivo'] ?? ''); ?> - <?php echo htmlspecialchars($turma_item['periodo'] ?? ''); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <div class="turma-grid">
                        <?php foreach ($todas_as_turmas as $turma_item): ?>
                            <div class="turma-card card-item">
                                <h4><?php echo htmlspecialchars($turma_item['nome_turma']); ?></h4>
                                <p>Ano: <?php echo htmlspecialchars($turma_item['ano_letivo'] ?? 'N/A'); ?> | Período: <?php echo htmlspecialchars($turma_item['periodo'] ?? 'N/A'); ?></p>
                                <div class="actions">
                                    <a href="gerenciar_turmas_coordenacao.php?turma_id_view=<?php echo $turma_item['id']; ?>" class="button button-info button-small"><i class="fas fa-eye"></i> Detalhes</a>
                                    <form action="gerenciar_turmas_coordenacao.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir a turma \'<?php echo htmlspecialchars(addslashes($turma_item['nome_turma'])); ?>\'? Esta ação não pode ser desfeita e irá desvincular alunos e professores.');">
                                        <input type="hidden" name="action" value="delete_turma">
                                        <input type="hidden" name="turma_id_delete" value="<?php echo $turma_item['id']; ?>">
                                        <button type="submit" class="button button-danger button-small"><i class="fas fa-trash-alt"></i> Excluir</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data-message info-message">Nenhuma turma cadastrada no momento.</p>
                <?php endif; ?>
            </section>

            <?php if ($turma_selecionada_id_view && !empty($nome_turma_selecionada_display)): ?>
                <section class="dashboard-section student-list-container card details-section">
                    <h3><i class="fas fa-info-circle"></i> Detalhes da Turma: <?php echo htmlspecialchars($nome_turma_selecionada_display); ?></h3>
                    
                    <h4><i class="fas fa-user-graduate"></i> Alunos Matriculados (<?php echo count($alunos_da_turma_selecionada); ?>)</h4>
                    <?php if (!empty($alunos_da_turma_selecionada)): ?>
                        <ul class="details-list">
                            <?php foreach ($alunos_da_turma_selecionada as $aluno): ?>
                                <li>
                                    <img src="<?php echo htmlspecialchars(!empty($aluno['foto_url']) ? $aluno['foto_url'] : 'img/alunos/default_avatar.png'); ?>" 
                                         alt="Foto de <?php echo htmlspecialchars($aluno['nome']); ?>" 
                                         onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                                    <span class="user-info-span"><?php echo htmlspecialchars($aluno['nome']); ?> (<?php echo htmlspecialchars($aluno['email'] ?? 'Email não informado'); ?>)</span>
                                    <form action="gerenciar_turmas_coordenacao.php?turma_id_view=<?php echo $turma_selecionada_id_view; ?>" method="POST" onsubmit="return confirm('Remover <?php echo htmlspecialchars(addslashes($aluno['nome'])); ?> desta turma?');">
                                        <input type="hidden" name="action" value="remove_aluno_turma">
                                        <input type="hidden" name="aluno_id" value="<?php echo $aluno['id']; ?>">
                                        <input type="hidden" name="turma_id" value="<?php echo $turma_selecionada_id_view; ?>">
                                        <button type="submit" class="button button-danger button-xsmall" title="Remover Aluno da Turma">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-data-message info-message">Nenhum aluno matriculado nesta turma.</p>
                    <?php endif; ?>
                    
                    <form action="gerenciar_turmas_coordenacao.php?turma_id_view=<?php echo $turma_selecionada_id_view; ?>" method="POST" class="form-container" style="margin-top:1.5rem; border-top:1px solid var(--border-color-soft); padding-top:1.5rem;">
                        <input type="hidden" name="action" value="add_aluno_turma">
                        <input type="hidden" name="turma_id" value="<?php echo $turma_selecionada_id_view; ?>">
                        <label for="aluno_id_add_<?php echo $turma_selecionada_id_view; ?>">Adicionar Aluno à Turma:</label>
                        <select name="aluno_id" id="aluno_id_add_<?php echo $turma_selecionada_id_view; ?>" class="input-field" required>
                            <option value="">Selecione um Aluno</option>
                            <?php 
                            $sql_alunos_disp = "SELECT id, nome, (SELECT nome_turma FROM turmas t WHERE t.id = alunos.turma_id) as nome_turma_atual FROM alunos WHERE turma_id IS NULL OR turma_id != ? ORDER BY nome";
                            $stmt_alunos_disp_prepare = mysqli_prepare($conn, $sql_alunos_disp);
                            if($stmt_alunos_disp_prepare){
                                mysqli_stmt_bind_param($stmt_alunos_disp_prepare, "i", $turma_selecionada_id_view);
                                mysqli_stmt_execute($stmt_alunos_disp_prepare);
                                $result_alunos_disp = mysqli_stmt_get_result($stmt_alunos_disp_prepare);
                                while($aluno_disp = mysqli_fetch_assoc($result_alunos_disp)){
                                    $label_aluno = htmlspecialchars($aluno_disp['nome']);
                                    if($aluno_disp['nome_turma_atual']) {
                                        $label_aluno .= " (Turma Atual: " . htmlspecialchars($aluno_disp['nome_turma_atual']) . ")";
                                    } else {
                                        $label_aluno .= " (Sem turma)";
                                    }
                                    echo "<option value='{$aluno_disp['id']}'>" . $label_aluno . "</option>";
                                }
                                mysqli_stmt_close($stmt_alunos_disp_prepare);
                            }
                            ?>
                        </select>
                        <button type="submit" class="button button-success button-small"><i class="fas fa-user-plus"></i> Adicionar Aluno</button>
                    </form>

                    <h4 style="margin-top:2rem;"><i class="fas fa-chalkboard-teacher"></i> Professores e Disciplinas (<?php echo count($professores_da_turma_selecionada); ?>)</h4>
                    <?php if (!empty($professores_da_turma_selecionada)): ?>
                        <ul class="details-list">
                            <?php foreach ($professores_da_turma_selecionada as $professor_turma): ?>
                                <li>
                                     <img src="<?php echo htmlspecialchars(!empty($professor_turma['foto_url']) ? $professor_turma['foto_url'] : 'img/professores/default_avatar.png'); ?>" 
                                         alt="Foto de <?php echo htmlspecialchars($professor_turma['nome']); ?>" 
                                         onerror="this.onerror=null; this.src='img/professores/default_avatar.png';">
                                    <span class="user-info-span"><?php echo htmlspecialchars($professor_turma['nome']); ?> (Disciplina: <?php echo htmlspecialchars($professor_turma['nome_disciplina']); ?>)</span>
                                    <form action="gerenciar_turmas_coordenacao.php?turma_id_view=<?php echo $turma_selecionada_id_view; ?>" method="POST" onsubmit="return confirm('Desvincular Prof. <?php echo htmlspecialchars(addslashes($professor_turma['nome'])); ?> da disciplina <?php echo htmlspecialchars(addslashes($professor_turma['nome_disciplina'])); ?> nesta turma?');">
                                        <input type="hidden" name="action" value="remove_professor_disciplina_turma">
                                        <input type="hidden" name="professor_id" value="<?php echo $professor_turma['id']; ?>">
                                        <input type="hidden" name="disciplina_id" value="<?php echo $professor_turma['disciplina_id']; ?>">
                                        <input type="hidden" name="turma_id" value="<?php echo $turma_selecionada_id_view; ?>">
                                        <button type="submit" class="button button-danger button-xsmall" title="Desvincular Professor/Disciplina">
                                            <i class="fas fa-unlink"></i>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="no-data-message info-message">Nenhum professor associado a esta turma ainda.</p>
                    <?php endif; ?>
                    
                    <form action="gerenciar_turmas_coordenacao.php?turma_id_view=<?php echo $turma_selecionada_id_view; ?>" method="POST" class="form-container" style="margin-top:1.5rem; border-top:1px solid var(--border-color-soft); padding-top:1.5rem;">
                        <input type="hidden" name="action" value="add_professor_disciplina_turma">
                        <input type="hidden" name="turma_id" value="<?php echo $turma_selecionada_id_view; ?>">
                        <label for="professor_id_add_assoc_<?php echo $turma_selecionada_id_view; ?>">Associar Professor:</label>
                        <select name="professor_id" id="professor_id_add_assoc_<?php echo $turma_selecionada_id_view; ?>" class="input-field" required>
                             <option value="">Selecione um Professor</option>
                            <?php 
                            $sql_profs_disp = "SELECT id, nome FROM professores ORDER BY nome";
                            $result_profs_disp = mysqli_query($conn, $sql_profs_disp);
                            if($result_profs_disp){
                                while($prof_disp = mysqli_fetch_assoc($result_profs_disp)){
                                    echo "<option value='{$prof_disp['id']}'>" . htmlspecialchars($prof_disp['nome']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <label for="disciplina_id_add_assoc_<?php echo $turma_selecionada_id_view; ?>">Para a Disciplina:</label>
                        <select name="disciplina_id" id="disciplina_id_add_assoc_<?php echo $turma_selecionada_id_view; ?>" class="input-field" required>
                            <option value="">Selecione uma Disciplina</option>
                             <?php 
                            $sql_disc_disp = "SELECT id, nome_disciplina FROM disciplinas ORDER BY nome_disciplina";
                            $result_disc_disp = mysqli_query($conn, $sql_disc_disp);
                            if($result_disc_disp){
                                while($disc_disp = mysqli_fetch_assoc($result_disc_disp)){
                                    echo "<option value='{$disc_disp['id']}'>" . htmlspecialchars($disc_disp['nome_disciplina']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <button type="submit" class="button button-success button-small"><i class="fas fa-link"></i> Associar Professor/Disciplina</button>
                    </form>
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
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>