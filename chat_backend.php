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

// Solución: Capturamos el ID sin importar si viene como id_item o id_vehiculo
$id_item = intval($_REQUEST['id_item'] ?? $_REQUEST['id_vehiculo'] ?? 0);
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
    $interlocutor_enviado = trim($_POST['destinatario'] ?? $_GET['destinatario'] ?? '');
    
    if (!empty($interlocutor_enviado)) {
        $destinatario = $interlocutor_enviado;
    } else {
        // Si el usuario actual es el dueño, por defecto necesita un destinatario válido, de lo contrario es el dueño del vehículo
        $destinatario = ($usuario_actual === $dueno) ? '' : $dueno;
    }

    // 1. ENVIAR MENSAJE
    if ($accion === 'enviar') {
        $mensaje = trim($_POST['mensaje'] ?? '');

        if (!empty($mensaje) && $id_item > 0) {
            // Si el remitente es el cliente, el destinatario obligado es el dueño. Si es el dueño, se respeta el destinatario enviado.
            $destinatario_final = ($usuario_actual === $dueno) ? $destinatario : $dueno;

            if (!empty($destinatario_final)) {
                $stmt = $pdo->prepare("INSERT INTO mensajes_chat (id_vehiculo, remitente, destinatario, mensaje, fecha) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$id_item, $usuario_actual, $destinatario_final, $mensaje]);
                
                echo json_encode(['status' => 'success']);
                exit();
            }
        }
        
        echo json_encode(['status' => 'empty', 'message' => 'Faltan datos, ID de vehículo inválido o destinatario vacío']);
        exit();
    }

    // 2. OBTENER MENSAJES (Filtrados estrictamente por este vehículo y entre estos dos usuarios)
    if ($accion === 'obtener') {
        $sql = "SELECT * FROM mensajes_chat 
                WHERE id_vehiculo = ? 
                AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?)) 
                ORDER BY fecha ASC";

        $stmt = $pdo->prepare($sql);
        
        // Determinamos con quién se está cruzando mensajes el usuario actual en este vehículo
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