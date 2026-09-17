<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pagina_formulario = "indexV.php"; 

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_registro_v'])) {
    
    // ==========================================
    // 1. CREDENCIALES DE CLOUDINARY
    // ==========================================
    $cloud_name = "bsd1wma1"; 
    $api_key    = "219554281638733";
    $api_secret = "RTx7SRXjxf0eBi5nWoqQMrxkuv8";

    // 2. RECOGER DATOS DEL FORMULARIO
    $id_proveedor = $_SESSION['IdUsuario']; 
    $tipo         = trim($_POST['tipo'] ?? '');
    $marca        = trim($_POST['marca'] ?? '');
    $modelo       = trim($_POST['modelo'] ?? '');
    $color        = trim($_POST['color'] ?? '');
    $placa        = strtoupper(trim($_POST['placa'] ?? ''));
    $motor        = trim($_POST['motor'] ?? ''); 
    $transmision  = trim($_POST['transmision'] ?? '');
    $traccion     = trim($_POST['traccion'] ?? '');
    $num_motor    = trim($_POST['num_motor'] ?? '');
    $num_chasis   = trim($_POST['num_chasis'] ?? '');
    $asientos     = intval($_POST['asientos'] ?? 5);
    $precio       = floatval($_POST['precio'] ?? 0);

    // Validación de placa para Colombia
    $patron_placa = '/^([A-Z]{3}[0-9]{3}|[A-Z]{3}[0-9]{2}[A-Z0-9])$/';
    if (!preg_match($patron_placa, $placa)) {
        $_SESSION['error_placa'] = "Formato de placa inválido. Carro (ABC123) o Moto (ABC12F).";
        header("Location: " . $pagina_formulario);
        exit();
    }

    // 3. PROCESAR Y SUBIR LA FOTO DE LA MATRÍCULA A CLOUDINARY
    $url_imagen_matricula = "";

    if (isset($_FILES['imagen_matricula']) && $_FILES['imagen_matricula']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagen_matricula']['tmp_name'];
        $url_cloudinary = "https://api.cloudinary.com/v1_1/" . $cloud_name . "/image/upload";
        
        $timestamp = time();
        $signature = sha1("folder=rentcar_matriculas&timestamp=" . $timestamp . $api_secret);
        
        $data = array(
            'file'      => new CURLFile($fileTmpPath),
            'api_key'   => $api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder'    => 'rentcar_matriculas'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_cloudinary);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $resultado_cloudinary = json_decode($response, true);
        
        if ($http_code === 200 && isset($resultado_cloudinary['secure_url'])) {
            $url_imagen_matricula = $resultado_cloudinary['secure_url'];
        } else {
            $_SESSION['error_placa'] = "Error al subir la tarjeta de propiedad a Cloudinary.";
            header("Location: " . $pagina_formulario);
            exit();
        }
    } else {
        $_SESSION['error_placa'] = "La foto de la tarjeta de propiedad / matrícula es obligatoria.";
        header("Location: " . $pagina_formulario);
        exit();
    }

    // 4. GUARDAR EN BASE DE DATOS (MYSQL)
    try {
        $host = "localhost";
        $dbname = "rentcar_db"; // Ajusta el nombre de tu base de datos
        $username = "root";
        $password = "";

        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Validar si la placa ya existe
        $stmt_check = $pdo->prepare("SELECT id FROM vehiculos WHERE placa = ?");
        $stmt_check->execute([$placa]);
        if ($stmt_check->rowCount() > 0) {
            $_SESSION['error_placa'] = "La placa '$placa' ya se encuentra registrada en el sistema.";
            header("Location: " . $pagina_formulario);
            exit();
        }

        // Insertar vehículo incluyendo el ID del usuario (proveedor) y la URL de la matrícula
        $sql = "INSERT INTO vehiculos (id_proveedor, tipo, marca, modelo, color, placa, motor, transmision, traccion, num_motor, num_chasis, asientos, precio_dia, imagen_matricula) 
                VALUES (:id_proveedor, :tipo, :marca, :modelo, :color, :placa, :motor, :transmision, :traccion, :num_motor, :num_chasis, :asientos, :precio, :imagen_matricula)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_proveedor'         => $id_proveedor,
            ':tipo'                 => $tipo,
            ':marca'                => $marca,
            ':modelo'               => $modelo,
            ':color'                => $color,
            ':placa'                => $placa,
            ':motor'                => $motor,
            ':transmision'          => $transmision,
            ':traccion'             => ($tipo === 'Motocicleta') ? 'N/A' : $traccion,
            ':num_motor'            => $num_motor,
            ':num_chasis'           => $num_chasis,
            ':asientos'             => ($tipo === 'Motocicleta') ? 2 : $asientos,
            ':precio'               => $precio,
            ':imagen_matricula'     => $url_imagen_matricula
        ]);

        // Capturar el ID del vehículo recién creado
        $id_vehiculo_nuevo = $pdo->lastInsertId();

        // 5. REDIRECCIÓN PROFESIONAL A SUBIR LAS 4 FOTOS RESTANTES
        header("Location: subirfoto.php?id=" . $id_vehiculo_nuevo);
        exit();

    } catch (PDOException $e) {
        $_SESSION['error_placa'] = "Error en base de datos: " . $e->getMessage();
        header("Location: " . $pagina_formulario);
        exit();
    }

} else {
    header("Location: indexV.php");
    exit();
}
?>