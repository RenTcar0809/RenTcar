<?php
session_start();

// ==========================================
// CONFIGURACIÓN DE CLOUDINARY
// ==========================================
// Reemplaza estos datos con los tuyos de tu panel de Cloudinary
$cloud_name = "bsd1wma1"; 
$api_key    = "219554281638733";
$api_secret = "RTx7SRXjxf0eBi5nWoqQMrxkuv8";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_registro_v'])) {
    
    // 1. Recoger y limpiar los datos enviados por el formulario
    $tipo        = trim($_POST['tipo'] ?? '');
    $marca       = trim($_POST['marca'] ?? '');
    $modelo      = trim($_POST['modelo'] ?? '');
    $color       = trim($_POST['color'] ?? '');
    $placa       = strtoupper(trim($_POST['placa'] ?? ''));
    $motor       = trim($_POST['motor'] ?? ''); // Aquí llega el CC de la moto o el motor del carro
    $transmision = trim($_POST['transmision'] ?? '');
    $traccion    = trim($_POST['traccion'] ?? '');
    $num_motor   = trim($_POST['num_motor'] ?? '');
    $num_chasis  = trim($_POST['num_chasis'] ?? '');
    $asientos    = intval($_POST['asientos'] ?? 5);
    $precio      = floatval($_POST['precio'] ?? 0);
    $id_usuario  = $_SESSION['usuario_id'] ?? 1; // Ajusta según cómo manejes las sesiones de usuario

    // Si es motocicleta, limpiamos campos que no aplican
    if ($tipo === 'Motocicleta') {
        $traccion = 'N/A';
        $asientos = 2; // Estándar para moto
    }

    // 2. Procesar la subida de la imagen de matrícula a Cloudinary
    $url_imagen_matricula = "";

    if (isset($_FILES['imagen_matricula']) && $_FILES['imagen_matricula']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['imagen_matricula']['tmp_name'];
        
        // Endpoint de la API de subida de Cloudinary
        $cloudinary_url = "https://api.cloudinary.com/v1_1/" . $cloud_name . "/image/upload";

        // Datos para la petición POST a Cloudinary
        $data = array(
            'file'      => new CURLFile($file_tmp_path),
            'upload_preset' => 'tu_upload_preset', // (Opcional) Si usas un preset unsigned. Si usas firma, requerirá auth por timestamp/signature.
            'api_key'   => $api_key
        );

        // Si prefieres autenticación básica con API Key y Secret en cURL:
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $cloudinary_url);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Si usas credenciales directas (alternativa al preset unsigned):
        // Generamos el timestamp y firma si usas signed upload, o usamos user/pass básico:
        $timestamp = time();
        $signature = sha1("timestamp=" . $timestamp . $api_secret);
        
        $data_signed = array(
            'file'      => new CURLFile($file_tmp_path),
            'timestamp' => $timestamp,
            'api_key'   => $api_key,
            'signature' => $signature,
            'folder'    => 'rentcar_matriculas' // Carpeta opcional en Cloudinary
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_signed);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $response_json = json_decode($response, true);
            $url_imagen_matricula = $response_json['secure_url'] ?? '';
        } else {
            $_SESSION['error_placa'] = "Error al subir la imagen de la matrícula a Cloudinary.";
            header("Location: indexV.php");
            exit();
        }
    } else {
        $_SESSION['error_placa'] = "Es obligatoria la foto de la tarjeta de propiedad / matrícula.";
        header("Location: indexV.php");
        exit();
    }

    // ==========================================
    // 3. CONEXIÓN A LA BASE DE DATOS Y GUARDADO
    // ==========================================
    try {
        // Ajusta tus credenciales de base de datos aquí
        $host = "localhost";
        $dbname = "rentcar_db";
        $username = "root";
        $password = "";

        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Validar si la placa ya existe en el sistema
        $stmt_check = $pdo->prepare("SELECT id FROM vehiculos WHERE placa = ?");
        $stmt_check->execute([$placa]);
        if ($stmt_check->rowCount() > 0) {
            $_SESSION['error_placa'] = "La placa '$placa' ya se encuentra registrada en el sistema.";
            header("Location: indexV.php");
            exit();
        }

        // Insertar el vehículo con la URL de Cloudinary y los nuevos datos técnicos
        $sql = "INSERT INTO vehiculos (id_usuario, tipo, marca, modelo, color, placa, motor, transmision, traccion, num_motor, num_chasis, asientos, precio_dia, imagen_matricula) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_usuario,
            $tipo,
            $marca,
            $modelo,
            $color,
            $placa,
            $motor,       // Aquí guarda el CC (ej: 150 CC) o el motor (ej: 1.6L)
            $transmision, // Aquí guarda la transmisión seleccionada
            $traccion,
            $num_motor,
            $num_chasis,
            $asientos,
            $precio,
            $url_imagen_matricula // URL segura devuelta por Cloudinary
        ]);

        // Redireccionar al panel o éxito
        $_SESSION['exito'] = "Vehículo registrado correctamente.";
        header("Location: dashboardf.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error_placa'] = "Error en la base de datos: " . $e->getMessage();
        header("Location: indexV.php");
        exit();
    }

} else {
    // Si intentan entrar directo por URL sin enviar el formulario
    header("Location: indexV.php");
    exit();
}
?>