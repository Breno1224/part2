<?php
session_start(); // GARANTIR QUE ESTÁ NO TOPO ABSOLUTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$nome_coordenador = $_SESSION['usuario_nome'];
$coordenador_id = $_SESSION['usuario_id']; 

$currentPageIdentifier = 'gerenciar_disciplinas_coord'; // Ajuste para o seu sidebar
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// --- LÓGICA DE PROCESSAMENTO DE FORMULÁRIOS (AÇÕES POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    mysqli_autocommit($conn, FALSE); 

    try {
        if ($_POST['action'] === 'add_disciplina' && isset($_POST['nome_nova_disciplina'])) {
            $nome_nova_disciplina = trim($_POST['nome_nova_disciplina']);
            $ementa_nova_disciplina = isset($_POST['ementa_nova_disciplina']) ? trim($_POST['ementa_nova_disciplina']) : NULL;
            $carga_horaria_nova_disciplina = isset($_POST['carga_horaria_nova_disciplina']) && is_numeric($_POST['carga_horaria_nova_disciplina']) ? intval($_POST['carga_horaria_nova_disciplina']) : NULL;


            if (!empty($nome_nova_disciplina)) {
                $sql_check = "SELECT id FROM disciplinas WHERE nome_disciplina = ?";
                $stmt_check = mysqli_prepare($conn, $sql_check);
                mysqli_stmt_bind_param($stmt_check, "s", $nome_nova_disciplina);
                mysqli_stmt_execute($stmt_check);
                mysqli_stmt_store_result($stmt_check);

                if (mysqli_stmt_num_rows($stmt_check) > 0) {
                    throw new Exception("Uma disciplina com o nome '".htmlspecialchars($nome_nova_disciplina)."' já existe.");
                }
                mysqli_stmt_close($stmt_check);

                $sql_insert_disciplina = "INSERT INTO disciplinas (nome_disciplina, ementa, carga_horaria) VALUES (?, ?, ?)";
                $stmt_insert = mysqli_prepare($conn, $sql_insert_disciplina);
                mysqli_stmt_bind_param($stmt_insert, "ssi", $nome_nova_disciplina, $ementa_nova_disciplina, $carga_horaria_nova_disciplina);
                if (mysqli_stmt_execute($stmt_insert)) {
                    $_SESSION['manage_disciplina_status_message'] = "Disciplina '" . htmlspecialchars($nome_nova_disciplina) . "' adicionada com sucesso!";
                    $_SESSION['manage_disciplina_status_type'] = "status-success";
                } else {
                    throw new Exception("Erro ao adicionar disciplina: " . mysqli_stmt_error($stmt_insert));
                }
                mysqli_stmt_close($stmt_insert);
            } else {
                throw new Exception("O nome da disciplina é obrigatório.");
            }
        } elseif ($_POST['action'] === 'delete_disciplina' && isset($_POST['disciplina_id_delete'])) {
            $disciplina_id_del = intval($_POST['disciplina_id_delete']);

            if ($disciplina_id_del > 0) {
                // Verificar se a disciplina está sendo usada em professores_turmas_disciplinas
                $sql_check_usage = "SELECT COUNT(*) as total FROM professores_turmas_disciplinas WHERE disciplina_id = ?";
                $stmt_check_usage = mysqli_prepare($conn, $sql_check_usage);
                mysqli_stmt_bind_param($stmt_check_usage, "i", $disciplina_id_del);
                mysqli_stmt_execute($stmt_check_usage);
                $result_usage = mysqli_stmt_get_result($stmt_check_usage);
                $usage_count = mysqli_fetch_assoc($result_usage)['total'];
                mysqli_stmt_close($stmt_check_usage);

                if ($usage_count > 0) {
                    throw new Exception("Não é possível excluir a disciplina, pois ela está atualmente associada a uma ou mais turmas/professores. Remova essas associações primeiro.");
                }

                $sql_delete_disciplina = "DELETE FROM disciplinas WHERE id = ?";
                $stmt_delete = mysqli_prepare($conn, $sql_delete_disciplina);
                mysqli_stmt_bind_param($stmt_delete, "i", $disciplina_id_del);
                if (mysqli_stmt_execute($stmt_delete)) {
                    if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                        $_SESSION['manage_disciplina_status_message'] = "Disciplina excluída com sucesso!";
                        $_SESSION['manage_disciplina_status_type'] = "status-success";
                    } else {
                        throw new Exception("Nenhuma disciplina encontrada com o ID fornecido para exclusão.");
                    }
                } else {
                    throw new Exception("Erro ao excluir disciplina: " . mysqli_stmt_error($stmt_delete));
                }
                mysqli_stmt_close($stmt_delete);
            } else {
                 throw new Exception("ID da disciplina inválido para exclusão.");
            }
        }
        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn); 
        $_SESSION['manage_disciplina_status_message'] = "Erro: " . $e->getMessage();
        $_SESSION['manage_disciplina_status_type'] = "status-error";
        error_log("Erro em coordenacao_gerenciar_disciplinas.php (action): " . $e->getMessage());
    }
    mysqli_autocommit($conn, TRUE); 
    header("Location: coordenacao_gerenciar_disciplinas.php"); 
    exit();
}
// --- FIM LÓGICA DE PROCESSAMENTO DE FORMULÁRIOS ---

// Buscar todas as disciplinas
$todas_as_disciplinas = [];
$sql_todas_disciplinas = "SELECT id, nome_disciplina, ementa, carga_horaria FROM disciplinas ORDER BY nome_disciplina ASC";
$result_todas_disciplinas = mysqli_query($conn, $sql_todas_disciplinas);
if ($result_todas_disciplinas) {
    while ($row = mysqli_fetch_assoc($result_todas_disciplinas)) {
        $todas_as_disciplinas[] = $row;
    }
} else {
    error_log("Erro ao buscar todas as disciplinas (coordenacao_gerenciar_disciplinas.php): " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Disciplinas - Coordenação ACADMIX</title>
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
    --tag-background-color: #e9ecef;
    /* Cor para tags de disciplina */
    --tag-text-color: #495057;
    /* Cor do texto em tags */
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
    /* Borda 8bit */
    --8bit-border-color-soft: #95a5a6;
    /* Borda suave 8bit */
    --8bit-card-background: #34495e;
    /* Fundo dos cards 8bit */
    --8bit-card-shadow: 8px 8px 0px rgba(0, 0, 0, 0.5);
    /* Sombra estilo 8bit */
    --8bit-card-hover-shadow: 12px 12px 0px rgba(0, 0, 0, 0.7);
    /* Sombra hover 8bit */
    --8bit-button-text-color: #ecf0f1;
    /* Cor do texto em botões 8bit */
    --8bit-info-color: #3498db;
    /* Cor info 8bit */
    --8bit-success-color: #27ae60;
    /* Cor sucesso 8bit */
    --8bit-warning-color: #f39c12;
    /* Cor aviso 8bit */
    --8bit-danger-color: #e74c3c;
    /* Cor perigo 8bit */
    --8bit-tag-background-color: #4a627a;
    /* Cor para tags de disciplina 8bit */
    --8bit-tag-text-color: #ecf0f1;
    /* Cor do texto em tags 8bit */
    --8bit-accent-color: #7f8c8d;
    /* Cor destaque 8bit */
    --8bit-accent-color-extra-light: #505d6b;
    /* Cinza muito claro 8bit */
    --8bit-hover-background-color: #4a627a;
    /* Fundo hover 8bit */
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
body.theme-8bit .professor-card-header h3 {
    font-family: 'Press Start 2P', cursive;
    text-shadow: 3px 3px 0px var(--8bit-primary-color-dark);
}

body.theme-8bit .card,
body.theme-8bit .professor-card {
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


/* Professores Section */
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
    border-bottom: 2px solid var(--primary-color);
}

body.theme-8bit .dashboard-section h3 {
    color: var(--8bit-primary-color);
    border-bottom: 2px dashed var(--8bit-primary-color);
}

/* Professor Grid */
.professor-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

/* Professor Card */
.professor-card {
    padding: 1.5rem;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    background-color: var(--card-background);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease;
    color: var(--text-color);
}

body.theme-8bit .professor-card {
    background-color: var(--8bit-card-background);
    box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.3);
    color: var(--8bit-text-color);
    border: 1px dashed var(--8bit-border-color);
}

.professor-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

body.theme-8bit .professor-card:hover {
    transform: translate(-4px, -4px);
    box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.5);
}

.professor-card-header {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.professor-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 1rem;
    border: 2px solid var(--border-color-soft);
}

body.theme-8bit .professor-photo {
    border: 2px dashed var(--8bit-border-color-soft);
}

.professor-info h3 {
    margin: 0 0 0.3rem 0;
    font-size: 1.25rem;
    color: var(--text-color);
}

body.theme-8bit .professor-info h3 {
    color: var(--8bit-text-color);
}

.professor-info p {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
    color: var(--text-color-light);
}

body.theme-8bit .professor-info p {
    color: var(--8bit-text-color-light);
}

.professor-disciplinas {
    margin-bottom: 1rem;
}

.professor-disciplinas strong {
    font-size: 0.95rem;
    color: var(--primary-color-dark);
}

body.theme-8bit .professor-disciplinas strong {
    color: var(--8bit-primary-color-dark);
}

.disciplinas-list {
    list-style: none;
    padding-left: 0;
    font-size: 0.85rem;
    margin-top: 0.3rem;
}

.disciplinas-list li {
    display: inline-block;
    background-color: var(--tag-background-color);
    color: var(--tag-text-color);
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    margin-right: 5px;
    margin-bottom: 5px;
}

body.theme-8bit .disciplinas-list li {
    background-color: var(--8bit-tag-background-color);
    color: var(--8bit-tag-text-color);
    border: 1px dashed var(--8bit-border-color-soft);
}


.professor-actions {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color-soft);
    display: flex;
    justify-content: flex-start;
    gap: 10px;
    flex-wrap: wrap;
    /* Adicionado para responsividade dos botões */
}

body.theme-8bit .professor-actions {
    border-top: 1px dashed var(--8bit-border-color-soft);
}

.professor-actions .button {
    text-decoration: none;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    border-radius: 20px;
}

/* Botão adicionar novo professor */
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


/* Botão Secundário (Ver Perfil) */
.button-secondary {
    background: linear-gradient(135deg, var(--accent-color) 0%, #5a6268 100%);
    color: var(--button-text-color);
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.button-secondary:hover {
    background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
    box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
}

body.theme-8bit .button-secondary {
    background: var(--8bit-accent-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
    box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.5);
}

body.theme-8bit .button-secondary:hover {
    background: var(--8bit-accent-color-extra-light);
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.7);
}


/* Botão Warning (Editar) */
.button-warning {
    background: linear-gradient(135deg, var(--warning-color) 0%, #d39e00 100%);
    color: var(--button-text-color);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
}

.button-warning:hover {
    background: linear-gradient(135deg, #d39e00 0%, #b88a00 100%);
    box-shadow: 0 8px 25px rgba(255, 193, 7, 0.4);
}

body.theme-8bit .button-warning {
    background: var(--8bit-warning-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
    box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.5);
}

body.theme-8bit .button-warning:hover {
    background: var(--8bit-warning-color);
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.7);
}


/* Botão Danger (Excluir) */
.button-danger {
    background: linear-gradient(135deg, var(--danger-color) 0%, #c82333 100%);
    color: var(--button-text-color);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.button-danger:hover {
    background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
}

body.theme-8bit .button-danger {
    background: var(--8bit-danger-color);
    border: 1px solid var(--8bit-border-color);
    color: var(--8bit-button-text-color);
    box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.5);
}

body.theme-8bit .button-danger:hover {
    background: var(--8bit-danger-color);
    transform: translate(-2px, -2px);
    box-shadow: 6px 6px 0px rgba(0, 0, 0, 0.7);
}


/* Mensagens de status */
.status-message {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 4px;
    text-align: center;
    font-size: 0.95rem;
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


/* Mensagem de "nenhum dado" */
.no-data-message {
    padding: 1rem;
    text-align: center;
    border-radius: 4px;
    background-color: rgba(var(--info-color-rgb, 23, 162, 184), 0.05);
    border: 1px dashed rgba(var(--info-color-rgb, 23, 162, 184), 0.2);
    color: var(--text-color-alt);
}

body.theme-8bit .no-data-message {
    background-color: rgba(52, 152, 219, 0.05);
    border: 1px dashed rgba(52, 152, 219, 0.2);
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

    .professor-grid {
        grid-template-columns: 1fr;
    }

    .professor-card {
        padding: 1rem;
        text-align: center;
    }

    .professor-card-header {
        flex-direction: column;
        text-align: center;
        margin-bottom: 0.5rem;
    }

    .professor-photo {
        margin-right: 0;
        margin-bottom: 0.5rem;
    }

    .professor-info {
        flex-grow: unset;
        width: 100%;
    }

    .professor-info h3 {
        font-size: 1.1rem;
    }

    .professor-info p {
        font-size: 0.8rem;
    }

    .professor-disciplinas {
        margin-bottom: 0.8rem;
        text-align: center;
    }

    .professor-disciplinas strong {
        display: block;
        margin-bottom: 0.5rem;
    }

    .disciplinas-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }

    .disciplinas-list li {
        margin: 3px;
    }

    .professor-actions {
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .professor-actions .button {
        width: 100%;
        max-width: 200px;
        /* Limita o tamanho do botão para não ficar muito largo */
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


/* --- INÍCIO CSS NOVO CHAT ACADÊMICO --- */
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
        .form-container { max-width: 700px; margin: 0 auto 2rem auto; }
        .form-container label { display: block; margin-top: 1rem; margin-bottom: 0.3rem; font-weight: bold;}
        .form-container input[type="text"], .form-container input[type="number"], .form-container textarea { width: 100%; padding: 0.6rem; margin-bottom: 0.8rem; box-sizing: border-box; border-radius: 4px;}
        .form-container textarea { min-height: 100px; }
        .form-container button { display: block; width: auto; margin-top: 1rem; padding: 0.7rem 1.5rem; }
        
        .disciplina-list table { width: 100%; border-collapse: collapse; }
        .disciplina-list th, .disciplina-list td { padding: 0.75rem; text-align: left; vertical-align: top; }
        .disciplina-list .actions-cell form { display: inline; }
        .disciplina-list .actions-cell button { margin-left: 5px; }
        .ementa-cell { max-width: 300px; white-space: pre-wrap; word-break: break-word; font-size: 0.85em; }

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
        <h1>ACADMIX - Gerenciar Disciplinas (Coord. <?php echo htmlspecialchars($nome_coordenador); ?>)</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Gerenciamento de Disciplinas</h2>

            <?php if(isset($_SESSION['manage_disciplina_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['manage_disciplina_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['manage_disciplina_status_message']); ?>
                </div>
                <?php unset($_SESSION['manage_disciplina_status_message']); unset($_SESSION['manage_disciplina_status_type']); ?>
            <?php endif; ?>

            <section class="dashboard-section card action-section">
                <h3><i class="fas fa-plus-square"></i> Adicionar Nova Disciplina</h3>
                <form action="coordenacao_gerenciar_disciplinas.php" method="POST" class="form-container">
                    <input type="hidden" name="action" value="add_disciplina">
                    <label for="nome_nova_disciplina">Nome da Disciplina:</label>
                    <input type="text" id="nome_nova_disciplina" name="nome_nova_disciplina" class="input-field" required placeholder="Ex: Matemática Aplicada, História do Brasil">
                    
                    <label for="ementa_nova_disciplina">Ementa (Opcional):</label>
                    <textarea id="ementa_nova_disciplina" name="ementa_nova_disciplina" class="input-field" placeholder="Descreva os tópicos principais da disciplina..."></textarea>

                    <label for="carga_horaria_nova_disciplina">Carga Horária Semanal (Opcional):</label>
                    <input type="number" id="carga_horaria_nova_disciplina" name="carga_horaria_nova_disciplina" class="input-field" placeholder="Ex: 4 (para 4 aulas/semana)" min="1">
                    
                    <button type="submit" class="button button-primary"><i class="fas fa-plus-circle"></i> Adicionar Disciplina</button>
                </form>
            </section>

            <section class="dashboard-section card disciplina-list">
                <h3><i class="fas fa-book-open"></i> Disciplinas Cadastradas</h3>
                <?php if (!empty($todas_as_disciplinas)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome da Disciplina</th>
                                <th>Ementa (Início)</th>
                                <th>C. Horária</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todas_as_disciplinas as $disciplina_item): ?>
                                <tr>
                                    <td><?php echo $disciplina_item['id']; ?></td>
                                    <td><?php echo htmlspecialchars($disciplina_item['nome_disciplina']); ?></td>
                                    <td class="ementa-cell"><?php echo htmlspecialchars(mb_substr($disciplina_item['ementa'] ?? '', 0, 70)) . (mb_strlen($disciplina_item['ementa'] ?? '') > 70 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars($disciplina_item['carga_horaria'] ?? '-'); ?></td>
                                    <td class="actions-cell">
                                        <form action="coordenacao_gerenciar_disciplinas.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir a disciplina \'<?php echo htmlspecialchars(addslashes($disciplina_item['nome_disciplina'])); ?>\'? Verifique se ela não está em uso por turmas/professores.');">
                                            <input type="hidden" name="action" value="delete_disciplina">
                                            <input type="hidden" name="disciplina_id_delete" value="<?php echo $disciplina_item['id']; ?>">
                                            <button type="submit" class="button button-danger button-xsmall" title="Excluir Disciplina"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data-message info-message">Nenhuma disciplina cadastrada no momento.</p>
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