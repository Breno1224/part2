<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
$nome_coordenador = $_SESSION['usuario_nome'];
$currentPageIdentifier = 'painel_coordenacao';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel da Coordenação - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Estilos do dashboard do professor podem ser adaptados aqui */
        .main-content .welcome-message-coordenador { text-align: left; font-size: 1.6rem; color: #333; margin-bottom: 1.5rem; font-weight: 500; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .dashboard-card { background-color: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 1.5rem; text-align: center; text-decoration: none; color: #333; transition: transform 0.2s, box-shadow 0.2s; }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
        .dashboard-card i { font-size: 3rem; margin-bottom: 1rem; display: block; }
        .dashboard-card span { font-size: 1.1rem; font-weight: bold; }
        /* Cores para os cards */
        .card-aluno { color: #208A87; } /* Ciano */
        .card-professor { color: #D69D2A; } /* Mostarda */
        .card-comunicado { color: #5D3A9A; } /* Roxo */
        .card-turma { color: #C54B6C; } /* Rosa */
        .card-disciplina { color: #28a745; } /* Verde */
    </style>
</head>
<body>
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Painel da Coordenação</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>
    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?>
        </nav>
        <main class="main-content">
            <div class="welcome-message-coordenador">
                Bem-vindo(a), Coordenador(a) <?php echo htmlspecialchars($nome_coordenador); ?>!
            </div>
            <div class="dashboard-grid">
                <a href="coordenacao_add_aluno.php" class="dashboard-card card-aluno">
                    <i class="fas fa-user-plus"></i>
                    <span>Adicionar Aluno</span>
                </a>
                <a href="coordenacao_add_professor.php" class="dashboard-card card-professor">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Adicionar Professor</span>
                </a>
                <a href="coordenacao_ver_alunos.php" class="dashboard-card card-aluno">
                    <i class="fas fa-users"></i>
                    <span>Visualizar Alunos</span>
                </a>
                <a href="coordenacao_ver_professores.php" class="dashboard-card card-professor">
                    <i class="fas fa-user-tie"></i>
                    <span>Visualizar Professores</span>
                </a>
                <a href="coordenacao_lancar_comunicado.php" class="dashboard-card card-comunicado">
                    <i class="fas fa-bullhorn"></i>
                    <span>Enviar Comunicado</span>
                </a>
                <a href="coordenacao_gerenciar_turmas.php" class="dashboard-card card-turma">
                    <i class="fas fa-sitemap"></i>
                    <span>Gerenciar Turmas</span>
                </a>
                <a href="coordenacao_gerenciar_disciplinas.php" class="dashboard-card card-disciplina">
                    <i class="fas fa-book"></i>
                    <span>Gerenciar Disciplinas</span>
                </a>
                </div>
        </main>
    </div>
    <script>
        // Script do menu lateral
        document.getElementById('menu-toggle').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('hidden');
            document.querySelector('.container').classList.toggle('full-width');
        });
    </script>
</body>
</html>