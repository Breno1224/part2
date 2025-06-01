<?php
session_start(); // GARANTIR que está no topo absoluto

// Verifica se o usuário é um docente logado
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php'; // Conexão com o banco

$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id']; 

// Define o identificador da página atual para a sidebar
$currentPageIdentifier = 'lancar_notas';

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

// Buscar turmas e disciplinas do banco
$turmas_result_query = mysqli_query($conn, "SELECT id, nome_turma FROM turmas ORDER BY nome_turma");
$disciplinas_result_query = mysqli_query($conn, "SELECT id, nome_disciplina FROM disciplinas ORDER BY nome_disciplina");

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lançar Notas - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos inline que você tinha. 
           Muitos deles (cores, fundos) agora devem ser controlados pelo temas_globais.css.
           Mantenha apenas o que for ESTRUTURAL e específico DESTA PÁGINA, ou mova para professor.css.
        */
        .form-section label { 
            margin-bottom: 0.5rem; /* Reduzido um pouco */
            display: block; 
            font-weight: bold; 
        }
        .form-section select, 
        .form-section input[type="text"], 
        .form-section input[type="number"] {
            width: 100%; 
            padding: 0.6rem; /* Padronizado */
            margin-bottom: 0.8rem; /* Padronizado */
            box-sizing: border-box; 
            /* border, border-radius, background-color, color virão do tema ou css/professor.css */
        }
        .form-section button[type="button"] { /* Botão "Carregar Alunos" */
            padding: 0.7rem 1.2rem; /* Padronizado */
            /* background-color, color, border virão do tema */
            cursor: pointer; 
            border-radius: 4px; /* Padronizado */
            width: auto; 
            margin-top: 0.5rem; /* Adicionado */
        }
         #alunosSection button[type="submit"] { /* Botão "Lançar Notas" */
            padding: 0.7rem 1.2rem; /* Padronizado */
            /* background-color, color, border virão do tema */
            cursor: pointer; 
            border-radius: 4px; /* Padronizado */
            width: auto;
            margin-top: 1rem;
        }
        #alunosSection table { width: 100%; margin-top: 1.5rem; border-collapse: collapse; }
        #alunosSection th, 
        #alunosSection td { 
            text-align: left; padding: 0.75rem; /* Padronizado */
            /* border virá do tema ou professor.css */
        }
        /* #alunosSection th { background-color: #f2f2f2; -- Virá do tema } */
        .hidden { display: none; }
        #statusMessage { 
            margin-top: 1rem; /* Padronizado */
            padding: 0.8rem;  /* Padronizado */
            border-radius: 4px; 
            /* background-color, color, border virão do tema (.status-success / .status-error) */
        }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">

    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Lançar Notas (Prof. <?php echo htmlspecialchars($nome_professor); ?>)</h1>
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
                echo "<p style='padding:1rem; color:white;'>Erro: Sidebar não encontrada.</p>"; 
            }
            ?>
        </nav>

        <main class="main-content">
            <h2>Lançamento de Notas</h2>
            <div id="statusMessage" class="hidden"></div>

            <div class="form-section">
                <label for="turmaSelect">Turma:</label>
                <select id="turmaSelect" name="turma_id">
                    <option value="">Selecione uma Turma</option>
                    <?php if($turmas_result_query) while ($turma = mysqli_fetch_assoc($turmas_result_query)): ?>
                        <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome_turma']); ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="disciplinaSelect">Disciplina:</label>
                <select id="disciplinaSelect" name="disciplina_id">
                    <option value="">Selecione uma Disciplina</option>
                     <?php if($disciplinas_result_query) while ($disciplina = mysqli_fetch_assoc($disciplinas_result_query)): ?>
                        <option value="<?php echo $disciplina['id']; ?>"><?php echo htmlspecialchars($disciplina['nome_disciplina']); ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="avaliacaoInput">Avaliação:</label>
                <input type="text" id="avaliacaoInput" name="avaliacao" placeholder="Ex: Prova 1, Trabalho Bimestral">

                <label for="bimestreSelect">Bimestre:</label>
                <select id="bimestreSelect" name="bimestre">
                    <option value="">Selecione o Bimestre</option>
                    <option value="1">1º Bimestre</option>
                    <option value="2">2º Bimestre</option>
                    <option value="3">3º Bimestre</option>
                    <option value="4">4º Bimestre</option>
                </select>

                <button type="button" onclick="carregarAlunos()">Carregar Alunos</button>
            </div>

            <div id="alunosSection" class="hidden">
                <h3>Inserir Notas</h3>
                <form id="notasForm">
                    <input type="hidden" name="turma_id_form" id="turma_id_form">
                    <input type="hidden" name="disciplina_id_form" id="disciplina_id_form">
                    <input type="hidden" name="avaliacao_form" id="avaliacao_form">
                    <input type="hidden" name="bimestre_form" id="bimestre_form">

                    <table>
                        <thead>
                            <tr>
                                <th>Aluno (ID)</th>
                                <th>Nome</th>
                                <th>Nota (0.00 - 10.00)</th>
                            </tr>
                        </thead>
                        <tbody id="alunosTableBody">
                            </tbody>
                    </table>
                    <button type="submit">Lançar Notas</button>
                </form>
            </div>
        </main>
    </div>

    <script src="js/lancar-notas.js"></script>
    <script>
        // Script do menu lateral
        document.getElementById('menu-toggle').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('hidden'); 
            document.querySelector('.container').classList.toggle('full-width'); 
        });
    </script>
</body>
</html>
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>