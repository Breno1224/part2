<?php
session_start();
include 'db.php';

header('Content-Type: application/json'); // Define o tipo de conteúdo da resposta

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $turma_id = isset($_POST['turma_id_form']) ? intval($_POST['turma_id_form']) : null;
    $disciplina_id = isset($_POST['disciplina_id_form']) ? intval($_POST['disciplina_id_form']) : null;
    $avaliacao = isset($_POST['avaliacao_form']) ? trim($_POST['avaliacao_form']) : null;
    $bimestre = isset($_POST['bimestre_form']) ? intval($_POST['bimestre_form']) : null;
    $notas_alunos = isset($_POST['notas']) ? $_POST['notas'] : []; // Array de [id_aluno => nota]
    
    $professor_id = $_SESSION['usuario_id'];
    $data_lancamento = date('Y-m-d'); // Data atual no formato YYYY-MM-DD

    if (empty($turma_id) || empty($disciplina_id) || empty($avaliacao) || empty($bimestre) || empty($notas_alunos)) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos. Verifique todos os campos.']);
        exit();
    }

    $erros = [];
    $sucessos = 0;

    // Prepara a query uma vez
    $sql = "INSERT INTO notas (aluno_id, turma_id, disciplina_id, professor_id, avaliacao, nota, data_lancamento, bimestre)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query: ' . mysqli_error($conn)]);
        exit();
    }

    foreach ($notas_alunos as $aluno_id => $nota_str) {
        if ($nota_str === '' || $nota_str === null) { // Permite notas vazias (não lançar para este aluno)
            continue;
        }
        
        $nota = filter_var($nota_str, FILTER_VALIDATE_FLOAT);
        $aluno_id_int = intval($aluno_id);

        if ($nota === false || $nota < 0 || $nota > 10) {
            $erros[] = "Nota inválida ('{$nota_str}') para o aluno ID {$aluno_id_int}. A nota deve ser entre 0 e 10.";
            continue;
        }
        
        mysqli_stmt_bind_param($stmt, "iiiisdsi", $aluno_id_int, $turma_id, $disciplina_id, $professor_id, $avaliacao, $nota, $data_lancamento, $bimestre);
        
        if (mysqli_stmt_execute($stmt)) {
            $sucessos++;
        } else {
            $erros[] = "Erro ao lançar nota para aluno ID {$aluno_id_int}: " . mysqli_stmt_error($stmt);
        }
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    if (count($erros) > 0) {
        $errorMessage = "Notas lançadas com {$sucessos} sucesso(s). Erros encontrados: <br>" . implode("<br>", $erros);
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    } elseif ($sucessos > 0) {
        echo json_encode(['success' => true, 'message' => "{$sucessos} nota(s) lançada(s) com sucesso!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma nota válida foi processada.']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
?>