<?php
session_start(); // GARANTIR que está no topo absoluto

// Verifica se o usuário é um aluno logado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    header("Location: index.html");
    exit();
}
// include 'db.php'; // Descomente se for buscar dados do banco para esta página

$nome_aluno = $_SESSION['usuario_nome'];
$aluno_id = $_SESSION['usuario_id']; // Pode ser útil para buscar dados específicos do aluno
// $turma_id_aluno = $_SESSION['turma_id']; // Pode ser útil

// Define o identificador da página atual para a sidebar
$currentPageIdentifier = 'inicio_noticias'; 

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****


// Dados Estáticos para as Novas Seções:
$acesso_rapido_aluno = [
    ["titulo" => "Meu Boletim", "icone" => "fas fa-graduation-cap", "link" => "boletim.php", "cor" => "#208A87"],
    ["titulo" => "Calendário", "icone" => "fas fa-calendar-alt", "link" => "calendario.php", "cor" => "#D69D2A"],
    ["titulo" => "Materiais", "icone" => "fas fa-book-open", "link" => "materiais.php", "cor" => "#5D3A9A"],
    ["titulo" => "Comunicados", "icone" => "fas fa-bell", "link" => "comunicados_aluno.php", "cor" => "#C54B6C"]
];

$proximas_atividades = [
    ["data" => "2025-06-10", "descricao" => "Entrega do Trabalho de História - Revolução Industrial.", "tipo" => "trabalho"],
    ["data" => "2025-06-15", "descricao" => "Prova Bimestral de Matemática - Unidades 3 e 4.", "tipo" => "prova"],
    ["data" => "2025-06-20", "descricao" => "Apresentação do Seminário de Ciências.", "tipo" => "evento"]
];


// Para este exemplo, usaremos dados estáticos (simulando notícias):
$noticias_static = [
    ["titulo" => "Inscrições para o ENEM 2025 Abertas!", "resumo" => "O período de inscrição para o Exame Nacional do Ensino Médio (ENEM) de 2025 já começou. Não perca o prazo e garanta sua participação no maior vestibular do país...", "link_externo" => "https://www.gov.br/inep/pt-br/areas-de-atuacao/avaliacao-e-exames-educacionais/enem", "imagem_url" => "img/noticias/enem_2025.jpg", "data_publicacao" => "2025-05-28", "categoria" => "ENEM"],
    ["titulo" => "Dicas Essenciais para Organizar sua Rotina de Estudos", "resumo" => "Manter uma rotina de estudos organizada é fundamental para o sucesso acadêmico. Confira dicas práticas sobre como criar um cronograma eficiente...", "link_externo" => "#", "imagem_url" => "img/noticias/rotina_estudos.jpg", "data_publicacao" => "2025-05-25", "categoria" => "Dicas de Estudo"],
    ["titulo" => "Novos Materiais de Matemática Adicionados!", "resumo" => "Professores adicionaram novas videoaulas e listas de exercícios de Matemática na seção de Materiais Didáticos...", "link_externo" => "materiais.php", "imagem_url" => "img/noticias/novos_materiais.jpg", "data_publicacao" => "2025-05-22", "categoria" => "Materiais Didáticos"],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Portal do Aluno - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos inline que você tinha. Mova o máximo possível para css/aluno.css ou temas_globais.css */
        .main-content h2.section-title { /* Título para seções como Acesso Rápido, Próximas Atividades */
            font-size: 1.6rem;
            /* color: #2C1B17; -- Virá do tema */
            margin-top: 2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            /* border-bottom: 2px solid #D69D2A; -- Virá do tema ou aluno.css */
        }
         .main-content h2.page-title { /* Título "Fique por Dentro" */
            text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem;
            padding-bottom: 0.5rem; display: inline-block;
            /* color, border-bottom virão do tema ou aluno.css */
        }
        .welcome-message-aluno {
            text-align: center; font-size: 1.5rem; 
            /* color: #333; -- Virá do tema */
            margin-bottom: 2rem; /* Aumentado para separar do Acesso Rápido */
            font-weight: 500;
        }

        /* Acesso Rápido (similar ao do professor) */
        .quick-access-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .quick-access-card { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.2rem 1rem; border-radius: 8px; text-decoration: none; transition: transform 0.2s, box-shadow 0.2s; min-height: 110px; /* background-color e color virão do tema */ }
        .quick-access-card:hover { transform: translateY(-5px); /* box-shadow virá do tema */ }
        .quick-access-card i { font-size: 2.2rem; margin-bottom: 0.7rem; }
        .quick-access-card span { font-size: 0.95rem; font-weight: bold; text-align: center; }

        /* Próximas Atividades */
        .upcoming-activities ul { list-style: none; padding-left: 0; }
        .upcoming-activities li {
            /* background-color: #f9f9f9; -- Virá do tema */
            padding: 0.8rem 1rem; margin-bottom: 0.7rem; border-radius: 4px;
            /* border, border-left virão do tema ou aluno.css */
            font-size: 0.95rem;
        }
        .upcoming-activities li .activity-date { font-weight: bold; /* color virá do tema */ }
        .upcoming-activities li .activity-tipo-trabalho { color: #17a2b8; } /* Azul Info */
        .upcoming-activities li .activity-tipo-prova { color: #dc3545; } /* Vermelho Perigo */
        .upcoming-activities li .activity-tipo-evento { color: #ffc107; } /* Amarelo Aviso */

        /* Feed de Notícias (estilos anteriores mantidos e ajustados) */
        .news-feed { display: grid; gap: 1.5rem; }
        .news-item { /* background-color, border, box-shadow virão do tema */ border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s ease-in-out; }
        .news-item:hover { transform: translateY(-5px); }
        .news-image-container { width: 100%; max-height: 200px; overflow: hidden; }
        .news-image { width: 100%; height: 100%; object-fit: cover; display: block; }
        .news-content { padding: 1rem 1.5rem 1.5rem 1.5rem; flex-grow: 1; display: flex; flex-direction: column; }
        .news-title { font-size: 1.3rem; /* color virá do tema */ margin-bottom: 0.5rem; }
        .news-meta { font-size: 0.8rem; /* color virá do tema */ margin-bottom: 0.75rem; }
        .news-meta .news-date { font-weight: bold; }
        .news-meta .news-category { /* background-color, color virá do tema */ padding: 0.2rem 0.5rem; border-radius: 4px; }
        .news-summary { font-size: 0.95rem; /* color virá do tema */ line-height: 1.6; margin-bottom: 1rem; flex-grow: 1; }
        .btn-news-readmore { display: inline-block; padding: 0.5rem 1rem; /* background-color, color virão do tema */ text-decoration: none; border-radius: 4px; font-size: 0.9rem; text-align: center; align-self: flex-start; transition: background-color 0.3s; }
        /* .btn-news-readmore:hover { background-color: #C58624; -- Virá do tema } */
        .btn-news-readmore i { margin-left: 5px; }
        .no-news, .no-activities { text-align: center; padding: 30px; font-size: 1.2rem; /* color virá do tema */ }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 

    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Portal do Aluno</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php'; // Usando o include da sidebar do aluno
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                // Fallback caso o arquivo não seja encontrado
                 echo "<ul><li><a href='aluno.php' class='active'><i class='fas fa-home'></i> Início</a></li><li><a href='#'><i class='fas fa-user'></i> Perfil</a></li></ul>"; // Exemplo mínimo
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <div class="welcome-message-aluno">
                <h3>Bem-vindo(a), <?php echo htmlspecialchars($nome_aluno); ?>!</h3>
            </div>

            <section class="dashboard-section quick-access">
                <h2 class="section-title" style="text-align:left; display:block;">Acesso Rápido</h2>
                <div class="quick-access-grid">
                    <?php foreach ($acesso_rapido_aluno as $item): ?>
                        <a href="<?php echo htmlspecialchars($item['link']); ?>" class="quick-access-card" style="background-color: <?php echo htmlspecialchars($item['cor']); ?>; color:white;">
                            <i class="<?php echo htmlspecialchars($item['icone']); ?>"></i>
                            <span><?php echo htmlspecialchars($item['titulo']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="dashboard-section upcoming-activities">
                <h2 class="section-title" style="text-align:left; display:block;">Próximas Atividades e Prazos</h2>
                <?php if (empty($proximas_atividades)): ?>
                    <p class="no-activities">Nenhuma atividade programada por enquanto.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($proximas_atividades as $atividade): ?>
                            <li class="activity-tipo-<?php echo htmlspecialchars($atividade['tipo']); ?>">
                                <span class="activity-date"><i class="fas fa-calendar-day"></i> <?php echo date("d/m/Y", strtotime($atividade['data'])); ?>:</span>
                                <?php echo htmlspecialchars($atividade['descricao']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <div style="text-align: center; margin-top: 2rem;"> 
                 <h2 class="page-title">Fique por Dentro!</h2>
            </div>
            <div class="news-feed">
                <?php if (empty($noticias_static)): ?>
                    <p class="no-news">Nenhuma notícia ou atualização no momento.</p>
                <?php else: ?>
                    <?php foreach ($noticias_static as $noticia): ?>
                        <article class="news-item">
                            <?php if (!empty($noticia['imagem_url']) && file_exists($noticia['imagem_url'])): ?>
                            <div class="news-image-container">
                                <img src="<?php echo htmlspecialchars($noticia['imagem_url']); ?>" alt="Imagem para <?php echo htmlspecialchars($noticia['titulo']); ?>" class="news-image">
                            </div>
                            <?php endif; ?>
                            <div class="news-content">
                                <h3 class="news-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h3>
                                <p class="news-meta">
                                    <span class="news-date"><i class="fas fa-calendar-alt"></i> <?php echo date("d/m/Y", strtotime($noticia['data_publicacao'])); ?></span>
                                    <?php if(!empty($noticia['categoria'])): ?>
                                        | <span class="news-category"><?php echo htmlspecialchars($noticia['categoria']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="news-summary"><?php echo htmlspecialchars($noticia['resumo']); ?></p>
                                <?php if (!empty($noticia['link_externo']) && $noticia['link_externo'] !== '#'): ?>
                                <a href="<?php echo htmlspecialchars($noticia['link_externo']); ?>" class="btn-news-readmore" target="_blank">
                                    Saiba Mais <i class="fas fa-external-link-alt"></i>
                                </a>
                                <?php elseif ($noticia['link_externo'] === 'materiais.php'): ?>
                                 <a href="<?php echo htmlspecialchars($noticia['link_externo']); ?>" class="btn-news-readmore">
                                    Ver Materiais <i class="fas fa-arrow-right"></i>
                                 </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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