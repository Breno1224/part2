<?php
// includes/sidebar_aluno.php
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'aluno') {
    echo "<p style='padding:1rem; color:white;'>Acesso restrito.</p>";
    return;
}


$loggedInAlunoId = $_SESSION['usuario_id']; // Assumindo que já existe
$linkMeuPerfilAluno = "perfil_aluno.php";
// $loggedInAlunoId = $_SESSION['usuario_id']; // Se precisar do ID do aluno aqui
// $linkMeuPerfilAluno = "perfil_aluno.php?id=" . $loggedInAlunoId; // Se houver página de perfil para aluno
?>
<ul>
    <li>
        <a href="aluno.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'inicio_noticias') echo 'class="active"'; ?>>
            <i class="fas fa-home"></i> Início & Notícias
        </a>
    </li>
    <li>
        <a href="boletim.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'boletim') echo 'class="active"'; ?>>
            <i class="fas fa-book"></i> Boletim
        </a>
    </li>
    <li>
        <a href="calendario.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'calendario') echo 'class="active"'; ?>>
            <i class="fas fa-calendar-alt"></i> Calendário
        </a>
    </li>
    <li>
        <a href="materiais.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'materiais') echo 'class="active"'; ?>>
            <i class="fas fa-book-open"></i> Materiais Didáticos
        </a>
    </li>
    <li>
        <a href="comunicados_aluno.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'comunicados_aluno') echo 'class="active"'; ?>>
            <i class="fas fa-bell"></i> Comunicados
        </a>
    </li>
      <li>
        <a href="<?php echo $linkMeuPerfilAluno; ?>" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'meu_perfil_aluno') echo 'class="active"'; ?>>
            <i class="fas fa-user-circle"></i> Meu Perfil
        </a>
    </li>
</ul>