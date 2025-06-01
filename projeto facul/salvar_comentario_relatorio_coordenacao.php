<?php
session_start();
include 'db.php';

header('Content-Type: application/json'); // Resposta será JSON

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'coordenacao' || !isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $relatorio_id = isset($_POST['relatorio_id_comentario']) ? intval($_POST['relatorio_id_comentario']) : 0;
    $comentario_texto = isset($_POST['comentario_coordenacao_texto']) ? trim($_POST['comentario_coordenacao_texto']) : '';
    $coordenador_id_comentario = $_SESSION['usuario_id'];
    $data_comentario = date('Y-m-d H:i:s'); // Data e hora atuais

    if ($relatorio_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID do relatório inválido.']);
        exit();
    }
    // Comentário pode ser vazio para limpar um comentário existente, se desejado, 
    // ou você pode adicionar uma validação para não permitir comentários vazios se for para adicionar.
    // Para este exemplo, um comentário vazio limpará o campo, ou pode ser validado no JS/PHP para não ser vazio.

    $sql = "UPDATE relatorios_aula 
            SET comentario_coordenacao = ?, coordenador_id_comentario = ?, data_comentario_coordenacao = ?
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        // Se o comentário for vazio, salvar NULL no banco em vez de string vazia
        $comentario_final = !empty($comentario_texto) ? $comentario_texto : NULL;
        $coordenador_final = !empty($comentario_texto) ? $coordenador_id_comentario : NULL;
        $data_final = !empty($comentario_texto) ? $data_comentario : NULL;

        mysqli_stmt_bind_param($stmt, "sisi", $comentario_final, $coordenador_final, $data_final, $relatorio_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Comentário salvo com sucesso.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar comentário: ' . mysqli_stmt_error($stmt)]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar SQL: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
}
mysqli_close($conn);
exit();
?>