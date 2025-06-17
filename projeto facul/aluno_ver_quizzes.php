<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

// Variáveis do Aluno Logado
$nome_aluno = $_SESSION['usuario_nome'];
$aluno_id = $_SESSION['usuario_id']; 
$turma_id_aluno = isset($_SESSION['turma_id']) ? intval($_SESSION['turma_id']) : 0;

$currentPageIdentifier = 'ver_quizzes_aluno'; // Para o sidebar
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Lógica para buscar quizzes disponíveis para a turma do aluno
$quizzes_disponiveis = [];

if ($turma_id_aluno > 0) {
    $sql = "
        SELECT 
            q.id, q.titulo, q.descricao, q.data_inicio, q.data_prazo,
            d.nome_disciplina,
            p.nome as nome_professor,
            tentativa.id as tentativa_id,
            tentativa.status as status_tentativa,
            tentativa.nota_final
        FROM quizzes q
        JOIN disciplinas d ON q.disciplina_id = d.id
        JOIN professores p ON q.professor_id = p.id
        LEFT JOIN quiz_tentativas_alunos tentativa ON q.id = tentativa.quiz_id AND tentativa.aluno_id = ?
        WHERE q.turma_id = ? AND NOW() >= q.data_inicio
        ORDER BY q.data_prazo ASC
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $aluno_id, $turma_id_aluno);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $quizzes_disponiveis[] = $row;
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Erro ao buscar quizzes para o aluno (aluno_ver_quizzes.php): " . mysqli_error($conn));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Provas e Quizzes - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css">
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .quiz-card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .quiz-info h3 { margin-top: 0; font-size: 1.3rem; }
        .quiz-meta { font-size: 0.9rem; opacity: 0.9; }
        .quiz-actions { text-align: right; }
        .status-badge {
            font-size: 0.8rem;
            font-weight: bold;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            color: white;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        .status-badge.disponivel { background-color: var(--success-color, green); }
        .status-badge.finalizado { background-color: var(--accent-color, #6c757d); }
        .status-badge.avaliado { background-color: var(--primary-color, #007bff); }
        .status-badge.prazo-encerrado { background-color: var(--danger-color, red); }
        .no-data-message { padding: 2rem; text-align: center; }

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
        <h1>ACADMIX - Provas e Quizzes</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_aluno.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Provas e Quizzes Disponíveis</h2>

            <?php if (empty($quizzes_disponiveis)): ?>
                <p class="no-data-message info-message card">Nenhuma prova ou quiz disponível para você no momento.</p>
            <?php else: ?>
                <?php foreach ($quizzes_disponiveis as $quiz): ?>
                    <div class="quiz-card card">
                        <div class="quiz-info">
                            <h3><?php echo htmlspecialchars($quiz['titulo']); ?></h3>
                            <p class="quiz-meta">
                                <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($quiz['nome_disciplina']); ?></span> | 
                                <span><i class="fas fa-chalkboard-teacher"></i> Prof. <?php echo htmlspecialchars($quiz['nome_professor']); ?></span>
                            </p>
                            <p class="quiz-meta">
                                <span><i class="fas fa-calendar-alt"></i> Prazo Final: <strong><?php echo date("d/m/Y H:i", strtotime($quiz['data_prazo'])); ?></strong></span>
                            </p>
                        </div>
                        <div class="quiz-actions">
                            <?php
                                $prazo_encerrado = new DateTime() > new DateTime($quiz['data_prazo']);
                                $status = $quiz['status_tentativa'];
                                
                                if ($status === 'avaliado') {
                                    echo '<span class="status-badge status-avaliado">Avaliado</span>';
                                    echo '<p>Nota: <strong>' . number_format($quiz['nota_final'], 2, ',', '.') . '</strong></p>';
                                    echo '<a href="aluno_ver_resultado_quiz.php?id=' . $quiz['id'] . '" class="button button-secondary button-small">Ver Resultado</a>';
                                } elseif ($status === 'finalizado') {
                                    echo '<span class="status-badge status-finalizado">Finalizado</span>';
                                    echo '<p>Aguardando avaliação</p>';
                                } elseif ($prazo_encerrado && $status !== 'finalizado') {
                                    echo '<span class="status-badge status-prazo-encerrado">Prazo Encerrado</span>';
                                } else {
                                    echo '<span class="status-badge status-disponivel">Disponível</span>';
                                    echo '<a href="aluno_responder_quiz.php?id=' . $quiz['id'] . '" class="button button-primary">Iniciar Prova</a>';
                                }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <div id="academicChatWidget" class="chat-widget-acad chat-collapsed">
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
        // COLE AQUI O JAVASCRIPT COMPLETO DO CHAT PARA ALUNO
    </script>

</body>
</html>
<?php 
if(isset($conn) && $conn) mysqli_close($conn); 
?>