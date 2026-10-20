<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_proveedor']) && !isset($_SESSION['nombre_empresa'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

$remitente = $_POST['remitente'] ?? '';
$idVehiculo = $_POST['id_vehiculo'] ?? null;

if (empty($remitente) || empty($idVehiculo)) {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes para eliminar']);
    exit();
}

try {
    // Borrar todos los mensajes asociados a ese remitente para ese vehículo
    $stmt = $pdo->prepare("DELETE FROM mensajes_chat WHERE remitente = :remitente AND id_vehiculo = :id_v");
    $stmt->execute([
        ':remitente' => $remitente,
        ':id_v'      => $idVehiculo
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}