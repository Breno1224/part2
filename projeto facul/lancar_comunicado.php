<?php
session_start(); // GARANTIR que está no topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];
$currentPageIdentifier = 'lancar_comunicado'; // Para a sidebar

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

// Buscar turmas para o select (apenas as turmas do professor)
$sql_turmas_prof = "SELECT DISTINCT t.id, t.nome_turma
                    FROM turmas t
                    JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id
                    WHERE ptd.professor_id = ?
                    ORDER BY t.nome_turma";
$stmt_turmas = mysqli_prepare($conn, $sql_turmas_prof);
$turmas_professor_result = [];
if ($stmt_turmas) {
    mysqli_stmt_bind_param($stmt_turmas, "i", $professor_id);
    mysqli_stmt_execute($stmt_turmas);
    $result_turmas_assoc = mysqli_stmt_get_result($stmt_turmas); // Renomeado para evitar conflito com $result_turmas
    while($turma_row = mysqli_fetch_assoc($result_turmas_assoc)){ // Renomeado para evitar conflito com $turma
        $turmas_professor_result[] = $turma_row;
    }
    mysqli_stmt_close($stmt_turmas);
}


// Buscar comunicados já enviados por este professor
$comunicados_enviados_sql = "
    SELECT c.id, c.titulo, c.data_publicacao, c.publico_alvo, t.nome_turma
    FROM comunicados c
    LEFT JOIN turmas t ON c.turma_id = t.id
    WHERE c.professor_id = ? AND c.coordenador_id IS NULL
    ORDER BY c.data_publicacao DESC LIMIT 10";
$stmt_comunicados = mysqli_prepare($conn, $comunicados_enviados_sql);
$comunicados_enviados_result = null; // Inicializar
if($stmt_comunicados){
    mysqli_stmt_bind_param($stmt_comunicados, "i", $professor_id);
    mysqli_stmt_execute($stmt_comunicados);
    $comunicados_enviados_result = mysqli_stmt_get_result($stmt_comunicados);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançar Comunicado - Professor ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos inline. Mova para css/professor.css ou temas_globais.css */
        .form-section, .list-section { 
            margin-bottom: 2rem; padding: 1.5rem; border-radius: 5px; 
            /* background-color, box-shadow virão do tema ou css/professor.css */
        }
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.5rem; font-weight: bold; }
        .form-section input[type="text"],
        .form-section textarea,
        .form-section select {
            width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box;
            /* border, background-color, color virão do tema ou css/professor.css */
        }
        .form-section textarea { min-height: 150px; }
        .form-section button[type="submit"] {
            /* background-color, color virão do tema */
            padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; 
            font-size: 1rem; margin-top: 1.5rem;
        }
        /* .form-section button[type="submit"]:hover { background-color: #186D6A; -- Virá do tema } */
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; /* Cores virão do tema */ }
        .list-section table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .list-section th, .list-section td { 
            padding: 0.75rem; text-align: left; 
            /* border virá do tema ou css/professor.css */
        }
        /* .list-section th { background-color: #f2f2f2; -- Virá do tema } */
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Lançar Comunicado (Prof. <?php echo htmlspecialchars($nome_professor); ?>)</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <h2>Novo Comunicado</h2>

            <?php if(isset($_SESSION['comunicado_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['comunicado_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['comunicado_status_message']); ?>
                </div>
                <?php unset($_SESSION['comunicado_status_message']); unset($_SESSION['comunicado_status_type']); ?>
            <?php endif; ?>

            <section class="form-section">
                <form action="salvar_comunicado.php" method="POST">
                    <label for="titulo">Título do Comunicado:</label>
                    <input type="text" id="titulo" name="titulo" required>

                    <label for="conteudo">Conteúdo:</label>
                    <textarea id="conteudo" name="conteudo" required></textarea>

                    <label for="turma_id">Enviar Para Turma Específica (Opcional):</label>
                    <select id="turma_id" name="turma_id">
                        <option value="">Geral (para alunos de suas turmas)</option>
                        <?php if(!empty($turmas_professor_result)): ?>
                            <?php foreach($turmas_professor_result as $turma_item): ?> 
                                <option value="<?php echo $turma_item['id']; ?>"><?php echo htmlspecialchars($turma_item['nome_turma']); ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Você não está associado a turmas específicas</option>
                        <?php endif; ?>
                    </select>
                    <p style="font-size:0.8em; color:#666;">Se nenhuma turma for selecionada, o comunicado será considerado geral para os alunos das turmas que você leciona.</p>

                    <button type="submit">Publicar Comunicado</button>
                </form>
            </section>

            <section class="list-section">
                <h3>Seus Últimos Comunicados Enviados</h3>
                <?php if($comunicados_enviados_result && mysqli_num_rows($comunicados_enviados_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Público</th>
                            <th>Para Turma</th>
                            <th>Data Publicação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($comunicado = mysqli_fetch_assoc($comunicados_enviados_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comunicado['titulo']); ?></td>
                            <td><?php echo htmlspecialchars(str_replace('_', ' ', $comunicado['publico_alvo'])); ?></td>
                            <td><?php echo htmlspecialchars($comunicado['nome_turma'] ?? 'N/A'); ?></td>
                            <td><?php echo date("d/m/Y H:i", strtotime($comunicado['data_publicacao'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Nenhum comunicado enviado por você ainda.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script>
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const container = document.querySelector('.container');
        if (menuToggle && sidebar && container) {
            menuToggle.addEventListener('click', function () {
                sidebar.classList.toggle('hidden');
                container.classList.toggle('full-width');
            });
        }
    </script>
</body>
</html>
<?php 
if(isset($stmt_comunicados)) mysqli_stmt_close($stmt_comunicados); 
if(isset($conn) && $conn) mysqli_close($conn); 
?>