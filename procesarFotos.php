<?php
session_start();

// 1. Verificación de sesión
if (!isset($_SESSION['IdUsuario'])) {
    die("Acceso denegado.");
}

// 2. Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "rentcar");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// 3. Obtención de datos
$id_usuario = $_SESSION['IdUsuario'];
$id_vehiculo = intval($_POST['id_vehiculo']);

// 4. Validación estricta en servidor: Obligatorio subir 4 fotos
if (!isset($_FILES['fotos']) || count($_FILES['fotos']['name']) !== 4) {
    // Si falla la validación, volvemos al formulario con un mensaje
    header("Location: subirfotos.php?id=$id_vehiculo&error=obligatorio");
    exit();
}

// 5. Preparación de la consulta
$stmt = $conn->prepare("INSERT INTO fotos_vehiculos (id_vehiculo, id_usuario, ruta_imagen) VALUES (?, ?, ?)");

// 6. Procesamiento de archivos
foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp_name) {
    // Validar extensión
    $ext = strtolower(pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION));
    $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
    
    if (in_array($ext, $permitidos)) {
        $nombre_f = "IDV_{$id_vehiculo}_U{$id_usuario}_" . time() . "_$i." . $ext;
        $ruta_destino = 'imagenes/' . $nombre_f;

        if (move_uploaded_file($tmp_name, $ruta_destino)) {
            $stmt->bind_param("iis", $id_vehiculo, $id_usuario, $ruta_destino);
            $stmt->execute();
        }
    }
}

// 7. Cierre de recursos
$stmt->close();
$conn->close();

// 8. REDIRECCIÓN AUTOMÁTICA DIRECTA AL HISTORIAL
// Al no haber ningún 'echo' o 'print' antes, el redireccionamiento funcionará correctamente.
header("Location: historial_vehiculo.php");
exit();
?>