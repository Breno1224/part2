<?php
// includes/sidebar_coordenacao.php
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao') {
    echo "<p style='padding:1rem; color:white;'>Acesso restrito.</p>";
    return;
}
$loggedInCoordenadorId = $_SESSION['usuario_id'];
$linkMeuPerfilCoordenador = "perfil_coordenador.php"; // Link direto para o próprio perfil
$isPerfilPageActive = (isset($currentPageIdentifier) && $currentPageIdentifier === 'perfil_coordenador');
?>
<ul>
    <li>
        <a href="coordenacao_painel.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'painel_coordenacao') echo 'class="active"'; ?>>
            <i class="fas fa-landmark"></i> Painel Coordenação
        </a>
    </li>
    <li>
        <a href="visualizar_relatorios_coordenacao.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'ver_relatorios_coord') echo 'class="active"'; ?>>
            <i class="fas fa-clipboard-check"></i> Ver Relatórios de Aula
        </a>
    </li>
    <li>
        <a href="coordenacao_add_aluno.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'add_aluno') echo 'class="active"'; ?>>
            <i class="fas fa-user-plus"></i> Adicionar Aluno
        </a>
    </li>
    <li>
        <a href="coordenacao_add_professor.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'add_professor') echo 'class="active"'; ?>>
            <i class="fas fa-chalkboard-teacher"></i> Adicionar Professor
        </a>
    </li>
    <li>
        <a href="coordenacao_ver_alunos.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'ver_alunos') echo 'class="active"'; ?>>
            <i class="fas fa-users"></i> Ver Alunos
        </a>
    </li>
    <li>
        <a href="coordenacao_ver_professores.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'ver_professores') echo 'class="active"'; ?>>
            <i class="fas fa-user-tie"></i> Ver Professores
        </a>
    </li>
    <li>
        <a href="coordenacao_lancar_comunicado.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'comunicados_coord') echo 'class="active"'; ?>>
            <i class="fas fa-bullhorn"></i> Enviar Comunicado
        </a>
    </li>
    <li>
        <a href="coordenacao_gerenciar_turmas.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'gerenciar_turmas') echo 'class="active"'; ?>>
            <i class="fas fa-sitemap"></i> Gerenciar Turmas
        </a>
    </li>
     <li>
        <a href="coordenacao_gerenciar_disciplinas.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'gerenciar_disciplinas') echo 'class="active"'; ?>>
            <i class="fas fa-book"></i> Gerenciar Disciplinas
        </a>
    </li>
    <li>
        <a href="perfil_coordenador.php" <?php if (isset($currentPageIdentifier) && $currentPageIdentifier === 'perfil_coordenador') echo 'class="active"'; ?>>
            <i class="fas fa-user-cog"></i> Meu Perfil
        </a>
    </li>
</ul>