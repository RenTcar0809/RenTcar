<?php
// Diagnóstico de emergencia para la sesión
session_start();
echo "ID de Sesión recibido: " . session_id() . "<br>";
echo "Contenido de $_SESSION: <pre>";
print_r($_SESSION);
echo "</pre>";
exit(); // Detiene la ejecución para ver qué tiene la sesión exactamente

// 2. Conexión a la base de datos usando PDO (unificado con el resto del proyecto)
require_once 'conexion.php';

// 3. Obtención de datos
$id_usuario = $_SESSION['IdUsuario'];
$id_vehiculo = intval($_POST['id_vehiculo'] ?? 0);

if ($id_vehiculo <= 0) {
    die("ID de vehículo inválido.");
}

// 4. Validación estricta en servidor: Obligatorio subir 4 fotos
if (!isset($_FILES['fotos']) || count($_FILES['fotos']['name']) !== 4) {
    // Si falla la validación, volvemos al formulario con un mensaje
    header("Location: subirfotos.php?id=$id_vehiculo&error=obligatorio");
    exit();
}

try {
    // 5. Preparación de la consulta con PDO
    $sql = "INSERT INTO fotos_vehiculos (id_vehiculo, id_usuario, ruta_imagen) VALUES (:id_vehiculo, :id_usuario, :ruta_imagen)";
    $stmt = $pdo->prepare($sql);

    // 6. Procesamiento de archivos
    foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp_name) {
        // Validar si el archivo se subió correctamente
        if ($_FILES['fotos']['error'][$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $permitidos)) {
                $nombre_f = "IDV_{$id_vehiculo}_U{$id_usuario}_" . time() . "_$i." . $ext;
                $ruta_destino = 'imagenes/' . $nombre_f;

                if (move_uploaded_file($tmp_name, $ruta_destino)) {
                    // Ejecutamos la inserción segura con PDO
                    $stmt->execute([
                        ':id_vehiculo' => $id_vehiculo,
                        ':id_usuario'  => $id_usuario,
                        ':ruta_imagen' => $ruta_destino
                    ]);
                }
            }
        }
    }

    // 7. Redirección automática al historial
    header("Location: historial_vehiculo.php");
    exit();

} catch (PDOException $e) {
    die("Error en el sistema al guardar las fotos: " . $e->getMessage());
}
?>