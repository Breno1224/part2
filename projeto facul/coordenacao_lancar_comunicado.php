<?php
session_start(); // GARANTIR QUE ESTÁ NO TOPO ABSOLUTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
include 'db.php';
// Ajustando variáveis para o contexto da coordenação
$nome_coordenador = $_SESSION['usuario_nome'];
$coordenador_id = $_SESSION['usuario_id']; // Essencial para o chat

$currentPageIdentifier = 'comunicados_coord'; // Para a sidebar

// PEGAR TEMA DA SESSÃO
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Buscar turmas para o select
$turmas_result = mysqli_query($conn, "SELECT id, nome_turma FROM turmas ORDER BY nome_turma");

// Buscar comunicados já enviados por esta coordenação
$comunicados_enviados_sql = "
    SELECT c.id, c.titulo, c.data_publicacao, c.publico_alvo, t.nome_turma
    FROM comunicados c
    LEFT JOIN turmas t ON c.turma_id = t.id
    WHERE c.coordenador_id = ? -- Filtrar por coordenador_id
    ORDER BY c.data_publicacao DESC LIMIT 10";
$stmt_com_prepare = mysqli_prepare($conn, $comunicados_enviados_sql); // Nome da variável ajustado
$comunicados_enviados_result_data = null; // Nome da variável ajustado
if ($stmt_com_prepare) {
    mysqli_stmt_bind_param($stmt_com_prepare, "i", $coordenador_id); // Usar $coordenador_id
    mysqli_stmt_execute($stmt_com_prepare);
    $comunicados_enviados_result_data = mysqli_stmt_get_result($stmt_com_prepare);
} else {
    error_log("Erro ao buscar comunicados enviados pela coordenação: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Enviar Comunicado - Coordenação ACADMIX</title>
    <link rel="stylesheet" href="css/coordenacao.css"> <link rel="stylesheet" href="css/temas_globais.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos da página coordenacao_lancar_comunicado.php */
        .form-section, .list-section { 
            margin-bottom: 2rem; padding: 1.5rem; border-radius: 8px; /* Ajustado border-radius */
        }
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.5rem; font-weight: bold; }
        .form-section input[type="text"],
        .form-section textarea,
        .form-section select {
            width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box;
        }
        .form-section textarea { min-height: 150px; }
        .form-section button[type="submit"] {
            padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; 
            font-size: 1rem; margin-top: 1.5rem;
        }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        /* .status-success e .status-error devem vir de temas_globais.css ou um CSS base */
        .list-section table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .list-section th, .list-section td { 
            padding: 0.75rem; text-align: left; 
        }
        #turma_select_div { display: none; margin-top: 1rem; } 

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
        /* --- FIM CSS NOVO CHAT ACADÊMICO --- */
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Enviar Comunicado (Coord. <?php echo htmlspecialchars($nome_coordenador); ?>)</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Novo Comunicado da Coordenação</h2>

            <?php if(isset($_SESSION['comunicado_coord_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['comunicado_coord_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['comunicado_coord_status_message']); ?>
                </div>
                <?php unset($_SESSION['comunicado_coord_status_message']); unset($_SESSION['comunicado_coord_status_type']); ?>
            <?php endif; ?>

            <section class="form-section dashboard-section card">
                <form action="salvar_comunicado_coordenacao.php" method="POST">
                    <label for="titulo">Título do Comunicado:</label>
                    <input type="text" id="titulo" name="titulo" required class="input-field">

                    <label for="conteudo">Conteúdo:</label>
                    <textarea id="conteudo" name="conteudo" required class="input-field"></textarea>

                    <label for="publico_alvo_select">Enviar Para:</label>
                    <select id="publico_alvo_select" name="publico_alvo_select" required class="input-field" onchange="toggleTurmaSelect()">
                        <option value="">Selecione o Público</option>
                        <option value="TODOS_ALUNOS">Alunos (Geral - Todas as Turmas)</option>
                        <option value="TURMA_ESPECIFICA_ALUNOS">Alunos (Turma Específica)</option>
                        <option value="TODOS_PROFESSORES">Professores (Todos)</option>
                    </select>

                    <div id="turma_select_div">
                        <label for="turma_id">Selecione a Turma (para alunos):</label>
                        <select id="turma_id" name="turma_id" class="input-field">
                            <option value="">Selecione a Turma</option>
                            <?php 
                            if($turmas_result) { // Verifica se a query foi bem sucedida
                                mysqli_data_seek($turmas_result, 0);
                                while($turma = mysqli_fetch_assoc($turmas_result)): ?>
                                    <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome_turma']); ?></option>
                                <?php endwhile; 
                            }?>
                        </select>
                    </div>

                    <button type="submit" class="button button-primary"><i class="fas fa-paper-plane"></i> Publicar Comunicado</button>
                </form>
            </section>
             <section class="list-section dashboard-section card">
                <h3><i class="fas fa-history"></i> Últimos Comunicados Enviados</h3>
                <?php if($comunicados_enviados_result_data && mysqli_num_rows($comunicados_enviados_result_data) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Público</th>
                            <th>Turma Específica</th>
                            <th>Data Publicação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($com = mysqli_fetch_assoc($comunicados_enviados_result_data)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($com['titulo']); ?></td>
                            <td><?php echo htmlspecialchars(str_replace('_', ' ', $com['publico_alvo'])); ?></td>
                            <td><?php echo htmlspecialchars($com['nome_turma'] ?? 'N/A (Geral)'); ?></td>
                            <td><?php echo date("d/m/Y H:i", strtotime($com['data_publicacao'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-data-message info-message">Nenhum comunicado enviado por você ainda.</p>
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

        // Script específico da página para mostrar/ocultar select de turma
        function toggleTurmaSelect() {
            const publicoSelect = document.getElementById('publico_alvo_select');
            const turmaDiv = document.getElementById('turma_select_div');
            const turmaSelect = document.getElementById('turma_id');
            if (publicoSelect.value === 'TURMA_ESPECIFICA_ALUNOS') { // Ajustado valor
                turmaDiv.style.display = 'block';
                turmaSelect.required = true;
            } else {
                turmaDiv.style.display = 'none';
                turmaSelect.required = false;
                turmaSelect.value = ''; 
            }
        }
        // Chamar ao carregar a página para estado inicial correto, se o select já tiver um valor
        document.addEventListener('DOMContentLoaded', toggleTurmaSelect);
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
        const defaultCoordenadorPhoto = 'img/coordenadores/default_avatar.png'; // Crie ou ajuste o caminho

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
<?php 
if(isset($stmt_com_prepare)) mysqli_stmt_close($stmt_com_prepare); // Nome da variável de statement corrigido
if(isset($conn) && $conn) mysqli_close($conn); 
?>