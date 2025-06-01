<?php
session_start(); // <<<< GARANTIR QUE ESTÁ NO TOPO ABSOLUTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];

$currentPageIdentifier = 'minhas_turmas'; // Para a sidebar

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

// 1. Buscar as turmas associadas a este professor
$turmas_professor = [];
$sql_turmas = "SELECT DISTINCT t.id, t.nome_turma 
               FROM turmas t
               JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id
               WHERE ptd.professor_id = ?
               ORDER BY t.nome_turma";
$stmt_turmas = mysqli_prepare($conn, $sql_turmas);
if ($stmt_turmas) {
    mysqli_stmt_bind_param($stmt_turmas, "i", $professor_id);
    mysqli_stmt_execute($stmt_turmas);
    $result_turmas = mysqli_stmt_get_result($stmt_turmas);
    while ($row = mysqli_fetch_assoc($result_turmas)) {
        $turmas_professor[] = $row;
    }
    mysqli_stmt_close($stmt_turmas);
} else {
    error_log("Erro ao buscar turmas do professor: " . mysqli_error($conn));
}

// 2. Verificar se uma turma foi selecionada para listar os alunos
$alunos_da_turma = [];
$turma_selecionada_id = null;
$nome_turma_selecionada = "";
$professor_tem_acesso_turma = false; 

if (isset($_GET['turma_id']) && !empty($_GET['turma_id'])) {
    $turma_selecionada_id = intval($_GET['turma_id']);

    foreach ($turmas_professor as $turma_p) {
        if ($turma_p['id'] == $turma_selecionada_id) {
            $professor_tem_acesso_turma = true;
            $nome_turma_selecionada = $turma_p['nome_turma'];
            break;
        }
    }

    if ($professor_tem_acesso_turma) {
        $sql_alunos = "SELECT id, nome, email, foto_url 
                       FROM alunos 
                       WHERE turma_id = ? 
                       ORDER BY nome";
        $stmt_alunos = mysqli_prepare($conn, $sql_alunos);
        if ($stmt_alunos) {
            mysqli_stmt_bind_param($stmt_alunos, "i", $turma_selecionada_id);
            mysqli_stmt_execute($stmt_alunos);
            $result_alunos = mysqli_stmt_get_result($stmt_alunos);
            while ($row = mysqli_fetch_assoc($result_alunos)) {
                $alunos_da_turma[] = $row;
            }
            mysqli_stmt_close($stmt_alunos);
        } else {
            error_log("Erro ao buscar alunos da turma: " . mysqli_error($conn));
        }
    } else {
      $turma_selecionada_id = null; 
      if (isset($_GET['turma_id']) && !empty($_GET['turma_id'])) {
          // Considerar mensagem de erro se tentativa de acesso a turma inválida
      }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Turmas - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="css/temas_globais.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos inline que você tinha. Mova para css/professor.css ou temas_globais.css */
        .dashboard-section { 
            padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; 
            /* background-color e box-shadow virão do tema */
        }
        .dashboard-section h3 { 
            font-size: 1.4rem; margin-bottom: 1rem; padding-bottom: 0.5rem; 
            /* color e border-bottom virão do tema */
        }
        .turma-select-form label { font-weight: bold; margin-right: 10px; }
        .turma-select-form select { padding: 0.5rem; border-radius: 4px; margin-right: 10px; min-width: 200px; 
            /* border virá do tema */
        }
        .turma-select-form button { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; 
            /* background-color e color virão do tema */
        }
        .student-list-container { margin-top: 2rem; }
        .student-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        .student-card { border-radius: 8px; padding: 1rem; display: flex; align-items: center; 
            /* background-color, border, box-shadow virão do tema */
        }
        .student-photo { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-right: 1rem; border: 2px solid #ddd; /* Borda da foto pode ser temática */ }
        .student-info h4 { margin: 0 0 0.3rem 0; font-size: 1.1rem; /* color virá do tema */ }
        .student-info p { margin: 0 0 0.5rem 0; font-size: 0.85rem; /* color virá do tema */ }
        .student-info .btn-profile { font-size: 0.8rem; padding: 0.3rem 0.7rem; text-decoration: none; border-radius: 4px; transition: background-color 0.2s;
            /* background-color e color virão do tema para botões */
        }
        .no-data-message { padding: 1rem; text-align: center; border-radius: 4px; /* color e background-color virão do tema ou css base */ }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Gerenciar Turmas (Prof. <?php echo htmlspecialchars($nome_professor); ?>)</h1>
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
                echo "<p style='padding:1rem; color:white;'>Erro: Arquivo da sidebar não encontrado.</p>";
            }
            ?>
        </nav>

        <main class="main-content">
            <section class="dashboard-section">
                <h3>Selecione uma Turma</h3>
                <?php if (!empty($turmas_professor)): ?>
                    <form method="GET" action="gerenciar_turmas_professor.php" class="turma-select-form">
                        <label for="turma_id_select">Turma:</label>
                        <select name="turma_id" id="turma_id_select" onchange="this.form.submit()"> 
                            <option value="">-- Selecione uma Turma --</option>
                            <?php foreach ($turmas_professor as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>" <?php echo ($turma_selecionada_id == $turma['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($turma['nome_turma']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php else: ?>
                    <p class="no-data-message">Você não está associado a nenhuma turma no momento ou nenhuma turma foi cadastrada.</p>
                <?php endif; ?>
            </section>

            <?php if ($turma_selecionada_id && $professor_tem_acesso_turma): ?>
                <section class="dashboard-section student-list-container">
                    <h3>Alunos da Turma: <?php echo htmlspecialchars($nome_turma_selecionada); ?></h3>
                    <?php if (!empty($alunos_da_turma)): ?>
                        <div class="student-grid">
                            <?php foreach ($alunos_da_turma as $aluno): ?>
                                <div class="student-card">
                                    <img src="<?php echo htmlspecialchars(!empty($aluno['foto_url']) ? $aluno['foto_url'] : 'img/alunos/default_avatar.png'); ?>" 
                                         alt="Foto de <?php echo htmlspecialchars($aluno['nome']); ?>" 
                                         class="student-photo"
                                         onerror="this.onerror=null; this.src='img/alunos/default_avatar.png';">
                                    <div class="student-info">
                                        <h4><?php echo htmlspecialchars($aluno['nome']); ?></h4>
                                        <?php if(!empty($aluno['email'])): ?>
                                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($aluno['email']); ?></p>
                                        <?php endif; ?>
                                        <a href="perfil_aluno_detalhado.php?aluno_id=<?php echo $aluno['id']; ?>" class="btn-profile">
                                            <i class="fas fa-user-circle"></i> Ver Perfil
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data-message">Nenhum aluno encontrado para esta turma.</p>
                    <?php endif; ?>
                </section>
            <?php elseif (isset($_GET['turma_id']) && !$professor_tem_acesso_turma && !empty($_GET['turma_id'])): ?>
                 <section class="dashboard-section student-list-container">
                    <h3>Alunos da Turma</h3>
                     <p class="no-data-message">Você não tem permissão para visualizar esta turma ou a turma selecionada é inválida.</p>
                 </section>
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