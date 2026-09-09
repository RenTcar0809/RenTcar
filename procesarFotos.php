<?php
session_start();
require_once 'conexion.php';

// 1. Verificación estricta de sesión y datos pendientes del vehículo
if (!isset($_SESSION['IdUsuario']) || !isset($_SESSION['vehiculo_pendiente'])) {
    die("Acceso denegado o sesión expirada. Vuelva a registrar el vehículo.");
}

$id_usuario = $_SESSION['IdUsuario'];
$datos_vehiculo = $_SESSION['vehiculo_pendiente'];

// 2. Validación estricta en servidor: Obligatorio subir exactamente 4 fotos
if (!isset($_FILES['fotos']) || count($_FILES['fotos']['name']) !== 4) {
    header("Location: subirfotos.php?error=obligatorio");
    exit();
}

// CONFIGURACIÓN DE CLOUDINARY (Asegúrate de poner tus datos reales aquí)
$cloudName = "TU_CLOUD_NAME";       
$uploadPreset = "TU_UPLOAD_PRESET"; 

try {
    // Iniciamos la transacción para asegurar que el vehículo y las fotos se guarden juntos
    $pdo->beginTransaction();

    // 3. INSERTAMOS EL VEHÍCULO EN LA BD
    $sql_vehiculo = "INSERT INTO vehiculo (id_proveedor, tipo, num_motor, num_chasis, traccion, motor, transmision, color, marca, placa, modelo, precio, asientos) 
                     VALUES (:id_proveedor, :tipo, :num_motor, :num_chasis, :traccion, :motor, :transmision, :color, :marca, :placa, :modelo, :precio, :asientos)";
    
    $stmt_v = $pdo->prepare($sql_vehiculo);
    $stmt_v->execute($datos_vehiculo);

    // Obtenemos el ID real generado para el vehículo
    $id_nuevo_vehiculo = $pdo->lastInsertId();

    // 4. PREPARAR EL INSERT DE LAS FOTOS USANDO id_vehiculo
    $sql_foto = "INSERT INTO fotos_vehiculos (id_vehiculo, id_usuario, ruta_imagen) VALUES (:id_vehiculo, :id_usuario, :ruta_imagen)";
    $stmt_f = $pdo->prepare($sql_foto);

    // 5. PROCESAMIENTO Y SUBIDA DE LOS 4 ARCHIVOS A CLOUDINARY
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

            if (isset($responseData['secure_url'])) {
                $stmt_f->execute([
                    ':id_vehiculo' => $id_nuevo_vehiculo,
                    ':id_usuario'  => $id_usuario,
                    ':ruta_imagen' => $responseData['secure_url'] // URL pública de Cloudinary
                ]);
            } else {
                throw new Exception("Error al subir la imagen a Cloudinary.");
            }
        }
    }

    // Si todo salió bien, guardamos definitivamente en la BD
    $pdo->commit();

    // Limpiamos la variable temporal de la sesión
    unset($_SESSION['vehiculo_pendiente']);

    // 6. Redirección final al historial
    header("Location: historial_vehiculo.php");
    exit();

} catch (Exception $e) {
    // Si algo falla, revertimos toda la operación
    $pdo->rollBack();
    die("Error en el sistema al registrar el vehículo: " . $e->getMessage());
}
?>