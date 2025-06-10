<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];

$currentPageIdentifier = 'lancar_tarefas_prof'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Lógica de Ações (POST) para Criar, Avaliar e Excluir Tarefas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $redirect_url = "professor_lancar_tarefa.php";

    if ($_POST['action'] === 'add_tarefa') {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        list($turma_id, $disciplina_id) = explode('-', $_POST['turma_disciplina_id']);
        $turma_id = intval($turma_id);
        $disciplina_id = intval($disciplina_id);
        $data_prazo = $_POST['data_prazo'];
        $arquivo_path = null;

        if (empty($titulo) || empty($turma_id) || empty($disciplina_id) || empty($data_prazo)) {
            $_SESSION['tarefa_prof_status_message'] = "Erro: Todos os campos (exceto anexo) são obrigatórios.";
            $_SESSION['tarefa_prof_status_type'] = "status-error";
        } else {
            if (isset($_FILES['arquivo_professor']) && $_FILES['arquivo_professor']['error'] == 0) {
                $upload_dir = 'uploads/tarefas/professor/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0775, true); }
                $file_name = "prof" . $professor_id . "_" . time() . '_' . basename($_FILES['arquivo_professor']['name']);
                $arquivo_path = $upload_dir . $file_name;
                if (!move_uploaded_file($_FILES['arquivo_professor']['tmp_name'], $arquivo_path)) {
                    $_SESSION['tarefa_prof_status_message'] = "Erro ao fazer upload do arquivo.";
                    $_SESSION['tarefa_prof_status_type'] = "status-error";
                    $arquivo_path = null;
                }
            }

            if (!isset($_SESSION['tarefa_prof_status_message'])) {
                $sql = "INSERT INTO tarefas (titulo, descricao, professor_id, disciplina_id, turma_id, data_prazo, arquivo_path_professor) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssiiiss", $titulo, $descricao, $professor_id, $disciplina_id, $turma_id, $data_prazo, $arquivo_path);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['tarefa_prof_status_message'] = "Tarefa criada com sucesso!";
                    $_SESSION['tarefa_prof_status_type'] = "status-success";
                } else {
                    $_SESSION['tarefa_prof_status_message'] = "Erro ao criar tarefa: " . mysqli_stmt_error($stmt);
                    $_SESSION['tarefa_prof_status_type'] = "status-error";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    elseif ($_POST['action'] === 'avaliar_submissoes' && isset($_POST['tarefa_id_avaliada'], $_POST['submissoes'])) {
        $tarefa_id_avaliada = intval($_POST['tarefa_id_avaliada']);
        $redirect_url = "professor_lancar_tarefa.php?view_submissions_for=" . $tarefa_id_avaliada;
        $erros = 0;
        foreach ($_POST['submissoes'] as $submissao_id => $dados) {
            if (empty($submissao_id)) continue;
            $nota = !empty($dados['nota']) ? floatval(str_replace(',', '.', $dados['nota'])) : null;
            $feedback = !empty($dados['feedback']) ? trim($dados['feedback']) : null;
            
            $sql = "UPDATE tarefas_submissoes SET nota = ?, feedback_professor = ?, data_avaliacao = NOW() WHERE id = ? AND tarefa_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "dsii", $nota, $feedback, $submissao_id, $tarefa_id_avaliada);
            if (!mysqli_stmt_execute($stmt)) {
                $erros++;
            }
            mysqli_stmt_close($stmt);
        }
        $_SESSION['tarefa_prof_status_message'] = $erros > 0 ? "Ocorreram {$erros} erros ao salvar as avaliações." : "Avaliações salvas com sucesso!";
        $_SESSION['tarefa_prof_status_type'] = $erros > 0 ? "status-error" : "status-success";
    }
    elseif ($_POST['action'] === 'delete_tarefa' && isset($_POST['tarefa_id_delete'])) {
        $tarefa_id_del = intval($_POST['tarefa_id_delete']);
        $sql = "DELETE FROM tarefas WHERE id = ? AND professor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $tarefa_id_del, $professor_id);
        if(mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0){
            $_SESSION['tarefa_prof_status_message'] = "Tarefa excluída com sucesso.";
            $_SESSION['tarefa_prof_status_type'] = "status-success";
        } else {
            $_SESSION['tarefa_prof_status_message'] = "Não foi possível excluir a tarefa.";
            $_SESSION['tarefa_prof_status_type'] = "status-error";
        }
        mysqli_stmt_close($stmt);
    }
    
    header("Location: " . $redirect_url);
    exit();
}

// --- LÓGICA DE VISUALIZAÇÃO (GET) ---
$view_mode = isset($_GET['view_submissions_for']) && !empty($_GET['view_submissions_for']) ? 'submissions' : 'list';
$tarefas_enviadas = [];
$submissoes_da_tarefa = [];
$tarefa_info_selecionada = null;
$associacoes = [];

if ($view_mode === 'list') {
    $sql = "SELECT t.id, t.titulo, t.data_prazo, d.nome_disciplina, tu.nome_turma, 
                   (SELECT COUNT(*) FROM tarefas_submissoes WHERE tarefa_id = t.id) as total_entregas
            FROM tarefas t
            JOIN disciplinas d ON t.disciplina_id = d.id
            JOIN turmas tu ON t.turma_id = tu.id
            WHERE t.professor_id = ? ORDER BY t.data_prazo DESC";
    $stmt_list = mysqli_prepare($conn, $sql);
    if ($stmt_list) {
        mysqli_stmt_bind_param($stmt_list, "i", $professor_id);
        mysqli_stmt_execute($stmt_list);
        $result = mysqli_stmt_get_result($stmt_list);
        while ($row = mysqli_fetch_assoc($result)) { $tarefas_enviadas[] = $row; }
        mysqli_stmt_close($stmt_list);
    }
    
    $sql_assoc = "SELECT DISTINCT t.id as turma_id, t.nome_turma, d.id as disciplina_id, d.nome_disciplina
                  FROM turmas t
                  JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id
                  JOIN disciplinas d ON ptd.disciplina_id = d.id
                  WHERE ptd.professor_id = ? ORDER BY t.nome_turma, d.nome_disciplina";
    $stmt_assoc = mysqli_prepare($conn, $sql_assoc);
    if($stmt_assoc){
        mysqli_stmt_bind_param($stmt_assoc, "i", $professor_id);
        mysqli_stmt_execute($stmt_assoc);
        $result_assoc = mysqli_stmt_get_result($stmt_assoc);
        while($row = mysqli_fetch_assoc($result_assoc)){ $associacoes[] = $row; }
        mysqli_stmt_close($stmt_assoc);
    }
} elseif ($view_mode === 'submissions') {
    $tarefa_id_selecionada = intval($_GET['view_submissions_for']);
    
    $sql_tarefa_info = "SELECT t.id, t.titulo, t.descricao, t.data_prazo, d.nome_disciplina, tu.nome_turma, tu.id as turma_id
                        FROM tarefas t
                        JOIN disciplinas d ON t.disciplina_id = d.id
                        JOIN turmas tu ON t.turma_id = tu.id
                        WHERE t.id = ? AND t.professor_id = ?";
    $stmt_info = mysqli_prepare($conn, $sql_tarefa_info);
    mysqli_stmt_bind_param($stmt_info, "ii", $tarefa_id_selecionada, $professor_id);
    mysqli_stmt_execute($stmt_info);
    $tarefa_info_selecionada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));
    mysqli_stmt_close($stmt_info);
    
    if($tarefa_info_selecionada){
        $turma_id_da_tarefa = $tarefa_info_selecionada['turma_id'];
        $sql_submissoes = "
            SELECT a.id as aluno_id, a.nome, a.foto_url,
                   sub.id as submissao_id, sub.data_submissao, sub.arquivo_path_aluno, sub.nota, sub.feedback_professor
            FROM alunos a
            LEFT JOIN tarefas_submissoes sub ON a.id = sub.aluno_id AND sub.tarefa_id = ?
            WHERE a.turma_id = ?
            ORDER BY a.nome";
        $stmt_sub = mysqli_prepare($conn, $sql_submissoes);
        mysqli_stmt_bind_param($stmt_sub, "ii", $tarefa_id_selecionada, $turma_id_da_tarefa);
        mysqli_stmt_execute($stmt_sub);
        $result_sub = mysqli_stmt_get_result($stmt_sub);
        while ($row = mysqli_fetch_assoc($result_sub)) {
            $submissoes_da_tarefa[] = $row;
        }
        mysqli_stmt_close($stmt_sub);
    } else {
        $_SESSION['tarefa_prof_status_message'] = "Tarefa não encontrada ou acesso negado.";
        $_SESSION['tarefa_prof_status_type'] = "status-error";
        header("Location: professor_lancar_tarefa.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Tarefas - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/professor.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .tarefas-container { display: grid; grid-template-columns: 1fr; gap: 2rem; }
        @media (min-width: 1200px) { .tarefas-container { grid-template-columns: 400px 1fr; } }
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.3rem; font-weight: bold; }
        .form-section input, .form-section textarea, .form-section select { width: 100%; padding: 0.6rem; margin-bottom: 0.8rem; box-sizing: border-box; border-radius: 4px; }
        .form-section textarea { min-height: 120px; }
        .form-section button { display: block; width: 100%; padding: 0.7rem; }
        .list-section table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
        .list-section th, .list-section td { padding: 0.75rem; text-align: left; vertical-align: middle; }
        .list-section .actions-cell form, .list-section .actions-cell a { margin: 0 2px; display: inline-block; }
        .submission-list-table img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 10px; }
        .submission-list-table input[type="number"] { width: 70px; padding: 5px; text-align: center; }
        .submission-list-table textarea { width: 100%; min-height: 40px; padding: 5px; box-sizing: border-box; font-size: 0.9em;}
        .submission-status-entregue { color: green; }
        .submission-status-atraso { color: orange; }
        .submission-status-pendente { opacity: 0.6; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        .no-data-message { padding: 1rem; text-align: center; }

        /* CSS do Chat */
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
        <h1>ACADMIX - Gerenciar Tarefas</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Gerenciamento de Tarefas</h2>

            <?php if(isset($_SESSION['tarefa_prof_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['tarefa_prof_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['tarefa_prof_status_message']); ?>
                </div>
                <?php unset($_SESSION['tarefa_prof_status_message']); unset($_SESSION['tarefa_prof_status_type']); ?>
            <?php endif; ?>

            <?php if ($view_mode === 'submissions'): ?>
                <section class="dashboard-section card">
                    <a href="professor_lancar_tarefa.php" class="button button-secondary button-small" style="margin-bottom: 1rem; display:inline-block;"><i class="fas fa-arrow-left"></i> Voltar para Lista de Tarefas</a>
                    <h3>Entregas para: "<?php echo htmlspecialchars($tarefa_info_selecionada['titulo']); ?>"</h3>
                    <p><strong>Turma:</strong> <?php echo htmlspecialchars($tarefa_info_selecionada['nome_turma']); ?> | <strong>Disciplina:</strong> <?php echo htmlspecialchars($tarefa_info_selecionada['nome_disciplina']); ?> | <strong>Prazo:</strong> <?php echo date("d/m/Y H:i", strtotime($tarefa_info_selecionada['data_prazo'])); ?></p>

                    <?php if (!empty($submissoes_da_tarefa)): ?>
                        <form action="professor_lancar_tarefa.php" method="POST">
                            <input type="hidden" name="action" value="avaliar_submissoes">
                            <input type="hidden" name="tarefa_id_avaliada" value="<?php echo $tarefa_id_selecionada; ?>">
                            <div style="overflow-x:auto;">
                                <table class="table submission-list-table">
                                    <thead>
                                        <tr>
                                            <th>Aluno</th>
                                            <th>Status da Entrega</th>
                                            <th>Arquivo Enviado</th>
                                            <th>Nota (0-10)</th>
                                            <th>Feedback</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissoes_da_tarefa as $sub): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo htmlspecialchars(!empty($sub['foto_url']) ? $sub['foto_url'] : 'img/alunos/default_avatar.png'); ?>" alt="Foto" onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                                                    <?php echo htmlspecialchars($sub['aluno_nome']); ?>
                                                </td>
                                                <td>
                                                    <?php if($sub['submissao_id']): 
                                                        $sub_time = new DateTime($sub['data_submissao']);
                                                        $prazo_time = new DateTime($tarefa_info_selecionada['data_prazo']);
                                                        if ($sub_time > $prazo_time) {
                                                            echo '<span class="submission-status-atraso">Entregue com atraso</span><br><small>' . $sub_time->format('d/m/Y H:i') . '</small>';
                                                        } else {
                                                            echo '<span class="submission-status-entregue">Entregue</span><br><small>' . $sub_time->format('d/m/Y H:i') . '</small>';
                                                        }
                                                    ?>
                                                    <?php else: ?>
                                                        <span class="submission-status-pendente">Pendente</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($sub['submissao_id']): ?>
                                                        <a href="<?php echo htmlspecialchars($sub['arquivo_path_aluno']); ?>" class="button button-info button-xsmall" target="_blank"><i class="fas fa-download"></i> Ver Arquivo</a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($sub['submissao_id']): ?>
                                                        <input type="number" name="submissoes[<?php echo $sub['submissao_id']; ?>][nota]" value="<?php echo htmlspecialchars($sub['nota'] ?? ''); ?>" step="0.1" min="0" max="10" class="input-field">
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                     <?php if($sub['submissao_id']): ?>
                                                        <textarea name="submissoes[<?php echo $sub['submissao_id']; ?>][feedback]" class="input-field"><?php echo htmlspecialchars($sub['feedback_professor'] ?? ''); ?></textarea>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="button button-primary" style="margin-top: 1.5rem;"><i class="fas fa-save"></i> Salvar Todas as Avaliações</button>
                        </form>
                    <?php else: ?>
                        <p class="no-data-message info-message">Nenhum aluno nesta turma para exibir.</p>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <div class="tarefas-container">
                    <section class="form-section dashboard-section card">
                        <h3><i class="fas fa-plus-square"></i> Criar Nova Tarefa</h3>
                        <form action="professor_lancar_tarefa.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_tarefa">
                            <label for="titulo">Título da Tarefa:</label>
                            <input type="text" id="titulo" name="titulo" class="input-field" required>

                            <label for="turma_disciplina_select">Para Turma / Disciplina:</label>
                            <select id="turma_disciplina_select" name="turma_disciplina_id" class="input-field" required>
                                <option value="">Selecione...</option>
                                <?php if(!empty($associacoes)): foreach($associacoes as $assoc): ?>
                                    <option value="<?php echo $assoc['turma_id'].'-'.$assoc['disciplina_id']; ?>">
                                        <?php echo htmlspecialchars($assoc['nome_turma']) . ' / ' . htmlspecialchars($assoc['nome_disciplina']); ?>
                                    </option>
                                <?php endforeach; else: ?>
                                <option value="" disabled>Você não tem turmas/disciplinas associadas.</option>
                                <?php endif; ?>
                            </select>

                            <label for="data_prazo">Data e Hora do Prazo Final:</label>
                            <input type="datetime-local" id="data_prazo" name="data_prazo" class="input-field" required>
                            
                            <label for="descricao">Descrição / Instruções:</label>
                            <textarea id="descricao" name="descricao" class="input-field"></textarea>

                            <label for="arquivo_professor">Anexar Arquivo de Apoio (Opcional):</label>
                            <input type="file" id="arquivo_professor" name="arquivo_professor" class="input-field">

                            <button type="submit" class="button button-primary"><i class="fas fa-paper-plane"></i> Enviar Tarefa</button>
                        </form>
                    </section>

                    <section class="list-section dashboard-section card">
                        <h3><i class="fas fa-history"></i> Tarefas Enviadas</h3>
                        <div style="overflow-x:auto;">
                             <table class="table list-section">
                                <thead><tr><th>Título</th><th>Turma</th><th>Disciplina</th><th>Prazo</th><th>Entregas</th><th>Ações</th></tr></thead>
                                <tbody>
                                    <?php if(!empty($tarefas_enviadas)): foreach($tarefas_enviadas as $tarefa_enviada): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tarefa_enviada['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($tarefa_enviada['nome_turma']); ?></td>
                                        <td><?php echo htmlspecialchars($tarefa_enviada['nome_disciplina']); ?></td>
                                        <td><?php echo date("d/m/y H:i", strtotime($tarefa_enviada['data_prazo'])); ?></td>
                                        <td><?php echo $tarefa_enviada['total_entregas']; ?></td>
                                        <td class="actions-cell">
                                            <a href="?view_submissions_for=<?php echo $tarefa_enviada['id']; ?>" class="button button-info button-xsmall" title="Ver Entregas e Avaliar"><i class="fas fa-list-check"></i></a>
                                            <form action="professor_lancar_tarefa.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta tarefa? Todas as submissões dos alunos também serão perdidas.');">
                                                <input type="hidden" name="action" value="delete_tarefa">
                                                <input type="hidden" name="tarefa_id_delete" value="<?php echo $tarefa_enviada['id']; ?>">
                                                <button type="submit" class="button button-danger button-xsmall" title="Excluir Tarefa"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr>
                                        <td colspan="6" class="no-data-message">Nenhuma tarefa criada por você ainda.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
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
<?php 
if(isset($conn) && $conn) mysqli_close($conn); 
?>