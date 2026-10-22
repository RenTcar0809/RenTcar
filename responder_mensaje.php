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
    // 1. Agregamos 'destinatario' a las columnas y su respectivo marcador ':destinatario'
    $stmt = $pdo->prepare("INSERT INTO mensajes_chat (remitente, destinatario, id_vehiculo, mensaje, fecha, leido) 
                           VALUES (:remitente, :destinatario, :id_v, :mensaje, NOW(), 0)");
    
    // 2. Pasamos el valor en el array de ejecución
    $stmt->execute([
        ':remitente'    => $remitente,
        ':destinatario' => $destinatario, // <-- ¡Este era el que faltaba!
        ':id_v'         => $idVehiculo,
        ':mensaje'      => $mensaje
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}