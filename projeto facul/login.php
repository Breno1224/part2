<?php
session_start();
include("db.php"); // Sua conexão com o banco

// Definindo para onde redirecionar em caso de sucesso
$redirect_page_aluno = "aluno.php";
$redirect_page_docente = "professor.php";
$redirect_page_coordenacao = "coordenacao_painel.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); // Deve ser o email
    $senha_fornecida = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($senha_fornecida) || empty($role)) {
        echo "<script>alert('Por favor, preencha todos os campos.'); window.location.href='index.html';</script>";
        exit();
    }

    $sql = "";
    $error_msg = null; 

    if ($role === "aluno") {
        $sql = "SELECT id, nome, email, senha, turma_id, tema_perfil FROM alunos WHERE email = ?";
    } elseif ($role === "docente") {
        $sql = "SELECT id, nome, email, senha, tema_perfil FROM professores WHERE email = ?";
    } elseif ($role === "coordenacao") {
        $sql = "SELECT id, nome, email, senha, tema_perfil FROM coordenadores WHERE email = ?";
    } else {
        echo "<script>alert('Papel inválido selecionado.'); window.location.href='index.html';</script>";
        exit();
    }

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            $senha_do_banco = $user['senha'];
            $login_sucesso = false;

            // Verificação de Senha
            if ($role === "coordenacao") {
                // ATENÇÃO: Coordenadores estão usando comparação de texto plano conforme solicitado anteriormente.
                // ISSO NÃO É SEGURO PARA PRODUÇÃO. O ideal é usar password_verify() para todos.
                if ($senha_fornecida === $senha_do_banco) {
                    $login_sucesso = true;
                } else {
                    $error_msg = "Usuário ou senha da coordenação incorretos.";
                }
            } else { 
                // Alunos e Docentes DEVEM usar password_verify, 
                // pois os scripts de cadastro (salvar_aluno.php, salvar_professor.php) salvam senhas com HASH.
                if (password_verify($senha_fornecida, $senha_do_banco)) {
                    $login_sucesso = true;
                } else {
                    $error_msg = "Usuário ou senha incorretos.";
                }
            }

            if ($login_sucesso) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_email'] = $user['email'];
                $_SESSION['role'] = $role;
                // Define o tema do usuário na sessão
                $_SESSION['tema_usuario'] = !empty($user['tema_perfil']) ? $user['tema_perfil'] : 'padrao';

                if ($role === "aluno") {
                    if (isset($user['turma_id'])) { // Verifica se turma_id existe e não é nulo
                       $_SESSION['turma_id'] = $user['turma_id'];
                    } else {
                       $_SESSION['turma_id'] = null; // Ou um valor padrão se necessário
                    }
                    header("Location: " . $redirect_page_aluno);
                    exit();
                } elseif ($role === "docente") {
                    header("Location: " . $redirect_page_docente);
                    exit();
                } elseif ($role === "coordenacao") {
                    header("Location: " . $redirect_page_coordenacao);
                    exit();
                }
            }
            // Se $error_msg não foi setado aqui, mas login_sucesso é false (não deveria acontecer com a lógica acima)
            // O $error_msg já terá sido definido pela falha na verificação da senha.

        } else {
            // Usuário não encontrado
            if ($role === "coordenacao") {
                $error_msg = "Usuário ou senha da coordenação incorretos.";
            } else {
                $error_msg = "Usuário ou senha incorretos.";
            }
        }
        mysqli_stmt_close($stmt);
    } else {
        // Erro na preparação da query
        $error_msg = "Erro no sistema de login. Por favor, tente novamente mais tarde. Detalhe do erro: " . mysqli_error($conn);
    }

    // Se chegou até aqui, significa que o login falhou ou houve um erro.
    if (isset($error_msg)) {
        echo "<script>alert('".$error_msg."'); window.location.href='index.html';</script>";
    } else {
        // Fallback para um erro genérico se $error_msg não foi setada mas não houve redirect
        echo "<script>alert('Ocorreu uma falha inesperada no login. Tente novamente.'); window.location.href='index.html';</script>";
    }
    exit();

} else {
    // Se não for POST, redireciona para o index
    header("Location: index.html");
    exit();
}

if (isset($conn) && $conn) { // Fecha a conexão se ela foi aberta
    mysqli_close($conn);
}
?>