<?php
session_start(); // No topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';

$aluno_id = $_SESSION['usuario_id'];
$nome_aluno_sessao = $_SESSION['usuario_nome']; 

$currentPageIdentifier = 'meu_perfil_aluno'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

$aluno_info = null;
$sql_aluno = "SELECT id, nome, email, foto_url, data_criacao, biografia, tema_perfil, interesses, turma_id 
              FROM alunos WHERE id = ?";
$stmt_aluno_prepare = mysqli_prepare($conn, $sql_aluno); // Nome da variável stmt corrigido para não conflitar
if ($stmt_aluno_prepare) {
    mysqli_stmt_bind_param($stmt_aluno_prepare, "i", $aluno_id);
    mysqli_stmt_execute($stmt_aluno_prepare);
    $result_aluno = mysqli_stmt_get_result($stmt_aluno_prepare);
    $aluno_info = mysqli_fetch_assoc($result_aluno);
    mysqli_stmt_close($stmt_aluno_prepare);
}

// Definir $turma_id_aluno para o chat, priorizando a sessão, depois o perfil do aluno
$turma_id_aluno = isset($_SESSION['turma_id']) ? intval($_SESSION['turma_id']) : (isset($aluno_info['turma_id']) ? intval($aluno_info['turma_id']) : 0);


$nome_turma_aluno_display = "Não informada"; // Variável para display no perfil
if ($aluno_info && !empty($aluno_info['turma_id'])) {
    $sql_turma_nome = "SELECT nome_turma FROM turmas WHERE id = ?";
    $stmt_turma = mysqli_prepare($conn, $sql_turma_nome);
    if ($stmt_turma) {
        mysqli_stmt_bind_param($stmt_turma, "i", $aluno_info['turma_id']);
        mysqli_stmt_execute($stmt_turma);
        $result_turma = mysqli_stmt_get_result($stmt_turma);
        if($turma_data = mysqli_fetch_assoc($result_turma)) {
            $nome_turma_aluno_display = $turma_data['nome_turma'];
        }
        mysqli_stmt_close($stmt_turma);
    }
}

$ano_inicio_escola = $aluno_info ? date("Y", strtotime($aluno_info['data_criacao'])) : 'N/A';
$tema_escolhido_pelo_aluno = $aluno_info && !empty($aluno_info['tema_perfil']) ? $aluno_info['tema_perfil'] : 'padrao';

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
    <title>Meu Perfil - <?php echo htmlspecialchars($nome_aluno_sessao); ?> - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos ESTRUTURAIS para a página de perfil. Cores/fontes vêm dos temas. */
        .profile-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; padding:1rem; }
        .profile-header { text-align: center; margin-bottom: 1.5rem; width:100%;}
        .profile-photo-wrapper { position: relative; margin-bottom: 1rem; display:inline-block; }
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; } 
        .profile-header h2 { font-size: 2rem; margin-bottom: 0.25rem; }
        .profile-header .member-since, .profile-header .turma-info { font-size: 1rem; margin-bottom: 0.5rem; }
        .profile-details { width: 100%; max-width: 800px; }
        .profile-section { padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .profile-section h3 { font-size: 1.3rem; margin-top: 0; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .profile-section p, .profile-section ul { font-size: 1rem; line-height: 1.6; }
        .profile-section ul { list-style: none; padding-left: 0; }
        .profile-section li { padding: 0.6rem 1rem; margin-bottom: 0.5rem; border-radius: 4px; border-left-style: solid; border-left-width: 3px; }
        .edit-section details { margin-bottom: 10px; }
        .edit-section summary { cursor: pointer; font-weight: bold; padding: 0.6rem 0.8rem; border-radius:4px; display: inline-block; }
        .edit-section form { margin-top: 1rem; padding:1rem; border:1px solid #eee; border-radius:4px;} 
        .edit-section label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .edit-section textarea, .edit-section select, .edit-section input[type="text"] { width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box; margin-bottom:1rem; }
        .edit-section textarea { min-height: 100px; }
        .edit-section button[type="submit"] { padding: 0.6rem 1.2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        .status-message-profile { padding: 0.8rem; margin-top:1rem; margin-bottom: 1rem; border-radius: 4px; text-align:center; font-size:0.9rem; }
        .upload-form-container { margin-top: 10px; text-align:center; }
        .upload-form-container input[type="file"] { display: inline-block; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
        .upload-form-container button[type="submit"] { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; font-size: 0.9rem; }
        .no-data { font-style: italic; }
        .error-message { text-align: center; color: red; font-size: 1.2rem; padding: 2rem; }
        .quick-actions-profile { margin-top: 1.5rem; text-align: center; }
        .quick-actions-profile a.button { margin: 0 10px; text-decoration: none; padding: 0.7rem 1.2rem; border-radius: 4px; display:inline-block; }

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
        <h1>ACADMIX - Meu Perfil</h1>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button></form>
    </header>
    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php 
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php';
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Sidebar não encontrada.</p>";
            }
            ?>
        </nav>
        <main class="main-content">
            <?php if ($aluno_info): ?>
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-photo-wrapper">
                            <img src="<?php echo htmlspecialchars(!empty($aluno_info['foto_url']) ? $aluno_info['foto_url'] : 'img/alunos/default_avatar.png'); ?>" 
                                 alt="Foto de <?php echo htmlspecialchars($aluno_info['nome']); ?>" class="profile-photo"
                                 onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                        </div>
                        <div class="upload-form-container">
                            <form action="upload_foto_aluno.php" method="post" enctype="multipart/form-data">
                                <input type="file" name="foto_perfil_aluno" accept="image/jpeg, image/png, image/gif" required>
                                <button type="submit"><i class="fas fa-upload"></i> Alterar Foto</button>
                            </form>
                        </div>
                        <?php if(isset($_SESSION['upload_aluno_status_message'])): ?>
                        <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['upload_aluno_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['upload_aluno_status_message']); ?></div>
                        <?php unset($_SESSION['upload_aluno_status_message']); unset($_SESSION['upload_aluno_status_type']); ?>
                        <?php endif; ?>
                        <h2><?php echo htmlspecialchars($aluno_info['nome']); ?></h2>
                        <p class="turma-info"><i class="fas fa-users"></i> Turma: <?php echo htmlspecialchars($nome_turma_aluno_display); ?></p>
                        <p class="member-since"><i class="fas fa-calendar-check"></i> Aluno(a) desde: <?php echo $ano_inicio_escola; ?></p>
                    </div>

                    <div class="profile-details">
                        <section class="profile-section edit-section card"> <details>
                                <summary><i class="fas fa-edit"></i> Personalizar Perfil (Bio, Interesses e Tema)</summary>
                                <form action="salvar_perfil_aluno.php" method="POST" style="margin-bottom:20px;">
                                    <input type="hidden" name="action" value="save_bio_interests">
                                    <label for="biografia">Minha Biografia:</label>
                                    <textarea id="biografia" name="biografia" rows="5"><?php echo htmlspecialchars($aluno_info['biografia'] ?? ''); ?></textarea>
                                    
                                    <label for="interesses" style="margin-top:1rem;">Meus Interesses (separados por vírgula):</label>
                                    <input type="text" id="interesses" name="interesses" value="<?php echo htmlspecialchars($aluno_info['interesses'] ?? ''); ?>" placeholder="Ex: Leitura, Games, Esportes">
                                    
                                    <button type="submit" class="button"><i class="fas fa-save"></i> Salvar Bio e Interesses</button> </form>
                                <?php if(isset($_SESSION['bio_interesses_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['bio_interesses_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['bio_interesses_status_message']); ?></div>
                                <?php unset($_SESSION['bio_interesses_status_message']); unset($_SESSION['bio_interesses_status_type']); ?>
                                <?php endif; ?><hr style="margin: 20px 0;">
                                
                                <form action="salvar_perfil_aluno.php" method="POST">
                                    <input type="hidden" name="action" value="save_theme">
                                    <label for="tema_perfil_select">Escolha um Tema para seu Perfil:</label>
                                    <select id="tema_perfil_select" name="tema_perfil">
                                        <?php foreach($temas_disponiveis as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>" <?php if($tema_global_usuario == $value) echo 'selected'; ?>> 
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="button"><i class="fas fa-palette"></i> Aplicar Tema</button> </form>
                                <?php if(isset($_SESSION['tema_aluno_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['tema_aluno_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['tema_aluno_status_message']); ?></div>
                                <?php unset($_SESSION['tema_aluno_status_message']); unset($_SESSION['tema_aluno_status_type']); ?>
                                <?php endif; ?>
                            </details>
                        </section>

                        <section class="profile-section bio-section card"><h3><i class="fas fa-id-card-alt"></i> Sobre Mim</h3>
                            <?php if (!empty($aluno_info['biografia'])): ?><p><?php echo nl2br(htmlspecialchars($aluno_info['biografia'])); ?></p>
                            <?php else: ?><p class="no-data">Nenhuma biografia informada. Edite seu perfil para adicionar.</p><?php endif; ?>
                        </section>

                        <section class="profile-section interests-section card"><h3><i class="fas fa-grin-stars"></i> Meus Interesses</h3>
                            <?php if (!empty($aluno_info['interesses'])): ?><p><?php echo htmlspecialchars($aluno_info['interesses']); ?></p>
                            <?php else: ?><p class="no-data">Nenhum interesse informado. Edite seu perfil para adicionar.</p><?php endif; ?>
                        </section>

                        <section class="profile-section card"><h3><i class="fas fa-info-circle"></i> Informações de Contato</h3><p><strong>Email:</strong> <?php echo htmlspecialchars($aluno_info['email']); ?></p></section>
                        
                        <section class="profile-section quick-actions-profile card">
                            <h3><i class="fas fa-bolt"></i> Acesso Rápido Acadêmico</h3>
                            <a href="boletim.php" class="button">Meu Boletim</a>
                            <a href="calendario.php" class="button">Meu Calendário</a>
                            <a href="materiais.php" class="button">Materiais Didáticos</a>
                        </section>
                    </div>
                </div>
            <?php else: ?>
                <p class="error-message">Perfil do aluno não encontrado.</p>
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
        // Script do menu lateral (Toggle) - PADRONIZADO
        const menuToggleButton = document.getElementById('menu-toggle'); // ID padronizado
        const sidebarNavigation = document.getElementById('sidebar'); // ID padronizado
        const mainContainer = document.getElementById('pageContainer'); // ID padronizado

        if (menuToggleButton && sidebarNavigation && mainContainer) {
            menuToggleButton.addEventListener('click', function () {
                sidebarNavigation.classList.toggle('hidden'); 
                mainContainer.classList.toggle('full-width'); 
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
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>