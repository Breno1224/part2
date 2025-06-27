<?php
session_start(); 
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$aluno_id = $_SESSION['usuario_id'];
$nome_aluno = $_SESSION['usuario_nome']; // Disponível para o título da página e potencialmente para o chat JS
$turma_id_aluno = isset($_SESSION['turma_id']) ? intval($_SESSION['turma_id']) : 0; // Essencial para o chat
$currentPageIdentifier = 'boletim'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

$disciplinas_da_turma_map = [];
if ($turma_id_aluno > 0) {
    // Ajuste na query para buscar disciplinas da turma via 'disciplinas' diretamente se 'professores_turmas_disciplinas' for uma junção complexa
    // Esta query assume que a tabela 'disciplinas' possui uma coluna 'turma_id' ou que há uma forma de ligá-las.
    // A query original usava 'professores_turmas_disciplinas'. Vou manter essa lógica,
    // mas se for mais simples, como d.turma_id, ajuste.
    $sql_disciplinas_turma = "SELECT DISTINCT d.id as id_disciplina, d.nome_disciplina
                              FROM disciplinas d
                              JOIN professores_turmas_disciplinas ptd ON d.id = ptd.disciplina_id 
                              WHERE ptd.turma_id = ? 
                              ORDER BY d.nome_disciplina";
    // Se a sua tabela `disciplinas` já tem `turma_id` e `professor_id`
    // $sql_disciplinas_turma = "SELECT id as id_disciplina, nome_disciplina FROM disciplinas WHERE turma_id = ? ORDER BY nome_disciplina";

    $stmt_disc_turma = mysqli_prepare($conn, $sql_disciplinas_turma);
    if ($stmt_disc_turma) {
        mysqli_stmt_bind_param($stmt_disc_turma, "i", $turma_id_aluno);
        mysqli_stmt_execute($stmt_disc_turma);
        $result_disc_turma = mysqli_stmt_get_result($stmt_disc_turma);
        while ($disc_row = mysqli_fetch_assoc($result_disc_turma)) {
            $disciplinas_da_turma_map[$disc_row['nome_disciplina']] = $disc_row; // Usar nome como chave pode ser problemático se houver nomes duplicados com IDs diferentes. Idealmente, use ID.
        }
        mysqli_stmt_close($stmt_disc_turma);
    } else {
        error_log("Erro ao buscar disciplinas da turma (boletim.php): " . mysqli_error($conn));
    }
}

$sql_notas = "
    SELECT 
        d.nome_disciplina, n.avaliacao, n.nota, n.bimestre
    FROM notas n
    JOIN disciplinas d ON n.disciplina_id = d.id
    WHERE n.aluno_id = ?
    ORDER BY d.nome_disciplina, n.bimestre, n.avaliacao";
$stmt_notas = mysqli_prepare($conn, $sql_notas);
$boletim_data_notas = []; 
if ($stmt_notas) { 
    mysqli_stmt_bind_param($stmt_notas, "i", $aluno_id);
    mysqli_stmt_execute($stmt_notas);
    $resultado_notas = mysqli_stmt_get_result($stmt_notas);
    while ($nota_row = mysqli_fetch_assoc($resultado_notas)) {
        $boletim_data_notas[$nota_row['nome_disciplina']][$nota_row['bimestre']][] = [
            'avaliacao' => $nota_row['avaliacao'],
            'nota' => $nota_row['nota']
        ];
    }
    mysqli_stmt_close($stmt_notas);
} else {
    error_log("Erro ao preparar statement para buscar notas (boletim.php): " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Boletim - ACADMIX</title>
    <link rel="stylesheet" href="css/aluno.css"> 
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        
        @import url('aluno.css');

/* Ativa o menu da página */
.sidebar a.active {
    background-color: #186D6A;
}

/* Esconde sidebar quando clica no hambúrguer */
.container.full-width .sidebar {
    display: none;
}

/* Título da página com estilo moderno */
.main-content h2.page-title-boletim {
    text-align: center;
    font-size: 1.8rem;
    margin-bottom: 2rem;
    color: #2C1B17;
    font-weight: 600;
    position: relative;
    padding-bottom: 1rem;
}

.main-content h2.page-title-boletim::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #208A87, #D69D2A);
    border-radius: 2px;
}

/* Container da tabela com estilo de card */
.table-container {
    overflow-x: auto;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(32, 138, 135, 0.1);
}

.table-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #208A87, #D69D2A);
}

.table-container:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
}

/* Estilo da tabela principal */
.boletim-table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Cabeçalho da tabela */
.boletim-table thead th {
    background: linear-gradient(135deg, #D69D2A 0%, #C58624 100%) !important;
    color: white !important;
    padding: 16px 12px;
    text-align: center;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
    position: relative;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.boletim-table thead th:first-child {
    text-align: left;
    padding-left: 20px;
}

/* Células do corpo da tabela */
.boletim-table tbody td {
    padding: 14px 12px;
    text-align: center;
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(32, 138, 135, 0.1);
    transition: all 0.3s ease;
    color: #2C1B17;
}

.boletim-table tbody td:first-child {
    text-align: left;
    padding-left: 20px;
    font-weight: 600;
    color: #208A87;
}

/* Linhas alternadas */
.boletim-table tbody tr:nth-child(4n+1),
.boletim-table tbody tr:nth-child(4n+2) {
    background-color: rgba(32, 138, 135, 0.02);
}

/* Hover effect nas linhas de matéria */
.boletim-table tbody tr.subject-row:hover {
    background: rgba(32, 138, 135, 0.05);
    cursor: pointer;
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(32, 138, 135, 0.1);
}

.boletim-table tbody tr.subject-row:hover td {
    color: #186D6A;
}

.boletim-table tbody tr.subject-row:hover td:first-child {
    color: #208A87;
    font-weight: 700;
}

/* Linhas de detalhes */
.details-row {
    display: none;
    background: linear-gradient(135deg, rgba(32, 138, 135, 0.03) 0%, rgba(214, 157, 42, 0.03) 100%);
}

.details-row td {
    padding: 0;
    border-bottom: 2px solid rgba(32, 138, 135, 0.1);
}

.details-content {
    padding: 20px;
    text-align: left;
    border-left: 4px solid #208A87;
    margin: 10px;
    border-radius: 8px;
    background: white;
    box-shadow: 0 2px 8px rgba(32, 138, 135, 0.1);
}

.details-content h4 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.1rem;
    color: #208A87;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.details-content h4::before {
    content: '📊';
    font-size: 1rem;
}

.details-content ul {
    list-style-type: none;
    padding-left: 0;
    margin: 0;
}

.details-content li {
    margin-bottom: 8px;
    font-size: 0.9rem;
    padding: 8px 12px;
    background: rgba(32, 138, 135, 0.05);
    border-radius: 6px;
    border-left: 3px solid #D69D2A;
    transition: all 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.details-content li:hover {
    background: rgba(32, 138, 135, 0.08);
    transform: translateX(4px);
}

.details-content p {
    margin: 0.8rem 0;
    font-weight: 600;
    color: #186D6A;
}

.details-content p strong {
    font-weight: 700;
    color: #208A87;
}

/* Estados de situação */
.situacao-aprovado {
    color: #28a745 !important;
    font-weight: 700 !important;
    background: rgba(40, 167, 69, 0.1);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.situacao-reprovado {
    color: #dc3545 !important;
    font-weight: 700 !important;
    background: rgba(220, 53, 69, 0.1);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.situacao-cursando {
    color: #D69D2A !important;
    font-weight: 700 !important;
    background: rgba(214, 157, 42, 0.1);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Mensagem quando não há notas */
.no-grades td {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
    background: rgba(32, 138, 135, 0.03);
    border: 2px dashed rgba(32, 138, 135, 0.2);
    border-radius: 8px;
    margin: 10px;
}

.no-grades td::before {
    content: '📚';
    display: block;
    font-size: 2rem;
    margin-bottom: 10px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .table-container {
        margin: 0 -1rem;
        border-radius: 8px;
    }
    
    .boletim-table thead th,
    .boletim-table tbody td {
        padding: 8px 6px;
        font-size: 0.8rem;
    }
    
    .boletim-table thead th:first-child,
    .boletim-table tbody td:first-child {
        padding-left: 10px;
    }
    
    .details-content {
        padding: 15px;
        margin: 5px;
    }
    
    .details-content h4 {
        font-size: 1rem;
    }
    
    .details-content li {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
}

/* Animações suaves */
.boletim-table tbody tr,
.details-content,
.details-content li {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Scroll suave na tabela */
.table-container {
    scrollbar-width: thin;
    scrollbar-color: #208A87 #f1f1f1;
}

.table-container::-webkit-scrollbar {
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #208A87, #186D6A);
    border-radius: 4px;
}
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
.table-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #186D6A, #145A57);
}
        .main-content h2.page-title-boletim { 
            text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem;
        }
        .table-container { 
            overflow-x: auto; 
        }
        .boletim-table { 
            width: 100%;
            border-collapse: collapse;
        }
        .boletim-table thead th { 
            background-color: #D69D2A !important; 
            color: white !important;
            padding: 12px;
            text-align: center;
            font-size: 0.9rem; 
        }
        .boletim-table tbody td {
            padding: 10px;
            text-align: center;
            font-size: 0.9rem;
        }
        .boletim-table tbody tr.subject-row:hover { 
            cursor: pointer; 
        }
        .details-row { 
            display: none; 
        }
        .details-row td { 
            padding: 0; 
        }
        .details-content { 
            padding: 15px; 
            text-align: left; 
        }
        .details-content h4 { margin-top:0; margin-bottom:10px; font-size:1.1rem; }
        .details-content ul { list-style-type: none; padding-left: 0; } 
        .details-content li { margin-bottom: 6px; font-size:0.9rem; }
        .details-content p { margin: 0.5rem 0;}
        .details-content p strong { font-weight:bold; }
        .no-grades td { text-align: center; padding: 20px; }
        .situacao-aprovado { color: green; font-weight:bold; }
        .situacao-reprovado { color: red; font-weight:bold; }
        .situacao-cursando { color: orange; font-weight:bold; }

        /* --- INÍCIO CSS NOVO CHAT ACADÊMICO --- */
        /* (Recomendado mover para um arquivo CSS externo como chat_academico.css) */
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
        <h1>ACADMIX - Boletim de <?php echo htmlspecialchars($nome_aluno); ?></h1>
        <form action="logout.php" method="post" style="display: inline;">
             <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container" id="pageContainer"> 
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php';
            if (file_exists($sidebar_path)) { include $sidebar_path; }
            else { echo "<p style='padding:1rem; color:white;'>Erro: Sidebar não encontrada.</p>"; }
            ?>
        </nav>

        <main class="main-content">
            <div style="text-align:center;">
                 <h2 class="page-title-boletim">Boletim Escolar</h2>
            </div>
            <div class="table-container dashboard-section card"> 
                <table class="boletim-table">
                    <thead>
                        <tr>
                            <th>Disciplina</th>
                            <th>1º Bimestre</th><th>2º Bimestre</th>
                            <th>3º Bimestre</th><th>4º Bimestre</th>
                            <th>Média Final</th><th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($disciplinas_da_turma_map)): ?>
                            <tr class="no-grades"><td colspan="7">Sua turma ainda não tem disciplinas configuradas ou não há notas lançadas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($disciplinas_da_turma_map as $nome_disciplina_turma => $disciplina_info_turma): ?>
                                <?php $bimestres_notas = $boletim_data_notas[$nome_disciplina_turma] ?? []; ?>
                                <tr class="subject-row">
                                    <td><?php echo htmlspecialchars($nome_disciplina_turma); ?></td>
                                    <?php
                                    $soma_medias_bimestrais = 0; $bimestres_com_media = 0; $media_final_disciplina = 0;
                                    $notas_detalhadas_para_linha = []; // Para armazenar todas as notas para a linha de detalhes

                                    for ($b = 1; $b <= 4; $b++):
                                        $notas_bimestre_atual = $bimestres_notas[$b] ?? [];
                                        $notas_detalhadas_para_linha[$b] = $notas_bimestre_atual; // Guarda para detalhes

                                        $soma_notas_bimestre = 0; $qtd_notas_bimestre = count($notas_bimestre_atual);
                                        if ($qtd_notas_bimestre > 0) {
                                            foreach ($notas_bimestre_atual as $avaliacao_info) { $soma_notas_bimestre += floatval($avaliacao_info['nota']); }
                                            $media_bimestre = $soma_notas_bimestre / $qtd_notas_bimestre;
                                            echo '<td>' . number_format($media_bimestre, 1, ',', '.') . '</td>';
                                            $soma_medias_bimestrais += $media_bimestre; $bimestres_com_media++;
                                        } else { echo '<td>-</td>'; }
                                    endfor;
                                    
                                    if ($bimestres_com_media > 0) {
                                        // A média final considera apenas os bimestres que tiveram notas lançadas.
                                        // Se a regra da escola for dividir sempre por 4 (total de bimestres), ajuste aqui.
                                        $media_final_disciplina = $soma_medias_bimestrais / $bimestres_com_media; 
                                        echo '<td>' . number_format($media_final_disciplina, 1, ',', '.') . '</td>';
                                        // Defina a média para aprovação (ex: 6.0)
                                        $media_aprovacao = 6.0; 
                                        if ($media_final_disciplina >= $media_aprovacao) { echo '<td class="situacao-aprovado">Aprovado</td>'; }
                                        // Adicione lógica para recuperação se necessário
                                        else { echo '<td class="situacao-reprovado">Reprovado</td>'; }
                                    } else { echo '<td>-</td>'; echo '<td class="situacao-cursando">Cursando</td>'; }
                                    ?>
                                </tr>
                                <tr class="details-row">
                                    <td colspan="7">
                                        <div class="details-content">
                                            <h4>Detalhes de <?php echo htmlspecialchars($nome_disciplina_turma); ?>:</h4>
                                            <?php $tem_detalhes_bimestre_geral = false; ?>
                                            <?php for ($b = 1; $b <= 4; $b++): ?>
                                                <?php $notas_do_bimestre_atual_detalhe = $notas_detalhadas_para_linha[$b] ?? []; ?>
                                                <?php if (!empty($notas_do_bimestre_atual_detalhe)): $tem_detalhes_bimestre_geral = true; ?>
                                                    <p><strong><?php echo $b; ?>º Bimestre:</strong></p>
                                                    <ul>
                                                        <?php foreach ($notas_do_bimestre_atual_detalhe as $avaliacao_info): ?>
                                                            <li><?php echo htmlspecialchars($avaliacao_info['avaliacao']); ?>: <?php echo number_format(floatval($avaliacao_info['nota']), 1, ',', '.'); // Ajustado para 1 casa decimal ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <?php if (!$tem_detalhes_bimestre_geral): ?>
                                                <p>Nenhuma avaliação detalhada lançada para esta disciplina.</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        const menuToggleBoletim = document.getElementById('menu-toggle'); // Usar ID específico se necessário, ou global
        const sidebarBoletim = document.getElementById('sidebar');    
        const containerBoletim = document.getElementById('pageContainer'); // Certifique-se que o ID do container principal é 'pageContainer'

        if (menuToggleBoletim && sidebarBoletim && containerBoletim) {
            menuToggleBoletim.addEventListener('click', function () {
                sidebarBoletim.classList.toggle('hidden'); 
                containerBoletim.classList.toggle('full-width'); 
            });
        }

        // SCRIPT PARA EXPANDIR/OCULTAR DETALHES DA DISCIPLINA
        const subjectRows = document.querySelectorAll('.subject-row');
        subjectRows.forEach(row => {
            row.addEventListener('click', () => {
                const nextRow = row.nextElementSibling;
                if (nextRow && nextRow.classList.contains('details-row')) {
                    if (nextRow.style.display === 'table-row') {
                        nextRow.style.display = 'none';
                    } else {
                        nextRow.style.display = 'table-row';
                    }
                }
            });
        });
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