<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];

$currentPageIdentifier = 'criar_quiz'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// --- LÓGICA DE PROCESSAMENTO DE AÇÕES (EXCLUIR QUIZ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete_quiz' && isset($_POST['quiz_id_delete'])) {
        $quiz_id_del = intval($_POST['quiz_id_delete']);
        
        // A tabela de quiz_questoes e quiz_tentativas_alunos tem ON DELETE CASCADE,
        // então todos os registros relacionados (questões, opções, tentativas, respostas) serão apagados automaticamente.
        $sql = "DELETE FROM quizzes WHERE id = ? AND professor_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $quiz_id_del, $professor_id);
            if(mysqli_stmt_execute($stmt)){
                if(mysqli_stmt_affected_rows($stmt) > 0) {
                     $_SESSION['quiz_status_message'] = "Prova excluída com sucesso.";
                     $_SESSION['quiz_status_type'] = "status-success";
                } else {
                     $_SESSION['quiz_status_message'] = "Não foi possível excluir a prova (não encontrada ou permissão negada).";
                     $_SESSION['quiz_status_type'] = "status-error";
                }
            } else {
                $_SESSION['quiz_status_message'] = "Erro ao executar a exclusão.";
                $_SESSION['quiz_status_type'] = "status-error";
            }
            mysqli_stmt_close($stmt);
        } else {
             $_SESSION['quiz_status_message'] = "Erro ao preparar a exclusão.";
             $_SESSION['quiz_status_type'] = "status-error";
        }
        
        header("Location: professor_criar_quiz.php");
        exit();
    }
}

// Buscar turmas e disciplinas que o professor leciona para os selects do formulário
$sql_assoc = "SELECT DISTINCT t.id as turma_id, t.nome_turma, d.id as disciplina_id, d.nome_disciplina
              FROM turmas t
              JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id
              JOIN disciplinas d ON ptd.disciplina_id = d.id
              WHERE ptd.professor_id = ? ORDER BY t.nome_turma, d.nome_disciplina";
$stmt_assoc = mysqli_prepare($conn, $sql_assoc);
$associacoes = [];
if($stmt_assoc){
    mysqli_stmt_bind_param($stmt_assoc, "i", $professor_id);
    mysqli_stmt_execute($stmt_assoc);
    $result_assoc = mysqli_stmt_get_result($stmt_assoc);
    while($row = mysqli_fetch_assoc($result_assoc)){ $associacoes[] = $row; }
    mysqli_stmt_close($stmt_assoc);
}

// Buscar quizzes já criados por este professor para a lista
$quizzes_criados = [];
$sql_quizzes = "SELECT q.id, q.titulo, q.data_prazo, tu.nome_turma, d.nome_disciplina,
                (SELECT COUNT(DISTINCT aluno_id) FROM quiz_tentativas_alunos WHERE quiz_id = q.id AND status != 'em_andamento') as total_entregas
                FROM quizzes q
                JOIN turmas tu ON q.turma_id = tu.id
                JOIN disciplinas d ON q.disciplina_id = d.id
                WHERE q.professor_id = ? 
                ORDER BY q.data_criacao DESC";
$stmt_quizzes = mysqli_prepare($conn, $sql_quizzes);
if($stmt_quizzes){
    mysqli_stmt_bind_param($stmt_quizzes, "i", $professor_id);
    mysqli_stmt_execute($stmt_quizzes);
    $result_quizzes = mysqli_stmt_get_result($stmt_quizzes);
    while($row = mysqli_fetch_assoc($result_quizzes)){ $quizzes_criados[] = $row; }
    mysqli_stmt_close($stmt_quizzes);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar e Gerenciar Provas - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/professor.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .main-container { display: grid; grid-template-columns: 1fr; gap: 2rem; }
        @media (min-width: 1200px) { .main-container { grid-template-columns: 450px 1fr; } }
        
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.3rem; font-weight: bold;}
        .form-section input, .form-section textarea, .form-section select { width: 100%; padding: 0.6rem; margin-bottom: 0.8rem; box-sizing: border-box; border-radius: 4px;}
        .form-section textarea { min-height: 80px; resize: vertical; }
        .form-section button { display: inline-block; width: auto; margin-top: 1rem; padding: 0.7rem 1.5rem; }
        .form-section .grid-2-col { display: grid; grid-template-columns: 1fr; gap: 1rem; }
        @media (min-width: 768px) { .form-section .grid-2-col { grid-template-columns: 1fr 1fr; } }
        
        #questoes-container { margin-top: 2rem; border-top: 2px solid var(--border-color, #ccc); padding-top: 1.5rem; }
        .questao-card { padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; position: relative; background-color: var(--background-color-offset, #f9f9f9); }
        .questao-card h4 { margin-top: 0; }
        .questao-card .remover-questao-btn { position: absolute; top: 10px; right: 10px; cursor: pointer; background: var(--danger-color-light); color: var(--danger-color-dark); border: none; border-radius: 50%; width: 28px; height: 28px; font-size: 1.1rem; line-height: 28px; text-align: center;}
        
        .opcoes-container { margin-top: 1rem; }
        .opcao-item { display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; }
        .opcao-item input[type="radio"] { flex-shrink: 0; width: auto; }
        .opcao-item input[type="text"] { flex-grow: 1; margin: 0; }
        .opcao-item button { margin: 0; padding: 0.4rem; width: auto; font-size: 0.8em; line-height: 1; }
        
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        
        .quiz-list table { width: 100%; border-collapse: collapse; font-size: 0.9em; }
        .quiz-list th, .quiz-list td { padding: 0.75rem; text-align: left; vertical-align: middle; }
        .quiz-list .actions-cell form, .quiz-list .actions-cell a { margin: 0 2px; display: inline-block; }

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
        <h1>ACADMIX - Criar e Gerenciar Provas</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Gerenciamento de Provas e Quizzes</h2>
            <?php if(isset($_SESSION['quiz_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['quiz_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['quiz_status_message']); ?>
                </div>
                <?php unset($_SESSION['quiz_status_message']); unset($_SESSION['quiz_status_type']); ?>
            <?php endif; ?>
            
            <div class="main-container">
                <section class="form-section dashboard-section card">
                    <h3><i class="fas fa-plus-square"></i> Criar Nova Prova</h3>
                    <form action="salvar_quiz.php" method="POST" id="quiz-form">
                        <label for="titulo">Título da Prova/Quiz:</label>
                        <input type="text" id="titulo" name="titulo" class="input-field" required>

                        <div class="grid-2-col">
                             <div>
                                <label for="turma_disciplina_id">Para Turma / Disciplina:</label>
                                <select id="turma_disciplina_id" name="turma_disciplina_id" class="input-field" required>
                                    <option value="">Selecione...</option>
                                    <?php if(!empty($associacoes)): foreach($associacoes as $assoc): ?>
                                        <option value="<?php echo $assoc['turma_id'].'-'.$assoc['disciplina_id']; ?>">
                                            <?php echo htmlspecialchars($assoc['nome_turma']) . ' / ' . htmlspecialchars($assoc['nome_disciplina']); ?>
                                        </option>
                                    <?php endforeach; else: ?>
                                    <option value="" disabled>Você não tem turmas/disciplinas associadas.</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                             <div>
                                <label for="duracao_minutos">Duração (min, 0=livre):</label>
                                <input type="number" id="duracao_minutos" name="duracao_minutos" class="input-field" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="grid-2-col">
                            <div>
                                <label for="data_inicio">Disponível a partir de:</label>
                                <input type="datetime-local" id="data_inicio" name="data_inicio" class="input-field" required>
                            </div>
                            <div>
                                <label for="data_prazo">Prazo final:</label>
                                <input type="datetime-local" id="data_prazo" name="data_prazo" class="input-field" required>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1rem;">
                            <input type="checkbox" id="aleatorizar_questoes" name="aleatorizar_questoes" value="1" style="width: auto; margin-right: 5px;">
                            <label for="aleatorizar_questoes" style="display:inline; font-weight:normal;">Aleatorizar ordem das questões</label>
                        </div>
                        
                        <label for="descricao" style="margin-top: 1.5rem;">Instruções / Descrição:</label>
                        <textarea id="descricao" name="descricao" class="input-field" placeholder="Ex: Leia atentamente as questões..."></textarea>

                        <div id="questoes-container">
                            </div>

                        <div style="margin-top:1rem; display:flex; gap:1rem;">
                            <button type="button" id="add-multipla-escolha-btn" class="button button-secondary"><i class="fas fa-plus-circle"></i> Múltipla Escolha</button>
                            <button type="button" id="add-dissertativa-btn" class="button button-secondary"><i class="fas fa-plus-circle"></i> Dissertativa</button>
                        </div>

                        <button type="submit" class="button button-primary" style="font-size:1.1rem; width:100%; margin-top: 2rem;"><i class="fas fa-save"></i> Salvar Prova Completa</button>
                    </form>
                </section>

                 <section class="list-section dashboard-section card">
                    <h3><i class="fas fa-history"></i> Provas Criadas</h3>
                    <div style="overflow-x:auto;">
                         <table class="table quiz-list">
                            <thead><tr><th>Título</th><th>Turma</th><th>Prazo</th><th>Entregas</th><th>Ações</th></tr></thead>
                            <tbody>
                                <?php if(!empty($quizzes_criados)): foreach($quizzes_criados as $quiz): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($quiz['titulo']); ?></td>
                                    <td><?php echo htmlspecialchars($quiz['nome_turma']); ?></td>
                                    <td><?php echo date("d/m/y H:i", strtotime($quiz['data_prazo'])); ?></td>
                                    <td><?php echo $quiz['total_entregas']; ?></td>
                                    <td class="actions-cell">
                                        <a href="professor_avaliar_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="button button-info button-xsmall" title="Ver Respostas e Avaliar"><i class="fas fa-list-check"></i></a>
                                        
                                        <form action="professor_criar_quiz.php" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta prova? Todas as tentativas e respostas dos alunos também serão perdidas.');">
                                            <input type="hidden" name="action" value="delete_quiz">
                                            <input type="hidden" name="quiz_id_delete" value="<?php echo $quiz['id']; ?>">
                                            <button type="submit" class="button button-danger button-xsmall" title="Excluir Prova"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="5" class="no-data-message">Nenhuma prova criada por você ainda.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
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

        // --- LÓGICA DO CONSTRUTOR DE QUIZ ---
        document.addEventListener('DOMContentLoaded', function() {
            const questoesContainer = document.getElementById('questoes-container');
            let questaoIndex = 0;

            document.getElementById('add-multipla-escolha-btn').addEventListener('click', () => {
                adicionarQuestao('multipla_escolha');
            });
            document.getElementById('add-dissertativa-btn').addEventListener('click', () => {
                adicionarQuestao('dissertativa');
            });

            window.adicionarQuestao = function(tipo) {
                const questaoId = `q_${questaoIndex}`;
                const questaoCard = document.createElement('div');
                questaoCard.className = 'questao-card card-item';
                questaoCard.id = questaoId;

                let htmlInterno = `
                    <button type="button" class="remover-questao-btn" title="Remover Questão" onclick="removerElemento('${questaoId}')">&times;</button>
                    <h4>Questão ${questaoIndex + 1} (${tipo === 'multipla_escolha' ? 'Múltipla Escolha' : 'Dissertativa'})</h4>
                    <input type="hidden" name="questoes[${questaoIndex}][tipo]" value="${tipo}">
                    
                    <label for="texto_${questaoId}">Enunciado da Questão:</label>
                    <textarea id="texto_${questaoId}" name="questoes[${questaoIndex}][texto]" class="input-field" required></textarea>
                    
                    <label for="pontos_${questaoId}">Pontos:</label>
                    <input type="number" id="pontos_${questaoId}" name="questoes[${questaoIndex}][pontos]" class="input-field" value="1.0" step="0.1" min="0" required style="width:100px;">
                `;

                if (tipo === 'multipla_escolha') {
                    htmlInterno += `
                        <div class="opcoes-container" id="opcoes_container_${questaoId}">
                            <label>Opções de Resposta (marque a correta):</label>
                        </div>
                        <button type="button" class="button button-secondary button-xsmall" onclick="adicionarOpcao('${questaoId}', ${questaoIndex})"><i class="fas fa-plus"></i> Adicionar Opção</button>
                    `;
                }
                
                questaoCard.innerHTML = htmlInterno;
                questoesContainer.appendChild(questaoCard);

                if (tipo === 'multipla_escolha') {
                    adicionarOpcao(questaoId, questaoIndex);
                    adicionarOpcao(questaoId, questaoIndex);
                }
                questaoIndex++;
            }

            window.adicionarOpcao = function(questaoId, qIndex) {
                const opcoesContainer = document.getElementById(`opcoes_container_${questaoId}`);
                const opcaoIndex = opcoesContainer.querySelectorAll('.opcao-item').length;
                const opcaoId = `op_${questaoId}_${opcaoIndex}`;

                const opcaoItem = document.createElement('div');
                opcaoItem.className = 'opcao-item';
                opcaoItem.id = opcaoId;

                opcaoItem.innerHTML = `
                    <input type="radio" name="questoes[${qIndex}][correta]" id="correta_${opcaoId}" value="${opcaoIndex}" required title="Marcar como correta">
                    <input type="text" name="questoes[${qIndex}][opcoes][${opcaoIndex}][texto]" class="input-field" placeholder="Texto da opção ${opcaoIndex + 1}" required>
                    <button type="button" class="button button-danger button-xsmall" onclick="removerElemento('${opcaoId}')" title="Remover Opção"><i class="fas fa-times"></i></button>
                `;
                opcoesContainer.appendChild(opcaoItem);
            }

            window.removerElemento = function(elementId) {
                const elemento = document.getElementById(elementId);
                if (elemento) {
                    elemento.remove();
                }
            }
        });
    </script>
    
    <script>
        // O JavaScript completo e padronizado do chat para PROFESSOR vai aqui.
        // Assegure-se de que ele use as variáveis `currentUserId` e `currentUserSessionRole`
        // definidas a partir das variáveis PHP `$professor_id` e `$_SESSION['role']`.
    </script>

</body>
</html>
<?php 
if(isset($conn) && $conn) mysqli_close($conn); 
?>