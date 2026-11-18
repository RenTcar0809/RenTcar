<?php
session_start();
require_once 'conexion.php';

// Mostrar errores en pantalla para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h3>Depurando solicitud de verificación...</h3>";

if (!isset($_SESSION['usuario_nombre'])) {
    die("<p style='color:red;'>❌ Error: No hay sesión activa de 'usuario_nombre'.</p>");
}

if (!isset($_GET['id_vehiculo'])) {
    die("<p style='color:red;'>❌ Error: No se recibió el parámetro 'id_vehiculo' por GET.</p>");
}

$id_vehiculo = intval($_GET['id_vehiculo']);
$nombre_usuario = $_SESSION['usuario_nombre'];

echo "<p>Usuario en sesión: <strong>$nombre_usuario</strong></p>";
echo "<p>ID de vehículo consultado: <strong>$id_vehiculo</strong></p>";

try {
    // 1. Buscar el ID del usuario actual
    $stmtUser = $pdo->prepare("SELECT id_usuario FROM usuario WHERE nombre = ? OR empresa = ? LIMIT 1");
    $stmtUser->execute([$nombre_usuario, $nombre_usuario]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        die("<p style='color:red;'>❌ Error: El usuario '$nombre_usuario' no existe en la tabla 'usuario'.</p>");
    }
    $idSolicitante = $userData['id_usuario'];
    echo "<p>✅ Usuario encontrado con ID: <strong>$idSolicitante</strong></p>";

    // 2. Buscar el vehículo y su propietario
    $stmtVeh = $pdo->prepare("SELECT id_v, marca, modelo, id_proveedor FROM vehiculo WHERE id_v = ?");
    $stmtVeh->execute([$id_vehiculo]);
    $vehData = $stmtVeh->fetch(PDO::FETCH_ASSOC);

    if (!$vehData) {
        die("<p style='color:red;'>❌ Error: El vehículo con ID $id_vehiculo no se encuentra en la tabla 'vehiculo'.</p>");
    }

    echo "<p>✅ Vehículo encontrado: <strong>{$vehData['marca']} {$vehData['modelo']}</strong></p>";
    
    $idPropietario = intval($vehData['id_proveedor']);
    echo "<p>ID del Propietario (id_proveedor): <strong>$idPropietario</strong></p>";

    if ($idPropietario <= 0) {
        die("<p style='color:red;'>❌ Error: Este vehículo tiene el 'id_proveedor' en 0 o vacío, por lo que no tiene un dueño asignado en la base de datos.</p>");
    }

    if ($idSolicitante === $idPropietario) {
        die("<p style='color:orange;'>⚠️ Aviso: Estás intentando solicitar verificación sobre tu propio vehículo. No se requiere inserción.</p>");
    }

    // 3. Insertar en la tabla de aprobaciones
    $stmtInsert = $pdo->prepare("
        INSERT INTO aprobaciones_licencias (id_proveedor, id_usuario, estado, fecha_actualizacion)
        VALUES (:prov, :cli, 0, CURRENT_TIMESTAMP)
        ON CONFLICT (id_proveedor, id_usuario) 
        DO UPDATE SET fecha_actualizacion = CURRENT_TIMESTAMP
    ");
    
    $stmtInsert->execute([
        ':prov' => $idPropietario,
        ':cli'  => $idSolicitante
    ]);

    echo "<p style='color:green; font-weight:bold;'>🎉 ¡Éxito! La solicitud se ha guardado correctamente en la base de datos.</p>";
    echo '<p><a href="subir_licencia.php">Ir a gestionar licencia</a></p>';

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Error de Base de Datos: " . $e->getMessage() . "</p>";
}
?>