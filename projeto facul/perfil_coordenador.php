<?php
session_start(); // Deve ser a primeira linha
if (!isset($_SESSION['usuario_id'])) { 
    header("Location: index.html");
    exit();
}
include 'db.php';

// Dados do PERFIL SENDO VISUALIZADO
$perfil_id_para_exibir = isset($_GET['id']) ? intval($_GET['id']) : (isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0); // Default para próprio perfil se ID não passado

// Dados do USUÁRIO LOGADO (VISUALIZADOR)
$viewer_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$viewer_id = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
$viewer_nome = $_SESSION['usuario_nome'] ?? 'Visitante';
// Turma do visualizador, relevante se o visualizador for um aluno
$viewer_turma_id = ($viewer_role === 'aluno' && isset($_SESSION['turma_id'])) ? intval($_SESSION['turma_id']) : 0; 

$is_own_profile = ($viewer_role === 'coordenacao' && $viewer_id == $perfil_id_para_exibir);
$currentPageIdentifier = $is_own_profile ? 'meu_perfil_coord' : null; 

// Tema global é SEMPRE do USUÁRIO LOGADO (VISUALIZADOR)
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

$coordenador_info = null; // Informações do perfil que está sendo visualizado
if ($perfil_id_para_exibir > 0) {
    $sql_coordenador = "SELECT id, nome, email, foto_url, data_criacao, tema_perfil 
                        FROM coordenadores 
                        WHERE id = ?";
    $stmt_coordenador_prepare = mysqli_prepare($conn, $sql_coordenador);
    if ($stmt_coordenador_prepare) {
        mysqli_stmt_bind_param($stmt_coordenador_prepare, "i", $perfil_id_para_exibir);
        mysqli_stmt_execute($stmt_coordenador_prepare);
        $result_coordenador = mysqli_stmt_get_result($stmt_coordenador_prepare);
        $coordenador_info = mysqli_fetch_assoc($result_coordenador);
        mysqli_stmt_close($stmt_coordenador_prepare);
    } else {
        error_log("Erro ao preparar statement para perfil do coordenador: " . mysqli_error($conn));
    }
}

$ano_inicio = $coordenador_info ? date("Y", strtotime($coordenador_info['data_criacao'])) : 'N/A';
// Tema que o DONO deste perfil escolheu (usado para pré-selecionar o <select> de temas SE for o próprio perfil)
$tema_escolhido_pelo_dono_do_perfil = $coordenador_info && !empty($coordenador_info['tema_perfil']) ? $coordenador_info['tema_perfil'] : 'padrao';

$temas_disponiveis = [
    'padrao' => 'Padrão do Sistema', '8bit' => '8-Bit Retrô',
    'natureza' => 'Natureza Calma', 'academico' => 'Acadêmico Clássico',
    'darkmode' => 'Modo Escuro Simples'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?php echo $coordenador_info ? htmlspecialchars($coordenador_info['nome']) : 'Coordenação'; ?> - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/coordenacao.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos ESTRUTURAIS. Cores/fontes virão dos temas via .card, .button, etc. */
        .profile-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; padding:1rem; }
        .profile-header { text-align: center; margin-bottom: 1.5rem; width:100%;}
        .profile-photo-wrapper { position: relative; margin-bottom: 1rem; display:inline-block; }
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; }
        .profile-header h2 { font-size: 2rem; margin-bottom: 0.25rem; }
        .profile-header .member-since { font-size: 1rem; margin-bottom: 1rem; }
        .upload-form-container { margin-top: 10px; text-align:center; }
        .status-message-profile { padding: 0.8rem; margin-top:1rem; margin-bottom: 1rem; border-radius: 4px; text-align:center; font-size:0.9rem; }
        .profile-details { width: 100%; max-width: 700px; }
        .profile-section { padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .profile-section h3 { font-size: 1.3rem; margin-top: 0; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .profile-section p { font-size: 1rem; line-height: 1.6; }
        .edit-section details { margin-bottom: 10px; }
        .edit-section summary { cursor: pointer; font-weight: bold; padding: 0.5rem; border-radius:4px; display: inline-block;}
        .edit-section form { margin-top: 1rem; padding:1rem; border:1px solid var(--border-color-soft, #eee); border-radius:4px;}
        .edit-section label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .edit-section textarea, .edit-section select { width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box; margin-bottom:1rem; }
        .edit-section button[type="submit"] { padding: 0.6rem 1.2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        .no-data { font-style: italic; }
        .error-message { text-align: center; color: var(--danger-color, red); font-size: 1.2rem; padding: 2rem; }

        /* --- CSS NOVO CHAT ACADÊMICO --- */
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
        <h1>ACADMIX - Perfil da Coordenação</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_include_path = __DIR__ . '/includes/sidebar_coordenacao.php';
            if (!empty($sidebar_include_path) && file_exists($sidebar_include_path)) { 
                include $sidebar_include_path; 
            } else { 
                echo "<p style='padding:1rem; color:white;'>Menu não disponível.</p>"; 
            }
            ?>
        </nav>

        <main class="main-content">
            <?php if ($coordenador_info): ?>
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-photo-wrapper">
                            <img src="<?php echo htmlspecialchars(!empty($coordenador_info['foto_url']) ? $coordenador_info['foto_url'] : 'img/coordenadores/default_avatar.png'); ?>"
                                 alt="Foto de <?php echo htmlspecialchars($coordenador_info['nome']); ?>"
                                 class="profile-photo"
                                 onerror="this.onerror=null; this.src='img/coordenadores/default_avatar.png';">
                        </div>

                        <?php if ($is_own_profile): ?>
                        <div class="upload-form-container">
                            <form action="upload_foto_coordenador.php" method="post" enctype="multipart/form-data">
                                <input type="file" name="foto_perfil" accept="image/jpeg, image/png, image/gif" required class="input-field">
                                <button type="submit" class="button button-small"><i class="fas fa-upload"></i> Alterar Foto</button>
                            </form>
                        </div>
                        <?php endif; ?>

                        <?php if(isset($_SESSION['upload_status_message'])): ?>
                            <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['upload_status_type']); ?>">
                                <?php echo htmlspecialchars($_SESSION['upload_status_message']); ?>
                            </div>
                            <?php unset($_SESSION['upload_status_message']); unset($_SESSION['upload_status_type']); ?>
                        <?php endif; ?>

                        <h2><?php echo htmlspecialchars($coordenador_info['nome']); ?></h2>
                        <p class="member-since">Membro da equipe desde <?php echo $ano_inicio; ?></p>
                    </div>

                    <div class="profile-details">
                        <?php if ($is_own_profile): ?>
                        <section class="profile-section edit-section card">
                            <details open> <summary><i class="fas fa-palette"></i> Editar Tema Visual do Sistema</summary>
                                <form action="salvar_tema_coordenador.php" method="POST" style="margin-top:1rem;">
                                    <label for="tema_perfil_select_coord">Escolha um Tema para o Sistema:</label>
                                    <select id="tema_perfil_select_coord" name="tema_perfil_coordenador" class="input-field">
                                        <?php 
                                        foreach($temas_disponiveis as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>" <?php if($tema_global_usuario == $value) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="button button-primary"><i class="fas fa-save"></i> Aplicar Tema</button>
                                </form>
                                <?php if(isset($_SESSION['tema_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['tema_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['tema_status_message']); ?></div>
                                <?php unset($_SESSION['tema_status_message']); unset($_SESSION['tema_status_type']); ?>
                                <?php endif; ?>
                            </details>
                        </section>
                        <?php endif; ?>

                        <section class="profile-section card">
                            <h3><i class="fas fa-info-circle"></i> Informações de Contato</h3>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($coordenador_info['email']); ?></p>
                        </section>
                        
                    </div>
                </div>
            <?php else: ?>
                <p class="error-message">Perfil da coordenação não encontrado.</p>
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
        const currentUserId = <?php echo json_encode($viewer_id); ?>; 
        const currentUserSessionRole = <?php echo json_encode($viewer_role); ?>; 
        const currentUserTurmaIdForStudent = <?php echo json_encode($viewer_turma_id); ?>;

        let currentUserChatRole = '';
        if (currentUserSessionRole === 'aluno') {
            currentUserChatRole = 'aluno'; 
        } else if (currentUserSessionRole === 'docente') {
            currentUserChatRole = 'professor'; 
        } else if (currentUserSessionRole === 'coordenacao') {
            currentUserChatRole = 'coordenador';
        } else {
            // O chat não será inicializado com uma lista de contatos se o papel não for reconhecido
            console.warn("Chat: Papel do visualizador não explicitamente suportado para lista de contatos:", currentUserSessionRole);
        }
        
        const defaultUserPhoto = 'img/alunos/default_avatar.png';
        const defaultProfessorPhoto = 'img/professores/default_avatar.png'; 
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
            if (!chatWidget) return; // Se o widget não existir, não faz nada
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
                if (currentUserTurmaIdForStudent === 0) {
                    if(userListUl) userListUl.innerHTML = '<li>Turma não definida para aluno.</li>';
                    return;
                }
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
        } else {
            console.warn("Elemento de cabeçalho do Chat não encontrado.");
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