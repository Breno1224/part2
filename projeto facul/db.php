<?php
$host = "localhost";
$user = "root"; // Usuário padrão do XAMPP
$pass = "";     // Senha padrão do XAMPP é vazia
$db = "acadmix_db"; // <<< MUDE AQUI SE NECESSÁRIO

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Falha na conexão: " . mysqli_connect_error());
}
// Opcional: Definir charset para utf8mb4 para suportar todos os caracteres
mysqli_set_charset($conn, "utf8mb4");
?>