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
$id_item = intval($_REQUEST['id_item'] ?? $_REQUEST['id_vehiculo'] ?? 0);
$usuario_actual = $_SESSION['usuario_nombre'];

try {
    // 1. Obtener al dueño del vehículo de la tabla 'vehiculo'
    $dueno = '';
    if ($id_item > 0) {
        $stmtV = $pdo->prepare("SELECT * FROM vehiculo WHERE id_v = ?");
        $stmtV->execute([$id_item]);
        $item = $stmtV->fetch(PDO::FETCH_ASSOC);

        if ($item && isset($item['id_proveedor'])) {
            $stmtU = $pdo->prepare('SELECT nombre, empresa, tipo FROM usuario WHERE "IdUsuario" = ?');
            $stmtU->execute([$item['id_proveedor']]);
            $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
            if ($uData) {
                $dueno = ($uData['tipo'] == 1) ? $uData['empresa'] : $uData['nombre'];
            }
        }
    }

    if (empty($dueno)) {
        $dueno = 'Arrendatario'; // Respaldo por defecto
    }

    $interlocutor_enviado = trim($_POST['destinatario'] ?? $_GET['destinatario'] ?? '');

    // 1. ENVIAR MENSAJE
    if ($accion === 'enviar') {
        $mensaje = trim($_POST['mensaje'] ?? '');

        if (!empty($mensaje) && $id_item > 0) {
            // Si mandaron un destinatario explícito, lo usamos; si no, va dirigido al dueño o por defecto
            $destinatario_final = !empty($interlocutor_enviado) ? $interlocutor_enviado : $dueno;

            // Si por alguna razón el usuario actual es el mismo dueño y no hay destinatario, buscamos en los mensajes previos con quién hablaba
            if ($usuario_actual === $destinatario_final) {
                $stmtUltimo = $pdo->prepare("SELECT remitente FROM mensajes_chat WHERE id_vehiculo = ? AND remitente != ? ORDER BY fecha DESC LIMIT 1");
                $stmtUltimo->execute([$id_item, $usuario_actual]);
                $ultimo_cliente = $stmtUltimo->fetchColumn();
                if ($ultimo_cliente) {
                    $destinatario_final = $ultimo_cliente;
                }
            }

            $stmt = $pdo->prepare("INSERT INTO mensajes_chat (id_vehiculo, remitente, destinatario, mensaje, fecha) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$id_item, $usuario_actual, $destinatario_final, $mensaje]);
            
            echo json_encode(['status' => 'success']);
            exit();
        }
        
        echo json_encode(['status' => 'empty', 'message' => 'Faltan datos, ID de vehículo inválido o mensaje vacío', 'id_recibido' => $id_item]);
        exit();
    }

    // 2. OBTENER MENSAJES
    if ($accion === 'obtener') {
        $otro_usuario = !empty($interlocutor_enviado) ? $interlocutor_enviado : $dueno;

        $sql = "SELECT * FROM mensajes_chat 
                WHERE id_vehiculo = ? 
                AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?)) 
                ORDER BY fecha ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_item, $usuario_actual, $otro_usuario, $otro_usuario, $usuario_actual]);
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si la consulta estricta anterior no trae nada (por ejemplo, primer mensaje automático), traemos todos los mensajes del vehículo
        if (empty($mensajes)) {
            $stmtG = $pdo->prepare("SELECT * FROM mensajes_chat WHERE id_vehiculo = ? ORDER BY fecha ASC");
            $stmtG->execute([$id_item]);
            $mensajes = $stmtG->fetchAll(PDO::FETCH_ASSOC);
        }

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