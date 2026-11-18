<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_nombre']) || !isset($_GET['id_vehiculo'])) {
    header("Location: automoviles.php");
    exit();
}

$id_vehiculo = intval($_GET['id_vehiculo']);
$usuario_actual = $_SESSION['usuario_nombre'];

try {
    // 1. Obtener el ID del solicitante (ej. admin)
    $stmtUser = $pdo->prepare("SELECT id_usuario, licencia_numero FROM usuario WHERE nombre = ? OR empresa = ? LIMIT 1");
    $stmtUser->execute([$usuario_actual, $usuario_actual]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // 2. Obtener el ID del dueño del vehículo (ej. thamara)
    $stmtVeh = $pdo->prepare("SELECT id_proveedor FROM vehiculo WHERE id_v = ?");
    $stmtVeh->execute([$id_vehiculo]);
    $vehData = $stmtVeh->fetch(PDO::FETCH_ASSOC);

    if ($userData && $vehData && $userData['id_usuario'] != $vehData['id_proveedor']) {
        $idSolicitante = $userData['id_usuario'];
        $idPropietario = $vehData['id_proveedor'];

        // 3. Registrar o mantener la solicitud pendiente (estado = 0) para este dueño específico
        $stmtInsert = $pdo->prepare("
            INSERT INTO aprobaciones_licencias (id_proveedor, id_usuario, estado, fecha_actualizacion)
            VALUES (:prov, :cli, 0, CURRENT_TIMESTAMP)
            ON CONFLICT (id_proveedor, id_usuario) DO NOTHING
        ");
        $stmtInsert->execute([
            ':prov' => $idPropietario,
            ':cli'  => $idSolicitante
        ]);
    }
} catch (PDOException $e) {
    // Manejar error silenciosamente o registrar en log
}

// Redirigir a subir/revisar licencia con aviso
header("Location: subir_licencia.php?solicitado=1");
exit();
?>