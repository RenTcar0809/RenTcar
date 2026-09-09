<?php
session_start();
require_once 'conexion.php';

// 1. Verificación estricta de sesión y datos pendientes
if (!isset($_SESSION['IdUsuario']) || !isset($_SESSION['vehiculo_pendiente'])) {
    die("Acceso denegado o sesión expirada. Vuelva a registrar el vehículo.");
}

$id_usuario = $_SESSION['IdUsuario'];
$datos_vehiculo = $_SESSION['vehiculo_pendiente'];

// 2. Validación estricta: Obligatorio subir exactamente 4 fotos
if (!isset($_FILES['fotos']) || count($_FILES['fotos']['name']) !== 4) {
    header("Location: subirfotos.php?error=obligatorio");
    exit();
}

// CONFIGURACIÓN DE CLOUDINARY (Reemplaza con tus datos reales)
$cloudName = "TU_CLOUD_NAME";       // <--- Pon aquí tu Cloud Name
$uploadPreset = "TU_UPLOAD_PRESET"; // <--- Pon aquí el nombre de tu preset sin firmar

try {
    $pdo->beginTransaction();

    // 3. Insertar el vehículo en la BD
    $sql_vehiculo = "INSERT INTO vehiculo (id_proveedor, tipo, num_motor, num_chasis, traccion, motor, transmision, color, marca, placa, modelo, precio, asientos) 
                     VALUES (:id_proveedor, :tipo, :num_motor, :num_chasis, :traccion, :motor, :transmision, :color, :marca, :placa, :modelo, :precio, :asientos)";
    
    $stmt_v = $pdo->prepare($sql_vehiculo);
    $stmt_v->execute($datos_vehiculo);

    $id_nuevo_vehiculo = $pdo->lastInsertId();

    // 4. Preparar el insert de las fotos guardando la URL de Cloudinary
    $sql_foto = "INSERT INTO fotos_vehiculos (id_v, id_usuario, ruta_imagen) VALUES (:id_v, :id_usuario, :ruta_imagen)";
    $stmt_f = $pdo->prepare($sql_foto);

    // 5. Subir cada archivo a Cloudinary mediante su API
    foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp_name) {
        if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
            
            $url_cloudinary = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url_cloudinary);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'file' => new CURLFile($tmp_name),
                'upload_preset' => $uploadPreset
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $responseData = json_decode($response, true);

            // Si Cloudinary nos devuelve la URL segura de la imagen
            if (isset($responseData['secure_url'])) {
                $stmt_f->execute([
                    ':id_v'        => $id_nuevo_vehiculo,
                    ':id_usuario'  => $id_usuario,
                    ':ruta_imagen' => $responseData['secure_url'] // Guardamos la URL web en la BD
                ]);
            } else {
                throw new Exception("Error al subir la imagen a Cloudinary.");
            }
        }
    }

    $pdo->commit();
    unset($_SESSION['vehiculo_pendiente']);

    header("Location: historial_vehiculo.php");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error en el sistema al registrar el vehículo: " . $e->getMessage());
}
?>