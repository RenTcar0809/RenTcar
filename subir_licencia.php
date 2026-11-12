<?php
session_start();
require_once 'conexion.php';

// Si utilizas phpdotenv para cargar el archivo .env, asegúrate de inicializarlo aquí:
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php");
    exit();
}

$idUsuario = $_SESSION['IdUsuario'];
$mensaje = "";
$tipoAlerta = "";

// ==========================================
// CARGAR CREDENCIALES DESDE VARIABLES DE ENTORNO (.env)
// ==========================================
$cloudName     = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? getenv('CLOUDINARY_CLOUD_NAME') ?? '';
$apiKey        = $_ENV['CLOUDINARY_API_KEY'] ?? getenv('CLOUDINARY_API_KEY') ?? '';
$apiKeySecret  = $_ENV['CLOUDINARY_API_SECRET'] ?? getenv('CLOUDINARY_API_SECRET') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $licenciaNumero = trim($_POST['licencia_numero'] ?? '');
    
    if (empty($licenciaNumero)) {
        $mensaje = "El número de licencia es obligatorio.";
        $tipoAlerta = "error";
    } elseif (empty($cloudName) || empty($apiKey) || empty($apiKeySecret)) {
        $mensaje = "Error de configuración: Faltan las credenciales de Cloudinary en el archivo .env.";
        $tipoAlerta = "error";
    } elseif (!isset($_FILES['licencia_imagen']) || $_FILES['licencia_imagen']['error'] === UPLOAD_ERR_NO_FILE) {
        $mensaje = "Debes subir una foto o documento de tu licencia.";
        $tipoAlerta = "error";
    } else {
        $archivo = $_FILES['licencia_imagen'];
        $tempPath = $archivo['tmp_name'];

        try {
            $timestamp = time();
            $folder = "rentcar/licencias";
            
            // Generar la firma de seguridad usando la clave secreta obtenida del .env
            $stringToSign = "folder={$folder}&timestamp={$timestamp}{$apiKeySecret}";
            $signature = sha1($stringToSign);

            $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";
            
            $datosPost = [
                'file'      => new CURLFile($tempPath),
                'api_key'   => $apiKey,
                'timestamp' => $timestamp,
                'folder'    => $folder,
                'signature' => $signature
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $datosPost);
            
            $respuesta = curl_exec($ch);
            $errorCurl = curl_error($ch);
            curl_close($ch);

            if ($errorCurl) {
                throw new Exception("Error de conexión cURL: " . $errorCurl);
            }

            $respuestaJson = json_decode($respuesta, true);

            if (isset($respuestaJson['secure_url'])) {
                $urlSegura = $respuestaJson['secure_url'];

                // Guardar en PostgreSQL
                $stmt = $pdo->prepare("
                    UPDATE usuario 
                    SET licencia_numero = :numero, 
                        licencia_imagen = :imagen, 
                        licencia_verificada = 0 
                    WHERE id_usuario = :id
                ");
                $stmt->execute([
                    ':numero' => $licenciaNumero,
                    ':imagen' => $urlSegura,
                    ':id'     => $idUsuario
                ]);

                $mensaje = "¡Licencia subida a Cloudinary con éxito! Pendiente de revisión.";
                $tipoAlerta = "success";
            } else {
                $errorMsg = $respuestaJson['error']['message'] ?? 'Error desconocido en Cloudinary';
                throw new Exception("Cloudinary rechazó la subida: " . $errorMsg);
            }

        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipoAlerta = "error";
        }
    }
}

// Consultar estado actual del usuario
$stmtUser = $pdo->prepare("SELECT licencia_numero, licencia_imagen, licencia_verificada FROM usuario WHERE id_usuario = :id");
$stmtUser->execute([':id' => $idUsuario]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
?>