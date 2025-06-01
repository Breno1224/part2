<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_coordenador = $_SESSION['usuario_nome'];
$coordenador_id = $_SESSION['usuario_id'];
$currentPageIdentifier = 'comunicados_coord';

$turmas_result = mysqli_query($conn, "SELECT id, nome_turma FROM turmas ORDER BY nome_turma");

// Buscar comunicados já enviados por esta coordenação
$comunicados_enviados_sql = "
    SELECT c.id, c.titulo, c.data_publicacao, c.publico_alvo, t.nome_turma
    FROM comunicados c
    LEFT JOIN turmas t ON c.turma_id = t.id
    WHERE c.coordenador_id = ?
    ORDER BY c.data_publicacao DESC LIMIT 10";
$stmt_com = mysqli_prepare($conn, $comunicados_enviados_sql);
mysqli_stmt_bind_param($stmt_com, "i", $coordenador_id);
mysqli_stmt_execute($stmt_com);
$comunicados_enviados_result = mysqli_stmt_get_result($stmt_com);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Enviar Comunicado - Coordenação ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Estilos da página coordenacao_add_aluno podem ser adaptados */
        .form-section, .list-section { margin-bottom: 2rem; padding: 1.5rem; background-color: #fff; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.5rem; font-weight: bold; }
        .form-section input[type="text"], .form-section textarea, .form-section select {
            width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        .form-section textarea { min-height: 150px; }
        .form-section button[type="submit"] { background-color: #5D3A9A; /* Cor coordenação */ color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 1.5rem; }
        .form-section button[type="submit"]:hover { background-color: #4a2d7d; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .status-success { background-color: #d4edda; color: #155724; } .status-error { background-color: #f8d7da; color: #721c24; }
        .list-section table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .list-section th, .list-section td { padding: 0.75rem; border: 1px solid #ddd; text-align: left; }
        .list-section th { background-color: #f2f2f2; }
        #turma_select_div { display: none; } /* Ocultar inicialmente */
    </style>
</head>
<body>
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Enviar Comunicado (Coordenação)</h1>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit"><i class="fas fa-sign-out-alt"></i> Sair</button></form>
    </header>
    <div class="container">
        <nav class="sidebar" id="sidebar"><?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?></nav>
        <main class="main-content">
            <h2>Novo Comunicado da Coordenação</h2>
            <?php if(isset($_SESSION['comunicado_coord_status_message'])): ?>
                <div class="status-message <?php echo $_SESSION['comunicado_coord_status_type']; ?>"><?php echo $_SESSION['comunicado_coord_status_message']; ?></div>
                <?php unset($_SESSION['comunicado_coord_status_message']); unset($_SESSION['comunicado_coord_status_type']); ?>
            <?php endif; ?>

            <section class="form-section">
                <form action="salvar_comunicado_coordenacao.php" method="POST">
                    <label for="titulo">Título do Comunicado:</label>
                    <input type="text" id="titulo" name="titulo" required>

                    <label for="conteudo">Conteúdo:</label>
                    <textarea id="conteudo" name="conteudo" required></textarea>

                    <label for="publico_alvo_select">Enviar Para:</label>
                    <select id="publico_alvo_select" name="publico_alvo_select" required onchange="toggleTurmaSelect()">
                        <option value="">Selecione o Público</option>
                        <option value="TODOS_ALUNOS">Alunos (Geral - Todas as Turmas)</option>
                        <option value="TURMA_ESPECIFICA_ALUNOS">Alunos (Turma Específica)</option>
                        <option value="TODOS_PROFESSORES">Professores (Todos)</option>
                    </select>

                    <div id="turma_select_div">
                        <label for="turma_id">Selecione a Turma (para alunos):</label>
                        <select id="turma_id" name="turma_id">
                            <option value="">Selecione a Turma</option>
                            <?php mysqli_data_seek($turmas_result, 0); ?>
                            <?php while($turma = mysqli_fetch_assoc($turmas_result)): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome_turma']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit">Publicar Comunicado</button>
                </form>
            </section>
             <section class="list-section">
                <h3>Comunicados Enviados pela Coordenação</h3>
                <?php if(mysqli_num_rows($comunicados_enviados_result) > 0): ?>
                <table>
                    <thead><tr><th>Título</th><th>Público</th><th>Turma</th><th>Data</th></tr></thead>
                    <tbody>
                        <?php while($com = mysqli_fetch_assoc($comunicados_enviados_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($com['titulo']); ?></td>
                            <td><?php echo htmlspecialchars(str_replace('_', ' ', $com['publico_alvo'])); ?></td>
                            <td><?php echo htmlspecialchars($com['nome_turma'] ?? 'N/A'); ?></td>
                            <td><?php echo date("d/m/Y H:i", strtotime($com['data_publicacao'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?><p>Nenhum comunicado enviado por você ainda.</p><?php endif; ?>
            </section>
        </main>
    </div>
    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() { /* ... código do menu ... */ });
        function toggleTurmaSelect() {
            const publicoSelect = document.getElementById('publico_alvo_select');
            const turmaDiv = document.getElementById('turma_select_div');
            const turmaSelect = document.getElementById('turma_id');
            if (publicoSelect.value === 'TURMA_ESPECIFICA_ALUNOS') {
                turmaDiv.style.display = 'block';
                turmaSelect.required = true;
            } else {
                turmaDiv.style.display = 'none';
                turmaSelect.required = false;
                turmaSelect.value = ''; // Limpa seleção de turma
            }
        }
        toggleTurmaSelect(); // Chamar ao carregar a página para estado inicial correto
    </script>
</body>
</html>
<?php mysqli_stmt_close($stmt_com); mysqli_close($conn); ?>