<?php
$db_url = getenv('DATABASE_URL');

try {
    if ($db_url) {
        $dbopts = parse_url($db_url);
        $host = $dbopts["host"];
        $port = isset($dbopts["port"]) ? $dbopts["port"] : "20890";
        $user = $dbopts["user"];
        $pass = $dbopts["pass"];
        $db   = ltrim($dbopts["path"], '/');
    } else {
        $envPath = __DIR__ . '/.env';
        if (file_exists($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
        $host = $_ENV['DB_HOST'] ?? 'pg-2ea2476-rentcarcop-0a02.h.aivencloud.com';
        $port = $_ENV['DB_PORT'] ?? '20890';
        $db   = $_ENV['DB_NAME'] ?? 'defaultdb';
        $user = $_ENV['DB_USER'] ?? 'avnadmin';
        $pass = $_ENV['DB_PASS'] ?? '';
    }

    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db;sslmode=require", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>