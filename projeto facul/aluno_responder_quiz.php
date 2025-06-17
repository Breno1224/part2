<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    header("Location: index.html");
    exit();
}
include 'db.php';

$aluno_id = $_SESSION['usuario_id'];
$turma_id_aluno = $_SESSION['turma_id'];
$nome_aluno = $_SESSION['usuario_nome'];
$currentPageIdentifier = 'ver_quizzes_aluno';
$tema_global_usuario = isset($_SESSION['tema_usuario']) ? $_SESSION['tema_usuario'] : 'padrao';

if (!isset($_GET['id'])) {
    header("Location: aluno_ver_quizzes.php");
    exit();
}
$quiz_id = intval($_GET['id']);

// 1. Buscar informações do quiz e validar permissões
$sql_quiz_info = "SELECT * FROM quizzes WHERE id = ? AND turma_id = ? AND NOW() <= data_prazo";
$stmt_quiz = mysqli_prepare($conn, $sql_quiz_info);
mysqli_stmt_bind_param($stmt_quiz, "ii", $quiz_id, $turma_id_aluno);
mysqli_stmt_execute($stmt_quiz);
$quiz_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_quiz));
mysqli_stmt_close($stmt_quiz);

if (!$quiz_info) {
    $_SESSION['quiz_aluno_status_message'] = "Prova não encontrada, indisponível ou prazo encerrado.";
    $_SESSION['quiz_aluno_status_type'] = "status-error";
    header("Location: aluno_ver_quizzes.php");
    exit();
}

// 2. Verificar se já existe uma tentativa
$sql_tentativa = "SELECT id, status FROM quiz_tentativas_alunos WHERE quiz_id = ? AND aluno_id = ?";
$stmt_tentativa = mysqli_prepare($conn, $sql_tentativa);
mysqli_stmt_bind_param($stmt_tentativa, "ii", $quiz_id, $aluno_id);
mysqli_stmt_execute($stmt_tentativa);
$tentativa_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_tentativa));
mysqli_stmt_close($stmt_tentativa);

if ($tentativa_info && in_array($tentativa_info['status'], ['finalizado', 'avaliado'])) {
    $_SESSION['quiz_aluno_status_message'] = "Você já finalizou esta prova.";
    $_SESSION['quiz_aluno_status_type'] = "status-info";
    header("Location: aluno_ver_quizzes.php");
    exit();
}

// 3. Se não houver tentativa, criar uma nova
$tentativa_id = $tentativa_info['id'] ?? null;
if (!$tentativa_id) {
    $sql_nova_tentativa = "INSERT INTO quiz_tentativas_alunos (quiz_id, aluno_id, status) VALUES (?, ?, 'em_andamento')";
    $stmt_nova = mysqli_prepare($conn, $sql_nova_tentativa);
    mysqli_stmt_bind_param($stmt_nova, "ii", $quiz_id, $aluno_id);
    mysqli_stmt_execute($stmt_nova);
    $tentativa_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_nova);
}

// 4. Buscar todas as questões e opções da prova
$questoes = [];
$sql_questoes = "SELECT id, texto_pergunta, tipo_pergunta, pontos FROM quiz_questoes WHERE quiz_id = ? ORDER BY ordem ASC";
$stmt_q = mysqli_prepare($conn, $sql_questoes);
mysqli_stmt_bind_param($stmt_q, "i", $quiz_id);
mysqli_stmt_execute($stmt_q);
$result_q = mysqli_stmt_get_result($stmt_q);
while ($row_q = mysqli_fetch_assoc($result_q)) {
    if ($row_q['tipo_pergunta'] === 'multipla_escolha') {
        $row_q['opcoes'] = [];
        $sql_opcoes = "SELECT id, texto_opcao FROM quiz_opcoes WHERE questao_id = ?";
        $stmt_o = mysqli_prepare($conn, $sql_opcoes);
        mysqli_stmt_bind_param($stmt_o, "i", $row_q['id']);
        mysqli_stmt_execute($stmt_o);
        $result_o = mysqli_stmt_get_result($stmt_o);
        while ($row_o = mysqli_fetch_assoc($result_o)) {
            $row_q['opcoes'][] = $row_o;
        }
        mysqli_stmt_close($stmt_o);
    }
    $questoes[] = $row_q;
}
mysqli_stmt_close($stmt_q);

// Aleatorizar questões se a opção estiver marcada no quiz
if ($quiz_info['aleatorizar_questoes']) {
    shuffle($questoes);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Respondendo: <?php echo htmlspecialchars($quiz_info['titulo']); ?> - ACADMIX</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/aluno.css">
    <link rel="stylesheet" href="css/temas_globais.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($tema_global_usuario === '8bit'): ?>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <?php endif; ?>
    <style>
        .quiz-header { text-align: center; margin-bottom: 2rem; }
        .quiz-header h2 { font-size: 1.8rem; }
        .quiz-header #timer { font-size: 1.5rem; font-weight: bold; color: var(--danger-color); }
        .questao { margin-bottom: 2rem; padding: 1.5rem; border-radius: 8px; }
        .questao-header { font-weight: bold; margin-bottom: 1rem; }
        .questao-texto { white-space: pre-wrap; margin-bottom: 1rem; }
        .opcao-resposta { display: block; margin-bottom: 0.7rem; }
        .opcao-resposta input[type="radio"] { margin-right: 10px; }
        .resposta-dissertativa { width: 100%; min-height: 150px; padding: 0.5rem; }
        .submit-quiz-btn { display: block; width: 100%; max-width: 400px; margin: 2rem auto 0 auto; padding: 1rem; font-size: 1.2rem; }
    </style>
</head>
<body class="theme-<?php echo htmlspecialchars($tema_global_usuario); ?>">
    <header>
        <h1>ACADMIX - Prova Online</h1>
    </header>
    <div class="container" style="padding-top: 2rem;"> <main class="main-content" style="width: 100%; max-width: 900px; margin: auto;">
            <div class="quiz-header">
                <h2><?php echo htmlspecialchars($quiz_info['titulo']); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($quiz_info['descricao'])); ?></p>
                <?php if ($quiz_info['duracao_minutos'] > 0): ?>
                    <div id="timer">--:--</div>
                <?php endif; ?>
            </div>

            <form id="quiz-form" action="aluno_salvar_respostas_quiz.php" method="POST">
                <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                <input type="hidden" name="tentativa_id" value="<?php echo $tentativa_id; ?>">

                <?php foreach ($questoes as $index => $q): ?>
                    <section class="questao card">
                        <p class="questao-header">Questão <?php echo $index + 1; ?> (Vale: <?php echo number_format($q['pontos'], 1, ',', '.'); ?> pontos)</p>
                        <div class="questao-texto"><?php echo nl2br(htmlspecialchars($q['texto_pergunta'])); ?></div>
                        
                        <?php if ($q['tipo_pergunta'] === 'multipla_escolha'): ?>
                            <div class="opcoes-container">
                                <?php 
                                    // Aleatorizar opções se desejado (requer alteração na lógica para manter consistência)
                                    // Por simplicidade, não vamos aleatorizar as opções por enquanto.
                                    foreach ($q['opcoes'] as $opcao): ?>
                                    <label class="opcao-resposta">
                                        <input type="radio" name="respostas[<?php echo $q['id']; ?>][opcao_id]" value="<?php echo $opcao['id']; ?>" required>
                                        <?php echo htmlspecialchars($opcao['texto_opcao']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($q['tipo_pergunta'] === 'dissertativa'): ?>
                            <textarea name="respostas[<?php echo $q['id']; ?>][texto]" class="resposta-dissertativa input-field" placeholder="Digite sua resposta aqui..." required></textarea>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>

                <button type="submit" class="button button-primary submit-quiz-btn"><i class="fas fa-check-circle"></i> Finalizar e Enviar Respostas</button>
            </form>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const duracaoMinutos = <?php echo intval($quiz_info['duracao_minutos']); ?>;
        const form = document.getElementById('quiz-form');

        if (duracaoMinutos > 0) {
            const timerDisplay = document.getElementById('timer');
            let tempoRestante = duracaoMinutos * 60;

            const intervalId = setInterval(() => {
                if (tempoRestante <= 0) {
                    clearInterval(intervalId);
                    alert("Tempo esgotado! A prova será enviada automaticamente.");
                    form.submit();
                    return;
                }
                
                tempoRestante--;

                const minutos = Math.floor(tempoRestante / 60);
                const segundos = tempoRestante % 60;
                timerDisplay.textContent = `${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;

            }, 1000);
        }

        form.addEventListener('submit', function(e) {
            const confirma = confirm("Tem certeza que deseja finalizar e enviar suas respostas? Esta ação não pode ser desfeita.");
            if (!confirma) {
                e.preventDefault(); // Impede o envio do formulário se o usuário clicar em "Cancelar"
            }
        });
    });
    </script>
</body>
</html>