<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_proveedor']) && !isset($_SESSION['nombre_empresa'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

$remitente = $_SESSION['nombre_empresa'] ?? 'Empresa';
$destinatario = $_POST['destinatario'] ?? '';
$idVehiculo = $_POST['id_vehiculo'] ?? null;
$mensaje = trim($_POST['mensaje'] ?? '');

if (empty($mensaje) || empty($destinatario)) {
    echo json_encode(['success' => false, 'message' => 'Campos incompletos']);
    exit();
}

try {
    // Insertar la respuesta directamente en mensajes_chat usando 'id_v'
    $stmt = $pdo->prepare("INSERT INTO mensajes_chat (remitente, id_vehiculo, mensaje, fecha, leido) 
                           VALUES (:remitente, :id_v, :mensaje, NOW(), false)");
    $stmt->execute([
        ':remitente' => $remitente,
        ':id_v'      => $idVehiculo,
        ':mensaje'   => $mensaje
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}