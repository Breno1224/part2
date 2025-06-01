<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    $_SESSION['frequencia_status_message'] = "Acesso negado ou sessão inválida.";
    $_SESSION['frequencia_status_type'] = "status-error";
    header("Location: frequencia_professor.php"); // Redireciona de volta
    exit();
}

$professor_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $turma_id = isset($_POST['turma_id']) ? intval($_POST['turma_id']) : null;
    $data_aula = isset($_POST['data_aula']) ? $_POST['data_aula'] : null;
    $frequencias_alunos = isset($_POST['frequencia']) ? $_POST['frequencia'] : [];

    if (empty($turma_id) || empty($data_aula) || empty($frequencias_alunos)) {
        $_SESSION['frequencia_status_message'] = "Dados incompletos para salvar a frequência.";
        $_SESSION['frequencia_status_type'] = "status-error";
        header("Location: frequencia_professor.php?turma_id=" . $turma_id . "&data_aula=" . $data_aula);
        exit();
    }

    // Preparar a query para INSERT ou UPDATE
    // A chave UNIQUE (aluno_id, turma_id, data_aula) na tabela 'frequencia' é essencial aqui.
    $sql = "INSERT INTO frequencia (aluno_id, turma_id, professor_id, data_aula, status, observacao)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            professor_id = VALUES(professor_id), 
            status = VALUES(status), 
            observacao = VALUES(observacao)";
    
    $stmt_save = mysqli_prepare($conn, $sql);

    if (!$stmt_save) {
        $_SESSION['frequencia_status_message'] = "Erro ao preparar a query para salvar frequência: " . mysqli_error($conn);
        $_SESSION['frequencia_status_type'] = "status-error";
        header("Location: frequencia_professor.php?turma_id=" . $turma_id . "&data_aula=" . $data_aula);
        exit();
    }

    $erros_salvamento = 0;
    $sucessos_salvamento = 0;

    foreach ($frequencias_alunos as $aluno_id => $dados_frequencia) {
        $aluno_id_int = intval($aluno_id);
        $status = isset($dados_frequencia['status']) ? $dados_frequencia['status'] : null;
        $observacao = isset($dados_frequencia['observacao']) ? trim($dados_frequencia['observacao']) : null;

        // Validar status
        $status_validos = ['P', 'F', 'A', 'FJ'];
        if (!in_array($status, $status_validos)) {
            // Ignorar ou registrar erro se o status for inválido
            $erros_salvamento++;
            continue; 
        }
        
        mysqli_stmt_bind_param($stmt_save, "iiisss", $aluno_id_int, $turma_id, $professor_id, $data_aula, $status, $observacao);
        if (mysqli_stmt_execute($stmt_save)) {
            $sucessos_salvamento++;
        } else {
            $erros_salvamento++;
            error_log("Erro ao salvar frequência para aluno ID {$aluno_id_int}: " . mysqli_stmt_error($stmt_save));
        }
    }
    mysqli_stmt_close($stmt_save);

    if ($erros_salvamento > 0) {
        $_SESSION['frequencia_status_message'] = "Frequência salva com {$sucessos_salvamento} sucesso(s) e {$erros_salvamento} erro(s).";
        $_SESSION['frequencia_status_type'] = $sucessos_salvamento > 0 ? "status-success" : "status-error"; // Pode ser misto
    } elseif ($sucessos_salvamento > 0) {
        $_SESSION['frequencia_status_message'] = "Frequência salva com sucesso para {$sucessos_salvamento} aluno(s)!";
        $_SESSION['frequencia_status_type'] = "status-success";
    } else {
        $_SESSION['frequencia_status_message'] = "Nenhuma alteração de frequência foi processada.";
        $_SESSION['frequencia_status_type'] = "status-error"; // Ou info
    }

} else {
    $_SESSION['frequencia_status_message'] = "Requisição inválida.";
    $_SESSION['frequencia_status_type'] = "status-error";
}

mysqli_close($conn);
// Redireciona de volta para a página de frequência com os filtros selecionados
header("Location: frequencia_professor.php?turma_id=" . $turma_id . "&data_aula=" . $data_aula);
exit();
?>