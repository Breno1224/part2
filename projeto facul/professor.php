<?php
session_start(); // Deve ser a primeira linha absoluta do arquivo

// Verifica se o usuário é um docente logado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
// include 'db.php'; // Descomente se esta página buscar dados do banco

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];

// Define o identificador da página atual para a sidebar
$currentPageIdentifier = 'painel_inicial'; 

// Pega o tema da sessão do usuário, com 'padrao' como fallback
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

// Dados Estáticos para o Dashboard do Professor:
$lembretes_pendencias = [
    [
        "tipo" => "notas", // para ícone e cor
        "texto" => "Lançar notas da Avaliação Mensal - Turma 2º Ano A (Matemática). Prazo: 05/06/2025",
        "link" => "lancar-notas.php"
    ],
    [
        "tipo" => "frequencia",
        "texto" => "Registrar frequência da Turma 1º Ano B para a semana atual.",
        "link" => "frequencia_professor.php" 
    ],
    [
        "tipo" => "planejamento",
        "texto" => "Preparar plano de aula para o conteúdo de 'Análise Combinatória' - 3º Ano.",
        "link" => "#" 
    ]
];
$comunicados_coordenacao = [
    [
        "titulo" => "Reunião Pedagógica Mensal",
        "data" => "2025-06-03",
        "resumo" => "Lembramos a todos os docentes sobre nossa reunião pedagógica mensal, que ocorrerá na próxima terça-feira, 03/06, às 14h na sala dos professores. Pauta: Planejamento do 3º Bimestre e eventos escolares."
    ],
    [
        "titulo" => "Atualização do Sistema de Notas",
        "data" => "2025-05-29",
        "resumo" => "O sistema ACADMIX passou por uma atualização na funcionalidade de lançamento de notas. Pedimos que explorem as novas ferramentas e reportem qualquer feedback."
    ]
];
$acesso_rapido_links = [
    ["titulo" => "Lançar Notas", "icone" => "fas fa-pen-alt", "link" => "lancar-notas.php", "cor" => "#208A87"],
    ["titulo" => "Frequência", "icone" => "fas fa-clipboard-list", "link" => "frequencia_professor.php", "cor" => "#D69D2A"],
    ["titulo" => "Enviar Materiais", "icone" => "fas fa-folder-plus", "link" => "gerenciar_materiais.php", "cor" => "#5D3A9A"],
    ["titulo" => "Ver Comunicados", "icone" => "fas fa-bell", "link" => "comunicados_professor_ver.php", "cor" => "#C54B6C"] 
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Professor - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="css/temas_globais.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estes estilos eram os que você tinha inline. 
           Muitos deles (cores, fundos, sombras de .dashboard-section) agora devem ser 
           controlados pelo css/temas_globais.css (na seção body.theme-padrao ou temas específicos).
           Os estilos de layout (display, grid, gap, etc.) e alguns muito específicos desta página 
           podem permanecer aqui ou serem movidos para css/professor.css.
        */
        .main-content .welcome-message-professor {
            text-align: left; 
            font-size: 1.6rem;
            /* color: #333; -- Definido pelo tema */
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .dashboard-section {
            /* background-color: #fff; -- Definido pelo tema */
            padding: 1.5rem;
            border-radius: 8px;
            /* box-shadow: 0 2px 10px rgba(0,0,0,0.07); -- Definido pelo tema */
            margin-bottom: 2rem;
        }
        .dashboard-section h3 {
            font-size: 1.4rem;
            /* color: #2C1B17; -- Definido pelo tema */
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            /* border-bottom: 2px solid #D69D2A; -- Definido pelo tema ou css/professor.css */
        }
        .quick-access-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .quick-access-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            border-radius: 8px;
            color: white; /* Cor do texto do card, pode ser sobrescrita pelo tema se necessário */
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            min-height: 120px;
        }
        .quick-access-card:hover {
            transform: translateY(-5px);
            /* box-shadow: 0 6px 12px rgba(0,0,0,0.15); -- Sombra pode ser ajustada por tema */
        }
        .quick-access-card i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }
        .quick-access-card span {
            font-size: 1rem;
            font-weight: bold;
            text-align: center;
        }
        .reminders-list ul, .comunicados-list ul {
            list-style: none;
            padding-left: 0;
        }
        .reminders-list li, .comunicados-list li {
            padding: 0.75rem 0;
            /* border-bottom: 1px solid #eee; -- Definido pelo tema */
            display: flex;
            align-items: flex-start;
        }
        .reminders-list li:last-child, .comunicados-list li:last-child {
            border-bottom: none;
        }
        .reminders-list .reminder-icon {
            font-size: 1.2rem; margin-right: 1rem; margin-top: 0.2rem; 
            width: 25px; text-align: center;
        }
        .reminder-icon.notas { color: #28a745; } 
        .reminder-icon.frequencia { color: #ffc107; } 
        .reminder-icon.planejamento { color: #17a2b8; } 
        .reminder-icon.aviso { color: #dc3545; } 
        .reminders-list .reminder-text a {
            /* color: #208A87; -- Definido pelo tema */
            text-decoration: none; font-weight: 500;
        }
        .reminders-list .reminder-text a:hover { text-decoration: underline; }
        .comunicado-item h4 { font-size: 1.1rem; /* color: #186D6A; -- Definido pelo tema */ margin-bottom: 0.25rem; }
        .comunicado-item .comunicado-date { font-size: 0.8rem; /* color: #777; -- Definido pelo tema */ margin-bottom: 0.5rem; }
        .comunicado-item p { font-size: 0.9rem; line-height: 1.5; /* color: #555; -- Definido pelo tema */ }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">

    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Painel do Professor</h1>
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
                echo "<ul><li><a href='#' class='active'><i class='fas fa-tachometer-alt'></i> Painel Inicial</a></li><li><a href='perfil_professor.php?id=" . htmlspecialchars($_SESSION['usuario_id']) . "'><i class='fas fa-user'></i> Meu Perfil</a></li></ul>";
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <div class="welcome-message-professor">
                Olá, Professor(a) <?php echo htmlspecialchars($nome_professor); ?>!
            </div>

            <section class="dashboard-section">
                <h3>Acesso Rápido</h3>
                <div class="quick-access-grid">
                    <?php foreach ($acesso_rapido_links as $item): ?>
                        <a href="<?php echo htmlspecialchars($item['link']); ?>" class="quick-access-card" style="background-color: <?php echo htmlspecialchars($item['cor']); ?>; color: white;"> 
                            <i class="<?php echo htmlspecialchars($item['icone']); ?>"></i>
                            <span><?php echo htmlspecialchars($item['titulo']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="dashboard-section">
                <h3>Lembretes e Pendências</h3>
                <div class="reminders-list">
                    <?php if (empty($lembretes_pendencias)): ?>
                        <p>Nenhum lembrete ou pendência no momento.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($lembretes_pendencias as $lembrete): ?>
                                <li>
                                    <span class="reminder-icon <?php echo htmlspecialchars($lembrete['tipo']); ?>">
                                        <?php
                                            switch ($lembrete['tipo']) {
                                                case 'notas': echo '<i class="fas fa-edit"></i>'; break;
                                                case 'frequencia': echo '<i class="fas fa-user-check"></i>'; break;
                                                case 'planejamento': echo '<i class="fas fa-chalkboard-teacher"></i>'; break;
                                                default: echo '<i class="fas fa-info-circle"></i>'; break;
                                            }
                                        ?>
                                    </span>
                                    <span class="reminder-text">
                                        <?php if (!empty($lembrete['link']) && $lembrete['link'] !== '#'): ?>
                                            <a href="<?php echo htmlspecialchars($lembrete['link']); ?>"><?php echo htmlspecialchars($lembrete['texto']); ?></a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($lembrete['texto']); ?>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>

            <section class="dashboard-section">
                <h3>Comunicados Recentes da Coordenação</h3>
                <div class="comunicados-list">
                     <?php if (empty($comunicados_coordenacao)): ?>
                        <p>Nenhum comunicado recente.</p>
                    <?php else: ?>
                        <ul>
                            <?php foreach ($comunicados_coordenacao as $comunicado): ?>
                                <li class="comunicado-item">
                                    <div>
                                        <h4><?php echo htmlspecialchars($comunicado['titulo']); ?></h4>
                                        <p class="comunicado-date">Publicado em: <?php echo date("d/m/Y", strtotime($comunicado['data'])); ?></p>
                                        <p><?php echo nl2br(htmlspecialchars($comunicado['resumo'])); ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
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
<?php if(isset($conn) && $conn) mysqli_close($conn); // Adicionado fechamento da conexão se aberta ?>