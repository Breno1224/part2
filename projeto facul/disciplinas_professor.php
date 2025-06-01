<?php
session_start(); // GARANTIR que está no topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];
$currentPageIdentifier = 'disciplinas'; // Para a sidebar

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

// Buscar as associações de turmas e disciplinas para este professor
$sql_associacoes = "
    SELECT 
        t.id as turma_id, 
        t.nome_turma, 
        d.id as disciplina_id, 
        d.nome_disciplina
        -- , d.ementa -- Descomente se você adicionou a coluna ementa
    FROM turmas t
    JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id
    JOIN disciplinas d ON d.id = ptd.disciplina_id
    WHERE ptd.professor_id = ?
    ORDER BY t.nome_turma, d.nome_disciplina";

$stmt_assoc = mysqli_prepare($conn, $sql_associacoes);
$disciplinas_por_turma = [];

if ($stmt_assoc) {
    mysqli_stmt_bind_param($stmt_assoc, "i", $professor_id);
    mysqli_stmt_execute($stmt_assoc);
    $result_assoc = mysqli_stmt_get_result($stmt_assoc);
    while ($row = mysqli_fetch_assoc($result_assoc)) {
        $disciplinas_por_turma[$row['nome_turma']][] = $row;
    }
    mysqli_stmt_close($stmt_assoc);
} else {
    error_log("Erro ao buscar disciplinas e turmas do professor: " . mysqli_error($conn));
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minhas Disciplinas - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos inline que você tinha. Mova para css/professor.css ou temas_globais.css */
        .main-content h2.page-title {
            text-align: center; font-size: 1.8rem; margin-bottom: 2rem;
            padding-bottom: 0.5rem; display: inline-block;
            /* color, border-bottom virão do tema ou professor.css */
        }
        .turma-section {
            padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;
            /* background-color, box-shadow virão do tema ou professor.css */
        }
        .turma-section h3 { 
            font-size: 1.6rem; margin-top: 0; margin-bottom: 1.5rem;
            padding-bottom: 0.5rem; 
            /* color, border-bottom virão do tema ou professor.css */
        }
        .disciplina-card {
            border-left-style: solid; border-left-width: 5px; 
            padding: 1rem; margin-bottom: 1rem; border-radius: 0 5px 5px 0;
            /* background-color, border, border-left-color virão do tema ou professor.css */
        }
        .disciplina-card h4 { 
            font-size: 1.25rem; margin-top: 0; margin-bottom: 0.75rem;
            /* color virá do tema */
        }
        .disciplina-ementa {
            font-size: 0.9rem; margin-bottom: 1rem; padding-left: 1rem;
            border-left-style: solid; border-left-width: 2px;
            /* color, border-left-color virão do tema ou professor.css */
        }
        .disciplina-ementa p { margin: 0.5rem 0; }
        .disciplina-actions a {
            display: inline-block; margin-right: 10px; margin-bottom: 5px; 
            padding: 0.4rem 0.8rem; font-size: 0.85rem; text-decoration: none;
            color: white; /* Cor do texto do botão, pode ser sobrescrita pelo tema específico do botão */
            border-radius: 4px; transition: opacity 0.2s;
        }
        .disciplina-actions a:hover { opacity: 0.85; }
        /* As classes action-* definem o background-color e devem ser mantidas se não forem
           cobertas por uma regra de botão mais genérica nos temas. */
        .action-notas { background-color: #28a745; } 
        .action-frequencia { background-color: #ffc107; color: #333 !important; } 
        .action-materiais { background-color: #17a2b8; } 
        .action-relatorios { background-color: #6f42c1; } 
        .no-data-message {
            padding: 1rem; text-align: center; border-radius: 4px;
            /* color, background-color virão do tema ou professor.css */
        }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Minhas Disciplinas (Prof. <?php echo htmlspecialchars($nome_professor); ?>)</h1>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button></form>
    </header>

    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <div style="text-align: center;">
                <h2 class="page-title">Minhas Disciplinas e Turmas</h2>
            </div>

            <?php if (empty($disciplinas_por_turma)): ?>
                <p class="no-data-message">Você não está associado a nenhuma disciplina ou turma no momento.</p>
            <?php else: ?>
                <?php foreach ($disciplinas_por_turma as $nome_turma_key => $disciplinas_da_turma_array): ?>
                    <section class="turma-section">
                        <h3><i class="fas fa-users-class"></i> Turma: <?php echo htmlspecialchars($nome_turma_key); ?></h3>
                        <?php foreach ($disciplinas_da_turma_array as $disciplina_info): ?>
                            <div class="disciplina-card">
                                <h4><i class="fas fa-book-reader"></i> <?php echo htmlspecialchars($disciplina_info['nome_disciplina']); ?></h4>
                                
                                <?php /* if (!empty($disciplina_info['ementa'])): // Descomente se adicionou a coluna ementa ?>
                                 <div class="disciplina-ementa">
                                     <strong>Ementa:</strong>
                                     <p><?php echo nl2br(htmlspecialchars($disciplina_info['ementa'])); ?></p>
                                 </div>
                                <?php else: ?>
                                 <div class="disciplina-ementa">
                                     <p><em>Ementa não cadastrada para esta disciplina.</em></p>
                                 </div>
                                <?php endif; */ ?>

                                <div class="disciplina-actions">
                                    <a href="lancar-notas.php?turma_id=<?php echo $disciplina_info['turma_id']; ?>&disciplina_id=<?php echo $disciplina_info['disciplina_id']; ?>" class="action-notas" title="Lançar Notas para esta Turma/Disciplina">
                                        <i class="fas fa-edit"></i> Lançar Notas
                                    </a>
                                    <a href="frequencia_professor.php?turma_id=<?php echo $disciplina_info['turma_id']; ?>" class="action-frequencia" title="Registrar Frequência para esta Turma">
                                        <i class="fas fa-user-check"></i> Frequência
                                    </a>
                                    <a href="gerenciar_materiais.php?turma_id=<?php echo $disciplina_info['turma_id']; ?>&disciplina_id=<?php echo $disciplina_info['disciplina_id']; ?>" class="action-materiais" title="Enviar Materiais para esta Turma/Disciplina">
                                        <i class="fas fa-folder-open"></i> Materiais
                                    </a>
                                    <a href="enviar_relatorio_professor.php?turma_id=<?php echo $disciplina_info['turma_id']; ?>&disciplina_id=<?php echo $disciplina_info['disciplina_id']; ?>" class="action-relatorios" title="Ver/Enviar Relatórios para esta Turma/Disciplina">
                                        <i class="fas fa-file-alt"></i> Relatórios
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
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
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>