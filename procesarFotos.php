<?php
session_start();
require_once 'conexion.php';

// 1. Validar sesión activa y que llegue el ID del vehículo por la URL
if (!isset($_SESSION['IdUsuario']) || !isset($_GET['id'])) {
    header("Location: indexV.php?error=sesion_expirada");
    exit();
}

$id_usuario = $_SESSION['IdUsuario'];
$id_vehiculo = intval($_GET['id']);

// 2. Validación estricta en servidor: Obligatorio subir exactamente 4 fotos
if (!isset($_FILES['fotos']) || empty($_FILES['fotos']['name'][0]) || count($_FILES['fotos']['name']) !== 4) {
    header("Location: subirfoto.php?id=" . $id_vehiculo . "&error=obligatorio");
    exit();
}

// CONFIGURACIÓN DE CLOUDINARY
$cloudName = "bsd1wma1";       
$uploadPreset = "xkzfwqa0"; 

try {
    // 3. PREPARAR EL INSERT DE LAS FOTOS USANDO EL ID DEL VEHÍCULO
    $sql_foto = "INSERT INTO fotos_vehiculos (id_vehiculo, id_usuario, ruta_imagen) VALUES (:id_vehiculo, :id_usuario, :ruta_imagen)";
    $stmt_f = $pdo->prepare($sql_foto);

    // 4. PROCESAMIENTO Y SUBIDA DE LOS 4 ARCHIVOS A CLOUDINARY
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
            
            if (curl_errno($ch)) {
                throw new Exception("Error de conexión cURL: " . curl_error($ch));
            }
            
            curl_close($ch);
            
            $responseData = json_decode($response, true);

            if (isset($responseData['secure_url'])) {
                $stmt_f->execute([
                    ':id_vehiculo' => $id_vehiculo,
                    ':id_usuario'  => $id_usuario,
                    ':ruta_imagen' => $responseData['secure_url'] // URL pública de Cloudinary
                ]);
            } else {
                throw new Exception("Error al subir la imagen a Cloudinary. Respuesta: " . $response);
            }
        } else {
            throw new Exception("Error en la carga del archivo código: " . $_FILES['fotos']['error'][$i]);
        }
    }

    // 5. Redirección final al historial si todo sale bien
    header("Location: historial_vehiculo.php?registro=exitoso");
    exit();

} catch (Exception $e) {
    die("Error en el sistema al registrar las fotos: " . $e->getMessage());
}
?>