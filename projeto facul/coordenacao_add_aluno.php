<?php
session_start(); // No topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    header("Location: index.html");
    exit();
}
include 'db.php';
$currentPageIdentifier = 'add_aluno'; // Para a sidebar

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

// Buscar turmas para o select
$turmas_result = mysqli_query($conn, "SELECT id, nome_turma FROM turmas ORDER BY nome_turma");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Novo Aluno - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style> 
        /* Estilos inline que você tinha. Mova para css dedicado ou temas_globais.css */
        .form-section { 
            margin-bottom: 2rem; padding: 1.5rem; border-radius: 5px; 
            /* background-color, box-shadow virão do tema */
        }
        .form-section label { display: block; margin-top: 1rem; margin-bottom: 0.5rem; font-weight: bold; }
        .form-section input[type="text"], 
        .form-section input[type="email"], 
        .form-section input[type="password"], 
        .form-section input[type="file"], 
        .form-section select {
            width: 100%; padding: 0.75rem; border-radius: 4px; box-sizing: border-box;
            /* border, background-color, color virão do tema */
        }
        .form-section button[type="submit"] { 
            padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; 
            font-size: 1rem; margin-top: 1.5rem;
            /* background-color, color virão do tema para botões da coordenação */
        }
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; /* Cores virão do tema */ }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Adicionar Aluno</h1>
        <form action="logout.php" method="post" style="display: inline;"><button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button></form> 
    </header>
    <div class="container" id="pageContainer"> 
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_coordenacao.php'; ?>
        </nav>
        <main class="main-content">
            <h2>Cadastrar Novo Aluno</h2>
            <?php if(isset($_SESSION['form_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['form_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['form_status_message']); ?>
                </div>
                <?php unset($_SESSION['form_status_message']); unset($_SESSION['form_status_type']); ?>
            <?php endif; ?>
            <section class="form-section">
                <form action="salvar_aluno.php" method="POST" enctype="multipart/form-data">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <label for="senha">Senha Inicial:</label>
                    <input type="password" id="senha" name="senha" required>
                    <label for="turma_id">Turma:</label>
                    <select id="turma_id" name="turma_id" required>
                        <option value="">Selecione a Turma</option>
                        <?php if($turmas_result) while($turma = mysqli_fetch_assoc($turmas_result)): ?>
                            <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome_turma']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <label for="foto_aluno_input">Foto do Aluno (Opcional):</label> 
                    <input type="file" id="foto_aluno_input" name="foto_aluno" accept="image/*">
                    <button type="submit">Cadastrar Aluno</button>
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