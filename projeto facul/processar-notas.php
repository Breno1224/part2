<?php
include 'db.php';

$turma = $_POST['turma'];
$disciplina = $_POST['disciplina'];
$avaliacao = $_POST['avaliacao'];
$alunos = $_POST['alunos']; // array de [id_aluno => nota]

foreach ($alunos as $id_aluno => $nota) {
    $sql = "INSERT INTO notas (aluno_id, turma_id, disciplina_id, avaliacao, nota, data_lancamento)
            VALUES ('$id_aluno', '$turma', '$disciplina', '$avaliacao', '$nota', NOW())";

    mysqli_query($conn, $sql);
}

echo "Notas lançadas com sucesso!";
?>
