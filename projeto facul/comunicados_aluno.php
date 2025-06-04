<?php
session_start(); // GARANTIR que está no topo absoluto
// Verifica se o usuário é um aluno logado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_aluno = $_SESSION['usuario_nome'];
$aluno_id = $_SESSION['usuario_id']; // Essencial para o chat
$turma_id_aluno = isset($_SESSION['turma_id']) ? intval($_SESSION['turma_id']) : 0; // Essencial para o chat
$currentPageIdentifier = 'comunicados_aluno'; // Para a sidebar

// PEGAR TEMA DA SESSÃO
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Query para buscar comunicados relevantes para o aluno
$sql_comunicados = "
    SELECT 
        c.titulo, 
        c.conteudo, 
        c.data_publicacao, 
        c.publico_alvo,
        c.turma_id AS comunicado_turma_id,
        p.nome as nome_professor, 
        coord.nome as nome_coordenador,
        t.nome_turma 
    FROM comunicados c
    LEFT JOIN professores p ON c.professor_id = p.id
    LEFT JOIN coordenadores coord ON c.coordenador_id = coord.id
    LEFT JOIN turmas t ON c.turma_id = t.id 
    WHERE 
        (c.coordenador_id IS NOT NULL AND c.publico_alvo = 'TODOS_ALUNOS') 
        OR
        (c.coordenador_id IS NOT NULL AND c.publico_alvo = 'TURMA_ESPECIFICA' AND c.turma_id = ?)
        OR
        (c.professor_id IS NOT NULL AND c.publico_alvo = 'TURMA_ESPECIFICA' AND c.turma_id = ?)
    ORDER BY c.data_publicacao DESC";

$stmt = mysqli_prepare($conn, $sql_comunicados);
$result_comunicados = null; 
if ($stmt) { 
    mysqli_stmt_bind_param($stmt, "ii", $turma_id_aluno, $turma_id_aluno); 
    mysqli_stmt_execute($stmt);
    $result_comunicados = mysqli_stmt_get_result($stmt);
} else {
    error_log("Erro ao preparar statement para buscar comunicados do aluno (comunicados_aluno.php): " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Comunicados - ACADMIX</title>
    <link rel="stylesheet" href="css/aluno.css"> 
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos da página comunicados_aluno.php */
        .main-content h2.page-title { 
            text-align: center; font-size: 1.8rem; margin-bottom: 2rem; 
            padding-bottom: 0.5rem; display: inline-block;
        }
        .comunicado-item {
            border-left-width: 5px; border-left-style: solid; 
            padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 0 5px 5px 0; 
        }
        .comunicado-item h3 {
            font-size: 1.3rem; margin-top: 0; margin-bottom: 0.5rem;
        }
        .comunicado-meta {
            font-size: 0.85rem; margin-bottom: 1rem; 
        }
        .comunicado-meta .author, .comunicado-meta .target-turma {
            font-weight: bold;
        }
        .comunicado-conteudo {
            font-size: 1rem; line-height: 1.6; white-space: pre-wrap; 
        }
        .no-comunicados {
            text-align: center; padding: 20px; font-size: 1.1rem; 
        }

        /* --- INÍCIO CSS NOVO CHAT ACADÊMICO --- */
        /* (Mova para um arquivo CSS externo como chat_academico.css para todas as páginas) */
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
        <h1>ACADMIX - Comunicados</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php'; 
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <div style="text-align: center;">
                <h2 class="page-title">Quadro de Avisos</h2>
            </div>

            <?php if($result_comunicados && mysqli_num_rows($result_comunicados) > 0): ?>
                <?php while($comunicado = mysqli_fetch_assoc($result_comunicados)): ?>
                    <?php
                    $remetente_display = "";
                    $classe_extra_css = ""; 
                    $publico_display = "";

                    if (!empty($comunicado['coordenador_id'])) {
                        $remetente_display = "Coordenação (" . htmlspecialchars($comunicado['nome_coordenador'] ?? 'N/A') . ")";
                        $classe_extra_css = "coord-comunicado"; 
                    } elseif (!empty($comunicado['professor_id'])) {
                        $remetente_display = "Prof. " . htmlspecialchars($comunicado['nome_professor'] ?? 'N/A');
                    } else {
                        $remetente_display = "Sistema";
                    }

                    if ($comunicado['publico_alvo'] === 'TODOS_ALUNOS') {
                        $publico_display = "Alunos (Geral)";
                    } elseif ($comunicado['publico_alvo'] === 'TURMA_ESPECIFICA' && !empty($comunicado['nome_turma'])) {
                        $publico_display = htmlspecialchars($comunicado['nome_turma']);
                    } elseif ($comunicado['publico_alvo'] === 'PROFESSOR_GERAL_ALUNOS') {
                        $publico_display = "Alunos do Professor (Geral)";
                    }
                    ?>
                    <article class="comunicado-item card <?php echo $classe_extra_css; ?>"> <h3><?php echo htmlspecialchars($comunicado['titulo']); ?></h3>
                        <p class="comunicado-meta">
                            Publicado por: <span class="author"><?php echo $remetente_display; ?></span> | 
                            Em: <?php echo date("d/m/Y H:i", strtotime($comunicado['data_publicacao'])); ?>
                            <?php if(!empty($publico_display)): ?>
                                | Para: <span class="target-turma"><?php echo $publico_display; ?></span>
                            <?php endif; ?>
                        </p>
                        <div class="comunicado-conteudo">
                            <?php echo nl2br(htmlspecialchars($comunicado['conteudo'])); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-comunicados">Nenhum comunicado disponível para você no momento.</p>
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
        const pageContainerGlobal = document.getElementById('pageContainer'); // Usando ID padronizado

        if (menuToggleButtonGlobal && sidebarElementGlobal && pageContainerGlobal) {
            menuToggleButtonGlobal.addEventListener('click', function () {
                sidebarElementGlobal.classList.toggle('hidden'); 
                pageContainerGlobal.classList.toggle('full-width'); 
            });
        }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const currentUserId = <?php echo json_encode($aluno_id); ?>;
        const currentUserTurmaId = <?php echo json_encode($turma_id_aluno); ?>; 
        const defaultUserPhoto = 'img/alunos/default_avatar.png'; 

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

        let allTurmaUsers = []; 
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
                    fetchAndDisplayTurmaUsers();
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
            conversationUserPhoto.src = contact.foto_url || defaultUserPhoto;
            
            if (shouldFetchMessages) {
                messagesContainer.innerHTML = ''; 
                fetchAndDisplayMessages(contact.id);
            }
            messageInput.focus();
        }
        
        async function fetchAndDisplayTurmaUsers() {
            if (currentUserTurmaId === 0) {
                userListUl.innerHTML = '<li>Turma não definida.</li>';
                return;
            }
            userListUl.innerHTML = '<li><i class="fas fa-spinner fa-spin"></i> Carregando usuários...</li>';
            
            try {
                const response = await fetch(`chat_api.php?action=get_turma_users`);
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error ${response.status}: ${errorText}`);
                }
                const users = await response.json();

                if (users.error) {
                    console.error('API Erro (get_turma_users):', users.error);
                    userListUl.innerHTML = `<li>Erro: ${users.error}</li>`;
                    return;
                }
                allTurmaUsers = users;
                renderUserList(allTurmaUsers);

            } catch (error) {
                console.error('Falha ao buscar usuários:', error);
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
                
                const img = document.createElement('img');
                img.src = user.foto_url || defaultUserPhoto;
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
                }
                
                li.addEventListener('click', () => {
                    showConversationScreen(user, true); 
                });
                userListUl.appendChild(li);
            });
        }

        async function fetchAndDisplayMessages(contactId) {
            messagesContainer.innerHTML = '<p style="text-align:center;font-size:0.8em;"><i class="fas fa-spinner fa-spin"></i> Carregando...</p>';
            try {
                const response = await fetch(`chat_api.php?action=get_messages&contact_id=${contactId}`);
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
                        appendMessageToChat(msg.message_text, parseInt(msg.sender_id) === currentUserId ? 'sent-acad' : 'received-acad');
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
            const filteredUsers = allTurmaUsers.filter(user => 
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
<?php 
if(isset($stmt)) mysqli_stmt_close($stmt); 
if(isset($conn) && $conn) mysqli_close($conn); 
?>