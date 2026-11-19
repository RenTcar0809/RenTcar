<?php
// Activar errores temporalmente para identificar fallos en pantalla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexion.php';

// Verificar sesión con la variable correcta de tu plataforma
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

$nombreUsuario = trim((string)$_SESSION['usuario_nombre']);
$mensaje = "";
$tipoAlerta = "";

// Credenciales de Cloudinary desde variables de entorno (.env o Render)
$cloudName     = getenv('CLOUDINARY_CLOUD_NAME') ?: 'bsd1wma1';
$apiKey        = getenv('CLOUDINARY_API_KEY') ?: '219554281638733';
$apiKeySecret  = getenv('CLOUDINARY_API_SECRET') ?: 'RTx7SRXjxf0eBi5nWoqQMrxkuv8';

// Resolver ID del usuario actual (priorizando sesión y respaldando por nombre/empresa)
$idUsuarioSesion = intval($_SESSION['IdUsuario'] ?? $_SESSION['id_usuario'] ?? 0);
if ($idUsuarioSesion <= 0 && $nombreUsuario !== '') {
    $stmtUserGet = $pdo->prepare("
        SELECT id_usuario 
        FROM usuario 
        WHERE TRIM(nombre) ILIKE TRIM(?) OR TRIM(empresa) ILIKE TRIM(?) 
        ORDER BY id_usuario ASC 
        LIMIT 1
    ");
    $stmtUserGet->execute([$nombreUsuario, $nombreUsuario]);
    $idUsuarioSesion = intval($stmtUserGet->fetchColumn() ?: 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $licenciaNumero = trim($_POST['licencia_numero'] ?? '');
    
    if (empty($licenciaNumero)) {
        $mensaje = "El número de licencia es obligatorio.";
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
            
            // Generar firma de seguridad de Cloudinary
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

                // 1. Actualizar la base de datos del usuario dejando licencia_verificada = 0 (pendiente)
                if ($idUsuarioSesion > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE usuario 
                        SET licencia_numero = :numero, 
                            licencia_imagen = :imagen, 
                            licencia_verificada = 0 
                        WHERE id_usuario = :id_usuario
                    ");
                    $stmt->execute([
                        ':numero'     => $licenciaNumero,
                        ':imagen'     => $urlSegura,
                        ':id_usuario' => $idUsuarioSesion
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE usuario 
                        SET licencia_numero = :numero, 
                            licencia_imagen = :imagen, 
                            licencia_verificada = 0 
                        WHERE TRIM(nombre) ILIKE TRIM(:nombre) OR TRIM(empresa) ILIKE TRIM(:nombre)
                    ");
                    $stmt->execute([
                        ':numero' => $licenciaNumero,
                        ':imagen' => $urlSegura,
                        ':nombre' => $nombreUsuario
                    ]);
                }

                // 2. Registrar o reiniciar solicitudes individuales pendientes (estado = 0) para los propietarios
                if ($idUsuarioSesion > 0) {
                    $stmtProveedores = $pdo->prepare("
                        SELECT DISTINCT id_proveedor 
                        FROM vehiculo 
                        WHERE id_proveedor IS NOT NULL AND id_proveedor != ?
                    ");
                    $stmtProveedores->execute([$idUsuarioSesion]);
                    $proveedores = $stmtProveedores->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($proveedores as $idProv) {
                        $stmtInsApp = $pdo->prepare("
                            INSERT INTO aprobaciones_licencias (id_proveedor, id_usuario, estado, fecha_actualizacion)
                            VALUES (?, ?, 0, CURRENT_TIMESTAMP)
                            ON CONFLICT (id_proveedor, id_usuario) 
                            DO UPDATE SET estado = 0, fecha_actualizacion = CURRENT_TIMESTAMP
                        ");
                        $stmtInsApp->execute([$idProv, $idUsuarioSesion]);
                    }
                }

                $mensaje = "¡Licencia subida con éxito! Pendiente de revisión por los propietarios.";
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

// Consultar estado actual del usuario de forma segura
if ($idUsuarioSesion > 0) {
    $stmtUser = $pdo->prepare("SELECT licencia_numero, licencia_imagen, licencia_verificada FROM usuario WHERE id_usuario = ? LIMIT 1");
    $stmtUser->execute([$idUsuarioSesion]);
} else {
    $stmtUser = $pdo->prepare("SELECT licencia_numero, licencia_imagen, licencia_verificada FROM usuario WHERE TRIM(nombre) ILIKE TRIM(?) OR TRIM(empresa) ILIKE TRIM(?) LIMIT 1");
    $stmtUser->execute([$nombreUsuario, $nombreUsuario]);
}
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Licencia - RentCar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="licencia.css">
</head>
<body>
    <div class="licencia-container">
        <div class="licencia-card">
            <h2><i class="fa-solid fa-id-card"></i> Verificación de Licencia</h2>
            <p class="subtitle">Sube tu documento para habilitar tus reservas.</p>

            <?php if (!empty($mensaje)): ?>
                <div class="alert <?php echo $tipoAlerta; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <div class="status-box">
                <?php if (!empty($userData['licencia_numero'])): ?>
                    <p><strong>Licencia registrada:</strong> <?php echo htmlspecialchars($userData['licencia_numero']); ?></p>
                    <p><strong>Estado:</strong> 
                        <?php echo ((int)($userData['licencia_verificada'] ?? 0) === 1) ? '<span class="badge success">Aprobada</span>' : '<span class="badge pending">Pendiente de revisión</span>'; ?>
                    </p>
                    <?php if (!empty($userData['licencia_imagen'])): ?>
                        <p style="margin-top: 10px;"><a href="<?php echo htmlspecialchars($userData['licencia_imagen']); ?>" target="_blank" style="color: #e50914;"><i class="fa-solid fa-eye"></i> Ver documento actual</a></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Aún no has registrado tu documento.</p>
                <?php endif; ?>
            </div>

            <form action="subir_licencia.php" method="POST" enctype="multipart/form-data" class="licencia-form">
                <div class="form-group">
                    <label for="licencia_numero">Número de Licencia:</label>
                    <input type="text" id="licencia_numero" name="licencia_numero" required placeholder="Ej: AB-1234567">
                </div>

                <div class="form-group">
                    <label for="licencia_imagen">Foto o Archivo de la Licencia:</label>
                    <input type="file" id="licencia_imagen" name="licencia_imagen" accept=".jpg, .jpeg, .png, .pdf" required>
                </div>

                <div class="preview-container" id="previewContainer">
                    <img id="imagePreview" src="#" alt="Previsualización" style="display: none;">
                </div>

                <button type="submit" class="btn-submit"><i class="fa-solid fa-cloud-arrow-up"></i> Subir Licencia</button>
            </form>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="dashboardf.php" style="color: #888; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Volver al Inicio</a>
            </div>
        </div>
    </div>
    <script src="licencia.js"></script>
</body>
</html>