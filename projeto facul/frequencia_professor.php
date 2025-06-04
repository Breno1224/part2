<?php
session_start(); // GARANTIR que está no topo absoluto
// Verifica se o usuário é um docente logado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php'; // Conexão com o banco

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id']; // Essencial para o chat
// $_SESSION['role'] é 'docente', será usado no JS do chat

$currentPageIdentifier = 'frequencia'; // Para a sidebar

// PEGAR TEMA DA SESSÃO
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// (PHP para buscar turmas do professor - como no seu código)
$turmas_professor = [];
$sql_turmas = "SELECT DISTINCT t.id, t.nome_turma FROM turmas t JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id WHERE ptd.professor_id = ? ORDER BY t.nome_turma";
$stmt_turmas_fetch = mysqli_prepare($conn, $sql_turmas);
if ($stmt_turmas_fetch) {
    mysqli_stmt_bind_param($stmt_turmas_fetch, "i", $professor_id);
    mysqli_stmt_execute($stmt_turmas_fetch);
    $result_turmas = mysqli_stmt_get_result($stmt_turmas_fetch);
    while ($row = mysqli_fetch_assoc($result_turmas)) {
        $turmas_professor[] = $row;
    }
    mysqli_stmt_close($stmt_turmas_fetch);
} else {
    error_log("Erro ao buscar turmas do professor (frequencia_professor.php): " . mysqli_error($conn));
}


$turma_selecionada_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
$data_aula_selecionada = isset($_GET['data_aula']) ? $_GET['data_aula'] : date('Y-m-d');
$nome_turma_selecionada = "";
$alunos_com_frequencia = [];
$professor_tem_acesso_turma = false; 

if ($turma_selecionada_id && !empty($data_aula_selecionada)) { // Adicionada verificação para data_aula_selecionada
    foreach ($turmas_professor as $turma_p) {
        if ($turma_p['id'] == $turma_selecionada_id) {
            $professor_tem_acesso_turma = true;
            $nome_turma_selecionada = $turma_p['nome_turma'];
            break;
        }
    }
    
    if ($professor_tem_acesso_turma) {
        $sql_alunos_frequencia = "
            SELECT a.id as aluno_id, a.nome as aluno_nome, a.foto_url, f.status, f.observacao
            FROM alunos a
            LEFT JOIN frequencia f ON a.id = f.aluno_id AND f.turma_id = ? AND f.data_aula = ?
            WHERE a.turma_id = ?
            ORDER BY a.nome";
        $stmt_alunos = mysqli_prepare($conn, $sql_alunos_frequencia);
        if ($stmt_alunos) {
            mysqli_stmt_bind_param($stmt_alunos, "isi", $turma_selecionada_id, $data_aula_selecionada, $turma_selecionada_id);
            mysqli_stmt_execute($stmt_alunos);
            $result_alunos_frequencia = mysqli_stmt_get_result($stmt_alunos);
            while ($row = mysqli_fetch_assoc($result_alunos_frequencia)) {
                if (is_null($row['status'])) {
                    $row['status'] = 'P'; // Assume Presente como padrão se não houver registro
                }
                $alunos_com_frequencia[] = $row;
            }
            mysqli_stmt_close($stmt_alunos);
        } else {
            error_log("Erro ao buscar alunos com frequência (frequencia_professor.php): " . mysqli_error($conn));
        }
    } else {
        $turma_selecionada_id = null; 
        if (isset($_GET['turma_id']) && !empty($_GET['turma_id'])) {
         $_SESSION['frequencia_status_message'] = "Você não tem acesso à turma selecionada ou ela não existe.";
         $_SESSION['frequencia_status_type'] = "status-error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registro de Frequência - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> 
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos da página frequencia_professor.php */
        .dashboard-section { padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .dashboard-section h3 { font-size: 1.4rem; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .form-inline label { margin-right: 0.5rem; font-weight:bold; }
        .form-inline select, .form-inline input[type="date"] { padding: 0.5rem; border-radius: 4px; margin-right: 1rem; }
        .form-inline button { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
        .chamada-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .chamada-table th, .chamada-table td { padding: 0.75rem; text-align: left; vertical-align: middle; }
        .chamada-table .aluno-nome-clickable { cursor: pointer; font-weight: 500; }
        .status-buttons { display: flex; gap: 5px; }
        .status-buttons input[type="radio"] { display: none; }
        .status-buttons label { padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: bold; transition: background-color 0.2s, color 0.2s; min-width: 35px; text-align: center; }
        .status-buttons input[type="radio"]:checked + label { color: white; border-color: transparent; }
        .status-buttons .status-P input[type="radio"]:checked + label { background-color: #28a745; } 
        .status-buttons .status-F input[type="radio"]:checked + label { background-color: #dc3545; } 
        .status-buttons .status-A input[type="radio"]:checked + label { background-color: #ffc107; color: #333 !important; } 
        .status-buttons .status-FJ input[type="radio"]:checked + label { background-color: #17a2b8; } 
        .status-buttons .status-P label:hover { background-color: #d4edda; }
        .status-buttons .status-F label:hover { background-color: #f8d7da; }
        .status-buttons .status-A label:hover { background-color: #fff3cd; }
        .status-buttons .status-FJ label:hover { background-color: #d1ecf1; }
        .chamada-table input[type="text"].observacao-input { width: 95%; padding: 0.3rem; font-size:0.85rem; border-radius: 3px; }
        .btn-salvar-chamada { display: block; width: auto; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 1.5rem; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { margin: 10% auto; padding: 25px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; position: relative; }
        .modal-close-button { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .modal-close-button:hover, .modal-close-button:focus { color: black; text-decoration: none; cursor: pointer; }
        #modalAlunoNome { margin-top: 0; }
        #modalAlunoStats p { font-size: 1.1rem; line-height: 1.6; }
        #modalAlunoStats .highlight { font-weight: bold; }

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
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Registro de Frequência (Prof. <?php echo htmlspecialchars($nome_professor); ?>)</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader" class="button button-logout"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <section class="dashboard-section card">
                <h3>Selecionar Turma e Data para Chamada</h3>
                <form method="GET" action="frequencia_professor.php" class="form-inline">
                     <label for="turma_id_select">Turma:</label>
                    <select name="turma_id" id="turma_id_select" required class="input-field">
                        <option value="">-- Selecione --</option>
                        <?php foreach ($turmas_professor as $turma): ?>
                            <option value="<?php echo $turma['id']; ?>" <?php echo ($turma_selecionada_id == $turma['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($turma['nome_turma']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="data_aula_select">Data:</label>
                    <input type="date" name="data_aula" id="data_aula_select" value="<?php echo htmlspecialchars($data_aula_selecionada); ?>" required class="input-field">
                    <button type="submit" class="button"><i class="fas fa-list-alt"></i> Carregar Alunos</button>
                </form>
            </section>

            <?php if(isset($_SESSION['frequencia_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['frequencia_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['frequencia_status_message']); ?>
                </div>
                <?php unset($_SESSION['frequencia_status_message']); unset($_SESSION['frequencia_status_type']); ?>
            <?php endif; ?>

            <?php if ($turma_selecionada_id && $data_aula_selecionada && $professor_tem_acesso_turma): ?>
            <section class="dashboard-section card">
                <h3>Chamada para: <?php echo htmlspecialchars($nome_turma_selecionada); ?> - Data: <?php echo date("d/m/Y", strtotime($data_aula_selecionada)); ?></h3>
                <?php if (!empty($alunos_com_frequencia)): ?>
                <form action="salvar_frequencia.php" method="POST">
                    <input type="hidden" name="turma_id" value="<?php echo $turma_selecionada_id; ?>">
                    <input type="hidden" name="data_aula" value="<?php echo $data_aula_selecionada; ?>">
                    <table class="chamada-table table"> <thead>
                            <tr>
                                <th>Aluno</th>
                                <th width="35%">Status (P, F, A, FJ)</th>
                                <th width="35%">Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos_com_frequencia as $aluno): ?>
                            <tr>
                                <td>
                                    <span class="aluno-nome-clickable" data-aluno-id="<?php echo $aluno['aluno_id']; ?>" data-aluno-nome="<?php echo htmlspecialchars($aluno['aluno_nome']); ?>" data-turma-id="<?php echo $turma_selecionada_id; ?>">
                                        <?php echo htmlspecialchars($aluno['aluno_nome']); ?>
                                    </span>
                                </td>
                                <td class="status-buttons">
                                    <?php $aluno_id_input = $aluno['aluno_id']; ?>
                                    <div class="status-P">
                                        <input type="radio" id="p_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="P" <?php echo ($aluno['status'] == 'P') ? 'checked' : ''; ?>>
                                        <label for="p_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>">P</label>
                                    </div>
                                    <div class="status-F">
                                        <input type="radio" id="f_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="F" <?php echo ($aluno['status'] == 'F') ? 'checked' : ''; ?>>
                                        <label for="f_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>">F</label>
                                    </div>
                                    <div class="status-A">
                                        <input type="radio" id="a_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="A" <?php echo ($aluno['status'] == 'A') ? 'checked' : ''; ?>>
                                        <label for="a_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>">A</label>
                                    </div>
                                    <div class="status-FJ">
                                        <input type="radio" id="fj_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="FJ" <?php echo ($aluno['status'] == 'FJ') ? 'checked' : ''; ?>>
                                        <label for="fj_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>_<?php echo str_replace('-', '', $data_aula_selecionada);?>">FJ</label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="frequencia[<?php echo $aluno_id_input; ?>][observacao]" class="observacao-input input-field" value="<?php echo htmlspecialchars($aluno['observacao'] ?? ''); ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn-salvar-chamada button"><i class="fas fa-save"></i> Salvar Chamada</button>
                </form>
                <?php else: ?>
                    <p class="no-data-message info-message">Nenhum aluno encontrado nesta turma para a data selecionada.</p>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </main>
    </div>

    <div id="frequenciaModal" class="modal">
        <div class="modal-content card"> <span class="modal-close-button" onclick="document.getElementById('frequenciaModal').style.display='none'">&times;</span>
            <h3 id="modalAlunoNome"></h3>
            <div id="modalAlunoStats"><p>Carregando estatísticas...</p></div>
        </div>
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

        // Script para o Modal de Frequência do Aluno (existente)
        const modal = document.getElementById('frequenciaModal');
        document.querySelectorAll('.aluno-nome-clickable').forEach(item => {
            item.addEventListener('click', function() {
                const alunoId = this.dataset.alunoId;
                const alunoNome = this.dataset.alunoNome;
                const turmaId = this.dataset.turmaId; // Adicionado para o contexto do AJAX
                
                document.getElementById('modalAlunoNome').textContent = "Estatísticas de Frequência: " + alunoNome;
                document.getElementById('modalAlunoStats').innerHTML = "<p>Buscando dados...</p>";
                modal.style.display = "block";

                // AJAX para buscar estatísticas
                fetch(`ajax_busca_stats_frequencia.php?aluno_id=${alunoId}&turma_id=${turmaId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.error){
                        document.getElementById('modalAlunoStats').innerHTML = `<p style="color:red;">${data.error}</p>`;
                    } else {
                        let statsHtml = `<p><span class="highlight">Total de Aulas Registradas:</span> ${data.total_aulas || 0}</p>`;
                        statsHtml += `<p><span class="highlight">Presenças (P):</span> ${data.presencas || 0}</p>`;
                        statsHtml += `<p><span class="highlight">Faltas (F):</span> ${data.faltas || 0}</p>`;
                        statsHtml += `<p><span class="highlight">Atestados (A):</span> ${data.atestados || 0}</p>`;
                        statsHtml += `<p><span class="highlight">Faltas Justificadas (FJ):</span> ${data.faltas_justificadas || 0}</p>`;
                        let percentualPresenca = data.total_aulas > 0 ? ((data.presencas / (data.total_aulas - data.atestados - data.faltas_justificadas)) * 100) : 0;
                         if ( (data.total_aulas - data.atestados - data.faltas_justificadas) <=0 ) percentualPresenca = data.presencas > 0 ? 100 : 0; // Evita divisão por zero se só tiver atestado/FJ

                        statsHtml += `<p><span class="highlight">Percentual de Presença Efetiva:</span> ${percentualPresenca.toFixed(1)}%</p>`;
                        document.getElementById('modalAlunoStats').innerHTML = statsHtml;
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar estatísticas:', error);
                    document.getElementById('modalAlunoStats').innerHTML = "<p style='color:red;'>Erro ao carregar dados.</p>";
                });
            });
        });
        if (modal) { // Garante que o modal existe antes de adicionar o event listener
            const closeButton = modal.querySelector('.modal-close-button');
            if(closeButton) {
                 closeButton.onclick = function() { modal.style.display = "none"; }
            }
            window.onclick = function(event) { if (event.target == modal) { modal.style.display = "none"; } }
        }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const currentUserId = <?php echo json_encode($professor_id); ?>;
        const currentUserSessionRole = <?php echo json_encode($_SESSION['role']); ?>; 
        let currentUserChatRole = '';
        if (currentUserSessionRole === 'docente') {
            currentUserChatRole = 'professor'; 
        } else {
            currentUserChatRole = currentUserSessionRole; 
        }
        
        const defaultUserPhoto = 'img/alunos/default_avatar.png';
        const defaultProfessorPhoto = 'img/professores/default_avatar_prof.png'; 

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
                if (!isChatInitiallyLoaded) { 
                    fetchContactsForProfessor();
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
            if (contact.foto_url) photoToUse = contact.foto_url;
            conversationUserPhoto.src = photoToUse;
            
            if (shouldFetchMessages) {
                messagesContainer.innerHTML = ''; 
                fetchAndDisplayMessages(contact.id, contact.role);
            }
            messageInput.focus();
        }
        
        async function fetchContactsForProfessor() { 
            userListUl.innerHTML = '<li><i class="fas fa-spinner fa-spin"></i> Carregando contatos...</li>';
            
            try {
                const response = await fetch(`chat_api.php?action=get_professor_contacts`); 
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error ${response.status}: ${errorText}`);
                }
                const users = await response.json();

                if (users.error) {
                    console.error('API Erro (get_professor_contacts):', users.error);
                    userListUl.innerHTML = `<li>Erro: ${users.error}</li>`;
                    return;
                }
                allContacts = users; 
                renderUserList(allContacts);

            } catch (error) {
                console.error('Falha ao buscar contatos do professor:', error);
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
            const filteredUsers = allContacts.filter(user => 
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