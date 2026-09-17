<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluimos tu conexión centralizada (detecta PostgreSQL en Render o MySQL local)
require_once 'conexion.php';

$pagina_formulario = "indexV.php"; 

// Validar sesión activa
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_registro_v'])) {
    
    // ==========================================
    // 1. CREDENCIALES DE CLOUDINARY
    // ==========================================
    $cloud_name = "tu_cloud_name"; // Reemplaza con tu Cloud Name de Cloudinary
    $api_key    = "tu_api_key";    // Reemplaza con tu API Key
    $api_secret = "tu_api_secret"; // Reemplaza con tu API Secret

    $url_imagen_matricula = "";

    // 2. PROCESAR Y SUBIR LA FOTO DE LA MATRÍCULA A CLOUDINARY
    if (isset($_FILES['imagen_matricula']) && $_FILES['imagen_matricula']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagen_matricula']['tmp_name'];
        $url_cloudinary = "https://api.cloudinary.com/v1_1/" . $cloud_name . "/image/upload";
        
        $timestamp = time();
        // Generar firma de seguridad para Cloudinary
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

    // 3. RECOGER Y LIMPIAR DATOS TÉCNICOS DEL FORMULARIO ACTUAL
    $id_proveedor = intval($_SESSION['IdUsuario']); 
    $tipo         = trim($_POST['tipo'] ?? '');
    $marca        = trim($_POST['marca'] ?? '');
    $modelo       = trim($_POST['modelo'] ?? '');
    $color        = trim($_POST['color'] ?? '');
    $placa        = strtoupper(trim($_POST['placa'] ?? ''));
    $motor        = trim($_POST['motor'] ?? ''); 
    $transmision  = trim($_POST['transmision'] ?? '');
    $asientos     = ($_POST['asientos'] !== '') ? intval($_POST['asientos']) : (($tipo === 'Motocicleta') ? 2 : 5);
    $precio       = ($_POST['precio'] !== '') ? floatval($_POST['precio']) : 0.00;

    // 4. VALIDACIÓN DE PLACA PARA COLOMBIA (Carros ABC123 y Motos ABC12F / ABC123)
    $patron_placa = '/^([A-Z]{3}[0-9]{3}|[A-Z]{3}[0-9]{2}[A-Z0-9])$/';
    if (!preg_match($patron_placa, $placa)) {
        $_SESSION['error_placa'] = "Formato de placa inválido. Carro (ABC123) o Moto (ABC12F).";
        header("Location: " . $pagina_formulario);
        exit();
    }

    try {
        // 5. VERIFICAR SI LA PLACA YA EXISTE EN LA TABLA 'vehiculo'
        $stmt_check = $pdo->prepare("SELECT id_v FROM vehiculo WHERE placa = ?");
        $stmt_check->execute([$placa]);
        
        if ($stmt_check->rowCount() > 0) {
            $_SESSION['error_placa'] = "La placa '$placa' ya se encuentra registrada en el sistema.";
            header("Location: " . $pagina_formulario);
            exit();
        }

        // 6. INSERTAR DATOS EN LA TABLA 'vehiculo' (Sincronizado con tus columnas reales)
        $sql = "INSERT INTO vehiculo (id_proveedor, tipo, marca, modelo, color, placa, motor, transmision, asientos, precio, imagen) 
                VALUES (:id_proveedor, :tipo, :marca, :modelo, :color, :placa, :motor, :transmision, :asientos, :precio, :imagen)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_proveedor' => $id_proveedor,
            ':tipo'         => $tipo,
            ':marca'        => $marca,
            ':modelo'       => $modelo,
            ':color'        => $color,
            ':placa'        => $placa,
            ':motor'        => $motor,
            ':transmision'  => $transmision,
            ':asientos'     => $asientos,
            ':precio'       => $precio,
            ':imagen'       => $url_imagen_matricula // URL segura devuelta por Cloudinary
        ]);

        // 7. CAPTURAR EL ID RECIÉN CREADO (Compatible con PostgreSQL y su secuencia)
        try {
            $id_vehiculo_nuevo = $pdo->lastInsertId('vehiculo_id_v_seq');
        } catch (Exception $ex) {
            $id_vehiculo_nuevo = $pdo->lastInsertId(); 
        }

        // Respaldo por seguridad si la secuencia no retorna el ID directo
        if (!$id_vehiculo_nuevo) {
            $stmt_id = $pdo->prepare("SELECT id_v FROM vehiculo WHERE placa = ?");
            $stmt_id->execute([$placa]);
            $vehiculo_encontrado = $stmt_id->fetch();
            $id_vehiculo_nuevo = $vehiculo_encontrado['id_v'] ?? 0;
        }

        // 8. REDIRECCIÓN PROFESIONAL AL PASO 2 (SUBIR LAS 4 FOTOS DE GALERÍA)
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