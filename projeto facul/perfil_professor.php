<?php
session_start(); // Deve ser a primeira linha
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}
include 'db.php';

$professor_id_para_exibir = isset($_GET['id']) ? intval($_GET['id']) : 0;
$professor_info = null;
$disciplinas_lecionadas = [];
$turmas_lecionadas = [];
$is_own_profile = false; 
$currentPageIdentifier = null;

$viewer_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$viewer_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;

// Tema global do USUÁRIO LOGADO (VISUALIZADOR)
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

if ($viewer_role === 'docente' && $viewer_id == $professor_id_para_exibir) {
    $is_own_profile = true;
    $currentPageIdentifier = 'meu_perfil'; 
}

if ($professor_id_para_exibir > 0) {
    $sql_professor = "SELECT id, nome, email, foto_url, data_criacao, biografia, tema_perfil 
                      FROM professores 
                      WHERE id = ?";
    $stmt_professor = mysqli_prepare($conn, $sql_professor);
    if ($stmt_professor) {
        mysqli_stmt_bind_param($stmt_professor, "i", $professor_id_para_exibir);
        mysqli_stmt_execute($stmt_professor);
        $result_professor = mysqli_stmt_get_result($stmt_professor);
        $professor_info = mysqli_fetch_assoc($result_professor);
        mysqli_stmt_close($stmt_professor);
    }

    if ($professor_info) {
        $sql_disciplinas = "SELECT DISTINCT d.nome_disciplina FROM disciplinas d JOIN professores_turmas_disciplinas ptd ON d.id = ptd.disciplina_id WHERE ptd.professor_id = ? ORDER BY d.nome_disciplina";
        $stmt_disciplinas = mysqli_prepare($conn, $sql_disciplinas);
        if ($stmt_disciplinas) {
            mysqli_stmt_bind_param($stmt_disciplinas, "i", $professor_id_para_exibir);
            mysqli_stmt_execute($stmt_disciplinas);
            $result_disciplinas = mysqli_stmt_get_result($stmt_disciplinas);
            while ($row = mysqli_fetch_assoc($result_disciplinas)) { $disciplinas_lecionadas[] = $row['nome_disciplina']; }
            mysqli_stmt_close($stmt_disciplinas);
        }
        $sql_turmas = "SELECT DISTINCT t.nome_turma FROM turmas t JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id WHERE ptd.professor_id = ? ORDER BY t.nome_turma";
        $stmt_turmas = mysqli_prepare($conn, $sql_turmas);
        if ($stmt_turmas) {
            mysqli_stmt_bind_param($stmt_turmas, "i", $professor_id_para_exibir);
            mysqli_stmt_execute($stmt_turmas);
            $result_turmas = mysqli_stmt_get_result($stmt_turmas);
            while ($row = mysqli_fetch_assoc($result_turmas)) { $turmas_lecionadas[] = $row['nome_turma']; }
            mysqli_stmt_close($stmt_turmas);
        }
    }
}
$ano_inicio = $professor_info ? date("Y", strtotime($professor_info['data_criacao'])) : 'N/A';
// Tema que o DONO deste perfil escolheu (usado para pré-selecionar o <select> de temas)
$tema_escolhido_pelo_dono_do_perfil = $professor_info && !empty($professor_info['tema_perfil']) ? $professor_info['tema_perfil'] : 'padrao';

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
    <title>Perfil de <?php echo $professor_info ? htmlspecialchars($professor_info['nome']) : 'Professor'; ?> - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos ESTRUTURAIS mínimos para esta página. Cores e fontes virão dos temas. */
        .profile-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; padding:1rem; }
        .profile-header { text-align: center; margin-bottom: 1.5rem; width:100%;}
        .profile-photo-wrapper { position: relative; margin-bottom: 1rem; display:inline-block; }
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; }
        .profile-header h2 { font-size: 2rem; margin-bottom: 0.25rem; }
        .profile-header .member-since { font-size: 1rem; margin-bottom: 1rem; }
        .profile-details { width: 100%; max-width: 800px; }
        .profile-section { padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .profile-section h3 { font-size: 1.3rem; margin-top: 0; margin-bottom: 1rem; padding-bottom: 0.5rem; }
        .profile-section p, .profile-section ul { font-size: 1rem; line-height: 1.6; }
        .profile-section ul { list-style: none; padding-left: 0; }
        .profile-section li { padding: 0.6rem 1rem; margin-bottom: 0.5rem; border-radius: 4px; border-left-style: solid; border-left-width: 3px; }
        .edit-section details { margin-bottom: 10px; }
        .edit-section summary { cursor: pointer; font-weight: bold; padding: 0.5rem; border-radius:4px; display: inline-block;}
        .edit-section form { margin-top: 1rem; padding:1rem; border:1px solid #eee; border-radius:4px;}
        .edit-section label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .edit-section textarea, .edit-section select { width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box; margin-bottom:1rem; }
        .edit-section textarea { min-height: 150px; }
        .edit-section button[type="submit"] { padding: 0.6rem 1.2rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        .status-message-profile { padding: 0.8rem; margin-top:1rem; margin-bottom: 1rem; border-radius: 4px; text-align:center; font-size:0.9rem; }
        .upload-form-container { margin-top: 10px; text-align:center; }
        .upload-form-container input[type="file"] { display: inline-block; padding: 6px 12px; cursor: pointer; border-radius: 4px; }
        .upload-form-container button[type="submit"] { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; font-size: 0.9rem; }
        .no-data { font-style: italic; }
        .error-message { text-align: center; color: red; font-size: 1.2rem; padding: 2rem; }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">
    
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Perfil do Professor</h1>
        <?php if(isset($_SESSION['usuario_id'])): ?>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button></form>
        <?php endif; ?>
    </header>

    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_include_path = '';
            if ($viewer_role === 'docente') { $sidebar_include_path = __DIR__ . '/includes/sidebar_professor.php'; }
            elseif ($viewer_role === 'aluno') { $sidebar_include_path = __DIR__ . '/includes/sidebar_aluno.php'; }
            elseif ($viewer_role === 'coordenacao') { $sidebar_include_path = __DIR__ . '/includes/sidebar_coordenacao.php'; }
            
            if (!empty($sidebar_include_path) && file_exists($sidebar_include_path)) { include $sidebar_include_path; }
            else { echo "<p style='padding:1rem; color:white;'>Menu não disponível.</p>"; }
            ?>
        </nav>

        <main class="main-content">
            <?php if ($professor_info): ?>
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-photo-wrapper">
                            <img src="<?php echo htmlspecialchars(!empty($professor_info['foto_url']) ? $professor_info['foto_url'] : 'img/professores/default_avatar.png'); ?>" 
                                 alt="Foto de <?php echo htmlspecialchars($professor_info['nome']); ?>" class="profile-photo"
                                 onerror="this.onerror=null; this.src='img/professores/default_avatar.png';">
                        </div>
                        <?php if ($is_own_profile): ?>
                            <div class="upload-form-container">
                                <form action="upload_foto_professor.php" method="post" enctype="multipart/form-data">
                                    <input type="file" name="foto_perfil" accept="image/jpeg, image/png, image/gif" required>
                                    <button type="submit"><i class="fas fa-upload"></i> Alterar Foto</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['upload_status_message'])): ?>
                            <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['upload_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['upload_status_message']); ?></div>
                            <?php unset($_SESSION['upload_status_message']); unset($_SESSION['upload_status_type']); ?>
                        <?php endif; ?>
                        <h2><?php echo htmlspecialchars($professor_info['nome']); ?></h2>
                        <p class="member-since">Na instituição desde <?php echo $ano_inicio; ?></p>
                    </div>

                    <div class="profile-details">
                        <?php if ($is_own_profile): ?>
                        <section class="profile-section edit-section">
                            <details>
                                <summary><i class="fas fa-edit"></i> Editar Perfil (Biografia e Tema)</summary>
                                <form action="salvar_bio_professor.php" method="POST" style="margin-bottom:20px;">
                                    <label for="biografia">Minha Biografia:</label>
                                    <textarea id="biografia" name="biografia" rows="6"><?php echo htmlspecialchars($professor_info['biografia'] ?? ''); ?></textarea>
                                    <button type="submit"><i class="fas fa-save"></i> Salvar Biografia</button>
                                </form>
                                <?php if(isset($_SESSION['bio_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['bio_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['bio_status_message']); ?></div>
                                <?php unset($_SESSION['bio_status_message']); unset($_SESSION['bio_status_type']); ?>
                                <?php endif; ?>
                                <hr style="margin: 20px 0;">
                                <form action="salvar_tema_professor.php" method="POST">
                                    <label for="tema_perfil_select">Escolha um Tema para seu Perfil:</label>
                                    <select id="tema_perfil_select" name="tema_perfil">
                                        <?php foreach($temas_disponiveis as $value => $label): ?>
                                            <option value="<?php echo htmlspecialchars($value); ?>" <?php if($tema_escolhido_pelo_dono_do_perfil == $value) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit"><i class="fas fa-palette"></i> Aplicar Tema</button>
                                </form>
                                <?php if(isset($_SESSION['tema_status_message'])): ?>
                                <div class="status-message-profile <?php echo htmlspecialchars($_SESSION['tema_status_type']); ?>"><?php echo htmlspecialchars($_SESSION['tema_status_message']); ?></div>
                                <?php unset($_SESSION['tema_status_message']); unset($_SESSION['tema_status_type']); ?>
                                <?php endif; ?>
                            </details>
                        </section>
                        <?php endif; ?>

                        <section class="profile-section bio-section"><h3><i class="fas fa-id-card-alt"></i> Sobre Mim</h3>
                            <?php if (!empty($professor_info['biografia'])): ?><p><?php echo nl2br(htmlspecialchars($professor_info['biografia'])); ?></p>
                            <?php else: ?><p class="no-data">Nenhuma biografia informada. <?php if($is_own_profile) echo 'Clique em "Editar Perfil" para adicionar uma.'; ?></p><?php endif; ?>
                        </section>
                        <section class="profile-section"><h3><i class="fas fa-info-circle"></i> Informações de Contato</h3><p><strong>Email:</strong> <?php echo htmlspecialchars($professor_info['email']); ?></p></section>
                        <section class="profile-section"><h3><i class="fas fa-chalkboard"></i> Disciplinas Lecionadas</h3>
                            <?php if (!empty($disciplinas_lecionadas)): ?><ul><?php foreach ($disciplinas_lecionadas as $d): ?><li><?php echo htmlspecialchars($d); ?></li><?php endforeach; ?></ul><?php else: ?><p class="no-data">Nenhuma.</p><?php endif; ?>
                        </section>
                        <section class="profile-section"><h3><i class="fas fa-users"></i> Turmas Atuais</h3>
                            <?php if (!empty($turmas_lecionadas)): ?><ul><?php foreach ($turmas_lecionadas as $t): ?><li><?php echo htmlspecialchars($t); ?></li><?php endforeach; ?></ul><?php else: ?><p class="no-data">Nenhuma.</p><?php endif; ?>
                        </section>
                    </div>
                </div>
            <?php else: ?>
                <p class="error-message">Perfil do professor não encontrado ou ID inválido.</p>
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
<?php if($conn) mysqli_close($conn); ?>