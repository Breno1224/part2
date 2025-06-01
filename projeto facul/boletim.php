<?php
session_start(); 
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    header("Location: index.html");
    exit();
}
include 'db.php'; 

$aluno_id = $_SESSION['usuario_id'];
$nome_aluno = $_SESSION['usuario_nome'];
$turma_id_aluno = isset($_SESSION['turma_id']) ? intval($_SESSION['turma_id']) : 0;
$currentPageIdentifier = 'boletim'; 
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

$disciplinas_da_turma_map = [];
if ($turma_id_aluno > 0) {
    $sql_disciplinas_turma = "SELECT DISTINCT d.id as id_disciplina, d.nome_disciplina
                              FROM disciplinas d
                              JOIN professores_turmas_disciplinas ptd ON d.id = ptd.disciplina_id
                              WHERE ptd.turma_id = ?
                              ORDER BY d.nome_disciplina";
    $stmt_disc_turma = mysqli_prepare($conn, $sql_disciplinas_turma);
    if ($stmt_disc_turma) {
        mysqli_stmt_bind_param($stmt_disc_turma, "i", $turma_id_aluno);
        mysqli_stmt_execute($stmt_disc_turma);
        $result_disc_turma = mysqli_stmt_get_result($stmt_disc_turma);
        while ($disc_row = mysqli_fetch_assoc($result_disc_turma)) {
            $disciplinas_da_turma_map[$disc_row['nome_disciplina']] = $disc_row;
        }
        mysqli_stmt_close($stmt_disc_turma);
    } else {
        error_log("Erro ao buscar disciplinas da turma: " . mysqli_error($conn));
    }
}

$sql_notas = "
    SELECT 
        d.nome_disciplina, n.avaliacao, n.nota, n.bimestre
    FROM notas n
    JOIN disciplinas d ON n.disciplina_id = d.id
    WHERE n.aluno_id = ?
    ORDER BY d.nome_disciplina, n.bimestre, n.avaliacao";
$stmt_notas = mysqli_prepare($conn, $sql_notas);
$boletim_data_notas = []; 
if ($stmt_notas) { 
    mysqli_stmt_bind_param($stmt_notas, "i", $aluno_id);
    mysqli_stmt_execute($stmt_notas);
    $resultado_notas = mysqli_stmt_get_result($stmt_notas);
    while ($nota_row = mysqli_fetch_assoc($resultado_notas)) {
        $boletim_data_notas[$nota_row['nome_disciplina']][$nota_row['bimestre']][] = [
            'avaliacao' => $nota_row['avaliacao'],
            'nota' => $nota_row['nota']
        ];
    }
    mysqli_stmt_close($stmt_notas);
} else {
    error_log("Erro ao preparar statement para buscar notas: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Boletim - ACADMIX</title>
    <link rel="stylesheet" href="css/aluno.css"> 
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        .main-content h2.page-title-boletim { 
            text-align: center; font-size: 1.8rem; margin-bottom: 1.5rem;
            /* color e border-bottom virão do tema ou aluno.css */
        }
        .table-container { 
            overflow-x: auto; 
            /* background-color, box-shadow, border virão do tema para .dashboard-section se você usar essa classe no div pai */
        }
        .boletim-table { 
            width: 100%;
            border-collapse: collapse;
            /* background-color virá do tema */
        }
        /* Estilo ESPECÍFICO para o cabeçalho da tabela do boletim, garantindo o amarelo */
        .boletim-table thead th { 
            background-color: #D69D2A !important; 
            color: white !important;
            padding: 12px;
            text-align: center;
            font-size: 0.9rem; 
        }
        .boletim-table tbody td {
            padding: 10px;
            text-align: center;
            font-size: 0.9rem;
            /* border-bottom virá do tema ou aluno.css */
        }
        .boletim-table tbody tr:nth-child(even) {
            /* background-color: #f9f9f9; -- Estilo de zebra PODE vir do tema ou ser definido aqui se necessário */
        }
        .boletim-table tbody tr.subject-row:hover { 
            cursor: pointer; 
            /* background-color: #f1f1f1; -- Virá do tema */
        }
         /* CSS ESSENCIAL para a funcionalidade de expandir/ocultar */
        .details-row { 
            display: none; 
            /* background-color: #f9f9f9; -- Virá do tema se aplicado a .details-row td */
        }
        .details-row td { /* Para que o fundo do tema seja aplicado corretamente */
            padding: 0; /* Remover padding padrão para o td que contém o details-content */
        }
        .details-content { 
            padding: 15px; 
            text-align: left; 
            /* background-color virá do tema para .details-row, ou pode ser estilizado diretamente */
        }
        .details-content h4 { margin-top:0; margin-bottom:10px; font-size:1.1rem; /* color virá do tema */ }
        .details-content ul { list-style-type: none; padding-left: 0; } 
        .details-content li { margin-bottom: 6px; font-size:0.9rem; }
        .details-content p { margin: 0.5rem 0;}
        .details-content p strong { font-weight:bold; /* color virá do tema */}

        .no-grades td { text-align: center; padding: 20px; /* color virá do tema */ }
        .situacao-aprovado { color: green; font-weight:bold; }
        .situacao-reprovado { color: red; font-weight:bold; }
        .situacao-cursando { color: orange; font-weight:bold; }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">
    <header>
        <button id="menu-toggle" class="menu-btn"><i class="fas fa-bars"></i></button>
        <h1>ACADMIX - Boletim de <?php echo htmlspecialchars($nome_aluno); ?></h1>
        <form action="logout.php" method="post" style="display: inline;">
             <button type="submit" id="logoutBtnHeader"><i class="fas fa-sign-out-alt"></i> Sair</button>
        </form>
    </header>

    <div class="container">
        <nav class="sidebar" id="sidebar">
            <?php
            $sidebar_path = __DIR__ . '/includes/sidebar_aluno.php';
            if (file_exists($sidebar_path)) { include $sidebar_path; }
            else { echo "<p style='padding:1rem; color:white;'>Erro: Sidebar não encontrada.</p>"; }
            ?>
        </nav>

        <main class="main-content">
            <div style="text-align:center;">
                 <h2 class="page-title-boletim">Boletim Escolar</h2>
            </div>
            <div class="table-container dashboard-section"> 
                <table class="boletim-table">
                    <thead>
                        <tr>
                            <th>Disciplina</th>
                            <th>1º Bimestre</th><th>2º Bimestre</th>
                            <th>3º Bimestre</th><th>4º Bimestre</th>
                            <th>Média Final</th><th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($disciplinas_da_turma_map)): ?>
                            <tr class="no-grades"><td colspan="7">Sua turma ainda não tem disciplinas configuradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($disciplinas_da_turma_map as $nome_disciplina_turma => $disciplina_info_turma): ?>
                                <?php $bimestres_notas = $boletim_data_notas[$nome_disciplina_turma] ?? []; ?>
                                <tr class="subject-row">
                                    <td><?php echo htmlspecialchars($nome_disciplina_turma); ?></td>
                                    <?php
                                    $soma_medias_bimestrais = 0; $bimestres_com_media = 0; $media_final_disciplina = 0;
                                    for ($b = 1; $b <= 4; $b++):
                                        $notas_bimestre_atual = $bimestres_notas[$b] ?? [];
                                        $soma_notas_bimestre = 0; $qtd_notas_bimestre = count($notas_bimestre_atual);
                                        if ($qtd_notas_bimestre > 0) {
                                            foreach ($notas_bimestre_atual as $avaliacao_info) { $soma_notas_bimestre += floatval($avaliacao_info['nota']); }
                                            $media_bimestre = $soma_notas_bimestre / $qtd_notas_bimestre;
                                            echo '<td>' . number_format($media_bimestre, 1, ',', '.') . '</td>';
                                            $soma_medias_bimestrais += $media_bimestre; $bimestres_com_media++;
                                        } else { echo '<td>-</td>'; }
                                    endfor;
                                    if ($bimestres_com_media > 0) {
                                        // Ajuste na média final: se nem todos os bimestres têm nota, a média pode ser sobre os que têm ou sobre 4.
                                        // Aqui, está sobre os bimestres que TÊM média. Ajuste conforme a regra da sua escola.
                                        $media_final_disciplina = $soma_medias_bimestrais / $bimestres_com_media; 
                                        echo '<td>' . number_format($media_final_disciplina, 1, ',', '.') . '</td>';
                                        if ($media_final_disciplina >= 6.0) { echo '<td class="situacao-aprovado">Aprovado</td>'; }
                                        else { echo '<td class="situacao-reprovado">Reprovado</td>'; }
                                    } else { echo '<td>-</td>'; echo '<td class="situacao-cursando">Cursando</td>'; }
                                    ?>
                                </tr>
                                <tr class="details-row">
                                    <td colspan="7">
                                        <div class="details-content">
                                            <h4>Detalhes de <?php echo htmlspecialchars($nome_disciplina_turma); ?>:</h4>
                                            <?php $tem_detalhes_bimestre_geral = false; ?>
                                            <?php for ($b = 1; $b <= 4; $b++): ?>
                                                <?php $notas_do_bimestre_atual_detalhe = $bimestres_notas[$b] ?? []; ?>
                                                <?php if (!empty($notas_do_bimestre_atual_detalhe)): $tem_detalhes_bimestre_geral = true; ?>
                                                    <p><strong><?php echo $b; ?>º Bimestre:</strong></p>
                                                    <ul>
                                                        <?php foreach ($notas_do_bimestre_atual_detalhe as $avaliacao_info): ?>
                                                            <li><?php echo htmlspecialchars($avaliacao_info['avaliacao']); ?>: <?php echo number_format(floatval($avaliacao_info['nota']), 2, ',', '.'); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <?php if (!$tem_detalhes_bimestre_geral): ?>
                                                <p>Nenhuma avaliação detalhada lançada para esta disciplina.</p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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

        // SCRIPT PARA EXPANDIR/OCULTAR DETALHES DA DISCIPLINA (ESSENCIAL)
        const subjectRows = document.querySelectorAll('.subject-row');
        subjectRows.forEach(row => {
            row.addEventListener('click', () => {
                const nextRow = row.nextElementSibling;
                if (nextRow && nextRow.classList.contains('details-row')) {
                    // Alterna a exibição da linha de detalhes
                    if (nextRow.style.display === 'table-row') {
                        nextRow.style.display = 'none';
                    } else {
                        nextRow.style.display = 'table-row';
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php if(isset($conn) && $conn) mysqli_close($conn); ?>