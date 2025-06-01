<?php
session_start(); // GARANTIR que está no topo absoluto
// Verifica se o usuário é um aluno logado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$nome_aluno = $_SESSION['usuario_nome'];
$aluno_id = $_SESSION['usuario_id'];
$turma_id_aluno = isset($_SESSION['turma_id']) ? intval($_SESSION['turma_id']) : 0;

// Define o identificador da página atual para a sidebar
$currentPageIdentifier = 'materiais';

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

$materiais_por_disciplina = [];

// SQL para buscar materiais:
$sql_materiais = "
    SELECT 
        m.titulo, 
        m.descricao, 
        m.arquivo_path_ou_link, 
        m.tipo_material, 
        d.nome_disciplina,
        CASE 
            WHEN m.arquivo_path_ou_link LIKE 'http%' OR m.arquivo_path_ou_link LIKE 'https%' THEN 0 
            ELSE 1 
        END as is_download,
        CASE
            WHEN LOWER(m.tipo_material) LIKE '%pdf%' THEN 'fas fa-file-pdf'
            WHEN LOWER(m.tipo_material) LIKE '%vídeo%' OR LOWER(m.tipo_material) LIKE '%video%' THEN 'fas fa-video'
            WHEN LOWER(m.tipo_material) LIKE '%apresentação%' OR LOWER(m.tipo_material) LIKE '%powerpoint%' OR LOWER(m.tipo_material) LIKE '%slide%' THEN 'fas fa-file-powerpoint'
            WHEN LOWER(m.tipo_material) LIKE '%documento%' OR LOWER(m.tipo_material) LIKE '%word%' THEN 'fas fa-file-word'
            WHEN LOWER(m.tipo_material) LIKE '%planilha%' OR LOWER(m.tipo_material) LIKE '%excel%' THEN 'fas fa-file-excel'
            WHEN LOWER(m.tipo_material) LIKE '%link%' OR LOWER(m.tipo_material) LIKE '%artigo online%' THEN 'fas fa-link'
            ELSE 'fas fa-file'
        END as icon_class
    FROM materiais_didaticos m
    JOIN disciplinas d ON m.disciplina_id = d.id
    WHERE (m.turma_id = ? OR m.turma_id IS NULL) 
    ORDER BY d.nome_disciplina, m.data_upload DESC";

$stmt = mysqli_prepare($conn, $sql_materiais);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $turma_id_aluno);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $materiais_por_disciplina[$row['nome_disciplina']][] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Erro ao preparar a query de materiais: " . mysqli_error($conn));
}
// mysqli_close($conn); // Fechar no final do script HTML

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Materiais Didáticos - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos inline que você tinha. Mova para css/aluno.css ou temas_globais.css */
        .main-content h2.page-title-materiais { /* Nome de classe específico para o título desta página */
            margin-bottom: 1.5rem; text-align: center; 
            /* color virá do tema ou aluno.css */
        }
        .disciplina-materiais { 
            margin-bottom: 2rem; padding-bottom: 1rem; 
            /* border-bottom virá do tema ou aluno.css */
        }
        .disciplina-materiais:last-child { border-bottom: none; }
        .disciplina-materiais h3 { 
            font-size: 1.4rem; margin-bottom: 1rem; padding-bottom: 0.5rem; 
            display: inline-block;
            /* color, border-bottom virão do tema ou aluno.css */
        }
        .material-item { 
            padding: 15px; margin-bottom: 15px; border-radius: 5px; 
            /* background-color, border, box-shadow virão do tema ou aluno.css */
        }
        .material-item h4 { 
            font-size: 1.2rem; margin-bottom: 0.5rem; 
            /* color virá do tema */
        }
        .material-item h4 i { 
            margin-right: 8px; 
            /* color: #208A87; -- Cor do ícone pode ser herdada ou vir do tema */
        } 
        .material-item p { 
            font-size: 0.95rem; margin-bottom: 1rem; line-height: 1.6; 
            /* color virá do tema */
        }
        .btn-material { 
            display: inline-block; padding: 8px 15px; 
            text-decoration: none; border-radius: 4px; font-size: 0.9rem; 
            transition: background-color 0.3s;
            /* background-color, color virão do tema para botões */
        }
        /* .btn-material:hover { background-color: #186D6A; -- Virá do tema } */
        .btn-material i { margin-right: 5px; } 
        .no-materials { 
            text-align: center; padding: 20px; font-size: 1.1rem; 
            /* color virá do tema */
        }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Materiais Didáticos</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php
            // Incluindo a sidebar padronizada do aluno
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php';
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <div style="text-align:center;">
                <h2 class="page-title-materiais">Materiais Didáticos Disponíveis</h2>
            </div>

            <?php if (empty($materiais_por_disciplina)): ?>
                <p class="no-materials">Nenhum material didático disponível para sua turma no momento.</p>
            <?php else: ?>
                <?php foreach ($materiais_por_disciplina as $disciplina => $materiais): ?>
                    <section class="disciplina-materiais dashboard-section"> 
                        <h3><?php echo htmlspecialchars($disciplina); ?></h3>
                        <?php if (empty($materiais)): ?>
                            <p>Nenhum material para esta disciplina.</p>
                        <?php else: ?>
                            <?php foreach ($materiais as $material): ?>
                                <div class="material-item"> 
                                    <h4><i class="<?php echo htmlspecialchars($material['icon_class']); ?>"></i> <?php echo htmlspecialchars($material['titulo']); ?></h4>
                                    <p><?php echo nl2br(htmlspecialchars($material['descricao'])); ?></p>
                                    <a href="<?php echo htmlspecialchars($material['arquivo_path_ou_link']); ?>" 
                                       class="btn-material" 
                                       target="_blank" <?php if ($material['is_download']): echo ' download '; endif; ?>>
                                        <?php echo htmlspecialchars($material['is_download'] ? 'Baixar' : 'Acessar'); ?> <?php echo htmlspecialchars($material['tipo_material']); ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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