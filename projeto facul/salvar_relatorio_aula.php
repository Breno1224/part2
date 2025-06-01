<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docente' || !isset($_SESSION['usuario_id'])) {
    $_SESSION['relatorio_status_message'] = "Acesso não autorizado.";
    $_SESSION['relatorio_status_type'] = "status-error";
    header("Location: index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $professor_id = $_SESSION['usuario_id'];
    $data_aula = trim($_POST['data_aula']);
    $turma_disciplina_id_str = trim($_POST['turma_disciplina_id']);
    $conteudo_lecionado = trim($_POST['conteudo_lecionado']);
    $observacoes = trim($_POST['observacoes']);
    $material_path = null;

    // Separar turma_id e disciplina_id
    list($turma_id, $disciplina_id) = explode('-', $turma_disciplina_id_str);
    $turma_id = intval($turma_id);
    $disciplina_id = intval($disciplina_id);

    if (empty($data_aula) || empty($turma_id) || empty($disciplina_id) || empty($conteudo_lecionado)) {
        $_SESSION['relatorio_status_message'] = "Data da aula, turma, disciplina e conteúdo lecionado são obrigatórios.";
        $_SESSION['relatorio_status_type'] = "status-error";
        header("Location: enviar_relatorio_professor.php");
        exit();
    }

    // Upload do Material de Aula (Opcional)
    if (isset($_FILES['material_aula']) && $_FILES['material_aula']['error'] == UPLOAD_ERR_OK) {
        $upload_dir_relatorios = 'uploads/materiais_relatorios/';
        if (!is_dir($upload_dir_relatorios)) {
            mkdir($upload_dir_relatorios, 0777, true);
        }

        $file_tmp = $_FILES['material_aula']['tmp_name'];
        $file_name = basename($_FILES['material_aula']['name']); // basename() para segurança
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_file_name = "relatorio_" . $professor_id . "_" . $turma_id . "_" . $disciplina_id . "_" . date('Ymd', strtotime($data_aula)) . "_" . uniqid() . "." . $file_ext;
        $dest_path_material = $upload_dir_relatorios . $unique_file_name;

        // Validar tipo e tamanho
        $allowed_extensions_material = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'png', 'zip'];
        if (in_array($file_ext, $allowed_extensions_material) && $_FILES['material_aula']['size'] <= 5 * 1024 * 1024) { // Max 5MB
            if (move_uploaded_file($file_tmp, $dest_path_material)) {
                $material_path = $dest_path_material;
            } else {
                $_SESSION['relatorio_status_message'] = "Falha ao mover o arquivo de material. O relatório foi salvo sem ele.";
                $_SESSION['relatorio_status_type'] = "status-error"; // Pode ser um aviso
            }
        } else {
             $_SESSION['relatorio_status_message'] = "Arquivo de material inválido (tipo ou tamanho). O relatório foi salvo sem ele.";
             $_SESSION['relatorio_status_type'] = "status-error"; // Pode ser um aviso
        }
    }

    // Inserir ou atualizar no banco de dados (ON DUPLICATE KEY UPDATE)
    $sql = "INSERT INTO relatorios_aula (professor_id, turma_id, disciplina_id, data_aula, conteudo_lecionado, observacoes, material_aula_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            conteudo_lecionado = VALUES(conteudo_lecionado), 
            observacoes = VALUES(observacoes), 
            material_aula_path = VALUES(material_aula_path),
            data_envio = CURRENT_TIMESTAMP";
            
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iiissss", $professor_id, $turma_id, $disciplina_id, $data_aula, $conteudo_lecionado, $observacoes, $material_path);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['relatorio_status_message'] = "Relatório de aula enviado/atualizado com sucesso!";
            $_SESSION['relatorio_status_type'] = "status-success";
        } else {
            $_SESSION['relatorio_status_message'] = "Erro ao salvar relatório: " . mysqli_stmt_error($stmt);
            $_SESSION['relatorio_status_type'] = "status-error";
            if ($material_path && file_exists($material_path)) { unlink($material_path); } // Remove material se DB falhar
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['relatorio_status_message'] = "Erro ao preparar SQL: " . mysqli_error($conn);
        $_SESSION['relatorio_status_type'] = "status-error";
    }
} else {
    $_SESSION['relatorio_status_message'] = "Requisição inválida.";
    $_SESSION['relatorio_status_type'] = "status-error";
}
mysqli_close($conn);
header("Location: enviar_relatorio_professor.php");
exit();
?>