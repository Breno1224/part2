<?php
session_start(); // GARANTIR que está no topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_professor_logado = $_SESSION['usuario_nome'];
$professor_id_logado = $_SESSION['usuario_id'];

// Define o identificador da página atual para a sidebar
$currentPageIdentifier = 'ver_comunicados_prof'; 

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

// Subquery para pegar as turmas do professor logado
$turmas_do_professor_ids = [];
$sql_minhas_turmas = "SELECT DISTINCT turma_id FROM professores_turmas_disciplinas WHERE professor_id = ?";
$stmt_minhas_turmas = mysqli_prepare($conn, $sql_minhas_turmas);
$turmas_ids_string = '0'; // Default para evitar erro SQL se não houver turmas

if ($stmt_minhas_turmas) {
    mysqli_stmt_bind_param($stmt_minhas_turmas, "i", $professor_id_logado);
    mysqli_stmt_execute($stmt_minhas_turmas);
    $result_minhas_turmas = mysqli_stmt_get_result($stmt_minhas_turmas);
    while ($row_turma = mysqli_fetch_assoc($result_minhas_turmas)) {
        $turmas_do_professor_ids[] = $row_turma['turma_id'];
    }
    mysqli_stmt_close($stmt_minhas_turmas);
    if (!empty($turmas_do_professor_ids)) {
        // Garante que os IDs sejam inteiros para segurança na query IN
        $turmas_ids_string = implode(',', array_map('intval', $turmas_do_professor_ids));
    }
}

$sql_comunicados_prof = "
    SELECT 
        c.titulo, c.conteudo, c.data_publicacao, 
        p_remetente.nome as nome_professor_remetente, 
        coord.nome as nome_coordenador_remetente,
        t.nome_turma,
        c.publico_alvo, c.professor_id AS comunicado_professor_id, c.coordenador_id AS comunicado_coordenador_id
    FROM comunicados c
    LEFT JOIN professores p_remetente ON c.professor_id = p_remetente.id
    LEFT JOIN coordenadores coord ON c.coordenador_id = coord.id
    LEFT JOIN turmas t ON c.turma_id = t.id 
    WHERE 
        (c.coordenador_id IS NOT NULL AND c.publico_alvo = 'TODOS_PROFESSORES') OR 
        (c.professor_id = ?) OR 
        (c.publico_alvo = 'TURMA_ESPECIFICA' AND c.turma_id IN (" . $turmas_ids_string . ") AND (c.professor_id != ? OR c.professor_id IS NULL) )
        -- A condição (c.professor_id != ? OR c.professor_id IS NULL) evita mostrar comunicados
        -- para turma específica que o próprio professor logado enviou, pois já são cobertos por (c.professor_id = ?)
        -- Se quiser simplificar e mostrar todos para a turma, incluindo os do próprio professor novamente:
        -- (c.publico_alvo = 'TURMA_ESPECIFICA' AND c.turma_id IN (" . $turmas_ids_string . "))
    ORDER BY c.data_publicacao DESC";

$stmt_prof_com = mysqli_prepare($conn, $sql_comunicados_prof);
$result_comunicados_prof = null; // Inicializar
if($stmt_prof_com){
    mysqli_stmt_bind_param($stmt_prof_com, "ii", $professor_id_logado, $professor_id_logado);
    mysqli_stmt_execute($stmt_prof_com);
    $result_comunicados_prof = mysqli_stmt_get_result($stmt_prof_com);
} else {
    error_log("Erro ao preparar statement para buscar comunicados do professor: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Visualizar Comunicados - Professor ACADMIX</title>
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
        .comunicado-item { 
            /* background-color, border, box-shadow virão do tema ou professor.css */
            border-left-width: 5px; border-left-style: solid; 
            padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 0 5px 5px 0; 
        }
        /* As classes .comunicado-item.coord e .comunicado-item.coord h3 já estão no temas_globais.css */
        .comunicado-item h3 { 
            font-size: 1.3rem; margin-top: 0; margin-bottom: 0.5rem; 
            /* color virá do tema */
        }
        .comunicado-meta { 
            font-size: 0.85rem; margin-bottom: 1rem; 
            /* color virá do tema */
        }
        .comunicado-meta .author, .comunicado-meta .author-coord { font-weight: bold; }
        /* .comunicado-meta .author-coord { color: #5D3A9A; -- Virá do tema para .coord */ }
        .comunicado-conteudo { 
            font-size: 1rem; line-height: 1.6; white-space: pre-wrap; 
            /* color virá do tema */
        }
        .no-comunicados { text-align: center; padding: 20px; font-size: 1.1rem; /* color virá do tema */ }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Quadro de Avisos</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
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
            <div style="text-align: center;">
                <h2 class="page-title">Comunicados Importantes</h2>
            </div>
            <?php if($result_comunicados_prof && mysqli_num_rows($result_comunicados_prof) > 0): ?>
                <?php while($com = mysqli_fetch_assoc($result_comunicados_prof)): ?>
                    <?php
                    $remetente_display = ""; 
                    $classe_css_remetente = ""; 
                    
                    if (!empty($com['comunicado_coordenador_id'])) {
                        $remetente_display = "Coordenação (" . htmlspecialchars($com['nome_coordenador_remetente'] ?? 'N/A') . ")";
                        $classe_css_remetente = "coord"; // Esta classe será usada pelo temas_globais.css
                    } elseif (!empty($com['comunicado_professor_id'])) {
                        if ($com['comunicado_professor_id'] == $professor_id_logado) {
                             $remetente_display = "Você";
                        } else {
                             $remetente_display = "Prof. " . htmlspecialchars($com['nome_professor_remetente'] ?? 'N/A');
                        }
                    } else {
                        $remetente_display = "Sistema"; // Fallback improvável
                    }

                    $publico_display = "";
                     if ($com['publico_alvo'] === 'TODOS_PROFESSORES') {
                        $publico_display = "Todos os Professores";
                    } elseif ($com['publico_alvo'] === 'TURMA_ESPECIFICA' && !empty($com['nome_turma'])) {
                        $publico_display = htmlspecialchars($com['nome_turma']);
                    } elseif ($com['publico_alvo'] === 'PROFESSOR_GERAL_ALUNOS') {
                         $publico_display = "Alunos (Geral do Remetente)";
                    } elseif ($com['publico_alvo'] === 'TODOS_ALUNOS') { // Geralmente da coordenação
                        $publico_display = "Alunos (Geral da Escola)";
                    }
                    ?>
                    <article class="comunicado-item <?php echo $classe_css_remetente; ?>">
                        <h3><?php echo htmlspecialchars($com['titulo']); ?></h3>
                        <p class="comunicado-meta">
                            Publicado por: <span class="author <?php if($classe_css_remetente === 'coord') echo 'author-coord'; ?>"><?php echo $remetente_display; ?></span> |
                            Em: <?php echo date("d/m/Y H:i", strtotime($com['data_publicacao'])); ?>
                            <?php if(!empty($publico_display)): ?> | Para: <span class="target-turma"><?php echo $publico_display; ?></span><?php endif; ?>
                        </p>
                        <div class="comunicado-conteudo"><?php echo nl2br(htmlspecialchars($com['conteudo'])); ?></div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-comunicados">Nenhum comunicado para visualizar no momento.</p>
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
if(isset($stmt_prof_com)) mysqli_stmt_close($stmt_prof_com); 
if(isset($conn) && $conn) mysqli_close($conn); 
?>