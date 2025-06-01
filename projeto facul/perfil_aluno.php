<?php
session_start(); // No topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';

$aluno_id = $_SESSION['usuario_id'];
$nome_aluno_sessao = $_SESSION['usuario_nome']; 

$currentPageIdentifier = 'meu_perfil_aluno'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

$aluno_info = null;
$sql_aluno = "SELECT id, nome, email, foto_url, data_criacao, biografia, tema_perfil, interesses, turma_id 
              FROM alunos WHERE id = ?";
$stmt_aluno = mysqli_prepare($conn, $sql_aluno);
if ($stmt_aluno) {
    mysqli_stmt_bind_param($stmt_aluno, "i", $aluno_id);
    mysqli_stmt_execute($stmt_aluno);
    $result_aluno = mysqli_stmt_get_result($stmt_aluno);
    $aluno_info = mysqli_fetch_assoc($result_aluno);
    mysqli_stmt_close($stmt_aluno);
}

$nome_turma_aluno = "Não informada";
if ($aluno_info && !empty($aluno_info['turma_id'])) {
    $sql_turma_nome = "SELECT nome_turma FROM turmas WHERE id = ?";
    $stmt_turma = mysqli_prepare($conn, $sql_turma_nome);
    if ($stmt_turma) {
        mysqli_stmt_bind_param($stmt_turma, "i", $aluno_info['turma_id']);
        mysqli_stmt_execute($stmt_turma);
        $result_turma = mysqli_stmt_get_result($stmt_turma);
        if($turma_data = mysqli_fetch_assoc($result_turma)) {
            $nome_turma_aluno = $turma_data['nome_turma'];
        }
        mysqli_stmt_close($stmt_turma);
    }
}

$ano_inicio_escola = $aluno_info ? date("Y", strtotime($aluno_info['data_criacao'])) : 'N/A';
// Tema que o aluno escolheu para seu perfil (será o mesmo que $tema_global_usuario aqui)
$tema_escolhido_pelo_aluno = $aluno_info && !empty($aluno_info['tema_perfil']) ? $aluno_info['tema_perfil'] : 'padrao';

$temas_disponiveis = [
    'padrao' => 'Padrão do Sistema', '8bit' => '8-Bit Retrô',
    'natureza' => 'Natureza Calma', 'academico' => 'Acadêmico Clássico',
    'darkmode' => 'Modo Escuro Simples'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - <?php echo htmlspecialchars($nome_aluno_sessao); ?> - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos ESTRUTURAIS para a página de perfil. Cores/fontes vêm dos temas. */
        .profile-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; padding:1rem; }
        .profile-header { text-align: center; margin-bottom: 1.5rem; width:100%;}
        .profile-photo-wrapper { position: relative; margin-bottom: 1rem; display:inline-block; }
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; } /* Estilo de borda/sombra virá do tema */
        .profile-header h2 { font-size: 2rem; margin-bottom: 0.25rem; }
        .profile-header .member-since, .profile-header .turma-info { font-size: 1rem; margin-bottom: 0.5rem; }
        .profile-details { width: 100%; max-width: 800px; }
        .profile-section { padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; /* BG/Borda/Sombra do tema */ }
        .profile-section h3 { font-size: 1.3rem; margin-top: 0; margin-bottom: 1rem; padding-bottom: 0.5rem; /* Cor/Borda-inf do tema */ }
        .profile-section p, .profile-section ul { font-size: 1rem; line-height: 1.6; }
        .profile-section ul { list-style: none; padding-left: 0; }
        .profile-section li { padding: 0.6rem 1rem; margin-bottom: 0.5rem; border-radius: 4px; border-left-style: solid; border-left-width: 3px; /* BG/Cor-borda-esq do tema */ }
        .edit-section details { margin-bottom: 10px; }
        .edit-section summary { cursor: pointer; font-weight: bold; padding: 0.6rem 0.8rem; border-radius:4px; display: inline-block; /* Cor/BG do tema */}
        .edit-section form { margin-top: 1rem; padding:1rem; border:1px solid #eee; border-radius:4px;} /* Borda pode ser do tema */
        .edit-section label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .edit-section textarea, .edit-section select, .edit-section input[type="text"] { width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box; margin-bottom:1rem; /* Border/BG/Color do tema */}
        .edit-section textarea { min-height: 100px; }
        .edit-section button[type="submit"] { padding: 0.6rem 1.2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; /* BG/Color do tema */}
        .status-message-profile { padding: 0.8rem; margin-top:1rem; margin-bottom: 1rem; border-radius: 4px; text-align:center; font-size:0.9rem; /* Cores do tema para .status-success/error */ }
        .upload-form-container { margin-top: 10px; text-align:center; }
        .upload-form-container input[type="file"] { display: inline-block; padding: 6px 12px; cursor: pointer; border-radius: 4px; /* Border/BG do tema */}
        .upload-form-container button[type="submit"] { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; font-size: 0.9rem; /* BG/Color do tema */}
        .no-data { font-style: italic; }
        .error-message { text-align: center; color: red; font-size: 1.2rem; padding: 2rem; }
        .quick-actions-profile { margin-top: 1.5rem; text-align: center; }
        .quick-actions-profile a.button { /* Nome da classe alterado para evitar conflito com botões de formulário */
             margin: 0 10px; text-decoration: none; padding: 0.7rem 1.2rem; border-radius: 4px; display:inline-block; 
             /* Cores e BG virão do tema para botões, mas esta classe permite estilo específico se necessário */
        }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">
    <header>
        <button id="menu-toggle-page" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Meu Perfil</h1>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button></form>
    </header>
    <div class="container" id="mainContainerPage">
        <nav class="sidebar" id="sidebarPage">
            <?php 
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php';
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Sidebar não encontrada.</p>";
            }
            ?>
        </nav>
        <main class="main-content">
            <?php if ($aluno_info): ?>
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-photo-wrapper">
                            <img src="<?php echo htmlspecialchars(!empty($aluno_info['foto_url']) ? $aluno_info['foto_url'] : 'img/alunos/default_avatar.png'); ?>" 
                                 alt="Foto de <?php echo htmlspecialchars($aluno_info['nome']); ?>" class="profile-photo"
                                 onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                        </div>
                        <div class="upload-form-container">
                            <form action="upload_foto_aluno.php" method="post" enctype="multipart/form-data">
                                <input type="file" name="foto_perfil_aluno" accept="image/jpeg, image/png, image/gif" required>
                                <button type="submit"><i class="fas fa-upload"></i> Alterar Foto</button>
                            </form>
                        </div>
                        <?php if(isset($_SESSION['upload_aluno_status_message'])): ?>
                            <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['upload_aluno_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['upload_aluno_status_message']); ?></div>
                            <?php unset($_SESSION['upload_aluno_status_message']); unset($_SESSION['upload_aluno_status_type']); ?>
                        <?php endif; ?>
                        <h2><?php echo htmlspecialchars($aluno_info['nome']); ?></h2>
                        <p class="turma-info"><i class="fas fa-users"></i> Turma: <?php echo htmlspecialchars($nome_turma_aluno); ?></p>
                        <p class="member-since"><i class="fas fa-calendar-check"></i> Aluno(a) desde: <?php echo $ano_inicio_escola; ?></p>
                    </div>

                    <div class="profile-details">
                        <section class="profile-section edit-section">
                            <details>
                                <summary><i class="fas fa-edit"></i> Personalizar Perfil (Bio, Interesses e Tema)</summary>
                                <form action="salvar_perfil_aluno.php" method="POST" style="margin-bottom:20px;">
                                    <input type="hidden" name="action" value="save_bio_interests">
                                    <label for="biografia">Minha Biografia:</label>
                                    <textarea id="biografia" name="biografia" rows="5"><?php echo htmlspecialchars($aluno_info['biografia'] ?? ''); ?></textarea>
                                    
                                    <label for="interesses" style="margin-top:1rem;">Meus Interesses (separados por vírgula):</label>
                                    <input type="text" id="interesses" name="interesses" value="<?php echo htmlspecialchars($aluno_info['interesses'] ?? ''); ?>" placeholder="Ex: Leitura, Games, Esportes">
                                    
                                    <button type="submit"><i class="fas fa-save"></i> Salvar Bio e Interesses</button>
                                </form>
                                <?php if(isset($_SESSION['bio_interesses_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['bio_interesses_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['bio_interesses_status_message']); ?></div>
                                <?php unset($_SESSION['bio_interesses_status_message']); unset($_SESSION['bio_interesses_status_type']); ?>
                                <?php endif; ?><hr style="margin: 20px 0;">
                                
                                <form action="salvar_perfil_aluno.php" method="POST">
                                    <input type="hidden" name="action" value="save_theme">
                                    <label for="tema_perfil_select">Escolha um Tema para seu Perfil:</label>
                                    <select id="tema_perfil_select" name="tema_perfil">
                                        <?php foreach($temas_disponiveis as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>" <?php if($tema_global_usuario == $value) echo 'selected'; ?>> 
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit"><i class="fas fa-palette"></i> Aplicar Tema</button>
                                </form>
                                <?php if(isset($_SESSION['tema_aluno_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['tema_aluno_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['tema_aluno_status_message']); ?></div>
                                <?php unset($_SESSION['tema_aluno_status_message']); unset($_SESSION['tema_aluno_status_type']); ?>
                                <?php endif; ?>
                            </details>
                        </section>

                        <section class="profile-section bio-section"><h3><i class="fas fa-id-card-alt"></i> Sobre Mim</h3>
                            <?php if (!empty($aluno_info['biografia'])): ?><p><?php echo nl2br(htmlspecialchars($aluno_info['biografia'])); ?></p>
                            <?php else: ?><p class="no-data">Nenhuma biografia informada. Edite seu perfil para adicionar.</p><?php endif; ?>
                        </section>

                        <section class="profile-section interests-section"><h3><i class="fas fa-grin-stars"></i> Meus Interesses</h3>
                            <?php if (!empty($aluno_info['interesses'])): ?><p><?php echo htmlspecialchars($aluno_info['interesses']); ?></p>
                            <?php else: ?><p class="no-data">Nenhum interesse informado. Edite seu perfil para adicionar.</p><?php endif; ?>
                        </section>

                        <section class="profile-section"><h3><i class="fas fa-info-circle"></i> Informações de Contato</h3><p><strong>Email:</strong> <?php echo htmlspecialchars($aluno_info['email']); ?></p></section>
                        
                        <section class="profile-section quick-actions-profile">
                            <h3><i class="fas fa-bolt"></i> Acesso Rápido Acadêmico</h3>
                            <a href="boletim.php" class="button">Meu Boletim</a>
                            <a href="calendario.php" class="button">Meu Calendário</a>
                            <a href="materiais.php" class="button">Materiais Didáticos</a>
                        </section>
                    </div>
                </div>
            <?php else: ?>
                <p class="error-message">Perfil do aluno não encontrado.</p>
            <?php endif; ?>
        </main>
    </div>
    <script>
        const menuToggleButtonPage = document.getElementById('menu-toggle-page');
        const sidebarNavigationPage = document.getElementById('sidebarPage'); 
        const mainContainerPage = document.getElementById('mainContainerPage'); 

        if (menuToggleButtonPage && sidebarNavigationPage && mainContainerPage) {
            menuToggleButtonPage.addEventListener('click', function () {
                sidebarNavigationPage.classList.toggle('hidden'); 
                mainContainerPage.classList.toggle('full-width'); 
            });
        }
    </script>
</body>
</html>
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>