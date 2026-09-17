<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pagina_formulario = "indexV.php"; 

if (!isset($_SESSION['IdUsuario'])) {
    die("❌ ERROR: No hay una sesión activa.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_registro_v'])) {
    
    $id_proveedor = $_SESSION['IdUsuario']; 
    $marca        = trim($_POST['marca']);
    $modelo       = trim($_POST['modelo']);
    $tipo         = $_POST['tipo'];         
    $color        = trim($_POST['color']);
    $placa        = strtoupper(trim($_POST['placa']));
    $motor        = trim($_POST['motor']);
    $transmision  = $_POST['transmision']; 
    $traccion     = trim($_POST['traccion'] ?? '');
    $num_motor    = trim($_POST['num_motor']);  
    $num_chasis   = trim($_POST['num_chasis']); 
    $asientos     = intval($_POST['asientos'] ?? 0);   
    $precio       = floatval($_POST['precio']); 

    // Validación de placa flexible (Carros: ABC123 | Motos: YSR13F)
    $patron_placa = '/^([A-Z]{3}[0-9]{3}|[A-Z]{3}[0-9]{2}[A-Z0-9])$/';

    if (!preg_match($patron_placa, $placa)) {
        $_SESSION['error_placa'] = "Formato de placa inválido. Para carros usa ABC123 y para motos YSR13F.";
        header("Location: " . $pagina_formulario);
        exit();
    }

    $url_imagen_cloudinary = "";

    if (isset($_FILES['imagen_matricula']) && $_FILES['imagen_matricula']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['imagen_matricula']['tmp_name'];
        
        $cloud_name = "bsd1wma1"; 
        $api_key    = "219554281638733";
        $api_secret = "RTx7SRXjxf0eBi5nWoqQMrxkuv8";
        
        $url = "https://api.cloudinary.com/v1_1/" . $cloud_name . "/image/upload";
        
        $timestamp = time();
        $signature = sha1("folder=rentcar_matriculas&timestamp=" . $timestamp . $api_secret);
        
        $data = array(
            'file' => new CURLFile($fileTmpPath),
            'api_key' => $api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder' => 'rentcar_matriculas'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $resultado_cloudinary = json_decode($response, true);
        
        if (isset($resultado_cloudinary['secure_url'])) {
            $url_imagen_cloudinary = $resultado_cloudinary['secure_url'];
        } else {
            $_SESSION['error_placa'] = "Error al subir la imagen a Cloudinary.";
            header("Location: " . $pagina_formulario);
            exit();
        }
    } else {
        $_SESSION['error_placa'] = "Es obligatorio adjuntar la foto de la tarjeta de propiedad.";
        header("Location: " . $pagina_formulario);
        exit();
    }

    try {
        $host = "localhost";
        $dbname = "tu_base_de_datos";
        $username = "tu_usuario";
        $password = "tu_contrasena";

        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $sql = "INSERT INTO vehiculos (id_proveedor, tipo, marca, modelo, color, placa, motor, transmision, traccion, num_motor, num_chasis, asientos, precio, foto_matricula) 
                VALUES (:id_proveedor, :tipo, :marca, :modelo, :color, :placa, :motor, :transmision, :traccion, :num_motor, :num_chasis, :asientos, :precio, :foto)";
        
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
            ':traccion'     => $traccion,
            ':num_motor'    => $num_motor,
            ':num_chasis'   => $num_chasis,
            ':asientos'     => $asientos,
            ':precio'       => $precio,
            ':foto'         => $url_imagen_cloudinary
        ]);

        header("Location: dashboardf.php?exito=vehiculo_registrado");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error_placa'] = "Error en base de datos: La placa ya se encuentra registrada.";
        header("Location: " . $pagina_formulario);
        exit();
    }

} else {
    header("Location: dashboardf.php");
    exit();
}
?>