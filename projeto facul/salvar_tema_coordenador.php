<?php
// salvar_tema_coordenador.php
session_start();
include 'db.php'; // Assume $conn é estabelecido aqui

// Verificar se o usuário está logado e tem o papel correto
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao' || !isset($_SESSION['usuario_id'])) {
    // Se o perfil sendo editado não é o do usuário logado, ou papel incorreto, redirecionar ou mostrar erro.
    // Para este script, assumimos que o coordenador SÓ PODE editar o SEU PRÓPRIO tema.
    $_SESSION['tema_status_message'] = "Acesso negado para alterar este tema.";
    $_SESSION['tema_status_type'] = "status-error";
    header("Location: painel_coordenacao.php"); // Ou uma página de erro apropriada
    exit();
}

$coordenador_id_logado = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tema_perfil_coordenador'])) { // Nome do select no formulário
    $novo_tema = $_POST['tema_perfil_coordenador'];

    // Validar $novo_tema contra uma lista de temas disponíveis para segurança
    $temas_disponiveis_validos = ['padrao', '8bit', 'natureza', 'academico', 'darkmode']; // Mantenha esta lista sincronizada

    if (in_array($novo_tema, $temas_disponiveis_validos)) {
        // Atualizar no banco de dados para o coordenador logado
        $sql_update_tema = "UPDATE coordenadores SET tema_perfil = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql_update_tema);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $novo_tema, $coordenador_id_logado);
            if (mysqli_stmt_execute($stmt)) {
                // MAIS IMPORTANTE: Atualizar a variável de sessão para efeito imediato
                $_SESSION['tema_usuario'] = $novo_tema;
                $_SESSION['tema_status_message'] = "Tema atualizado com sucesso!";
                $_SESSION['tema_status_type'] = "status-success";
            } else {
                $_SESSION['tema_status_message'] = "Erro ao salvar a preferência de tema no banco de dados: " . mysqli_stmt_error($stmt);
                $_SESSION['tema_status_type'] = "status-error";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['tema_status_message'] = "Erro ao preparar para salvar o tema: " . mysqli_error($conn);
            $_SESSION['tema_status_type'] = "status-error";
        }
    } else {
        $_SESSION['tema_status_message'] = "Tema selecionado é inválido.";
        $_SESSION['tema_status_type'] = "status-error";
    }
} else {
    $_SESSION['tema_status_message'] = "Requisição inválida para alterar o tema.";
    $_SESSION['tema_status_type'] = "status-error";
}

// Redirecionar de volta para a página de perfil do coordenador logado
header("Location: perfil_coordenador.php?id=" . $coordenador_id_logado);
exit();
?>