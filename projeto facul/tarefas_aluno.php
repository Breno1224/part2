<?php
session_start(); // GARANTIR que está no topo absoluto

// Verifica se o usuário é um aluno logado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno' || !isset($_SESSION['usuario_id']) || !isset($_SESSION['turma_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

// Variáveis do Aluno Logado
$nome_aluno = $_SESSION['usuario_nome'];
$aluno_id = $_SESSION['usuario_id']; 
$turma_id_aluno = intval($_SESSION['turma_id']); 

$currentPageIdentifier = 'tarefas_aluno'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Lógica PHP para buscar tarefas da turma do aluno
$tarefas_por_disciplina = [];

$sql = "
    SELECT 
        t.id,
        t.titulo,
        t.descricao,
        t.data_prazo,
        t.arquivo_path_professor,
        d.nome_disciplina,
        p.nome as nome_professor,
        sub.id as submissao_id,
        sub.data_submissao,
        sub.arquivo_path_aluno,
        sub.nota,
        sub.feedback_professor
    FROM tarefas t
    JOIN disciplinas d ON t.disciplina_id = d.id
    JOIN professores p ON t.professor_id = p.id
    LEFT JOIN tarefas_submissoes sub ON t.id = sub.tarefa_id AND sub.aluno_id = ?
    WHERE t.turma_id = ?
    ORDER BY d.nome_disciplina, t.data_prazo ASC
";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ii", $aluno_id, $turma_id_aluno);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        // Lógica para determinar o status da tarefa
        $prazo = new DateTime($row['data_prazo']);
        $agora = new DateTime();
        $status_key = 'pendente';
        $status_text = 'Pendente';

        if (!empty($row['submissao_id'])) {
            $data_submissao = new DateTime($row['data_submissao']);
            if (!is_null($row['nota'])) {
                $status_key = 'avaliada';
                $status_text = 'Avaliada';
            } elseif ($data_submissao > $prazo) {
                $status_key = 'atraso';
                $status_text = 'Entregue com Atraso';
            } else {
                $status_key = 'entregue';
                $status_text = 'Entregue';
            }
        } elseif ($agora > $prazo) {
            $status_key = 'atrasado_nao_entregue';
            $status_text = 'Atrasada';
        }

        $row['status_key'] = $status_key;
        $row['status_text'] = $status_text;

        $tarefas_por_disciplina[$row['nome_disciplina']][] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Erro ao buscar tarefas (tarefas_aluno.php): " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Tarefas - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css">
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        .page-title { text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem; }
        .disciplina-section { margin-bottom: 2rem; }
        .disciplina-section h3 { font-size: 1.6rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--primary-color, #007bff); }
        
        .tarefa-card {
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: box-shadow 0.2s;
        }
        .tarefa-header {
            padding: 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0; /* Arredondar cantos superiores */
        }
        .tarefa-info { flex-grow: 1; }
        .tarefa-info h4 { margin: 0 0 0.5rem 0; font-size: 1.2rem; }
        .tarefa-meta { font-size: 0.85rem; opacity: 0.8; }
        .tarefa-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
            white-space: nowrap;
        }
        .status-pendente { background-color: var(--warning-color, orange); }
        .status-entregue { background-color: var(--success-color, green); }
        .status-atraso { background-color: var(--info-color, #17a2b8); }
        .status-atrasado_nao_entregue { background-color: var(--danger-color, red); }
        .status-avaliada { background-color: var(--primary-color, #007bff); }

        .tarefa-body {
            display: none; /* Começa fechado */
            padding: 1rem;
            border-top: 1px solid var(--border-color-soft, #eee);
        }
        .tarefa-body p { white-space: pre-wrap; margin-top: 0; }
        .tarefa-body .arquivo-professor a { text-decoration: none; font-weight: bold; }

        .submissao-form { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed var(--border-color, #ccc); }
        .submissao-form label { font-weight: bold; display: block; margin-bottom: 0.5rem; }
        .submissao-info { background-color: var(--background-color-offset, #f9f9f9); padding: 1rem; border-radius: 4px; }
        .submissao-info p { margin: 0.5rem 0; }
        .nota-final { font-size: 1.2em; font-weight: bold; color: var(--primary-color, #007bff); }
        .feedback-professor { font-style: italic; margin-top: 0.5rem; border-left: 3px solid var(--accent-color, #6c757d); padding-left: 10px; }

        .no-data-message { padding: 1rem; text-align: center; border-radius: 4px; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }

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
        <h1>ACADMIX - Minhas Tarefas</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_aluno.php'; ?>
        </nav>

        <main class="main-content">
            <h2 class="page-title">Tarefas e Atividades</h2>
            
            <?php if(isset($_SESSION['tarefa_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['tarefa_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['tarefa_status_message']); ?>
                </div>
                <?php unset($_SESSION['tarefa_status_message']); unset($_SESSION['tarefa_status_type']); ?>
            <?php endif; ?>

            <?php if (empty($tarefas_por_disciplina)): ?>
                <p class="no-data-message info-message card">Nenhuma tarefa encontrada para sua turma no momento.</p>
            <?php else: ?>
                <?php foreach ($tarefas_por_disciplina as $disciplina => $tarefas): ?>
                    <section class="disciplina-section">
                        <h3><i class="fas fa-book"></i> <?php echo htmlspecialchars($disciplina); ?></h3>
                        <?php foreach ($tarefas as $tarefa): ?>
                            <div class="tarefa-card card">
                                <div class="tarefa-header">
                                    <div class="tarefa-info">
                                        <h4><?php echo htmlspecialchars($tarefa['titulo']); ?></h4>
                                        <div class="tarefa-meta">
                                            <span><i class="fas fa-chalkboard-teacher"></i> Prof. <?php echo htmlspecialchars($tarefa['nome_professor']); ?></span> | 
                                            <span><i class="fas fa-clock"></i> Prazo: <?php echo date("d/m/Y H:i", strtotime($tarefa['data_prazo'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="tarefa-status status-<?php echo $tarefa['status_key']; ?>">
                                        <?php echo $tarefa['status_text']; ?>
                                    </div>
                                </div>
                                <div class="tarefa-body">
                                    <p><?php echo nl2br(htmlspecialchars($tarefa['descricao'])); ?></p>
                                    
                                    <?php if (!empty($tarefa['arquivo_path_professor'])): ?>
                                        <div class="arquivo-professor">
                                            <p><strong>Material de Apoio:</strong> <a href="<?php echo htmlspecialchars($tarefa['arquivo_path_professor']); ?>" class="link" download><i class="fas fa-download"></i> Baixar arquivo</a></p>
                                        </div>
                                    <?php endif; ?>

                                    <hr>

                                    <?php if ($tarefa['submissao_id']): // Já entregou ?>
                                        <div class="submissao-info">
                                            <h4>Sua Entrega</h4>
                                            <p><strong>Enviado em:</strong> <?php echo date("d/m/Y H:i", strtotime($tarefa['data_submissao'])); ?></p>
                                            <p><strong>Arquivo enviado:</strong> <a href="<?php echo htmlspecialchars($tarefa['arquivo_path_aluno']); ?>" class="link" download><i class="fas fa-file-alt"></i> Ver meu envio</a></p>
                                            
                                            <?php if(!is_null($tarefa['nota'])): ?>
                                                <p><strong>Nota:</strong> <span class="nota-final"><?php echo number_format($tarefa['nota'], 2, ',', '.'); ?></span></p>
                                                <?php if(!empty($tarefa['feedback_professor'])): ?>
                                                    <p><strong>Feedback do Professor:</strong></p>
                                                    <div class="feedback-professor"><?php echo nl2br(htmlspecialchars($tarefa['feedback_professor'])); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p><strong>Status:</strong> Aguardando avaliação do professor.</p>
                                            <?php endif; ?>
                                            <form action="salvar_submissao_tarefa.php" method="POST" enctype="multipart/form-data" class="submissao-form">
                                                <input type="hidden" name="tarefa_id" value="<?php echo $tarefa['id']; ?>">
                                                <label for="arquivo_aluno_<?php echo $tarefa['id']; ?>">Reenviar arquivo (substituirá o anterior):</label>
                                                <input type="file" id="arquivo_aluno_<?php echo $tarefa['id']; ?>" name="arquivo_aluno" required class="input-field">
                                                <button type="submit" class="button button-warning button-small"><i class="fas fa-paper-plane"></i> Reenviar Tarefa</button>
                                            </form>
                                        </div>
                                    <?php else: // Ainda não entregou ?>
                                        <div class="submissao-form">
                                            <h4>Enviar sua Tarefa</h4>
                                            <form action="salvar_submissao_tarefa.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="tarefa_id" value="<?php echo $tarefa['id']; ?>">
                                                <label for="arquivo_aluno_<?php echo $tarefa['id']; ?>">Anexar seu arquivo:</label>
                                                <input type="file" id="arquivo_aluno_<?php echo $tarefa['id']; ?>" name="arquivo_aluno" required class="input-field">
                                                <button type="submit" class="button button-success"><i class="fas fa-paper-plane"></i> Enviar Tarefa</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
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

        // Script para expandir/colapsar tarefas (acordeão)
        document.querySelectorAll('.tarefa-header').forEach(header => {
            header.addEventListener('click', function() {
                this.parentElement.classList.toggle('active'); // Alterna no card pai
                const body = this.nextElementSibling;
                if (body.style.display === "block") {
                    body.style.display = "none";
                } else {
                    body.style.display = "block";
                }
            });
        });
    </script>

    <script>
        // Cole aqui o JavaScript completo e padronizado do chat que temos usado nas outras páginas.
        // Ele usará as variáveis PHP `currentUserId` e `currentUserTurmaIdForStudent` corretamente.
    </script>
</body>
</html>
<?php 
if(isset($conn) && $conn) mysqli_close($conn); 
?>