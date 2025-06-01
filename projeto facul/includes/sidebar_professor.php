<?php
// Arquivo: includes/sidebar_professor.php

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'docente') {
    echo "<p style='padding:1rem; color:white;'>Erro: Sessão de professor inválida para carregar sidebar.</p>";
    return;
}

$loggedInProfessorId = $_SESSION['usuario_id'];
$linkMeuPerfil = "perfil_professor.php?id=" . $loggedInProfessorId;

// Verifica se a página atual é o perfil do professor logado
$isPerfilPageActive = (isset($currentPageIdentifier) && $currentPageIdentifier === 'meu_perfil' && isset($_GET['id']) && intval($_GET['id']) == $loggedInProfessorId);

?>
<ul>
    <li>
        <a href="professor.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'painel_inicial') echo 'class="active"'; ?>>
            <i class="fas fa-tachometer-alt"></i> Painel Inicial
        </a>
    </li>
    <li>
        <a href="gerenciar_turmas_professor.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'minhas_turmas') echo 'class="active"'; ?>>
            <i class="fas fa-users"></i> Minhas Turmas
        </a>
    </li>
    <li>
        <a href="lancar-notas.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'lancar_notas') echo 'class="active"'; ?>>
            <i class="fas fa-pen"></i> Lançar Notas
        </a>
    </li>
    <li>
        <a href="frequencia_professor.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'frequencia') echo 'class="active"'; ?>>
            <i class="fas fa-clipboard-list"></i> Frequência
        </a>
    </li>
    <li>
        <a href="gerenciar_materiais.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'enviar_materiais') echo 'class="active"'; ?>>
            <i class="fas fa-folder-plus"></i> Enviar Materiais
        </a>
    </li>
    <li>
        <a href="lancar_comunicado.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'lancar_comunicado') echo 'class="active"'; ?>>
            <i class="fas fa-paper-plane"></i> Enviar Comunicado
        </a>
    </li>
    <li>
        <a href="comunicados_professor_ver.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'ver_comunicados_prof') echo 'class="active"'; ?>>
            <i class="fas fa-bell"></i> Ver Comunicados
        </a>
    </li>
    <li>
    <a href="disciplinas_professor.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'disciplinas') echo 'class="active"'; ?>>
        <i class="fas fa-chalkboard-teacher"></i> Minhas Disciplinas
    </a>
</li>
  <li>
    <a href="enviar_relatorio_professor.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'relatorios') echo 'class="active"'; ?>>
        <i class="fas fa-file-signature"></i> Relatórios de Aula
    </a>
</li>
    <li>
        <a href="<?php echo $linkMeuPerfil; ?>" <?php if ($isPerfilPageActive) echo 'class="active"'; ?>>
            <i class="fas fa-user"></i> Meu Perfil
        </a>
    </li>
</ul>