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