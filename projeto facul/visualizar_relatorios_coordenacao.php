<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_coordenador = $_SESSION['usuario_nome'];
$coordenador_id_logado = $_SESSION['usuario_id'];
$currentPageIdentifier = 'ver_relatorios_coord';

// Buscar dados para os filtros
$professores = mysqli_query($conn, "SELECT id, nome FROM professores ORDER BY nome");
$turmas = mysqli_query($conn, "SELECT id, nome_turma FROM turmas ORDER BY nome_turma");
$disciplinas = mysqli_query($conn, "SELECT id, nome_disciplina FROM disciplinas ORDER BY nome_disciplina");

// Lógica de Filtro
$where_clauses = [];
$params = [];
$param_types = "";

if (!empty($_GET['professor_id'])) {
    $where_clauses[] = "r.professor_id = ?";
    $params[] = intval($_GET['professor_id']);
    $param_types .= "i";
}
if (!empty($_GET['turma_id'])) {
    $where_clauses[] = "r.turma_id = ?";
    $params[] = intval($_GET['turma_id']);
    $param_types .= "i";
}
if (!empty($_GET['disciplina_id'])) {
    $where_clauses[] = "r.disciplina_id = ?";
    $params[] = intval($_GET['disciplina_id']);
    $param_types .= "i";
}
if (!empty($_GET['data_de'])) {
    $where_clauses[] = "r.data_aula >= ?";
    $params[] = $_GET['data_de'];
    $param_types .= "s";
}
if (!empty($_GET['data_ate'])) {
    $where_clauses[] = "r.data_aula <= ?";
    $params[] = $_GET['data_ate'];
    $param_types .= "s";
}

$sql_relatorios = "
    SELECT 
        r.id as relatorio_id, r.data_aula, r.conteudo_lecionado, r.observacoes, r.material_aula_path, r.data_envio,
        r.comentario_coordenacao, r.data_comentario_coordenacao,
        p.nome as nome_professor, 
        t.nome_turma, 
        d.nome_disciplina,
        coord_comment.nome as nome_coordenador_comentario
    FROM relatorios_aula r
    JOIN professores p ON r.professor_id = p.id
    JOIN turmas t ON r.turma_id = t.id
    JOIN disciplinas d ON r.disciplina_id = d.id
    LEFT JOIN coordenadores coord_comment ON r.coordenador_id_comentario = coord_comment.id ";

if (!empty($where_clauses)) {
    $sql_relatorios .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql_relatorios .= " ORDER BY r.data_aula DESC, p.nome, t.nome_turma";

$stmt_relatorios = mysqli_prepare($conn, $sql_relatorios);
if ($stmt_relatorios && !empty($params)) {
    mysqli_stmt_bind_param($stmt_relatorios, $param_types, ...$params);
}

$relatorios_result = null;
if ($stmt_relatorios) {
    mysqli_stmt_execute($stmt_relatorios);
    $relatorios_result = mysqli_stmt_get_result($stmt_relatorios);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Relatórios de Aula - Coordenação ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .dashboard-section { background-color: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); margin-bottom: 2rem; }
        .dashboard-section h3 { font-size: 1.4rem; color: #2C1B17; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #D69D2A; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; margin-bottom: 1.5rem; }
        .filter-form .form-group { display: flex; flex-direction: column; }
        .filter-form label { font-size: 0.85rem; margin-bottom: 0.3rem; color: #555; }
        .filter-form select, .filter-form input[type="date"] { padding: 0.5rem; border-radius: 4px; border: 1px solid #ccc; font-size:0.9rem; }
        .filter-form button { padding: 0.6rem 1rem; background-color: #5D3A9A; color: white; border: none; border-radius: 4px; cursor: pointer; font-size:0.9rem; }
        .filter-form button:hover { background-color: #4a2d7d; }
        .filter-form a.clear-filter { margin-left:0.5rem; padding: 0.6rem 1rem; background-color: #6c757d; color:white; text-decoration:none; border-radius:4px; font-size:0.9rem;}

        .relatorios-table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9rem; }
        .relatorios-table th, .relatorios-table td { border: 1px solid #ddd; padding: 0.75rem; text-align: left; }
        .relatorios-table th { background-color: #f2f2f2; }
        .relatorios-table .actions-cell button { font-size: 0.8rem; padding: 0.3rem 0.6rem; margin-right: 5px; }
        .relatorios-table .comentario-coord-cell { font-style: italic; color: #5D3A9A; font-size: 0.85rem; }
        .material-link { color: #208A87; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 700px; border-radius: 8px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .modal-close-button { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-close-button:hover { color: black; }
        .modal-content h4 { margin-top: 0; color: #186D6A; font-size: 1.5rem; }
        .report-detail { margin-bottom: 0.8rem; }
        .report-detail strong { color: #333; }
        .report-detail p, .report-detail div { white-space: pre-wrap; background: #f9f9f9; padding: 10px; border-radius:4px; border:1px solid #eee; }
        .modal-content textarea { width: calc(100% - 22px); min-height: 100px; padding: 10px; margin-top:10px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px; }
        .modal-content button#salvarComentarioBtn { background-color: #5D3A9A; color:white; padding:10px 15px; border:none; border-radius:4px; cursor:pointer; }
        #comentarioStatus { font-size:0.9em; margin-top:10px; }
    </style>
</head>
<body>
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Relatórios de Aula (Coordenação)</h1>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit"><i class="fas fa-sign-out-alt"></i> Sair</button></form>
    </header>
    <div class="container">
        <nav class="sidebar" id="sidebar"><?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?></nav>
        <main class="main-content">
            <section class="dashboard-section">
                <h3>Filtrar Relatórios</h3>
                <form method="GET" action="visualizar_relatorios_coordenacao.php" class="filter-form">
                    <div class="form-group">
                        <label for="professor_id">Professor:</label>
                        <select name="professor_id" id="professor_id"><option value="">Todos</option>
                            <?php while($p = mysqli_fetch_assoc($professores)): ?><option value="<?php echo $p['id']; ?>" <?php if(isset($_GET['professor_id']) && $_GET['professor_id'] == $p['id']) echo 'selected'; ?>><?php echo htmlspecialchars($p['nome']); ?></option><?php endwhile; mysqli_data_seek($professores,0); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="turma_id">Turma:</label>
                        <select name="turma_id" id="turma_id"><option value="">Todas</option>
                            <?php while($tu = mysqli_fetch_assoc($turmas)): ?><option value="<?php echo $tu['id']; ?>" <?php if(isset($_GET['turma_id']) && $_GET['turma_id'] == $tu['id']) echo 'selected'; ?>><?php echo htmlspecialchars($tu['nome_turma']); ?></option><?php endwhile; mysqli_data_seek($turmas,0); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="disciplina_id">Disciplina:</label>
                        <select name="disciplina_id" id="disciplina_id"><option value="">Todas</option>
                            <?php while($d = mysqli_fetch_assoc($disciplinas)): ?><option value="<?php echo $d['id']; ?>" <?php if(isset($_GET['disciplina_id']) && $_GET['disciplina_id'] == $d['id']) echo 'selected'; ?>><?php echo htmlspecialchars($d['nome_disciplina']); ?></option><?php endwhile; mysqli_data_seek($disciplinas,0); ?>
                        </select>
                    </div>
                    <div class="form-group"><label for="data_de">De:</label><input type="date" name="data_de" id="data_de" value="<?php echo isset($_GET['data_de']) ? htmlspecialchars($_GET['data_de']) : ''; ?>"></div>
                    <div class="form-group"><label for="data_ate">Até:</label><input type="date" name="data_ate" id="data_ate" value="<?php echo isset($_GET['data_ate']) ? htmlspecialchars($_GET['data_ate']) : ''; ?>"></div>
                    <button type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="visualizar_relatorios_coordenacao.php" class="clear-filter"><i class="fas fa-times"></i> Limpar</a>
                </form>
            </section>

            <section class="dashboard-section">
                <h3>Relatórios Recebidos</h3>
                <?php if($relatorios_result && mysqli_num_rows($relatorios_result) > 0): ?>
                <table class="relatorios-table">
                    <thead><tr><th>Data Aula</th><th>Professor</th><th>Turma</th><th>Disciplina</th><th>Conteúdo (Início)</th><th>Material</th><th>Comentário Coord.</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php while($rel = mysqli_fetch_assoc($relatorios_result)): ?>
                        <tr>
                            <td><?php echo date("d/m/Y", strtotime($rel['data_aula'])); ?></td>
                            <td><?php echo htmlspecialchars($rel['nome_professor']); ?></td>
                            <td><?php echo htmlspecialchars($rel['nome_turma']); ?></td>
                            <td><?php echo htmlspecialchars($rel['nome_disciplina']); ?></td>
                            <td><?php echo htmlspecialchars(mb_substr($rel['conteudo_lecionado'], 0, 40)) . (mb_strlen($rel['conteudo_lecionado']) > 40 ? '...' : ''); ?></td>
                            <td><?php if(!empty($rel['material_aula_path'])): ?><a href="<?php echo htmlspecialchars($rel['material_aula_path']); ?>" target="_blank" class="material-link"><i class="fas fa-paperclip"></i> Ver</a><?php else: ?>-<?php endif; ?></td>
                            <td class="comentario-coord-cell"><?php echo !empty($rel['comentario_coordenacao']) ? (htmlspecialchars(mb_substr($rel['comentario_coordenacao'], 0, 30)) . (mb_strlen($rel['comentario_coordenacao']) > 30 ? '...' : '')) : 'Nenhum'; ?></td>
                            <td class="actions-cell">
                                <button onclick='abrirModalRelatorio(<?php echo json_encode($rel, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-eye"></i> Detalhes/Comentar</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?><p class="no-data-message">Nenhum relatório encontrado com os filtros aplicados ou nenhum relatório enviado.</p><?php endif; ?>
            </section>
        </main>
    </div>

    <div id="relatorioModal" class="modal">
        <div class="modal-content">
            <span class="modal-close-button" onclick="document.getElementById('relatorioModal').style.display='none'">&times;</span>
            <h4>Detalhes do Relatório de Aula</h4>
            <div id="modalReportDetails">
                <p class="report-detail"><strong>Professor:</strong> <span id="modalProfNome"></span></p>
                <p class="report-detail"><strong>Turma:</strong> <span id="modalTurmaNome"></span></p>
                <p class="report-detail"><strong>Disciplina:</strong> <span id="modalDisciplinaNome"></span></p>
                <p class="report-detail"><strong>Data da Aula:</strong> <span id="modalDataAula"></span></p>
                <p class="report-detail"><strong>Conteúdo Lecionado:</strong></p>
                <div id="modalConteudo"></div>
                <p class="report-detail"><strong>Observações do Professor:</strong></p>
                <div id="modalObsProfessor"></div>
                <p class="report-detail"><strong>Material Anexado:</strong> <span id="modalMaterialLink"></span></p>
            </div>
            <hr>
            <h4>Comentário da Coordenação</h4>
            <form id="comentarioForm">
                <input type="hidden" name="relatorio_id_comentario" id="relatorio_id_comentario">
                <textarea name="comentario_coordenacao_texto" id="comentario_coordenacao_texto" placeholder="Adicione seu comentário aqui..."></textarea>
                <button type="button" id="salvarComentarioBtn" onclick="salvarComentario()">Salvar Comentário</button>
                <div id="comentarioStatus"></div>
            </form>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById('menu-toggle'); /* ... script do menu ... */
        if(menuToggle) menuToggle.addEventListener('click', function() { /*...*/ });

        const relatorioModal = document.getElementById('relatorioModal');
        const spanClose = relatorioModal.querySelector('.modal-close-button');

        function abrirModalRelatorio(relatorioData) {
            document.getElementById('modalProfNome').textContent = relatorioData.nome_professor;
            document.getElementById('modalTurmaNome').textContent = relatorioData.nome_turma;
            document.getElementById('modalDisciplinaNome').textContent = relatorioData.nome_disciplina;
            document.getElementById('modalDataAula').textContent = new Date(relatorioData.data_aula + 'T00:00:00').toLocaleDateString('pt-BR'); // Ajustar fuso se necessário
            document.getElementById('modalConteudo').textContent = relatorioData.conteudo_lecionado;
            document.getElementById('modalObsProfessor').textContent = relatorioData.observacoes || 'Nenhuma.';
            
            const materialLinkEl = document.getElementById('modalMaterialLink');
            if (relatorioData.material_aula_path) {
                materialLinkEl.innerHTML = `<a href="${relatorioData.material_aula_path}" target="_blank" class="material-link"><i class="fas fa-paperclip"></i> Visualizar Material</a>`;
            } else {
                materialLinkEl.textContent = 'Nenhum material anexado.';
            }

            document.getElementById('relatorio_id_comentario').value = relatorioData.relatorio_id;
            document.getElementById('comentario_coordenacao_texto').value = relatorioData.comentario_coordenacao || '';
            document.getElementById('comentarioStatus').textContent = ''; // Limpa status anterior
            if(relatorioData.data_comentario_coordenacao && relatorioData.nome_coordenador_comentario) {
                 document.getElementById('comentarioStatus').textContent = `Último comentário por ${relatorioData.nome_coordenador_comentario} em ${new Date(relatorioData.data_comentario_coordenacao).toLocaleString('pt-BR')}.`;
            }

            relatorioModal.style.display = 'block';
        }

        spanClose.onclick = function() { relatorioModal.style.display = 'none'; }
        window.onclick = function(event) { if (event.target == relatorioModal) { relatorioModal.style.display = 'none'; } }

        function salvarComentario() {
            const form = document.getElementById('comentarioForm');
            const formData = new FormData(form);
            const statusDiv = document.getElementById('comentarioStatus');
            statusDiv.textContent = 'Salvando...';

            fetch('salvar_comentario_relatorio_coordenacao.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    statusDiv.textContent = 'Comentário salvo com sucesso! Recarregando a página...';
                    statusDiv.style.color = 'green';
                    setTimeout(() => { window.location.reload(); }, 1500);
                } else {
                    statusDiv.textContent = 'Erro: ' + (data.message || 'Não foi possível salvar o comentário.');
                    statusDiv.style.color = 'red';
                }
            })
            .catch(error => {
                console.error('Erro no fetch:', error);
                statusDiv.textContent = 'Erro de comunicação ao salvar o comentário.';
                statusDiv.style.color = 'red';
            });
        }
    </script>
</body>
</html>
<?php 
if(isset($stmt_relatorios)) mysqli_stmt_close($stmt_relatorios); 
if($conn) mysqli_close($conn); 
?>