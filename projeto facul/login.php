<?php
session_start();
include("db.php"); 

$redirect_page_aluno = "aluno.php";
$redirect_page_docente = "professor.php";
$redirect_page_coordenacao = "coordenacao_painel.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']); 
    $senha_fornecida = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($senha_fornecida) || empty($role)) {
        echo "<script>alert('Por favor, preencha todos os campos.'); window.location.href='index.html';</script>";
        exit();
    }

    $sql = "";
    $error_msg = null; 

    if ($role === "aluno") {
        // ATUALIZADO: Adicionado tema_perfil
        $sql = "SELECT id, nome, email, senha, turma_id, tema_perfil FROM alunos WHERE email = ?";
    } elseif ($role === "docente") {
        // ATUALIZADO: Adicionado tema_perfil
        $sql = "SELECT id, nome, email, senha, tema_perfil FROM professores WHERE email = ?";
    } elseif ($role === "coordenacao") {
        // ATUALIZADO: Adicionado tema_perfil
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

            // Lembre-se de usar password_verify para senhas com hash!
            // Exemplo para coordenação (você ajustou para texto plano para teste):
            if ($role === "coordenacao") {
                if ($senha_fornecida === $senha_do_banco) { // Comparação de texto plano
                    $login_sucesso = true;
                } else {
                    $error_msg = "Usuário ou senha da coordenação incorretos.";
                }
            } elseif ($role === "aluno" || $role === "docente") {
                // Assumindo que alunos e docentes podem estar usando password_verify ou texto plano
                // Mantenha a lógica de verificação de senha que estava funcionando para eles
                // Para consistência com o exemplo da coordenação, se eles também usam texto plano:
                if ($senha_fornecida === $senha_do_banco) {
                     $login_sucesso = true;
                } else {
                     // Se estiverem usando hash, use: if(password_verify($senha_fornecida, $senha_do_banco))
                // if(password_verify($senha_fornecida, $senha_do_banco)) { // PARA SENHAS COM HASH
                //    $login_sucesso = true;
                // } else {
                    $error_msg = "Usuário ou senha incorretos.";
                }
            }

            if ($login_sucesso) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_email'] = $user['email'];
                $_SESSION['role'] = $role;
                // **** ADICIONAR O TEMA À SESSÃO ****
                $_SESSION['tema_usuario'] = !empty($user['tema_perfil']) ? $user['tema_perfil'] : 'padrao';
                // **** FIM DA ADIÇÃO ****

                if ($role === "aluno") {
                    $_SESSION['turma_id'] = $user['turma_id'];
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
        } else {
            $error_msg = ($role === "coordenacao") ? "Usuário ou senha da coordenação incorretos." : "Usuário ou senha incorretos.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_msg = "Erro no sistema de login: " . mysqli_error($conn);
    }

    if (isset($error_msg)) {
        echo "<script>alert('".$error_msg."'); window.location.href='index.html';</script>";
    } else {
        echo "<script>alert('Ocorreu uma falha no login. Tente novamente.'); window.location.href='index.html';</script>";
    }
    exit();

} else {
    header("Location: index.html");
    exit();
}
if($conn) mysqli_close($conn);
?>