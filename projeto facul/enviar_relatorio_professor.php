<?php
session_start(); // GARANTIR que está no topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];
$currentPageIdentifier = 'relatorios'; // Para a sidebar

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

// Buscar turmas e disciplinas associadas a este professor
$sql_turmas_disciplinas = "
    SELECT DISTINCT t.id as turma_id, t.nome_turma, d.id as disciplina_id, d.nome_disciplina
    FROM turmas t
    JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id
    JOIN disciplinas d ON d.id = ptd.disciplina_id
    WHERE ptd.professor_id = ?
    ORDER BY t.nome_turma, d.nome_disciplina";

$stmt_assoc = mysqli_prepare($conn, $sql_turmas_disciplinas);
$associacoes = [];
if ($stmt_assoc) {
    mysqli_stmt_bind_param($stmt_assoc, "i", $professor_id);
    mysqli_stmt_execute($stmt_assoc);
    $result_assoc = mysqli_stmt_get_result($stmt_assoc);
    while ($row = mysqli_fetch_assoc($result_assoc)) {
        $associacoes[] = $row;
    }
    mysqli_stmt_close($stmt_assoc);
}

// Buscar relatórios já enviados por este professor
$relatorios_enviados_sql = "
    SELECT r.id, r.data_aula, r.conteudo_lecionado, t.nome_turma, d.nome_disciplina, r.data_envio,
           r.comentario_coordenacao, coord.nome as nome_coordenador_que_comentou
    FROM relatorios_aula r
    JOIN turmas t ON r.turma_id = t.id
    JOIN disciplinas d ON r.disciplina_id = d.id
    LEFT JOIN coordenadores coord ON r.coordenador_id_comentario = coord.id
    WHERE r.professor_id = ?
    ORDER BY r.data_aula DESC, r.data_envio DESC LIMIT 10";
$stmt_relatorios = mysqli_prepare($conn, $relatorios_enviados_sql);
$relatorios_enviados_result = null;
if ($stmt_relatorios) {
    mysqli_stmt_bind_param($stmt_relatorios, "i", $professor_id);
    mysqli_stmt_execute($stmt_relatorios);
    $relatorios_enviados_result = mysqli_stmt_get_result($stmt_relatorios);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Enviar Relatório de Aula - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos inline que você tinha. Mova para css/professor.css ou temas_globais.css */
        .form-section, .list-section { 
            margin-bottom: 2rem; padding: 1.5rem; border-radius: 5px; 
            /* background-color, box-shadow virão do tema ou css/professor.css */
        }
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.5rem; font-weight: bold; }
        .form-section input[type="text"], .form-section input[type="date"],
        .form-section input[type="file"], .form-section textarea,
        .form-section select { 
            width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box;
            /* border, background-color, color virão do tema ou css/professor.css */
        }
        .form-section textarea { min-height: 120px; }
        .form-section button[type="submit"] { 
            padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; 
            font-size: 1rem; margin-top: 1.5rem;
            /* background-color, color virão do tema */
        }
        /* .form-section button[type="submit"]:hover { background-color: #186D6A; -- Virá do tema } */
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; /* Cores virão do tema */ }
        .list-section table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .list-section th, .list-section td { 
            padding: 0.75rem; text-align: left; font-size: 0.9rem; vertical-align: top;
            /* border virá do tema ou css/professor.css */
        }
        /* .list-section th { background-color: #f2f2f2; -- Virá do tema } */
        .list-section .actions a { margin-right: 10px; text-decoration: none; }
        .list-section .comment-cell { font-style: italic; font-size: 0.85em; /* color virá do tema */ }
        .list-section .comment-cell small { /* color virá do tema */ }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Relatórios de Aula (Prof. <?php echo htmlspecialchars($nome_professor); ?>)</h1>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button></form>
    </header>
    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_professor.php';
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>
        <main class="main-content">
            <h2>Enviar Novo Relatório de Aula</h2>
            <?php if(isset($_SESSION['relatorio_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['relatorio_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['relatorio_status_message']); ?>
                </div>
                <?php unset($_SESSION['relatorio_status_message']); unset($_SESSION['relatorio_status_type']); ?>
            <?php endif; ?>

            <section class="form-section">
                <form action="salvar_relatorio_aula.php" method="POST" enctype="multipart/form-data">
                    <label for="data_aula">Data da Aula:</label>
                    <input type="date" id="data_aula" name="data_aula" value="<?php echo date('Y-m-d'); ?>" required>

                    <label for="turma_disciplina_id">Turma e Disciplina:</label>
                    <select id="turma_disciplina_id" name="turma_disciplina_id" required>
                        <option value="">Selecione a Turma e Disciplina</option>
                        <?php if(!empty($associacoes)): ?>
                            <?php foreach($associacoes as $assoc): ?>
                                <option value="<?php echo $assoc['turma_id'] . '-' . $assoc['disciplina_id']; ?>">
                                    <?php echo htmlspecialchars($assoc['nome_turma']) . ' - ' . htmlspecialchars($assoc['nome_disciplina']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Você não está associado a turmas/disciplinas</option>
                        <?php endif; ?>
                    </select>

                    <label for="conteudo_lecionado">Conteúdo Lecionado:</label>
                    <textarea id="conteudo_lecionado" name="conteudo_lecionado" required></textarea>

                    <label for="observacoes">Observações (Opcional):</label>
                    <textarea id="observacoes" name="observacoes"></textarea>

                    <label for="material_aula">Material de Aula (Opcional - PDF, DOC, PPT, etc.):</label>
                    <input type="file" id="material_aula" name="material_aula">

                    <button type="submit"><i class="fas fa-paper-plane"></i> Enviar Relatório</button>
                </form>
            </section>

            <section class="list-section">
                <h3>Seus Últimos Relatórios Enviados</h3>
                <?php if($relatorios_enviados_result && mysqli_num_rows($relatorios_enviados_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Data Aula</th>
                            <th>Turma</th>
                            <th>Disciplina</th>
                            <th>Conteúdo (Início)</th>
                            <th>Enviado em</th>
                            <th>Comentário da Coordenação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($relatorio = mysqli_fetch_assoc($relatorios_enviados_result)): ?>
                        <tr>
                            <td><?php echo date("d/m/Y", strtotime($relatorio['data_aula'])); ?></td>
                            <td><?php echo htmlspecialchars($relatorio['nome_turma']); ?></td>
                            <td><?php echo htmlspecialchars($relatorio['nome_disciplina']); ?></td>
                            <td><?php echo htmlspecialchars(mb_substr($relatorio['conteudo_lecionado'], 0, 50)) . (mb_strlen($relatorio['conteudo_lecionado']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo date("d/m/Y H:i", strtotime($relatorio['data_envio'])); ?></td>
                            <td class="comment-cell">
                                <?php if(!empty($relatorio['comentario_coordenacao'])): ?>
                                    <em><?php echo htmlspecialchars(mb_substr($relatorio['comentario_coordenacao'], 0, 40)); ?>
                                    <?php if(mb_strlen($relatorio['comentario_coordenacao']) > 40) echo "..."; ?>
                                    </em>
                                    <?php if(!empty($relatorio['nome_coordenador_que_comentou'])): ?>
                                        <br><small>(Por: <?php echo htmlspecialchars($relatorio['nome_coordenador_que_comentou']); ?>)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Nenhum relatório enviado por você ainda.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <script> /* Script do menu lateral */ document.getElementById('menu-toggle').addEventListener('click', function() { document.getElementById('sidebar').classList.toggle('hidden'); document.querySelector('.container').classList.toggle('full-width'); }); </script>
</body>
</html>
<?php 
if(isset($stmt_relatorios)) mysqli_stmt_close($stmt_relatorios); 
if(isset($conn) && $conn) mysqli_close($conn); 
?>