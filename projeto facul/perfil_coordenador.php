<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
include 'db.php';

$coordenador_id = $_SESSION['usuario_id'];
$currentPageIdentifier = 'perfil_coordenador';

// Buscar informações do coordenador
$sql_coordenador = "SELECT id, nome, email, foto_url, data_criacao
                     FROM coordenadores
                     WHERE id = ?";
$stmt_coordenador = mysqli_prepare($conn, $sql_coordenador);
$coordenador_info = null;

if ($stmt_coordenador) {
    mysqli_stmt_bind_param($stmt_coordenador, "i", $coordenador_id);
    mysqli_stmt_execute($stmt_coordenador);
    $result_coordenador = mysqli_stmt_get_result($stmt_coordenador);
    $coordenador_info = mysqli_fetch_assoc($result_coordenador);
    mysqli_stmt_close($stmt_coordenador);
}

$ano_inicio = $coordenador_info ? date("Y", strtotime($coordenador_info['data_criacao'])) : 'N/A';
$currentPageIdentifier = 'perfil_coordenador'; // For sidebar (if you create one)
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil da Coordenação - <?php echo $coordenador_info ? htmlspecialchars($coordenador_info['nome']) : 'Perfil não encontrado'; ?> - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Reutilize estilos do perfil do professor com ajustes */
        .profile-container { display: flex; flex-direction: column; align-items: center; margin-bottom: 2rem; }
        .profile-header { text-align: center; margin-bottom: 2rem; }
        .profile-photo-wrapper { position: relative; margin-bottom: 1rem; }
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #8659a6; /* Cor temática da coordenação */
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .profile-header h2 { font-size: 2rem; color: #5D3A9A; margin-bottom: 0.25rem; }
        .profile-header .member-since { font-size: 1rem; color: #777; margin-bottom: 1rem; }

        .upload-form-container { margin-top: 10px; text-align:center; }
        .upload-form-container input {
            border: 1px solid #ccc;
            display: inline-block;
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        .upload-form-container button {
            background-color: #5D3A9A;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 0.9rem;
        }
        .upload-form-container button:hover { background-color: #4a2d7d; }
        .status-message-profile { padding: 0.8rem; margin-top:1rem; margin-bottom: 1rem; border-radius: 4px; text-align:center; font-size:0.9rem; }
        .status-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .profile-details { width: 100%; max-width: 700px; }
        .profile-section { background-color: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); margin-bottom: 1.5rem; }
        .profile-section h3 { font-size: 1.3rem; color: #5D3A9A; margin-top: 0; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #eee; }
        .profile-section p { font-size: 1rem; line-height: 1.6; color: #333; }
        .profile-section p strong { color: #5D3A9A; }
    </style>
</head>
<body>
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Perfil da Coordenação</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

   
        <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_coordenacao.php';
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <?php if ($coordenador_info): ?>
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-photo-wrapper">
                            <img src="<?php echo htmlspecialchars(!empty($coordenador_info['foto_url']) ? $coordenador_info['foto_url'] : 'img/coordenadores/default_avatar.png'); ?>"
                                 alt="Foto de <?php echo htmlspecialchars($coordenador_info['nome']); ?>"
                                 class="profile-photo"
                                 onerror="this.onerror=null; this.src='img/coordenadores/default_avatar.png';">
                        </div>

                        <div class="upload-form-container">
                            <form action="upload_foto_coordenador.php" method="post" enctype="multipart/form-data">
                                <input type="file" name="foto_perfil" accept="image/jpeg, image/png, image/gif" required>
                                <button type="submit"><i class="fas fa-upload"></i> Alterar Foto</button>
                            </form>
                        </div>

                        <?php if(isset($_SESSION['upload_status_message'])): ?>
                            <div class="status-message-profile <?php echo $_SESSION['upload_status_type']; ?>">
                                <?php echo $_SESSION['upload_status_message']; ?>
                            </div>
                            <?php unset($_SESSION['upload_status_message']); unset($_SESSION['upload_status_type']); ?>
                        <?php endif; ?>

                        <h2><?php echo htmlspecialchars($coordenador_info['nome']); ?></h2>
                        <p class="member-since">Membro desde <?php echo $ano_inicio; ?></p>
                    </div>

                    <div class="profile-details">
                        <section class="profile-section">
                            <h3><i class="fas fa-info-circle"></i> Informações de Contato</h3>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($coordenador_info['email']); ?></p>
                            </section>
                    </div>
                </div>
            <?php else: ?>
                <p class="error-message">Perfil da coordenação não encontrado.</p>
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