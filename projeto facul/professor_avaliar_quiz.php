<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];

$currentPageIdentifier = 'avaliar_quiz'; // Usado para destacar o menu, se aplicável
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

if (!isset($_GET['quiz_id'])) {
    header("Location: professor_criar_quiz.php"); // Volta para a lista se nenhum quiz for especificado
    exit();
}
$quiz_id_selecionado = intval($_GET['quiz_id']);

// --- LÓGICA DE PROCESSAMENTO DE AVALIAÇÕES (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_avaliacoes') {
    $tentativas_avaliadas = $_POST['avaliacoes'] ?? [];
    $redirect_url = "professor_avaliar_quiz.php?quiz_id=" . $quiz_id_selecionado;

    mysqli_autocommit($conn, FALSE);
    try {
        foreach ($tentativas_avaliadas as $tentativa_id => $respostas) {
            $tentativa_id = intval($tentativa_id);
            $nota_total_tentativa = 0;

            foreach ($respostas as $resposta_id => $dados) {
                $resposta_id = intval($resposta_id);
                $pontos = !empty($dados['pontos']) ? floatval(str_replace(',', '.', $dados['pontos'])) : 0.0;
                
                $sql_update_resposta = "UPDATE quiz_respostas_alunos SET pontos_obtidos = ? WHERE id = ?";
                $stmt_up_resp = mysqli_prepare($conn, $sql_update_resposta);
                mysqli_stmt_bind_param($stmt_up_resp, "di", $pontos, $resposta_id);
                mysqli_stmt_execute($stmt_up_resp);
                mysqli_stmt_close($stmt_up_resp);
                $nota_total_tentativa += $pontos;
            }
            
            $feedback_geral = $_POST['feedback_geral'][$tentativa_id] ?? null;
            $sql_update_tentativa = "UPDATE quiz_tentativas_alunos SET nota_final = ?, status = 'avaliado', data_avaliacao = NOW(), feedback_professor = ? WHERE id = ?";
            $stmt_up_tentativa = mysqli_prepare($conn, $sql_update_tentativa);
            mysqli_stmt_bind_param($stmt_up_tentativa, "dsi", $nota_total_tentativa, $feedback_geral, $tentativa_id);
            mysqli_stmt_execute($stmt_up_tentativa);
            mysqli_stmt_close($stmt_up_tentativa);
        }
        mysqli_commit($conn);
        $_SESSION['quiz_prof_status_message'] = "Avaliações salvas com sucesso!";
        $_SESSION['quiz_prof_status_type'] = "status-success";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['quiz_prof_status_message'] = "Erro ao salvar avaliações: " . $e->getMessage();
        $_SESSION['quiz_prof_status_type'] = "status-error";
        error_log("Erro ao salvar avaliações: " . $e->getMessage());
    }
     mysqli_autocommit($conn, TRUE);
     header("Location: " . $redirect_url);
     exit();
}

// --- LÓGICA DE VISUALIZAÇÃO (GET) ---
// 1. Buscar detalhes do quiz e validar se pertence ao professor
$sql_quiz_info = "SELECT q.id, q.titulo, q.descricao, d.nome_disciplina, tu.nome_turma, tu.id as turma_id
                  FROM quizzes q
                  JOIN disciplinas d ON q.disciplina_id = d.id
                  JOIN turmas tu ON q.turma_id = tu.id
                  WHERE q.id = ? AND q.professor_id = ?";
$stmt_info = mysqli_prepare($conn, $sql_quiz_info);
mysqli_stmt_bind_param($stmt_info, "ii", $quiz_id_selecionado, $professor_id);
mysqli_stmt_execute($stmt_info);
$quiz_info_selecionado = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));
mysqli_stmt_close($stmt_info);

if (!$quiz_info_selecionado) {
    // Redireciona para uma página anterior com uma mensagem de erro, em vez de interromper
    $_SESSION['quiz_prof_status_message'] = "Prova não encontrada ou você não tem permissão para acessá-la.";
    $_SESSION['quiz_prof_status_type'] = "status-error";
    header("Location: professor_criar_quiz.php");
    exit();
}

// 2. Buscar todas as questões e opções corretas do quiz
$questoes_do_quiz = [];
$sql_questoes = "SELECT id, texto_pergunta, tipo_pergunta, pontos FROM quiz_questoes WHERE quiz_id = ? ORDER BY ordem ASC";
$stmt_q = mysqli_prepare($conn, $sql_questoes);
mysqli_stmt_bind_param($stmt_q, "i", $quiz_id_selecionado);
mysqli_stmt_execute($stmt_q);
$result_q = mysqli_stmt_get_result($stmt_q);
while($row_q = mysqli_fetch_assoc($result_q)){
    if($row_q['tipo_pergunta'] === 'multipla_escolha') {
        $row_q['opcoes'] = [];
        $sql_opcoes = "SELECT id, texto_opcao, is_correta FROM quiz_opcoes WHERE questao_id = ? ORDER BY id";
        $stmt_o = mysqli_prepare($conn, $sql_opcoes);
        mysqli_stmt_bind_param($stmt_o, "i", $row_q['id']);
        mysqli_stmt_execute($stmt_o);
        $result_o = mysqli_stmt_get_result($stmt_o);
        while ($row_o = mysqli_fetch_assoc($result_o)) {
            if ($row_o['is_correta']) {
                $row_q['opcao_correta'] = $row_o; // Armazena a opção correta inteira
            }
        }
        mysqli_stmt_close($stmt_o);
    }
    $questoes_do_quiz[$row_q['id']] = $row_q;
}
mysqli_stmt_close($stmt_q);

// 3. Buscar alunos da turma e suas respectivas tentativas e respostas
$entregas = [];
$turma_id_do_quiz = $quiz_info_selecionado['turma_id'];

// ***** ESTA É A QUERY SQL CORRIGIDA *****
$sql_entregas = "
    SELECT 
        a.id as aluno_id, a.nome as aluno_nome, a.foto_url,
        tentativa.id as tentativa_id, tentativa.status as status_tentativa, tentativa.nota_final, tentativa.data_submissao, tentativa.feedback_professor as feedback_geral,
        resposta.id as resposta_id, resposta.questao_id, resposta.opcao_id_selecionada, resposta.texto_resposta_dissertativa, resposta.pontos_obtidos,
        opcao.texto_opcao as texto_opcao_selecionada
    FROM alunos a
    LEFT JOIN quiz_tentativas_alunos tentativa ON a.id = tentativa.aluno_id AND tentativa.quiz_id = ? -- CORREÇÃO: Usando quiz_id
    LEFT JOIN quiz_respostas_alunos resposta ON tentativa.id = resposta.tentativa_id
    LEFT JOIN quiz_opcoes opcao ON resposta.opcao_id_selecionada = opcao.id
    WHERE a.turma_id = ?
    ORDER BY a.nome, resposta.questao_id";

$stmt_entregas = mysqli_prepare($conn, $sql_entregas);
if ($stmt_entregas) {
    mysqli_stmt_bind_param($stmt_entregas, "ii", $quiz_id_selecionado, $turma_id_do_quiz);
    mysqli_stmt_execute($stmt_entregas);
    $result_entregas = mysqli_stmt_get_result($stmt_entregas);
    while($row = mysqli_fetch_assoc($result_entregas)){
        $aluno_id_atual = $row['aluno_id'];
        if(!isset($entregas[$aluno_id_atual])) {
            $entregas[$aluno_id_atual] = [
                'aluno_nome' => $row['aluno_nome'], 'foto_url' => $row['foto_url'],
                'tentativa_id' => $row['tentativa_id'], 'status' => $row['status_tentativa'],
                'nota_final' => $row['nota_final'], 'data_submissao' => $row['data_submissao'],
                'feedback_geral' => $row['feedback_geral'], 'respostas' => []
            ];
        }
        if($row['resposta_id']) {
            $entregas[$aluno_id_atual]['respostas'][$row['questao_id']] = $row;
        }
    }
    mysqli_stmt_close($stmt_entregas);
} else {
    die("Ocorreu um erro crítico ao carregar os dados das entregas. Verifique os logs do servidor.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Avaliar Quiz: <?php echo htmlspecialchars($quiz_info_selecionado['titulo']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/professor.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .quiz-details-header { text-align: center; margin-bottom: 2rem; padding: 1rem; border-radius: 8px; }
        .quiz-details-header p { margin: 0.2rem 0; }
        
        .aluno-avaliacao-card { margin-bottom: 1.5rem; border-radius: 8px; overflow: hidden; }
        .aluno-header { padding: 1rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: background-color 0.2s; }
        .aluno-header:hover { background-color: var(--hover-background-color); }
        .aluno-header.active { border-bottom: 1px solid var(--border-color-soft); }
        .aluno-header img { width: 40px; height: 40px; border-radius: 50%; margin-right: 15px; }
        .aluno-info { display: flex; align-items: center; flex-grow: 1; }
        .aluno-status-badge { font-size: 0.8rem; font-weight: bold; padding: 0.4rem 0.8rem; border-radius: 15px; color: white; margin-left: 1rem; }
        .status-entregue { background-color: var(--success-color, green); }
        .status-pendente { background-color: var(--text-color-muted, #6c757d); }
        .status-avaliado { background-color: var(--primary-color, #007bff); }
        
        .aluno-respostas-body { display: none; padding: 1.5rem; }
        .questao-avaliacao { margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px dashed var(--border-color-soft); }
        .questao-avaliacao:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .questao-avaliacao strong { display: block; margin-bottom: 0.5rem; }
        .resposta-aluno { background-color: var(--background-color-offset, #f8f9fa); padding: 0.8rem; border-radius: 4px; white-space: pre-wrap; margin-top: 0.5rem;}
        .resposta-correta { color: var(--success-color, green); font-weight: bold; }
        .resposta-incorreta { color: var(--danger-color, red); text-decoration: line-through; }
        .correcao-area { margin-top: 1rem; }
        .correcao-area label { font-weight: bold; }
        .correcao-area input[type="number"] { width: 80px; padding: 0.3rem; text-align: center; }
        .feedback-geral-area textarea { width: 100%; min-height: 60px; }
        .btn-salvar-avaliacoes { display: block; margin: 2rem auto; width: 100%; max-width: 400px; padding: 1rem; font-size: 1.2rem; }
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
        <h1>ACADMIX - Avaliar Prova</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <div class="quiz-details-header card">
                <a href="professor_criar_quiz.php" class="button button-secondary" style="float: left; margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Voltar</a>
                <h2 class="page-title" style="display:inline-block; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($quiz_info_selecionado['titulo']); ?></h2>
                <p>
                    <strong>Turma:</strong> <?php echo htmlspecialchars($quiz_info_selecionado['nome_turma']); ?> | 
                    <strong>Disciplina:</strong> <?php echo htmlspecialchars($quiz_info_selecionado['nome_disciplina']); ?>
                </p>
            </div>

            <?php if(isset($_SESSION['quiz_prof_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['quiz_prof_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['quiz_prof_status_message']); ?>
                </div>
                <?php unset($_SESSION['quiz_prof_status_message']); unset($_SESSION['quiz_prof_status_type']); ?>
            <?php endif; ?>

            <form action="professor_avaliar_quiz.php?quiz_id=<?php echo $quiz_id_selecionado; ?>" method="POST">
                <input type="hidden" name="action" value="salvar_avaliacoes">
                <?php foreach($entregas as $aluno_id_entrega => $entrega): ?>
                    <div class="aluno-avaliacao-card card">
                        <div class="aluno-header">
                            <div class="aluno-info">
                                <img src="<?php echo htmlspecialchars(!empty($entrega['foto_url']) ? $entrega['foto_url'] : 'img/alunos/default_avatar.png'); ?>" alt="Foto" onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                                <span><?php echo htmlspecialchars($entrega['aluno_nome']); ?></span>
                            </div>
                            <?php 
                                if($entrega['status'] === 'avaliado') {
                                    echo '<span class="aluno-status-badge status-avaliado">Avaliado: '.number_format($entrega['nota_final'], 2, ',', '.').'</span>';
                                } elseif($entrega['status'] === 'finalizado') {
                                    echo '<span class="aluno-status-badge status-entregue">Entregue</span>';
                                } else {
                                    echo '<span class="aluno-status-badge status-pendente">Pendente</span>';
                                }
                            ?>
                        </div>
                        <?php if($entrega['tentativa_id']): ?>
                        <div class="aluno-respostas-body">
                            <?php foreach($questoes_do_quiz as $questao_id_quiz => $questao): ?>
                                <div class="questao-avaliacao">
                                    <p><strong><?php echo htmlspecialchars($questao['texto_pergunta']); ?></strong> (<?php echo number_format($questao['pontos'], 1, ',', '.'); ?> pts)</p>
                                    
                                    <?php 
                                        $resposta_do_aluno = $entrega['respostas'][$questao_id_quiz] ?? null;
                                        $acertou_auto = false;
                                    ?>

                                    <?php if ($questao['tipo_pergunta'] === 'multipla_escolha'): ?>
                                        <p>Resposta do Aluno: 
                                            <?php 
                                                $texto_aluno = $resposta_do_aluno['texto_opcao_selecionada'] ?? 'Não respondeu';
                                                $opcao_correta_id = $questao['opcao_correta']['id'] ?? -1;
                                                if ($resposta_do_aluno && $resposta_do_aluno['opcao_id_selecionada'] == $opcao_correta_id) {
                                                    echo '<span class="resposta-correta">' . htmlspecialchars($texto_aluno) . ' <i class="fas fa-check-circle"></i></span>';
                                                    $acertou_auto = true;
                                                } else {
                                                    echo '<span class="resposta-incorreta">' . htmlspecialchars($texto_aluno) . '</span>';
                                                }
                                            ?>
                                        </p>
                                        <p>Resposta Correta: <span class="resposta-correta"><?php echo htmlspecialchars($questao['opcao_correta']['texto_opcao'] ?? 'N/A'); ?></span></p>
                                    <?php else: // Dissertativa ?>
                                        <p>Resposta do Aluno:</p>
                                        <div class="resposta-aluno"><?php echo nl2br(htmlspecialchars($resposta_do_aluno['texto_resposta_dissertativa'] ?? 'Não respondeu')); ?></div>
                                    <?php endif; ?>
                                    
                                    <div class="correcao-area">
                                        <label for="pontos_<?php echo $resposta_do_aluno['resposta_id']; ?>">Pontos Atribuídos:</label>
                                        <input type="number" name="avaliacoes[<?php echo $entrega['tentativa_id']; ?>][respostas][<?php echo $resposta_do_aluno['resposta_id']; ?>][pontos]" 
                                               id="pontos_<?php echo $resposta_do_aluno['resposta_id']; ?>" 
                                               value="<?php echo htmlspecialchars($resposta_do_aluno['pontos_obtidos'] ?? ($acertou_auto ? $questao['pontos'] : '0.0')); ?>" 
                                               step="0.1" min="0" max="<?php echo $questao['pontos']; ?>" class="input-field">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="feedback-geral-area">
                                <label for="feedback_geral_<?php echo $entrega['tentativa_id']; ?>">Feedback Geral para o Aluno:</label>
                                <textarea name="feedback_geral[<?php echo $entrega['tentativa_id']; ?>]" id="feedback_geral_<?php echo $entrega['tentativa_id']; ?>" class="input-field"><?php echo htmlspecialchars($entrega['feedback_geral'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn-salvar-avaliacoes button button-primary"><i class="fas fa-save"></i> Salvar Todas as Avaliações</button>
            </form>
        </main>
    </div>

    <script>
        // Script do menu lateral
        const menuToggleButtonGlobal = document.getElementById('menu-toggle');
        const sidebarElementGlobal = document.getElementById('sidebar');    
        const pageContainerGlobal = document.getElementById('pageContainer'); 
        if (menuToggleButtonGlobal && sidebarElementGlobal && pageContainerGlobal) {
            menuToggleButtonGlobal.addEventListener('click', function () {
                sidebarElementGlobal.classList.toggle('hidden'); 
                pageContainerGlobal.classList.toggle('full-width'); 
            });
        }

        // Script para expandir/colapsar respostas dos alunos
        document.querySelectorAll('.aluno-header').forEach(header => {
            header.addEventListener('click', function() {
                this.classList.toggle('active');
                const body = this.nextElementSibling;
                if (body) { 
                    if (body.style.display === "block") {
                        body.style.display = "none";
                    } else {
                        body.style.display = "block";
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php 
if(isset($conn) && $conn) mysqli_close($conn); 
?>