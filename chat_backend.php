<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

// Soportar diferentes nombres de sesión por seguridad
$usuario_actual = $_SESSION['usuario_nombre'] ?? $_SESSION['usuario'] ?? '';

if (empty($usuario_actual)) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit();
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$tipo = $_REQUEST['tipo_vehiculo'] ?? 'auto'; 
$id_item = intval($_REQUEST['id_item'] ?? $_REQUEST['id_vehiculo'] ?? 0);

// 1. VERIFICAR MENSAJES NUEVOS / NOTIFICACIONES (Solo cuenta no leídos)
if ($accion === 'verificar_nuevos') {
    try {
        $stmtN = $pdo->prepare("
            SELECT id_vehiculo, remitente, COUNT(*) as total 
            FROM mensajes_chat 
            WHERE destinatario = ? AND leido = 0
            GROUP BY id_vehiculo, remitente
        ");
        $stmtN->execute([$usuario_actual]);
        $conteo = $stmtN->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'notificaciones' => $conteo
        ]);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

// 2. ELIMINAR / TERMINAR CONVERSACIÓN
if ($accion === 'eliminar_chat') {
    $destinatario_borrar = trim($_POST['destinatario'] ?? $_GET['destinatario'] ?? '');

    if (!empty($destinatario_borrar)) {
        if ($id_item > 0) {
            $stmtDel = $pdo->prepare("
                DELETE FROM mensajes_chat 
                WHERE id_vehiculo = ? 
                AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?))
            ");
            $stmtDel->execute([$id_item, $usuario_actual, $destinatario_borrar, $destinatario_borrar, $usuario_actual]);
        } else {
            $stmtDel = $pdo->prepare("
                DELETE FROM mensajes_chat 
                WHERE (id_vehiculo IS NULL OR id_vehiculo = 0) 
                AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?))
            ");
            $stmtDel->execute([$usuario_actual, $destinatario_borrar, $destinatario_borrar, $usuario_actual]);
        }

        echo json_encode(['status' => 'success']);
        exit();
    }

    echo json_encode(['status' => 'error', 'message' => 'Faltan datos para eliminar la conversación']);
    exit();
}

try {
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
        $dueno = 'Arrendatario';
    }

    $interlocutor_enviado = trim($_POST['destinatario'] ?? $_GET['destinatario'] ?? '');

    // 3. ENVIAR MENSAJE (Inserta con leido = 0 por defecto)
    if ($accion === 'enviar') {
        $mensaje = trim($_POST['mensaje'] ?? '');

        if (!empty($mensaje) && $id_item > 0) {
            $destinatario_final = !empty($interlocutor_enviado) ? $interlocutor_enviado : $dueno;

            if ($usuario_actual === $destinatario_final) {
                $stmtUltimo = $pdo->prepare("SELECT remitente FROM mensajes_chat WHERE id_vehiculo = ? AND remitente != ? ORDER BY fecha DESC LIMIT 1");
                $stmtUltimo->execute([$id_item, $usuario_actual]);
                $ultimo_cliente = $stmtUltimo->fetchColumn();
                if ($ultimo_cliente) {
                    $destinatario_final = $ultimo_cliente;
                }
            }

            if ($usuario_actual === $destinatario_final) {
                echo json_encode(['status' => 'error', 'message' => 'No puedes enviarte mensajes a ti mismo.']);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO mensajes_chat (id_vehiculo, remitente, destinatario, mensaje, fecha, leido) VALUES (?, ?, ?, ?, NOW(), 0)");
            $stmt->execute([$id_item, $usuario_actual, $destinatario_final, $mensaje]);
            
            echo json_encode(['status' => 'success']);
            exit();
        }
        
        echo json_encode(['status' => 'empty', 'message' => 'Faltan datos, ID de vehículo inválido o mensaje vacío', 'id_recibido' => $id_item]);
        exit();
    }

    // 4. OBTENER MENSAJES (Y marcar automáticamente como leídos los recibidos)
    if ($accion === 'obtener') {
        $otro_usuario = !empty($interlocutor_enviado) ? $interlocutor_enviado : $dueno;

        // Marcar como leídos los mensajes que este usuario recibió de este interlocutor
        if ($id_item > 0) {
            $stmtMarcar = $pdo->prepare("UPDATE mensajes_chat SET leido = 1 WHERE destinatario = ? AND remitente = ? AND id_vehiculo = ? AND leido = 0");
            $stmtMarcar->execute([$usuario_actual, $otro_usuario, $id_item]);
        } else {
            $stmtMarcar = $pdo->prepare("UPDATE mensajes_chat SET leido = 1 WHERE destinatario = ? AND remitente = ? AND (id_vehiculo IS NULL OR id_vehiculo = 0) AND leido = 0");
            $stmtMarcar->execute([$usuario_actual, $otro_usuario]);
        }

        $sql = "SELECT * FROM mensajes_chat 
                WHERE id_vehiculo = ? 
                AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?)) 
                ORDER BY fecha ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_item, $usuario_actual, $otro_usuario, $otro_usuario, $usuario_actual]);
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($mensajes)) {
            $stmtG = $pdo->prepare("SELECT * FROM mensajes_chat WHERE id_vehiculo = ? ORDER BY fecha ASC");
            $stmtG->execute([$id_item]);
            $mensajes = $stmtG->fetchAll(PDO::FETCH_ASSOC) ?? [];
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