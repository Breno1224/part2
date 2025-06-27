<?php
session_start();
include 'db.php';

// 1. Autenticação e Validação da Requisição
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno' || !isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_id'], $_POST['tentativa_id'], $_POST['respostas'])) {
    $quiz_id = intval($_POST['quiz_id']);
    $tentativa_id = intval($_POST['tentativa_id']);
    $respostas = $_POST['respostas'];

    // 2. Segurança: Verificar se a tentativa pertence ao aluno logado
    $sql_check = "SELECT id FROM quiz_tentativas_alunos WHERE id = ? AND aluno_id = ? AND quiz_id = ? AND status = 'em_andamento'";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "iii", $tentativa_id, $aluno_id, $quiz_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    
    if (mysqli_stmt_num_rows($stmt_check) == 0) {
        $_SESSION['quiz_aluno_status_message'] = "Erro: Tentativa de prova inválida ou já finalizada.";
        $_SESSION['quiz_aluno_status_type'] = "status-error";
        header("Location: aluno_ver_quizzes.php");
        exit();
    }
    mysqli_stmt_close($stmt_check);

    // 3. Iniciar Transação e Salvar Respostas
    mysqli_autocommit($conn, FALSE);
    try {
        foreach ($respostas as $questao_id => $resposta_data) {
            $questao_id = intval($questao_id);
            $opcao_id = isset($resposta_data['opcao_id']) ? intval($resposta_data['opcao_id']) : null;
            $texto_dissertativo = isset($resposta_data['texto']) ? trim($resposta_data['texto']) : null;

            // Insere a resposta do aluno na tabela
            $sql_insert_resposta = "INSERT INTO quiz_respostas_alunos (tentativa_id, questao_id, opcao_id_selecionada, texto_resposta_dissertativa) VALUES (?, ?, ?, ?)";
            $stmt_resposta = mysqli_prepare($conn, $sql_insert_resposta);
            mysqli_stmt_bind_param($stmt_resposta, "iiis", $tentativa_id, $questao_id, $opcao_id, $texto_dissertativo);
            
            if (!mysqli_stmt_execute($stmt_resposta)) {
                throw new Exception("Erro ao salvar resposta para a questão ID " . $questao_id);
            }
            mysqli_stmt_close($stmt_resposta);
        }

        // 4. Finalizar a tentativa
        $sql_finaliza_tentativa = "UPDATE quiz_tentativas_alunos SET status = 'finalizado', data_fim = NOW() WHERE id = ?";
        $stmt_finaliza = mysqli_prepare($conn, $sql_finaliza_tentativa);
        mysqli_stmt_bind_param($stmt_finaliza, "i", $tentativa_id);
        if (!mysqli_stmt_execute($stmt_finaliza)) {
            throw new Exception("Erro ao finalizar a tentativa.");
        }
        mysqli_stmt_close($stmt_finaliza);

        mysqli_commit($conn);
        $_SESSION['quiz_aluno_status_message'] = "Prova enviada com sucesso! Aguarde a avaliação do professor.";
        $_SESSION['quiz_aluno_status_type'] = "status-success";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        error_log("Erro em salvar_respostas_quiz.php: " . $e->getMessage());
        $_SESSION['quiz_aluno_status_message'] = "Ocorreu um erro ao salvar suas respostas. Tente novamente.";
        $_SESSION['quiz_aluno_status_type'] = "status-error";
    }

    mysqli_autocommit($conn, TRUE);
    header("Location: aluno_ver_quizzes.php");
    exit();

} else {
    // Redireciona se a requisição for inválida
    header("Location: aluno_ver_quizzes.php");
    exit();
}
?>