<?php
session_start(); // GARANTIR QUE ESTÁ NO TOPO ABSOLUTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
include 'db.php'; // Conexão com o banco

$nome_coordenador = $_SESSION['usuario_nome'];
$coordenador_id = $_SESSION['usuario_id']; // Essencial para o chat

$currentPageIdentifier = 'ver_alunos_coord'; // Ajuste para o seu sidebar
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// --- LÓGICA DE PROCESSAMENTO DE AÇÕES (EX: EXCLUIR ALUNO) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    mysqli_autocommit($conn, FALSE); 

    try {
        if ($_POST['action'] === 'delete_aluno' && isset($_POST['aluno_id_delete'])) {
            $aluno_id_to_delete = intval($_POST['aluno_id_delete']);

            if ($aluno_id_to_delete > 0) {
                // ANTES DE EXCLUIR: Considere o que fazer com registros dependentes em outras tabelas
                // (notas, frequencia, chat_messages, etc.).
                // Idealmente, o banco de dados tem constraints ON DELETE CASCADE ou ON DELETE SET NULL.
                // Exemplo: Se quiser remover o aluno da turma antes de deletar (se turma_id em alunos não for ON DELETE CASCADE):
                // $sql_desvincular = "UPDATE alunos SET turma_id = NULL WHERE id = ?";
                // $stmt_desv = mysqli_prepare($conn, $sql_desvincular);
                // mysqli_stmt_bind_param($stmt_desv, "i", $aluno_id_to_delete);
                // mysqli_stmt_execute($stmt_desv);
                // mysqli_stmt_close($stmt_desv);

                // Excluir o aluno da tabela alunos
                $sql_delete_aluno = "DELETE FROM alunos WHERE id = ?";
                $stmt_delete = mysqli_prepare($conn, $sql_delete_aluno);
                mysqli_stmt_bind_param($stmt_delete, "i", $aluno_id_to_delete);
                if (mysqli_stmt_execute($stmt_delete)) {
                    if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                        $_SESSION['manage_aluno_status_message'] = "Aluno excluído com sucesso!";
                        $_SESSION['manage_aluno_status_type'] = "status-success";
                    } else {
                        throw new Exception("Aluno não encontrado ou já excluído.");
                    }
                } else {
                    throw new Exception("Erro ao excluir aluno: " . mysqli_stmt_error($stmt_delete));
                }
                mysqli_stmt_close($stmt_delete);
                mysqli_commit($conn);
            } else {
                throw new Exception("ID do aluno inválido para exclusão.");
            }
        }
        // Adicionar outras actions aqui se necessário

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['manage_aluno_status_message'] = "Erro: " . $e->getMessage();
        $_SESSION['manage_aluno_status_type'] = "status-error";
        error_log("Erro em coordenacao_ver_alunos.php (action): " . $e->getMessage());
    }
    mysqli_autocommit($conn, TRUE); 
    header("Location: coordenacao_ver_alunos.php" . (isset($_POST['turma_id_contexto']) ? "?turma_id_focus=".$_POST['turma_id_contexto'] : "" )); // Redirecionar
    exit();
}
// --- FIM LÓGICA DE PROCESSAMENTO DE AÇÕES ---


// Buscar todas as turmas e seus respectivos alunos
$todas_as_turmas_com_alunos = [];
$sql_turmas = "SELECT id, nome_turma, ano_letivo, periodo FROM turmas ORDER BY ano_letivo DESC, nome_turma ASC";
$result_turmas = mysqli_query($conn, $sql_turmas);

if ($result_turmas) {
    while ($turma = mysqli_fetch_assoc($result_turmas)) {
        $turma_id_atual = $turma['id'];
        $turma['alunos'] = [];

        $sql_alunos_na_turma = "SELECT id, nome, email, foto_url FROM alunos WHERE turma_id = ? ORDER BY nome ASC";
        $stmt_alunos = mysqli_prepare($conn, $sql_alunos_na_turma);
        if ($stmt_alunos) {
            mysqli_stmt_bind_param($stmt_alunos, "i", $turma_id_atual);
            mysqli_stmt_execute($stmt_alunos);
            $result_alunos_turma = mysqli_stmt_get_result($stmt_alunos);
            while ($aluno_data = mysqli_fetch_assoc($result_alunos_turma)) { // Renomeado $aluno para $aluno_data
                $turma['alunos'][] = $aluno_data;
            }
            mysqli_stmt_close($stmt_alunos);
        } else {
            error_log("Erro ao buscar alunos da turma " . $turma_id_atual . " (coordenacao_ver_alunos.php): " . mysqli_error($conn));
        }
        $todas_as_turmas_com_alunos[] = $turma; 
    }
} else {
    error_log("Erro ao buscar lista de turmas (coordenacao_ver_alunos.php): " . mysqli_error($conn));
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Alunos por Turma - ACADMIX</title>
    <link rel="stylesheet" href="css/coordenacao.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .turma-accordion-header {
            cursor: pointer;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            /* background-color e color virão do tema para .card */
        }
        .turma-accordion-header h3 { margin: 0; font-size: 1.3rem; }
        .turma-accordion-header .toggle-icon { transition: transform 0.3s ease; }
        .turma-accordion-header.active .toggle-icon { transform: rotate(90deg); }
        .turma-alunos-list {
            display: none; /* Começa fechado */
            padding-left: 1.5rem; /* Indentação para a lista de alunos */
            margin-bottom: 1.5rem;
            border-left: 3px solid var(--primary-color, #007bff); /* Linha visual para o conteúdo expandido */
        }
        .turma-alunos-list.active { display: block; }

        .aluno-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; padding-top: 1rem; }
        .aluno-card { padding: 1rem; border-radius: 8px; display: flex; align-items: center; }
        .aluno-photo { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-right: 1rem; border: 2px solid var(--border-color-soft, #ddd); }
        .aluno-info h4 { margin: 0 0 0.2rem 0; font-size: 1.05rem; }
        .aluno-info p { margin: 0 0 0.4rem 0; font-size: 0.8rem; }
        .aluno-actions .button { margin-top: 0.5rem; margin-right: 0.5rem; }
        .no-data-message { padding: 1rem; text-align: center; border-radius: 4px; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }

        /* CSS do Chat (igual às outras páginas) */
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
        <h1>ACADMIX - Visualizar Alunos (Coordenação)</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Alunos por Turma</h2>

            <?php if(isset($_SESSION['manage_aluno_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['manage_aluno_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['manage_aluno_status_message']); ?>
                </div>
                <?php unset($_SESSION['manage_aluno_status_message']); unset($_SESSION['manage_aluno_status_type']); ?>
            <?php endif; ?>
            
            <a href="coordenacao_add_aluno.php" class="button button-primary" style="margin-bottom: 1.5rem; display: inline-block;">
                <i class="fas fa-user-plus"></i> Adicionar Novo Aluno ao Sistema
            </a>

            <?php if (!empty($todas_as_turmas_com_alunos)): ?>
                <?php foreach ($todas_as_turmas_com_alunos as $turma_info): ?>
                    <section class="dashboard-section card turma-accordion">
                        <div class="turma-accordion-header card-header" data-turma-id="<?php echo $turma_info['id']; ?>">
                            <h3><i class="fas fa-users"></i> Turma: <?php echo htmlspecialchars($turma_info['nome_turma']); ?> (<?php echo htmlspecialchars($turma_info['ano_letivo'] . ' - ' . $turma_info['periodo']); ?>)</h3>
                            <span class="toggle-icon"><i class="fas fa-chevron-right"></i></span>
                        </div>
                        <div class="turma-alunos-list" id="alunos-turma-<?php echo $turma_info['id']; ?>">
                            <?php if (!empty($turma_info['alunos'])): ?>
                                <div class="aluno-grid">
                                    <?php foreach ($turma_info['alunos'] as $aluno): ?>
                                        <div class="aluno-card card-item">
                                            <img src="<?php echo htmlspecialchars(!empty($aluno['foto_url']) ? $aluno['foto_url'] : 'img/alunos/default_avatar.png'); ?>" 
                                                 alt="Foto de <?php echo htmlspecialchars($aluno['nome']); ?>" 
                                                 class="aluno-photo"
                                                 onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                                            <div class="aluno-info">
                                                <h4><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($aluno['email'] ?? 'Email não informado'); ?></p>
                                                <div class="aluno-actions">
                                                    <a href="perfil_aluno_coordenacao.php?id=<?php echo $aluno['id']; ?>" class="button button-secondary button-small" title="Ver Perfil Detalhado">
                                                        <i class="fas fa-eye"></i> Perfil
                                                    </a>
                                                    <form action="coordenacao_ver_alunos.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir o aluno(a) \'<?php echo htmlspecialchars(addslashes($aluno['nome'])); ?>\' do sistema? Esta ação não pode ser desfeita.');">
                                                        <input type="hidden" name="action" value="delete_aluno">
                                                        <input type="hidden" name="aluno_id_delete" value="<?php echo $aluno['id']; ?>">
                                                        <input type="hidden" name="turma_id_contexto" value="<?php echo $turma_info['id']; ?>"> <button type="submit" class="button button-danger button-small" title="Excluir Aluno do Sistema"><i class="fas fa-trash-alt"></i> Excluir</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-data-message info-message">Nenhum aluno matriculado nesta turma.</p>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-data-message info-message">Nenhuma turma cadastrada no sistema.</p>
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

        // Script para expandir/colapsar turmas
        document.querySelectorAll('.turma-accordion-header').forEach(header => {
            header.addEventListener('click', function() {
                this.classList.toggle('active');
                const content = this.nextElementSibling;
                if (content.style.display === "block") {
                    content.style.display = "none";
                } else {
                    content.style.display = "block";
                }
            });
        });

        // Para abrir a turma focada via GET parâmetro (após uma ação, por exemplo)
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const turmaIdFocus = urlParams.get('turma_id_focus');
            if (turmaIdFocus) {
                const headerToFocus = document.querySelector(`.turma-accordion-header[data-turma-id="${turmaIdFocus}"]`);
                if (headerToFocus) {
                    headerToFocus.click(); // Simula o clique para expandir
                    headerToFocus.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

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
        // Demais seletores de elementos do chat
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