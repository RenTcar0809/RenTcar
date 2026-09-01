<?php
$host = 'localhost';
$db   = 'rentcar'; // <--- CAMBIA ESTO por el nombre real de tu base de datos
$user = 'root';
$pass = ''; // Normalmente vacío en XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    // Configurar para que lance excepciones cuando algo falle
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>