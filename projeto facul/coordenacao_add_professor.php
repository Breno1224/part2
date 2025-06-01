<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
// include 'db.php'; // db.php já está incluído na sidebar
$currentPageIdentifier = 'add_professor';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Novo Professor - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style> /* Estilos idênticos ao coordenacao_add_aluno.php */
        .form-section { margin-bottom: 2rem; padding: 1.5rem; background-color: #fff; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.5rem; font-weight: bold; }
        .form-section input[type="text"], .form-section input[type="email"], .form-section input[type="password"], .form-section input[type="file"] {
            width: 100%; padding: 0.75rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        .form-section button[type="submit"] { background-color: #208A87; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 1.5rem; }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .status-success { background-color: #d4edda; color: #155724; } .status-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Adicionar Professor</h1>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit"><i class="fas fa-sign-out-alt"></i> Sair</button></form>
    </header>
    <div class="container">
        <nav class="sidebar" id="sidebar"><?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?></nav>
        <main class="main-content">
            <h2>Cadastrar Novo Professor</h2>
            <?php if(isset($_SESSION['form_status_message'])): ?>
                <div class="status-message <?php echo $_SESSION['form_status_type']; ?>"><?php echo $_SESSION['form_status_message']; ?></div>
                <?php unset($_SESSION['form_status_message']); unset($_SESSION['form_status_type']); ?>
            <?php endif; ?>
            <section class="form-section">
                <form action="salvar_professor.php" method="POST" enctype="multipart/form-data">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <label for="senha">Senha Inicial:</label>
                    <input type="password" id="senha" name="senha" required>
                    <label for="foto_url">Foto do Professor (Opcional):</label>
                    <input type="file" id="foto_url" name="foto_professor" accept="image/*">
                    <button type="submit">Cadastrar Professor</button>
                </form>
            </section>
        </main>
    </div>
    <script> /* Script do menu lateral */ document.getElementById('menu-toggle').addEventListener('click', function() { document.getElementById('sidebar').classList.toggle('hidden'); document.querySelector('.container').classList.toggle('full-width'); }); </script>
</body>
</html>