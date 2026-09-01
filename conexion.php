<?php
// Intentar obtener la URL de conexión que proporciona Render
$db_url = getenv('DATABASE_URL');

try {
    if ($db_url) {
        // --- CONFIGURACIÓN PARA RENDER (PostgreSQL) ---
        $dbopts = parse_url($db_url);
        
        $host = $dbopts["host"];
        $port = isset($dbopts["port"]) ? $dbopts["port"] : "5432";
        $user = $dbopts["user"];
        $pass = $dbopts["pass"];
        $db   = ltrim($dbopts["path"], '/');

        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    } else {
        // --- CONFIGURACIÓN LOCAL (XAMPP / MySQL) ---
        $host = '127.0.0.1'; 
        $db   = 'rentcar';
        $user = 'root';
        $pass = '';

        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    }

    // Configurar para que lance excepciones cuando algo falle
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>