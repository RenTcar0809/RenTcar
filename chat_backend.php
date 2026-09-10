<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_nombre'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// 1. ENVIAR MENSAJE
if ($accion === 'enviar') {
    $tipo = $_POST['tipo_vehiculo'] ?? 'auto'; // 'auto' o 'moto'
    $id_item = intval($_POST['id_item'] ?? 0);
    $mensaje = trim($_POST['mensaje'] ?? '');
    $remitente = $_SESSION['usuario_nombre'];
    $destinatario = 'Arrendatario'; // O el rol correspondiente

    if (!empty($mensaje)) {
        if ($tipo === 'moto') {
            $stmt = $pdo->prepare("INSERT INTO mensajes_chat (id_motocicleta, remitente, destinatario, mensaje) VALUES (?, ?, ?, ?)");
        } else {
            $stmt = $pdo->prepare("INSERT INTO mensajes_chat (id_vehiculo, remitente, destinatario, mensaje) VALUES (?, ?, ?, ?)");
        }
        $stmt->execute([$id_item, $remitente, $destinatario, $mensaje]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'empty']);
    }
    exit();
}

// 2. OBTENER MENSAJES (Polling en tiempo real)
if ($accion === 'obtener') {
    $tipo = $_GET['tipo_vehiculo'] ?? 'auto';
    $id_item = intval($_GET['id_item'] ?? 0);

    if ($tipo === 'moto') {
        $stmt = $pdo->prepare("SELECT * FROM mensajes_chat WHERE id_motocicleta = ? ORDER BY fecha ASC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM mensajes_chat WHERE id_vehiculo = ? ORDER BY fecha ASC");
    }
    $stmt->execute([$id_item]);
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'mensajes' => $mensajes, 'usuario_actual' => $_SESSION['usuario_nombre']]);
    exit();
}