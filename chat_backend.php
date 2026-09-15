<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_nombre'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$tipo = $_REQUEST['tipo_vehiculo'] ?? 'auto'; // 'auto' o 'moto'
$id_item = intval($_REQUEST['id_item'] ?? 0);
$usuario_actual = $_SESSION['usuario_nombre'];

try {
    // 1. Obtener al dueño del vehículo o motocicleta para saber con quién se establece la conversación privada
    $dueno = '';
    if ($tipo === 'moto') {
        // Asumiendo que tienes una tabla de motocicletas o campo equivalente. 
        // Si usas la misma tabla o una llamada 'motocicleta', ajusta el nombre de la tabla aquí:
        $stmtV = $pdo->prepare("SELECT * FROM motocicleta WHERE id_m = ?"); // Cambia 'id_m' o la tabla si es diferente
        $stmtV->execute([$id_item]);
        $item = $stmtV->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmtV = $pdo->prepare("SELECT * FROM vehiculo WHERE id_v = ?");
        $stmtV->execute([$id_item]);
        $item = $stmtV->fetch(PDO::FETCH_ASSOC);
    }

    if ($item) {
        // Intentamos obtener el dueño o empresa asociada al vehículo
        // Si en tu tabla vehículo guardas el ID o el nombre del creador/empresa:
        if (isset($item['id_usuario'])) {
            $stmtU = $pdo->prepare("SELECT nombre, empresa, tipo FROM usuario WHERE IdUsuario = ?");
            $stmtU->execute([$item['id_usuario']]);
            $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
            if ($uData) {
                $dueno = ($uData['tipo'] == 1) ? $uData['empresa'] : $uData['nombre'];
            }
        }
        // Fallback si el campo de empresa viene directo en el vehículo
        if (empty($dueno) && isset($item['empresa'])) {
            $dueno = $item['empresa'];
        }
    }

    // Si por alguna razón no se encuentra el dueño exacto, usamos un valor por defecto seguro
    if (empty($dueno)) {
        $dueno = 'Arrendatario';
    }

    // Definimos quién es el destinatario automático:
    // Si el usuario actual es el dueño, el destinatario será la contraparte (o el último que interactuó). 
    // Para simplificar la lógica de 1 a 1: si el usuario actual es el dueño, el chat privado se establece 
    // con el cliente que esté visualizando o enviando. Si es un cliente, su destinatario es el $dueno.
    $destinatario = ($usuario_actual === $dueno) ? 'Cliente' : $dueno; 
    // Nota: Si prefieres que el destinatario se guarde explícitamente como el dueño del carro:
    // $destinatario = $dueno;

    // 1. ENVIAR MENSAJE
    if ($accion === 'enviar') {
        $mensaje = trim($_POST['mensaje'] ?? '');

        if (!empty($mensaje)) {
            if ($tipo === 'moto') {
                $stmt = $pdo->prepare("INSERT INTO mensajes_chat (id_motocicleta, remitente, destinatario, mensaje) VALUES (?, ?, ?, ?)");
            } else {
                $stmt = $pdo->prepare("INSERT INTO mensajes_chat (id_vehiculo, remitente, destinatario, mensaje) VALUES (?, ?, ?, ?)");
            }
            // Guardamos asignando al usuario actual como remitente y al dueño/interesado como destinatario
            $stmt->execute([$id_item, $usuario_actual, $dueno, $mensaje]);
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'empty']);
        }
        exit();
    }

    // 2. OBTENER MENSAJES (Filtrados exclusivamente entre el usuario actual y el dueño del vehículo)
    if ($accion === 'obtener') {
        if ($tipo === 'moto') {
            $sql = "SELECT * FROM mensajes_chat 
                    WHERE id_motocicleta = ? 
                    AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?)) 
                    ORDER BY fecha ASC";
        } else {
            $sql = "SELECT * FROM mensajes_chat 
                    WHERE id_vehiculo = ? 
                    AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?)) 
                    ORDER BY fecha ASC";
        }

        $stmt = $pdo->prepare($sql);
        // Filtramos para que solo traiga mensajes donde participen ambos (Usuario Actual <-> Dueño)
        $stmt->execute([$id_item, $usuario_actual, $dueno, $dueno, $usuario_actual]);
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