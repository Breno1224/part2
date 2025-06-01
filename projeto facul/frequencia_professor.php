<?php
session_start(); // GARANTIR que está no topo absoluto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    header("Location: index.html");
    exit();
}
include 'db.php';
$nome_professor = $_SESSION['usuario_nome'];
$professor_id = $_SESSION['usuario_id'];
$currentPageIdentifier = 'frequencia'; // Para a sidebar

// **** NOVO: PEGAR TEMA DA SESSÃO ****
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';
// **** FIM DA ADIÇÃO ****

// (PHP para buscar turmas do professor - como no seu código)
$turmas_professor = [];
$sql_turmas = "SELECT DISTINCT t.id, t.nome_turma FROM turmas t JOIN professores_turmas_disciplinas ptd ON t.id = ptd.turma_id WHERE ptd.professor_id = ? ORDER BY t.nome_turma";
$stmt_turmas_fetch = mysqli_prepare($conn, $sql_turmas);
if ($stmt_turmas_fetch) {
    mysqli_stmt_bind_param($stmt_turmas_fetch, "i", $professor_id);
    mysqli_stmt_execute($stmt_turmas_fetch);
    $result_turmas = mysqli_stmt_get_result($stmt_turmas_fetch);
    while ($row = mysqli_fetch_assoc($result_turmas)) {
        $turmas_professor[] = $row;
    }
    mysqli_stmt_close($stmt_turmas_fetch);
}

$turma_selecionada_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : null;
$data_aula_selecionada = isset($_GET['data_aula']) ? $_GET['data_aula'] : date('Y-m-d');
$nome_turma_selecionada = "";
$alunos_com_frequencia = [];
$professor_tem_acesso_turma = false; 

if ($turma_selecionada_id && $data_aula_selecionada) {
    foreach ($turmas_professor as $turma_p) {
        if ($turma_p['id'] == $turma_selecionada_id) {
            $professor_tem_acesso_turma = true;
            $nome_turma_selecionada = $turma_p['nome_turma'];
            break;
        }
    }
    
    if ($professor_tem_acesso_turma) {
        $sql_alunos_frequencia = "
            SELECT a.id as aluno_id, a.nome as aluno_nome, a.foto_url, f.status, f.observacao
            FROM alunos a
            LEFT JOIN frequencia f ON a.id = f.aluno_id AND f.turma_id = ? AND f.data_aula = ?
            WHERE a.turma_id = ?
            ORDER BY a.nome";
        $stmt_alunos = mysqli_prepare($conn, $sql_alunos_frequencia);
        if ($stmt_alunos) {
            mysqli_stmt_bind_param($stmt_alunos, "isi", $turma_selecionada_id, $data_aula_selecionada, $turma_selecionada_id);
            mysqli_stmt_execute($stmt_alunos);
            $result_alunos_frequencia = mysqli_stmt_get_result($stmt_alunos);
            while ($row = mysqli_fetch_assoc($result_alunos_frequencia)) {
                if (is_null($row['status'])) {
                    $row['status'] = 'P'; 
                }
                $alunos_com_frequencia[] = $row;
            }
            mysqli_stmt_close($stmt_alunos);
        }
    } else {
        $turma_selecionada_id = null; 
         $_SESSION['frequencia_status_message'] = "Você não tem acesso à turma selecionada ou ela não existe.";
         $_SESSION['frequencia_status_type'] = "status-error";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registro de Frequência - ACADMIX</title>
    <link rel="stylesheet" href="css/professor.css"> <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        /* Estilos inline que você tinha. Mova o máximo para css/professor.css ou temas_globais.css */
        .dashboard-section { 
            padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; 
            /* background-color, box-shadow virão do tema */
        }
        .dashboard-section h3 { 
            font-size: 1.4rem; margin-bottom: 1rem; padding-bottom: 0.5rem; 
            /* color, border-bottom virão do tema ou professor.css */
        }
        .form-inline label { margin-right: 0.5rem; font-weight:bold; }
        .form-inline select, .form-inline input[type="date"] { 
            padding: 0.5rem; border-radius: 4px; margin-right: 1rem; 
            /* border, background-color, color virão do tema ou professor.css */
        }
        .form-inline button { 
            padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; 
            /* background-color, color virão do tema */
        }
        /* .form-inline button:hover { background-color: #104b49; -- Virá do tema } */
        
        .chamada-table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        .chamada-table th, .chamada-table td { 
            padding: 0.75rem; text-align: left; vertical-align: middle;
            /* border virá do tema ou professor.css */
        }
        /* .chamada-table th { background-color: #f8f9fa; -- Virá do tema } */
        .chamada-table .aluno-nome-clickable { cursor: pointer; font-weight: 500; /* color virá do tema */ }
        /* .chamada-table .aluno-nome-clickable:hover { text-decoration: underline; } */

        .status-buttons { display: flex; gap: 5px; }
        .status-buttons input[type="radio"] { display: none; }
        .status-buttons label {
            padding: 6px 10px; border-radius: 4px; cursor: pointer;
            font-size: 0.85rem; font-weight: bold; transition: background-color 0.2s, color 0.2s;
            min-width: 35px; text-align: center;
            /* border, background-color, color virão do tema e dos seletores :checked */
        }
        /* Cores dos botões de status :checked e :hover (mantidas, pois são específicas da funcionalidade) */
        .status-buttons input[type="radio"]:checked + label { color: white; border-color: transparent; }
        .status-buttons .status-P input[type="radio"]:checked + label { background-color: #28a745; } 
        .status-buttons .status-F input[type="radio"]:checked + label { background-color: #dc3545; } 
        .status-buttons .status-A input[type="radio"]:checked + label { background-color: #ffc107; color: #333 !important; } 
        .status-buttons .status-FJ input[type="radio"]:checked + label { background-color: #17a2b8; } 
        .status-buttons .status-P label:hover { background-color: #d4edda; }
        .status-buttons .status-F label:hover { background-color: #f8d7da; }
        .status-buttons .status-A label:hover { background-color: #fff3cd; }
        .status-buttons .status-FJ label:hover { background-color: #d1ecf1; }

        .chamada-table input[type="text"].observacao-input { 
            width: 95%; padding: 0.3rem; font-size:0.85rem; border-radius: 3px;
            /* border, background-color, color virão do tema ou professor.css */
        }
        .btn-salvar-chamada { 
            display: block; width: auto; padding: 0.75rem 1.5rem; 
            border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; margin-top: 1.5rem; 
            /* background-color, color virão do tema */
        }
        /* .btn-salvar-chamada:hover { background-color: #186D6A; -- Virá do tema } */
        .status-message { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; /* Cores virão do tema */ }
        
        /* Estilos do Modal (mantidos, podem ser globalizados em temas_globais.css se o modal for reutilizado) */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
        .modal-content { /* background-color: #fefefe; -- Virá do tema */ margin: 10% auto; padding: 25px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; position: relative; /* box-shadow virá do tema */ }
        .modal-close-button { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
        .modal-close-button:hover, .modal-close-button:focus { color: black; text-decoration: none; cursor: pointer; }
        #modalAlunoNome { margin-top: 0; /* color virá do tema */ }
        #modalAlunoStats p { font-size: 1.1rem; line-height: 1.6; }
        #modalAlunoStats .highlight { font-weight: bold; /* color virá do tema */ }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>"> 
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Registro de Frequência (Prof. <?php echo htmlspecialchars($nome_professor); ?>)</h1>
        <form action="logout.php" method="post" style="display: inline;">
            <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php include __DIR__ . '/includes/sidebar_professor.php'; ?>
        </nav>

        <main class="main-content">
            <section class="dashboard-section">
                <h3>Selecionar Turma e Data</h3>
                <form method="GET" action="frequencia_professor.php" class="form-inline">
                     <label for="turma_id_select">Turma:</label>
                    <select name="turma_id" id="turma_id_select" required>
                        <option value="">-- Selecione --</option>
                        <?php foreach ($turmas_professor as $turma): ?>
                            <option value="<?php echo $turma['id']; ?>" <?php echo ($turma_selecionada_id == $turma['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($turma['nome_turma']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="data_aula_select">Data:</label>
                    <input type="date" name="data_aula" id="data_aula_select" value="<?php echo htmlspecialchars($data_aula_selecionada); ?>" required>
                    <button type="submit"><i class="fas fa-list-alt"></i> Carregar</button>
                </form>
            </section>

            <?php if(isset($_SESSION['frequencia_status_message'])): ?>
                <div class="status-message <?php echo htmlspecialchars($_SESSION['frequencia_status_type']); ?>">
                    <?php echo htmlspecialchars($_SESSION['frequencia_status_message']); ?>
                </div>
                <?php unset($_SESSION['frequencia_status_message']); unset($_SESSION['frequencia_status_type']); ?>
            <?php endif; ?>

            <?php if ($turma_selecionada_id && $data_aula_selecionada && $professor_tem_acesso_turma): ?>
            <section class="dashboard-section">
                <h3>Chamada para: <?php echo htmlspecialchars($nome_turma_selecionada); ?> - Data: <?php echo date("d/m/Y", strtotime($data_aula_selecionada)); ?></h3>
                <?php if (!empty($alunos_com_frequencia)): ?>
                <form action="salvar_frequencia.php" method="POST">
                    <input type="hidden" name="turma_id" value="<?php echo $turma_selecionada_id; ?>">
                    <input type="hidden" name="data_aula" value="<?php echo $data_aula_selecionada; ?>">
                    <table class="chamada-table">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th width="35%">Status (P, F, A, FJ)</th>
                                <th width="35%">Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos_com_frequencia as $aluno): ?>
                            <tr>
                                <td>
                                    <span class="aluno-nome-clickable" data-aluno-id="<?php echo $aluno['aluno_id']; ?>" data-aluno-nome="<?php echo htmlspecialchars($aluno['aluno_nome']); ?>" data-turma-id="<?php echo $turma_selecionada_id; ?>">
                                        <?php echo htmlspecialchars($aluno['aluno_nome']); ?>
                                    </span>
                                </td>
                                <td class="status-buttons">
                                    <?php $aluno_id_input = $aluno['aluno_id']; ?>
                                    <div class="status-P">
                                        <input type="radio" id="p_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="P" <?php echo ($aluno['status'] == 'P') ? 'checked' : ''; ?>>
                                        <label for="p_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>">P</label>
                                    </div>
                                    <div class="status-F">
                                        <input type="radio" id="f_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="F" <?php echo ($aluno['status'] == 'F') ? 'checked' : ''; ?>>
                                        <label for="f_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>">F</label>
                                    </div>
                                    <div class="status-A">
                                        <input type="radio" id="a_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="A" <?php echo ($aluno['status'] == 'A') ? 'checked' : ''; ?>>
                                        <label for="a_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>">A</label>
                                    </div>
                                    <div class="status-FJ">
                                        <input type="radio" id="fj_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>" name="frequencia[<?php echo $aluno_id_input; ?>][status]" value="FJ" <?php echo ($aluno['status'] == 'FJ') ? 'checked' : ''; ?>>
                                        <label for="fj_<?php echo $aluno_id_input; ?>_<?php echo $turma_selecionada_id; ?>">FJ</label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="frequencia[<?php echo $aluno_id_input; ?>][observacao]" class="observacao-input" value="<?php echo htmlspecialchars($aluno['observacao'] ?? ''); ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn-salvar-chamada"><i class="fas fa-save"></i> Salvar Chamada</button>
                </form>
                <?php else: ?>
                    <p class="no-data-message">Nenhum aluno encontrado nesta turma.</p>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </main>
    </div>

    <div id="frequenciaModal" class="modal">
        <div class="modal-content">
            <span class="modal-close-button" onclick="document.getElementById('frequenciaModal').style.display='none'">&times;</span>
            <h3 id="modalAlunoNome"></h3>
            <div id="modalAlunoStats"><p>Carregando estatísticas...</p></div>
        </div>
    </div>

    <script>
        // Script do menu lateral (como antes)
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const container = document.querySelector('.container');
        if (menuToggle && sidebar && container) { /* ... */ }

        // Script para o Modal de Frequência do Aluno (como antes)
        const modal = document.getElementById('frequenciaModal');
        /* ... (resto do script do modal) ... */
        document.querySelectorAll('.aluno-nome-clickable').forEach(item => {
            item.addEventListener('click', function() { /* ... (lógica do modal como antes) ... */ });
        });
        window.onclick = function(event) { if (event.target == modal) { modal.style.display = "none"; } }
    </script>
</body>
</html>
<?php if($conn) mysqli_close($conn); ?>