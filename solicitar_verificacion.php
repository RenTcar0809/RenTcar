<?php
session_start();
require_once 'conexion.php';

// Mostrar errores temporalmente en pantalla para ver si hay fallas de SQL
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Validar que exista sesión y el ID del vehículo
if (!isset($_SESSION['usuario_nombre']) || !isset($_GET['id_vehiculo'])) {
    die("Error: Sesión no iniciada o vehículo no especificado.");
}

$id_vehiculo = intval($_GET['id_vehiculo']);
$nombre_usuario = $_SESSION['usuario_nombre'];

try {
    // 2. Obtener el ID del usuario actual de forma flexible
    $stmtUser = $pdo->prepare("SELECT id_usuario FROM usuario WHERE nombre = ? OR empresa = ? LIMIT 1");
    $stmtUser->execute([$nombre_usuario, $nombre_usuario]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        die("Error crítico: No se encontró el usuario '$nombre_usuario' en la base de datos.");
    }
    $idSolicitante = $userData['id_usuario'];

    // 3. Obtener el id_proveedor dueño del vehículo
    $stmtVeh = $pdo->prepare("SELECT id_proveedor FROM vehiculo WHERE id_v = ?");
    $stmtVeh->execute([$id_vehiculo]);
    $vehData = $stmtVeh->fetch(PDO::FETCH_ASSOC);

    if (!$vehData) {
        die("Error crítico: El vehículo con ID $id_vehiculo no existe en la base de datos.");
    }
    
    $idPropietario = intval($vehData['id_proveedor']);

    if ($idPropietario <= 0) {
        die("Error: Este vehículo no tiene un propietario (id_proveedor) asignado correctamente en la base de datos.");
    }

    // 4. Validar que el dueño no intente solicitarse a sí mismo
    if ($idSolicitante === $idPropietario) {
        // Si es el dueño intentando ver su propio carro, lo dejamos pasar al chat directo
        header("Location: detalles_vehiculo.php?id=$id_vehiculo");
        exit();
    }

    // 5. Inserción directa en la tabla de aprobaciones individuales
    // Nota: Si no tienes el UNIQUE constraint creado, usaremos un INSERT simple con verificación previa
    $stmtCheck = $pdo->prepare("SELECT id_aprobacion FROM aprobaciones_licencias WHERE id_proveedor = ? AND id_usuario = ?");
    $stmtCheck->execute([$idPropietario, $idSolicitante]);
    $existe = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$existe) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO aprobaciones_licencias (id_proveedor, id_usuario, estado, fecha_actualizacion)
            VALUES (?, ?, 0, CURRENT_TIMESTAMP)
        ");
        $stmtInsert->execute([$idPropietario, $idSolicitante]);
    } else {
        // Si ya existía pero estaba rechazada o pendiente, actualizamos la fecha
        $stmtUpdate = $pdo->prepare("
            UPDATE aprobaciones_licencias 
            SET fecha_actualizacion = CURRENT_TIMESTAMP 
            WHERE id_proveedor = ? AND id_usuario = ?
        ");
        $stmtUpdate->execute([$idPropietario, $idSolicitante]);
    }

} catch (PDOException $e) {
    die("Error de Base de Datos en solicitud: " . $e->getMessage());
}

// 6. Redirigir de vuelta al flujo de la licencia del usuario
header("Location: subir_licencia.php?solicitud=enviada");
exit();
?>