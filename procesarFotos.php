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

try {
    // Iniciamos transacción para asegurar que todo se guarde unido
    $pdo->beginTransaction();

    // 3. INSERTAMOS EL VEHÍCULO EN LA BD
    $sql_vehiculo = "INSERT INTO vehiculo (id_proveedor, tipo, num_motor, num_chasis, traccion, motor, transmision, color, marca, placa, modelo, precio, asientos) 
                     VALUES (:id_proveedor, :tipo, :num_motor, :num_chasis, :traccion, :motor, :transmision, :color, :marca, :placa, :modelo, :precio, :asientos)";
    
    $stmt_v = $pdo->prepare($sql_vehiculo);
    $stmt_v->execute($datos_vehiculo);

    // Obtenemos el ID real que acaba de generar la base de datos
    $id_nuevo_vehiculo = $pdo->lastInsertId();

    // 4. PREPARAR EL INSERT DE LAS FOTOS USANDO id_v
    $sql_foto = "INSERT INTO fotos_vehiculos (id_v, id_usuario, ruta_imagen) VALUES (:id_v, :id_usuario, :ruta_imagen)";
    $stmt_f = $pdo->prepare($sql_foto);

    // 5. PROCESAMIENTO Y SUBIDA DE LOS 4 ARCHIVOS
    foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp_name) {
        if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $permitidos)) {
                $nombre_f = "IDV_{$id_nuevo_vehiculo}_U{$id_usuario}_" . time() . "_$i." . $ext;
                $ruta_destino = 'imagenes/' . $nombre_f;

                if (!is_dir('imagenes')) {
                    mkdir('imagenes', 0777, true);
                }

                if (move_uploaded_file($tmp_name, $ruta_destino)) {
                    $stmt_f->execute([
                        ':id_v'        => $id_nuevo_vehiculo,
                        ':id_usuario'  => $id_usuario,
                        ':ruta_imagen' => $ruta_destino
                    ]);
                }
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
    // Si algo falla, revertimos la base de datos
    $pdo->rollBack();
    die("Error en el sistema al registrar el vehículo: " . $e->getMessage());
}
?>