<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_nombre'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$tipo = $_REQUEST['tipo_vehiculo'] ?? 'auto'; 
$id_item = intval($_REQUEST['id_item'] ?? 0);
$usuario_actual = $_SESSION['usuario_nombre'];

try {
    // 1. Obtener al dueño del vehículo de la tabla unificada 'vehiculo'
    $dueno = '';
    $stmtV = $pdo->prepare("SELECT * FROM vehiculo WHERE id_v = ?");
    $stmtV->execute([$id_item]);
    $item = $stmtV->fetch(PDO::FETCH_ASSOC);

    if ($item && isset($item['id_proveedor'])) {
        // Consultar el usuario proveedor asociado a este vehículo
        $stmtU = $pdo->prepare('SELECT nombre, empresa, tipo FROM usuario WHERE "IdUsuario" = ?');
        $stmtU->execute([$item['id_proveedor']]);
        $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($uData) {
            $dueno = ($uData['tipo'] == 1) ? $uData['empresa'] : $uData['nombre'];
        }
    }

    if (empty($dueno)) {
        $dueno = 'Arrendatario';
    }

    // Definir quién es el destinatario real según quién esté enviando el mensaje
    $interlocutor_enviado = trim($_POST['destinatario'] ?? '');
    if (!empty($interlocutor_enviado)) {
        $destinatario = $interlocutor_enviado;
    } else {
        $destinatario = ($usuario_actual === $dueno) ? '' : $dueno;
    }

    // 1. ENVIAR MENSAJE
    if ($accion === 'enviar') {
        $mensaje = trim($_POST['mensaje'] ?? '');

        if (!empty($mensaje) && !empty($destinatario)) {
            // Guardamos usando siempre id_vehiculo ya que autos y motos conviven en la tabla vehiculo
            $stmt = $pdo->prepare("INSERT INTO mensajes_chat (id_vehiculo, remitente, destinatario, mensaje, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$id_item, $usuario_actual, $destinatario, $mensaje]);
            
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'empty', 'message' => 'Faltan datos o destinatario']);
        }
        exit();
    }

    // 2. OBTENER MENSAJES (Filtrados estrictamente por este vehículo y entre estos dos usuarios)
    if ($accion === 'obtener') {
        $sql = "SELECT * FROM mensajes_chat 
                WHERE id_vehiculo = ? 
                AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?)) 
                ORDER BY fecha ASC";

        $stmt = $pdo->prepare($sql);
        // Si el usuario actual es el dueño, necesitamos saber con qué cliente está conversando en este ítem
        $otro_usuario = !empty($interlocutor_enviado) ? $interlocutor_enviado : $dueno;

        $stmt->execute([$id_item, $usuario_actual, $otro_usuario, $otro_usuario, $usuario_actual]);
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'mensajes' => $mensajes, 
            'usuario_actual' => $usuario_actual,
            'dueno' => $dueno
        ]);
        exit();
    }

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}
?>