<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_nombre']) || !isset($_POST['id_vehiculo'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada o datos incompletos.']);
    exit();
}

$nombre_usuario = $_SESSION['usuario_nombre'];
$id_vehiculo = intval($_POST['id_vehiculo']);

try {
    // 1. Obtener el ID del usuario actual
    $stmtUser = $pdo->prepare("SELECT id_usuario FROM usuario WHERE nombre = ? OR empresa = ? LIMIT 1");
    $stmtUser->execute([$nombre_usuario, $nombre_usuario]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userData) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado.']);
        exit();
    }
    $idSolicitante = $userData['id_usuario'];

    // 2. Obtener el propietario del vehículo
    $stmtVeh = $pdo->prepare("SELECT id_proveedor FROM vehiculo WHERE id_v = ?");
    $stmtVeh->execute([$id_vehiculo]);
    $vehData = $stmtVeh->fetch(PDO::FETCH_ASSOC);

    if (!$vehData) {
        echo json_encode(['status' => 'error', 'message' => 'Vehículo no encontrado.']);
        exit();
    }
    $idPropietario = intval($vehData['id_proveedor']);

    if ($idSolicitante === $idPropietario) {
        echo json_encode(['status' => 'success', 'message' => 'Es el propio dueño.']);
        exit();
    }

    // 3. Insertar o actualizar la solicitud en la tabla individual
    $stmtInsert = $pdo->prepare("
        INSERT INTO aprobaciones_licencias (id_proveedor, id_usuario, estado, fecha_actualizacion)
        VALUES (?, ?, 0, CURRENT_TIMESTAMP)
        ON CONFLICT (id_proveedor, id_usuario) 
        DO UPDATE SET fecha_actualizacion = CURRENT_TIMESTAMP
    ");
    $stmtInsert->execute([$idPropietario, $idSolicitante]);

    echo json_encode(['status' => 'success']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();