<?php
session_start();
include 'db.php'; 

// Validação de segurança: apenas professores logados podem salvar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    // Definir uma mensagem de erro na sessão e redirecionar para uma página segura
    $_SESSION['quiz_status_message'] = "Erro: Acesso negado.";
    $_SESSION['quiz_status_type'] = "status-error";
    header("Location: painel_professor.php"); // Redireciona para o painel principal
    exit();
}

$professor_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_autocommit($conn, FALSE); // Iniciar transação para garantir consistência dos dados

    try {
        // 1. Pegar e validar dados principais do quiz
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        // O valor do select vem como "turma_id-disciplina_id"
        if(empty($_POST['turma_disciplina_id']) || strpos($_POST['turma_disciplina_id'], '-') === false) {
            throw new Exception("Seleção de Turma/Disciplina inválida.");
        }
        list($turma_id, $disciplina_id) = explode('-', $_POST['turma_disciplina_id']);
        $turma_id = intval($turma_id);
        $disciplina_id = intval($disciplina_id);
        
        $data_inicio = $_POST['data_inicio'];
        $data_prazo = $_POST['data_prazo'];
        $duracao_minutos = intval($_POST['duracao_minutos']);
        $aleatorizar_questoes = isset($_POST['aleatorizar_questoes']) ? 1 : 0;

        if (empty($titulo) || empty($turma_id) || empty($disciplina_id) || empty($data_prazo) || empty($data_inicio)) {
            throw new Exception("Dados principais do quiz estão faltando.");
        }

        // 2. Inserir na tabela 'quizzes'
        $sql_quiz = "INSERT INTO quizzes (titulo, descricao, professor_id, disciplina_id, turma_id, data_inicio, data_prazo, duracao_minutos, aleatorizar_questoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_quiz = mysqli_prepare($conn, $sql_quiz);
        mysqli_stmt_bind_param($stmt_quiz, "ssiiisssi", $titulo, $descricao, $professor_id, $disciplina_id, $turma_id, $data_inicio, $data_prazo, $duracao_minutos, $aleatorizar_questoes);
        if (!mysqli_stmt_execute($stmt_quiz)) {
            throw new Exception("Erro ao salvar dados do quiz: " . mysqli_stmt_error($stmt_quiz));
        }
        $quiz_id = mysqli_insert_id($conn); 
        mysqli_stmt_close($stmt_quiz);

        // 3. Processar e inserir cada questão
        if (isset($_POST['questoes']) && is_array($_POST['questoes'])) {
            foreach ($_POST['questoes'] as $qIndex => $qData) {
                $texto_pergunta = trim($qData['texto']);
                $tipo_pergunta = $qData['tipo'];
                $pontos = floatval(str_replace(',', '.', $qData['pontos']));

                if(empty($texto_pergunta)){
                    throw new Exception("O enunciado da questão #".($qIndex+1)." não pode estar vazio.");
                }

                $sql_questao = "INSERT INTO quiz_questoes (quiz_id, texto_pergunta, tipo_pergunta, pontos, ordem) VALUES (?, ?, ?, ?, ?)";
                $stmt_questao = mysqli_prepare($conn, $sql_questao);
                mysqli_stmt_bind_param($stmt_questao, "issdi", $quiz_id, $texto_pergunta, $tipo_pergunta, $pontos, $qIndex);
                if (!mysqli_stmt_execute($stmt_questao)) {
                    throw new Exception("Erro ao salvar questão #".($qIndex+1).": " . mysqli_stmt_error($stmt_questao));
                }
                $questao_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt_questao);

                // 4. Se for múltipla escolha, inserir as opções
                if ($tipo_pergunta === 'multipla_escolha' && isset($qData['opcoes']) && is_array($qData['opcoes'])) {
                    $opcao_correta_index = isset($qData['correta']) ? intval($qData['correta']) : -1;
                    if ($opcao_correta_index === -1) {
                         throw new Exception("Uma resposta correta deve ser marcada para a questão de múltipla escolha #".($qIndex+1).".");
                    }
                    
                    foreach ($qData['opcoes'] as $oIndex => $oData) {
                        $texto_opcao = trim($oData['texto']);
                        if(empty($texto_opcao)){
                             throw new Exception("O texto da opção #".($oIndex+1)." na questão #".($qIndex+1)." não pode estar vazio.");
                        }
                        $is_correta = ($oIndex == $opcao_correta_index) ? 1 : 0;
                        
                        $sql_opcao = "INSERT INTO quiz_opcoes (questao_id, texto_opcao, is_correta) VALUES (?, ?, ?)";
                        $stmt_opcao = mysqli_prepare($conn, $sql_opcao);
                        mysqli_stmt_bind_param($stmt_opcao, "isi", $questao_id, $texto_opcao, $is_correta);
                        if (!mysqli_stmt_execute($stmt_opcao)) {
                            throw new Exception("Erro ao salvar opção da questão #".($qIndex+1).": " . mysqli_stmt_error($stmt_opcao));
                        }
                        mysqli_stmt_close($stmt_opcao);
                    }
                }
            }
        } else {
            throw new Exception("Nenhuma questão foi adicionada à prova.");
        }
        
        mysqli_commit($conn); // Se tudo deu certo, confirma as alterações
        $_SESSION['quiz_status_message'] = "Prova/Quiz criado com sucesso!";
        $_SESSION['quiz_status_type'] = "status-success";

    } catch (Exception $e) {
        mysqli_rollback($conn); // Se algo deu errado, desfaz tudo
        $_SESSION['quiz_status_message'] = "Erro: " . $e->getMessage();
        $_SESSION['quiz_status_type'] = "status-error";
        error_log("Erro ao salvar quiz: " . $e->getMessage());
    }
} else {
    $_SESSION['quiz_status_message'] = "Requisição inválida.";
    $_SESSION['quiz_status_type'] = "status-error";
}

// Redireciona de volta para a página de criação
header("Location: professor_criar_quiz.php");
exit();
?>