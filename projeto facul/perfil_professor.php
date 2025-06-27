<?php
session_start(); // Deve ser a primeira linha
if (!isset($_SESSION['usuario_id'])) { // Acesso apenas para usuários logados
    header("Location: index.html");
    exit();
}
include 'db.php';

// Dados do PERFIL SENDO VISUALIZADO
$professor_id_para_exibir = isset($_GET['id']) ? intval($_GET['id']) : 0;
$professor_info = null;
$disciplinas_lecionadas = [];
$turmas_lecionadas = [];

// Dados do USUÁRIO LOGADO (VISUALIZADOR) - ESSENCIAIS PARA O CHAT E SIDEBAR
$viewer_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$viewer_id = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 0;
$viewer_turma_id = ($viewer_role === 'aluno' && isset($_SESSION['turma_id'])) ? intval($_SESSION['turma_id']) : 0; // Para o chat do aluno

$is_own_profile = false; 
$currentPageIdentifier = null; // Será definido se for o próprio perfil

// Tema global do USUÁRIO LOGADO (VISUALIZADOR)
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

if ($viewer_role === 'docente' && $viewer_id == $professor_id_para_exibir) {
    $is_own_profile = true;
    $currentPageIdentifier = 'meu_perfil'; // Identificador para sidebar do professor vendo seu próprio perfil
}


if ($professor_id_para_exibir > 0) {
    $sql_professor = "SELECT id, nome, email, foto_url, data_criacao, biografia, tema_perfil 
                      FROM professores 
                      WHERE id = ?";
    $stmt_professor_prepare = mysqli_prepare($conn, $sql_professor); // Nome da variável ajustado
    if ($stmt_professor_prepare) {
        mysqli_stmt_bind_param($stmt_professor_prepare, "i", $professor_id_para_exibir);
        mysqli_stmt_execute($stmt_professor_prepare);
        $result_professor = mysqli_stmt_get_result($stmt_professor_prepare);
        $professor_info = mysqli_fetch_assoc($result_professor);
        mysqli_stmt_close($stmt_professor_prepare);
    } else {
        error_log("Erro ao preparar statement para perfil do professor: " . mysqli_error($conn));
    }

    if ($professor_info) {
        $sql_disciplinas = "SELECT DISTINCT d.nome_disciplina FROM disciplinas d JOIN professores_turmas_disciplinas ptd ON d.id = ptd.disciplina_id WHERE ptd.professor_id = ? ORDER BY d.nome_disciplina";
        $stmt_disciplinas_prepare = mysqli_prepare($conn, $sql_disciplinas); // Nome da variável ajustado
        if ($stmt_disciplinas_prepare) {
            mysqli_stmt_bind_param($stmt_disciplinas_prepare, "i", $professor_id_para_exibir);
            mysqli_stmt_execute($stmt_disciplinas_prepare);
            $result_disciplinas = mysqli_stmt_get_result($stmt_disciplinas_prepare);
            while ($row = mysqli_fetch_assoc($result_disciplinas)) { $disciplinas_lecionadas[] = $row['nome_disciplina']; }
            mysqli_stmt_close($stmt_disciplinas_prepare);
        } else {
            error_log("Erro ao buscar disciplinas do professor (perfil_professor.php): " . mysqli_error($conn));
        }

        $sql_turmas = "SELECT DISTINCT t.nome_turma FROM turmas t JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id WHERE ptd.professor_id = ? ORDER BY t.nome_turma";
        $stmt_turmas_prepare = mysqli_prepare($conn, $sql_turmas); // Nome da variável ajustado
        if ($stmt_turmas_prepare) {
            mysqli_stmt_bind_param($stmt_turmas_prepare, "i", $professor_id_para_exibir);
            mysqli_stmt_execute($stmt_turmas_prepare);
            $result_turmas = mysqli_stmt_get_result($stmt_turmas_prepare);
            while ($row = mysqli_fetch_assoc($result_turmas)) { $turmas_lecionadas[] = $row['nome_turma']; }
            mysqli_stmt_close($stmt_turmas_prepare);
        } else {
            error_log("Erro ao buscar turmas do professor (perfil_professor.php): " . mysqli_error($conn));
        }
    }
}
$ano_inicio = $professor_info ? date("Y", strtotime($professor_info['data_criacao'])) : 'N/A';
$tema_escolhido_pelo_dono_do_perfil = $professor_info && !empty($professor_info['tema_perfil']) ? $professor_info['tema_perfil'] : 'padrao';

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
    <title>Perfil de <?php echo $professor_info ? htmlspecialchars($professor_info['nome']) : 'Professor'; ?> - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> 
    <link rel="stylesheet" href="css/aluno.css"> <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        .chat-header-acad {
    padding: 10px 15px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #208A87 0%, #186D6A 100%);
    color: white;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    box-shadow: 0 2px 8px rgba(32, 138, 135, 0.3);
}
        /* Estilos ESTRUTURAIS mínimos para esta página. Cores e fontes virão dos temas. */
        .profile-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; padding:1rem; }
        .profile-header { text-align: center; margin-bottom: 1.5rem; width:100%;}
        .profile-photo-wrapper { position: relative; margin-bottom: 1rem; display:inline-block; }
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; }
        .profile-header h2 { font-size: 2rem; margin-bottom: 0.25rem; }
        .profile-header .member-since { font-size: 1rem; margin-bottom: 1rem; }
        .profile-details { width: 100%; max-width: 800px; }
        .profile-section { padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .profile-section h3 { font-size: 1.3rem; margin-top: 0; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .profile-section p, .profile-section ul { font-size: 1rem; line-height: 1.6; }
        .profile-section ul { list-style: none; padding-left: 0; }
        .profile-section li { padding: 0.6rem 1rem; margin-bottom: 0.5rem; border-radius: 4px; border-left-style: solid; border-left-width: 3px; }
        .edit-section details { margin-bottom: 10px; }
        .edit-section summary { cursor: pointer; font-weight: bold; padding: 0.5rem; border-radius:4px; display: inline-block;}
        .edit-section form { margin-top: 1rem; padding:1rem; border:1px solid var(--border-color-soft, #eee); border-radius:4px;}
        .edit-section label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .edit-section textarea, .edit-section select { width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box; margin-bottom:1rem; }
        .edit-section textarea { min-height: 150px; }
        .edit-section button[type="submit"] { padding: 0.6rem 1.2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        .status-message-profile { padding: 0.8rem; margin-top:1rem; margin-bottom: 1rem; border-radius: 4px; text-align:center; font-size:0.9rem; }
        .upload-form-container { margin-top: 10px; text-align:center; }
        .upload-form-container input[type="file"] { display: inline-block; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
        .upload-form-container button[type="submit"] { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; font-size: 0.9rem; }
        .no-data { font-style: italic; }
        .error-message { text-align: center; color: red; font-size: 1.2rem; padding: 2rem; }

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
        .teacher-icon-acad { margin-left: 5px; color: var(--primary-color, #007bff); font-size: 0.9em; }
        .student-icon-acad { margin-left: 5px; color: var(--accent-color, #6c757d); font-size: 0.9em; } 
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
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); // Tema do VISUALIZADOR ?>">
    
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Perfil do Professor</h1>
        <?php if(isset($_SESSION['usuario_id'])): // Usuário logado ?>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout">
                <i class="fas fa-sign-out-alt"></i> Sair
            </button>
        </form>
        <?php endif; ?>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php
            // O sidebar é carregado com base no PAPEL DO VISUALIZADOR
            $sidebar_include_path = '';
            if ($viewer_role === 'docente') { $sidebar_include_path = __DIR__ . '/includes/sidebar_professor.php'; }
            elseif ($viewer_role === 'aluno') { $sidebar_include_path = __DIR__ . '/includes/sidebar_aluno.php'; }
            elseif ($viewer_role === 'coordenacao') { $sidebar_include_path = __DIR__ . '/includes/sidebar_coordenacao.php'; }
            
            if (!empty($sidebar_include_path) && file_exists($sidebar_include_path)) { 
                // Passar $currentPageIdentifier para o sidebar, se for o próprio perfil do professor
                if ($is_own_profile) {
                    // $currentPageIdentifier já está definido como 'meu_perfil'
                } else {
                    // Se não é o próprio perfil, o $currentPageIdentifier não é relevante para o highlight do menu do professor
                    // Pode ser necessário limpar ou não definir para evitar highlight incorreto no menu do visualizador.
                    // Para esta página, se não é 'meu_perfil', não haverá item de menu ativo no sidebar (a menos que seja um item "ver perfis").
                    // $currentPageIdentifier = null; // Já inicializado como null
                }
                include $sidebar_include_path; 
            }
            else { echo "<p style='padding:1rem; color:white;'>Menu não disponível.</p>"; }
            ?>
        </nav>

        <main class="main-content">
            <?php if ($professor_info): ?>
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-photo-wrapper">
                            <img src="<?php echo htmlspecialchars(!empty($professor_info['foto_url']) ? $professor_info['foto_url'] : 'img/professores/default_avatar.png'); ?>" 
                                 alt="Foto de <?php echo htmlspecialchars($professor_info['nome']); ?>" class="profile-photo"
                                 onerror="this.onerror=null; this.src='img/professores/default_avatar.png';">
                        </div>
                        <?php if ($is_own_profile): ?>
                            <div class="upload-form-container">
                                <form action="upload_foto_professor.php" method="post" enctype="multipart/form-data">
                                    <input type="file" name="foto_perfil" accept="image/jpeg, image/png, image/gif" required class="input-field-file">
                                    <button type="submit" class="button button-small"><i class="fas fa-upload"></i> Alterar Foto</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['upload_status_message'])): ?>
                            <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['upload_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['upload_status_message']); ?></div>
                            <?php unset($_SESSION['upload_status_message']); unset($_SESSION['upload_status_type']); ?>
                        <?php endif; ?>
                        <h2><?php echo htmlspecialchars($professor_info['nome']); ?></h2>
                        <p class="member-since">Na instituição desde <?php echo $ano_inicio; ?></p>
                    </div>

                    <div class="profile-details">
                        <?php if ($is_own_profile): ?>
                        <section class="profile-section edit-section card">
                            <details>
                                <summary><i class="fas fa-edit"></i> Editar Perfil (Biografia e Tema)</summary>
                                <form action="salvar_bio_professor.php" method="POST" style="margin-bottom:20px;">
                                    <label for="biografia">Minha Biografia:</label>
                                    <textarea id="biografia" name="biografia" rows="6" class="input-field"><?php echo htmlspecialchars($professor_info['biografia'] ?? ''); ?></textarea>
                                    <button type="submit" class="button"><i class="fas fa-save"></i> Salvar Biografia</button>
                                </form>
                                <?php if(isset($_SESSION['bio_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['bio_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['bio_status_message']); ?></div>
                                <?php unset($_SESSION['bio_status_message']); unset($_SESSION['bio_status_type']); ?>
                                <?php endif; ?>
                                <hr style="margin: 20px 0;">
                                <form action="salvar_tema_professor.php" method="POST">
                                    <label for="tema_perfil_select">Escolha um Tema para seu Perfil (e para o sistema):</label>
                                    <select id="tema_perfil_select" name="tema_perfil" class="input-field">
                                        <?php foreach($temas_disponiveis as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>" <?php if($tema_escolhido_pelo_dono_do_perfil == $value) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="button"><i class="fas fa-palette"></i> Aplicar Tema</button>
                                </form>
                                <?php if(isset($_SESSION['tema_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['tema_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['tema_status_message']); ?></div>
                                <?php unset($_SESSION['tema_status_message']); unset($_SESSION['tema_status_type']); ?>
                                <?php endif; ?>
                            </details>
                        </section>
                        <?php endif; ?>

                        <section class="profile-section bio-section card"><h3><i class="fas fa-id-card-alt"></i> Sobre Mim</h3>
                            <?php if (!empty($professor_info['biografia'])): ?><p><?php echo nl2br(htmlspecialchars($professor_info['biografia'])); ?></p>
                            <?php else: ?><p class="no-data">Nenhuma biografia informada. <?php if($is_own_profile) echo 'Clique em "Editar Perfil" para adicionar uma.'; ?></p><?php endif; ?>
                        </section>
                        <section class="profile-section card"><h3><i class="fas fa-info-circle"></i> Informações de Contato</h3><p><strong>Email:</strong> <?php echo htmlspecialchars($professor_info['email']); ?></p></section>
                        <section class="profile-section card"><h3><i class="fas fa-book-reader"></i> Disciplinas Lecionadas</h3>
                            <?php if (!empty($disciplinas_lecionadas)): ?><ul><?php foreach ($disciplinas_lecionadas as $d): ?><li><?php echo htmlspecialchars($d); ?></li><?php endforeach; ?></ul><?php else: ?><p class="no-data">Nenhuma.</p><?php endif; ?>
                        </section>
                        <section class="profile-section card"><h3><i class="fas fa-users-class"></i> Turmas Atuais</h3>
                            <?php if (!empty($turmas_lecionadas)): ?><ul><?php foreach ($turmas_lecionadas as $t): ?><li><?php echo htmlspecialchars($t); ?></li><?php endforeach; ?></ul><?php else: ?><p class="no-data">Nenhuma.</p><?php endif; ?>
                        </section>
                    </div>
                </div>
            <?php else: ?>
                <p class="error-message">Perfil do professor não encontrado ou ID inválido.</p>
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
        // O chat funciona no contexto do USUÁRIO LOGADO (VISUALIZADOR)
        const currentUserId = <?php echo json_encode($viewer_id); ?>;
        const currentUserSessionRole = <?php echo json_encode($viewer_role); ?>; 
        const currentUserTurmaIdForStudent = <?php echo json_encode($viewer_turma_id); ?>; // 0 se não for aluno ou não tiver turma

        let currentUserChatRole = '';
        if (currentUserSessionRole === 'aluno') {
            currentUserChatRole = 'aluno'; 
        } else if (currentUserSessionRole === 'docente') {
            currentUserChatRole = 'professor'; 
        } else if (currentUserSessionRole === 'coordenacao') {
            currentUserChatRole = 'coordenador'; // Exemplo, se coordenadores também usarem o chat
        } else {
            console.warn("Chat: Papel do visualizador não explicitamente suportado para lista de contatos:", currentUserSessionRole);
            // O chat ainda pode funcionar para enviar/receber se alguém iniciar, mas a lista de contatos pode não carregar.
        }
        
        const defaultUserPhoto = 'img/alunos/default_avatar.png';
        const defaultProfessorPhoto = 'img/professores/default_avatar.png'; // Ajuste para um avatar de professor se tiver
        const defaultCoordenadorPhoto = 'img/coordenadores/default_avatar.png'; // Exemplo

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
                if (!isChatInitiallyLoaded && (currentUserChatRole === 'aluno' || currentUserChatRole === 'professor' || currentUserChatRole === 'coordenador')) { 
                    // Só carrega contatos se o papel for conhecido e suportado para ter uma lista
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
            else if (contact.role === 'coordenador') photoToUse = defaultCoordenadorPhoto; // Exemplo
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
                if (currentUserTurmaIdForStudent === 0) {
                    userListUl.innerHTML = '<li>Turma não definida para carregar contatos.</li>';
                    return;
                }
            } else if (currentUserChatRole === 'professor') {
                actionApi = 'get_professor_contacts';
            } else if (currentUserChatRole === 'coordenador') {
                actionApi = 'get_coordenador_contacts'; // Você precisará implementar esta ação na API
            } else {
                userListUl.innerHTML = '<li>Lista de contatos não disponível para seu perfil.</li>';
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
                    console.error('API Erro (' + actionApi + '):', users.error);
                    userListUl.innerHTML = `<li>Erro: ${users.error}</li>`;
                    return;
                }
                allContacts = users; 
                renderUserList(allContacts);

            } catch (error) {
                console.error('Falha ao buscar contatos:', error);
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
                     li.classList.add('chat-user-coordenador-acad'); // Crie esta classe CSS se necessário
                     const coordIcon = document.createElement('i');
                     coordIcon.className = 'fas fa-user-tie teacher-icon-acad'; // Reutilizando ou crie nova classe para ícone
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
            console.log("fetchAndDisplayMessages - Enviando para API:", { action: 'get_messages', contact_id: contactId, contact_role: contactRole, current_user_id: currentUserId, current_user_role: currentUserChatRole });
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

        if(chatHeader) { // Adicionar verificação se o widget de chat existe na página
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
        if(messageInput) messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSendMessage();
            }
        });
        if(messageInput) messageInput.addEventListener('input', function() { 
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Se o usuário logado não tiver um papel suportado para iniciar buscas de contato,
        // o chat ainda pode ser aberto, mas a lista de contatos não será populada automaticamente.
        if (!chatWidget) {
            console.warn("Elemento do Widget de Chat Principal não encontrado.");
        }

    });
    </script>

</body>
</html>
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>