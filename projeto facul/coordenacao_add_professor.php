<?php
session_start(); 
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
if (file_exists('db.php')) { 
    include 'db.php';
}

$currentPageIdentifier = 'add_professor';
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Novo Professor - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/coordenacao.css"> 
    <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos para garantir o layout vertical do formulário. 
           Mova para css/coordenacao.css ou seu CSS base quando possível. */
        .form-section { 
            margin-bottom: 2rem; 
            padding: 1.5rem; 
            border-radius: 8px; /* Ajuste conforme seu design padrão */
            /* background-color, box-shadow, border virão de .dashboard-section nos temas globais */
        }
        .form-section h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            font-size: 1.6rem; 
            /* color e border-bottom virão dos temas */
        }
        .form-section label { 
            display: block; /* FAZ O LABEL OCUPAR SUA PRÓPRIA LINHA */
            margin-top: 1rem; 
            margin-bottom: 0.4rem; /* Espaço entre o label e o input */
            font-weight: bold; 
            /* color virá do tema */
        }
        .form-section input[type="text"], 
        .form-section input[type="email"], 
        .form-section input[type="password"], 
        .form-section input[type="file"], 
        .form-section select { /* Adicionado select para consistência */
            display: block; /* FAZ O INPUT OCUPAR SUA PRÓPRIA LINHA (redundante se width:100%) */
            width: 100%; 
            padding: 0.75rem; 
            border-radius: 4px; 
            box-sizing: border-box;
            margin-bottom: 1rem; /* Espaço abaixo de cada input antes do próximo label */
            /* border, background-color, color virão do tema */
        }
        .form-section button[type="submit"] { 
            display: inline-block; /* Para o botão não ocupar 100% da largura */
            padding: 0.75rem 1.5rem; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 1rem; 
            margin-top: 1.5rem;
            font-weight: bold;
            /* background-color, color virão do tema */
        }
        .status-message { 
            padding: 1rem; 
            margin-bottom: 1.5rem; 
            border-radius: 4px; 
            text-align: center;
            font-size: 0.95rem;
            /* Cores (.status-success / .status-error) virão do tema */
        }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Adicionar Professor</h1>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button></form>
    </header>
    <div class="container" id="pageContainer">
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

            <section class="form-section dashboard-section"> 
                <h2>Cadastrar Novo Professor</h2>
                <?php if(isset($_SESSION['form_status_message'])): ?>
                    <div class="status-message <?php echo htmlspecialchars($_SESSION['form_status_type']); ?>">
                        <?php echo htmlspecialchars($_SESSION['form_status_message']); ?>
                    </div>
                    <?php unset($_SESSION['form_status_message']); unset($_SESSION['form_status_type']); ?>
                <?php endif; ?>
                
                <form action="salvar_professor.php" method="POST" enctype="multipart/form-data">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>

                    <label for="senha">Senha Inicial:</label>
                    <input type="password" id="senha" name="senha" required autocomplete="new-password">

                    <label for="foto_professor_input">Foto do Professor (Opcional):</label>
                    <input type="file" id="foto_professor_input" name="foto_professor" accept="image/jpeg, image/png, image/gif">
                    
                    <button type="submit"><i class="fas fa-user-plus"></i> Cadastrar Professor</button>
                </form>
            </section>
        </main>
    </div>
    <script> 
        const menuToggleButtonGlobal = document.getElementById('menu-toggle');
        const sidebarElementGlobal = document.getElementById('sidebar');    
        const pageContainerGlobal = document.getElementById('pageContainer');  

        if (menuToggleButtonGlobal && sidebarElementGlobal && pageContainerGlobal) {
            menuToggleButtonGlobal.addEventListener('click', function () {
                sidebarElementGlobal.classList.toggle('hidden'); 
                pageContainerGlobal.classList.toggle('full-width'); 
            });
        }
    </script>
</body>
</html>
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>