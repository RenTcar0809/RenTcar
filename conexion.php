<?php
// Intentar obtener la URL de conexión de variables de entorno (Render, Aiven, etc.)
$db_url = getenv('DATABASE_URL');

try {
    if ($db_url) {
        // --- CONFIGURACIÓN POR URL DE ENTORNO (PostgreSQL) ---
        $dbopts = parse_url($db_url);
        
        $host = $dbopts["host"];
        $port = isset($dbopts["port"]) ? $dbopts["port"] : "5432";
        $user = $dbopts["user"];
        $pass = $dbopts["pass"];
        $db   = ltrim($dbopts["path"], '/');

        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db;sslmode=require", $user, $pass);
    } else {
        // Cargar archivo .env local si existe
        $envPath = __DIR__ . '/.env';
        if (file_exists($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }

        // Detectar si usamos la base de datos externa (Aiven) o local (XAMPP/MySQL)
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '';
        $db   = $_ENV['DB_NAME'] ?? 'rentcar';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        // Si el host contiene aiven o es un puerto remoto, asumimos PostgreSQL, de lo contrario MySQL local
        if (strpos($host, 'aivencloud.com') !== false || !empty($port) && $port != '3306' && $port != '') {
            $port = $port ?: '20890';
            $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db;sslmode=require", $user, $pass);
        } else {
            // --- CONFIGURACIÓN LOCAL (XAMPP / MySQL) ---
            $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        }
    }

    // Configurar para que lance excepciones cuando algo falle
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>

//
