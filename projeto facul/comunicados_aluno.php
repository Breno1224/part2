<?php
session_start(); // GARANTIR que está no topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_aluno = $_SESSION['usuario_nome'];
$aluno_id = $_SESSION['usuario_id'];
$turma_id_aluno = isset($_SESSION['turma_id']) ? intval($_SESSION['turma_id']) : 0;
$currentPageIdentifier = 'comunicados_aluno'; // Para a sidebar

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

// Query para buscar comunicados relevantes para o aluno
$sql_comunicados = "
    SELECT 
        c.titulo, 
        c.conteudo, 
        c.data_publicacao, 
        c.publico_alvo,
        c.turma_id AS comunicado_turma_id,
        p.nome as nome_professor, 
        coord.nome as nome_coordenador,
        t.nome_turma 
    FROM comunicados c
    LEFT JOIN professores p ON c.professor_id = p.id
    LEFT JOIN coordenadores coord ON c.coordenador_id = coord.id
    LEFT JOIN turmas t ON c.turma_id = t.id 
    WHERE 
        (c.coordenador_id IS NOT NULL AND c.publico_alvo = 'TODOS_ALUNOS') 
        OR
        (c.coordenador_id IS NOT NULL AND c.publico_alvo = 'TURMA_ESPECIFICA' AND c.turma_id = ?)
        OR
        (c.professor_id IS NOT NULL AND c.publico_alvo = 'TURMA_ESPECIFICA' AND c.turma_id = ?)
    ORDER BY c.data_publicacao DESC";

$stmt = mysqli_prepare($conn, $sql_comunicados);
$result_comunicados = null; // Inicializar
if ($stmt) { // Verificar se a preparação foi bem-sucedida
    mysqli_stmt_bind_param($stmt, "ii", $turma_id_aluno, $turma_id_aluno); 
    mysqli_stmt_execute($stmt);
    $result_comunicados = mysqli_stmt_get_result($stmt);
} else {
    error_log("Erro ao preparar statement para buscar comunicados do aluno: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Comunicados - ACADMIX</title>
    <link rel="stylesheet" href="css/aluno.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos inline que você tinha. Mova para css/aluno.css ou temas_globais.css */
        .main-content h2.page-title { 
            text-align: center; font-size: 1.8rem; margin-bottom: 2rem; 
            padding-bottom: 0.5rem; display: inline-block;
            /* color e border-bottom virão do tema ou aluno.css */
        }
        .comunicado-item {
            /* background-color, border, box-shadow virão do tema ou aluno.css */
            border-left-width: 5px; border-left-style: solid; 
            padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 0 5px 5px 0; 
        }
        /* A classe .comunicado-item.coord e seus estilos para h3 e .author
           devem estar em temas_globais.css para serem aplicados corretamente pelo tema */
        
        .comunicado-item h3 {
            font-size: 1.3rem; margin-top: 0; margin-bottom: 0.5rem;
            /* color virá do tema */
        }
        .comunicado-meta {
            font-size: 0.85rem; margin-bottom: 1rem; 
            /* color virá do tema */
        }
        .comunicado-meta .author, .comunicado-meta .target-turma {
            font-weight: bold;
        }
        .comunicado-conteudo {
            font-size: 1rem; line-height: 1.6; white-space: pre-wrap; 
            /* color virá do tema */
        }
        .no-comunicados {
            text-align: center; padding: 20px; font-size: 1.1rem; 
            /* color virá do tema */
        }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Comunicados</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php'; 
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <div style="text-align: center;">
                <h2 class="page-title">Quadro de Avisos</h2>
            </div>

            <?php if($result_comunicados && mysqli_num_rows($result_comunicados) > 0): ?>
                <?php while($comunicado = mysqli_fetch_assoc($result_comunicados)): ?>
                    <?php
                    $remetente_display = "";
                    $classe_extra_css = ""; 
                    $publico_display = "";

                    if (!empty($comunicado['coordenador_id'])) {
                        $remetente_display = "Coordenação (" . htmlspecialchars($comunicado['nome_coordenador'] ?? 'N/A') . ")";
                        $classe_extra_css = "coord-comunicado"; 
                    } elseif (!empty($comunicado['professor_id'])) {
                        $remetente_display = "Prof. " . htmlspecialchars($comunicado['nome_professor'] ?? 'N/A');
                    } else {
                        $remetente_display = "Sistema"; // Fallback
                    }

                    if ($comunicado['publico_alvo'] === 'TODOS_ALUNOS') {
                        $publico_display = "Alunos (Geral)";
                    } elseif ($comunicado['publico_alvo'] === 'TURMA_ESPECIFICA' && !empty($comunicado['nome_turma'])) {
                        $publico_display = htmlspecialchars($comunicado['nome_turma']);
                    } elseif ($comunicado['publico_alvo'] === 'PROFESSOR_GERAL_ALUNOS') {
                        $publico_display = "Alunos do Professor (Geral)";
                    }
                    ?>
                    <article class="comunicado-item <?php echo $classe_extra_css; ?>">
                        <h3><?php echo htmlspecialchars($comunicado['titulo']); ?></h3>
                        <p class="comunicado-meta">
                            Publicado por: <span class="author"><?php echo $remetente_display; ?></span> | 
                            Em: <?php echo date("d/m/Y H:i", strtotime($comunicado['data_publicacao'])); ?>
                            <?php if(!empty($publico_display)): ?>
                                | Para: <span class="target-turma"><?php echo $publico_display; ?></span>
                            <?php endif; ?>
                        </p>
                        <div class="comunicado-conteudo">
                            <?php echo nl2br(htmlspecialchars($comunicado['conteudo'])); ?>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-comunicados">Nenhum comunicado disponível para você no momento.</p>
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
<?php 
if(isset($stmt)) mysqli_stmt_close($stmt); 
if(isset($conn) && $conn) mysqli_close($conn); 
?>