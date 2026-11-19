<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexion.php';

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$id_usuario = intval($_SESSION['IdUsuario']);
$nombre_usuario = '';
$error_msg = '';
$contactos = [];

function obtenerIdArrendatario(PDO $pdo, int $idUsuario, string $nombreUsuario): ?int {
    $stmtArr = $pdo->prepare("SELECT id_arrendatario FROM arrendatario WHERE id_usuario = ? LIMIT 1");
    $stmtArr->execute([$idUsuario]);
    $idArr = $stmtArr->fetchColumn();
    if ($idArr) {
        return intval($idArr);
    }

    try {
        $stmtInsArr = $pdo->prepare("
            INSERT INTO arrendatario (id_usuario, nombre, apellido, cedula, telefono, correo, direccion)
            VALUES (?, ?, 'N/A', 0, 0, 'cliente@rentcar.com', 'N/A')
            RETURNING id_arrendatario
        ");
        $stmtInsArr->execute([$idUsuario, $nombreUsuario ?: 'Cliente']);
        $idNuevo = $stmtInsArr->fetchColumn();
        if ($idNuevo) {
            return intval($idNuevo);
        }
    } catch (Exception $ex) {
    }

    $stmtArr2 = $pdo->prepare("SELECT id_arrendatario FROM arrendatario WHERE id_usuario = ? LIMIT 1");
    $stmtArr2->execute([$idUsuario]);
    $idArr2 = $stmtArr2->fetchColumn();
    if ($idArr2) {
        return intval($idArr2);
    }

    $stmtFallback = $pdo->prepare("SELECT id_arrendatario FROM arrendatario ORDER BY id_arrendatario ASC LIMIT 1");
    $stmtFallback->execute();
    $idFall = $stmtFallback->fetchColumn();
    return $idFall ? intval($idFall) : null;
}

function resolverIdArrendatarioParaPrestamo(PDO $pdo, int $idUsuarioActual, bool $esPropietario, string $interlocutor, string $nombreUsuarioActual): ?int {
    if (!$esPropietario) {
        return obtenerIdArrendatario($pdo, $idUsuarioActual, $nombreUsuarioActual);
    }

    if ($interlocutor !== '') {
        $stmtCli = $pdo->prepare("
            SELECT id_usuario, COALESCE(NULLIF(TRIM(empresa), ''), TRIM(nombre)) AS etiqueta
            FROM usuario
            WHERE TRIM(nombre) ILIKE TRIM(?) OR TRIM(empresa) ILIKE TRIM(?)
            LIMIT 1
        ");
        $stmtCli->execute([$interlocutor, $interlocutor]);
        $cli = $stmtCli->fetch(PDO::FETCH_ASSOC);
        if ($cli && !empty($cli['id_usuario'])) {
            return obtenerIdArrendatario($pdo, intval($cli['id_usuario']), (string)($cli['etiqueta'] ?? $interlocutor));
        }
    }

    return obtenerIdArrendatario($pdo, $idUsuarioActual, $nombreUsuarioActual);
}

try {
    $stmtU = $pdo->prepare('SELECT nombre, empresa, tipo FROM usuario WHERE id_usuario = ? LIMIT 1');
    $stmtU->execute([$id_usuario]);
    $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
    if ($uData) {
        $nombre_usuario = ((int)($uData['tipo'] ?? 0) === 1 && !empty(trim((string)($uData['empresa'] ?? ''))))
            ? trim((string)$uData['empresa'])
            : trim((string)($uData['nombre'] ?? ''));
    }
    $_SESSION['usuario_nombre'] = $nombre_usuario;

    if (isset($_GET['accion']) && $_GET['accion'] === 'confirmar_inicio' && isset($_GET['id_vehiculo'])) {
        $idVehiculoAccion = intval($_GET['id_vehiculo']);
        $interlocutorAccion = trim((string)($_GET['interlocutor'] ?? ''));

        $stmtVehInfo = $pdo->prepare("SELECT id_proveedor, precio FROM vehiculo WHERE id_v = ? LIMIT 1");
        $stmtVehInfo->execute([$idVehiculoAccion]);
        $vehInfo = $stmtVehInfo->fetch(PDO::FETCH_ASSOC);

        $esPropietarioActual = ($vehInfo && intval($vehInfo['id_proveedor'] ?? 0) === $id_usuario);
        $montoVehiculo = $vehInfo['precio'] ?? 0.00;
        $idArrendatario = resolverIdArrendatarioParaPrestamo($pdo, $id_usuario, $esPropietarioActual, $interlocutorAccion, $nombre_usuario);

        $stmtAlq = $pdo->prepare("
            SELECT * FROM prestamo
            WHERE id_vehiculo = ?
              AND LOWER(estado) IN ('pendiente', 'activo')
            ORDER BY id_prestamo DESC
            LIMIT 1
        ");
        $stmtAlq->execute([$idVehiculoAccion]);
        $alquilerData = $stmtAlq->fetch(PDO::FETCH_ASSOC);

        if (!$alquilerData) {
            $confCliente = $esPropietarioActual ? 0 : 1;
            $confPropietario = $esPropietarioActual ? 1 : 0;
            $estadoInicial = ($confCliente === 1 && $confPropietario === 1) ? 'activo' : 'pendiente';

            $stmtCreate = $pdo->prepare("
                INSERT INTO prestamo (
                    id_vehiculo, id_arrendatario, estado,
                    confirmar_cliente, confirmar_propietario,
                    fecha_inicio, fecha_fin, monto
                )
                VALUES (?, ?, ?, ?, ?, CURRENT_DATE, CURRENT_DATE + INTERVAL '7 days', ?)
            ");
            $stmtCreate->execute([
                $idVehiculoAccion,
                $idArrendatario,
                $estadoInicial,
                $confCliente,
                $confPropietario,
                $montoVehiculo
            ]);

            if ($estadoInicial === 'activo') {
                $pdo->prepare("UPDATE vehiculo SET estado = 'Alquilado' WHERE id_v = ?")->execute([$idVehiculoAccion]);
                $msgFinal = "¡Ambas partes han confirmado (2/2)! El alquiler se ha activado correctamente.";
            } else {
                $msgFinal = $esPropietarioActual
                    ? "Has confirmado como propietario (1/2). Esperando confirmación del cliente."
                    : "Has confirmado el alquiler (1/2). Esperando confirmación del propietario.";
            }
        } else {
            $idAlquiler = intval($alquilerData['id_prestamo']);
            $confCliente = intval($alquilerData['confirmar_cliente'] ?? 0);
            $confPropietario = intval($alquilerData['confirmar_propietario'] ?? 0);

            if ($esPropietarioActual) {
                $confPropietario = 1;
            } else {
                $confCliente = 1;
            }

            if ($confCliente === 1 && $confPropietario === 1) {
                $updAlq = $pdo->prepare("
                    UPDATE prestamo
                    SET estado = 'activo',
                        confirmar_cliente = 1,
                        confirmar_propietario = 1,
                        fecha_confirmacion = NOW(),
                        fecha_inicio = COALESCE(fecha_inicio, CURRENT_DATE),
                        fecha_fin = COALESCE(fecha_fin, CURRENT_DATE + INTERVAL '7 days'),
                        monto = COALESCE(monto, ?),
                        id_arrendatario = COALESCE(id_arrendatario, ?)
                    WHERE id_prestamo = ?
                ");
                $updAlq->execute([$montoVehiculo, $idArrendatario, $idAlquiler]);

                $updVeh = $pdo->prepare("UPDATE vehiculo SET estado = 'Alquilado' WHERE id_v = ?");
                $updVeh->execute([$idVehiculoAccion]);

                $msgFinal = "¡Ambas partes han confirmado (2/2)! El alquiler se ha activado correctamente.";
            } else {
                $updParcial = $pdo->prepare("
                    UPDATE prestamo
                    SET confirmar_cliente = ?,
                        confirmar_propietario = ?,
                        fecha_inicio = COALESCE(fecha_inicio, CURRENT_DATE),
                        fecha_fin = COALESCE(fecha_fin, CURRENT_DATE + INTERVAL '7 days'),
                        monto = COALESCE(monto, ?),
                        id_arrendatario = COALESCE(id_arrendatario, ?)
                    WHERE id_prestamo = ?
                ");
                $updParcial->execute([$confCliente, $confPropietario, $montoVehiculo, $idArrendatario, $idAlquiler]);

                $msgFinal = $esPropietarioActual
                    ? "Has confirmado como propietario (1/2). Esperando confirmación del cliente."
                    : "Has confirmado el alquiler (1/2). Esperando confirmación del propietario.";
            }
        }

        header("Location: mensajes.php?contacto=" . urlencode($idVehiculoAccion . '_' . $interlocutorAccion) . "&msg=" . urlencode($msgFinal));
        exit();
    }

    if (isset($_GET['accion']) && $_GET['accion'] === 'solicitar_finalizar' && isset($_GET['id_vehiculo'])) {
        $idVehiculoAccion = intval($_GET['id_vehiculo']);
        $interlocutorAccion = trim((string)($_GET['interlocutor'] ?? ''));

        $stmtVehInfo = $pdo->prepare("SELECT id_proveedor FROM vehiculo WHERE id_v = ? LIMIT 1");
        $stmtVehInfo->execute([$idVehiculoAccion]);
        $vehInfo = $stmtVehInfo->fetch(PDO::FETCH_ASSOC);
        $esPropietarioActual = ($vehInfo && intval($vehInfo['id_proveedor'] ?? 0) === $id_usuario);

        $stmtAlq = $pdo->prepare("
            SELECT * FROM prestamo
            WHERE id_vehiculo = ?
              AND LOWER(estado) = 'activo'
            ORDER BY id_prestamo DESC
            LIMIT 1
        ");
        $stmtAlq->execute([$idVehiculoAccion]);
        $alquilerData = $stmtAlq->fetch(PDO::FETCH_ASSOC);

        if ($alquilerData) {
            $idAlquiler = intval($alquilerData['id_prestamo']);
            $finCliente = intval($alquilerData['finalizar_cliente'] ?? 0);
            $finPropietario = intval($alquilerData['finalizar_propietario'] ?? 0);

            if ($esPropietarioActual) {
                $finPropietario = 1;
            } else {
                $finCliente = 1;
            }

            if ($finCliente === 1 && $finPropietario === 1) {
                $updAlq = $pdo->prepare("
                    UPDATE prestamo
                    SET estado = 'finalizado',
                        finalizar_cliente = 1,
                        finalizar_propietario = 1
                    WHERE id_prestamo = ?
                ");
                $updAlq->execute([$idAlquiler]);

                $updVeh = $pdo->prepare("UPDATE vehiculo SET estado = 'Disponible' WHERE id_v = ?");
                $updVeh->execute([$idVehiculoAccion]);

                $msgFinal = "¡Ambas partes han confirmado finalizar (2/2)! El alquiler ha finalizado con éxito.";
            } else {
                $updParcial = $pdo->prepare("
                    UPDATE prestamo
                    SET finalizar_cliente = ?,
                        finalizar_propietario = ?
                    WHERE id_prestamo = ?
                ");
                $updParcial->execute([$finCliente, $finPropietario, $idAlquiler]);

                $msgFinal = $esPropietarioActual
                    ? "Has confirmado finalizar como propietario (1/2). Esperando confirmación del cliente."
                    : "Has solicitado finalizar (1/2). Esperando confirmación del propietario.";
            }

            header("Location: mensajes.php?contacto=" . urlencode($idVehiculoAccion . '_' . $interlocutorAccion) . "&msg=" . urlencode($msgFinal));
            exit();
        }
    }

    $stmt_chats = $pdo->prepare('
        SELECT
            m.id_vehiculo, m.remitente, m.destinatario, v.id_v, v.marca, v.modelo, v.tipo, v.imagen AS imagen_principal_vehiculo,
            (SELECT f.ruta_imagen FROM fotos_vehiculos f WHERE f.id_vehiculo = m.id_vehiculo LIMIT 1) AS imagen_fotos_tabla
        FROM mensajes_chat m
        LEFT JOIN vehiculo v ON m.id_vehiculo = v.id_v
        WHERE m.destinatario = ? OR m.remitente = ?
        ORDER BY m.fecha DESC
    ');
    $stmt_chats->execute([$nombre_usuario, $nombre_usuario]);
    $todos_mensajes = $stmt_chats->fetchAll(PDO::FETCH_ASSOC);

    function obtenerUrlImagenUsuario($ruta) {
        $ruta = trim(str_replace('\\', '/', (string)$ruta));
        if ($ruta === '') return 'unnamed.png';
        if (strpos($ruta, 'http://') === 0 || strpos($ruta, 'https://') === 0) return $ruta;
        if (strpos($ruta, 'imagenes/') === 0 || strpos($ruta, 'uploads/') === 0) return $ruta;
        if (file_exists('uploads/' . $ruta)) return 'uploads/' . $ruta;
        if (file_exists('imagenes/' . $ruta)) return 'imagenes/' . $ruta;
        if (file_exists($ruta)) return $ruta;
        return 'unnamed.png';
    }

    foreach ($todos_mensajes as $msg) {
        $interlocutor = ($msg['remitente'] === $nombre_usuario) ? $msg['destinatario'] : $msg['remitente'];
        if (!empty($interlocutor) && $interlocutor !== $nombre_usuario) {
            $id_v_val = !empty($msg['id_vehiculo']) ? intval($msg['id_vehiculo']) : 0;
            $clave_contacto = $id_v_val . '_' . $interlocutor;
            $nombre_vehiculo = (!empty($msg['marca']) || !empty($msg['modelo']))
                ? trim(($msg['marca'] ?? '') . ' ' . ($msg['modelo'] ?? ''))
                : ($id_v_val > 0 ? "Vehículo #" . $id_v_val : "Conversación General");
            $url_foto_bruta = !empty($msg['imagen_fotos_tabla']) ? $msg['imagen_fotos_tabla'] : ($msg['imagen_principal_vehiculo'] ?? '');

            $tipo_v = strtolower((string)($msg['tipo'] ?? 'auto'));
            $tipo_formateado = (strpos($tipo_v, 'moto') !== false) ? 'moto' : 'auto';

            if (!isset($contactos[$clave_contacto])) {
                $contactos[$clave_contacto] = [
                    'interlocutor' => $interlocutor,
                    'id_vehiculo' => $id_v_val,
                    'nombre_auto' => $nombre_vehiculo,
                    'imagen_auto' => obtenerUrlImagenUsuario($url_foto_bruta),
                    'tipo_vehiculo' => $tipo_formateado
                ];
            }
        }
    }
} catch (PDOException $e) {
    $error_msg = "Error: " . $e->getMessage();
}

$keys_contacto = array_keys($contactos);
$contacto_activo_key = $_GET['contacto'] ?? (!empty($keys_contacto) ? $keys_contacto[0] : '');
$id_vehiculo_activo = 0;
$nombre_vehiculo_activo = '';
$imagen_vehiculo_activo = '';
$interlocutor_real = '';
$tipo_vehiculo_activo = 'auto';

if (!empty($contacto_activo_key) && isset($contactos[$contacto_activo_key])) {
    $id_vehiculo_activo = intval($contactos[$contacto_activo_key]['id_vehiculo']);
    $nombre_vehiculo_activo = $contactos[$contacto_activo_key]['nombre_auto'];
    $imagen_vehiculo_activo = $contactos[$contacto_activo_key]['imagen_auto'];
    $interlocutor_real = $contactos[$contacto_activo_key]['interlocutor'];
    $tipo_vehiculo_activo = $contactos[$contacto_activo_key]['tipo_vehiculo'] ?? 'auto';
}

$esPropietarioVista = false;
$alquilerChatActivo = null;
if ($id_vehiculo_activo > 0) {
    try {
        $stmtPropVista = $pdo->prepare("SELECT id_proveedor FROM vehiculo WHERE id_v = ? LIMIT 1");
        $stmtPropVista->execute([$id_vehiculo_activo]);
        $idProvVista = intval($stmtPropVista->fetchColumn() ?? 0);
        $esPropietarioVista = ($idProvVista > 0 && $idProvVista === $id_usuario);

        $stmtAlqCheck = $pdo->prepare("
            SELECT * FROM prestamo
            WHERE id_vehiculo = ?
              AND LOWER(estado) IN ('pendiente', 'activo')
            ORDER BY id_prestamo DESC
            LIMIT 1
        ");
        $stmtAlqCheck->execute([$id_vehiculo_activo]);
        $alquilerChatActivo = $stmtAlqCheck->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$mensajes_chat = [];
if (!empty($interlocutor_real)) {
    try {
        if ($id_vehiculo_activo > 0) {
            $pdo->prepare('UPDATE mensajes_chat SET leido = 1 WHERE destinatario = ? AND remitente = ? AND id_vehiculo = ? AND leido = 0')
                ->execute([$nombre_usuario, $interlocutor_real, $id_vehiculo_activo]);
            $stmt_h = $pdo->prepare('
                SELECT * FROM mensajes_chat
                WHERE id_vehiculo = ?
                  AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?))
                ORDER BY fecha ASC
            ');
            $stmt_h->execute([$id_vehiculo_activo, $nombre_usuario, $interlocutor_real, $interlocutor_real, $nombre_usuario]);
        } else {
            $pdo->prepare('UPDATE mensajes_chat SET leido = 1 WHERE destinatario = ? AND remitente = ? AND (id_vehiculo IS NULL OR id_vehiculo = 0) AND leido = 0')
                ->execute([$nombre_usuario, $interlocutor_real]);
            $stmt_h = $pdo->prepare('
                SELECT * FROM mensajes_chat
                WHERE (id_vehiculo IS NULL OR id_vehiculo = 0)
                  AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?))
                ORDER BY fecha ASC
            ');
            $stmt_h->execute([$nombre_usuario, $interlocutor_real, $interlocutor_real, $nombre_usuario]);
        }
        $mensajes_chat = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bandeja de Mensajes | RentCar</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="mensajes.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #0b0b0d;
            color: #f4f4f5;
            font-family: 'Inter', sans-serif;
        }
        .chat-wrapper {
            display: flex;
            height: 100vh;
            width: 100%;
            background: #0b0b0d;
            overflow: hidden;
        }
        .chat-sidebar {
            width: 320px;
            min-width: 280px;
            background: #141417;
            border-right: 1px solid #26262c;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 16px;
            border-bottom: 1px solid #26262c;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-header h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
        }
        .btn-volver {
            background: #232329;
            color: #e4e4e7;
            text-decoration: none;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-volver:hover { background: #2f2f37; }
        .contact-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: #e4e4e7;
            margin-bottom: 6px;
            transition: background 0.15s ease;
        }
        .contact-item:hover { background: #1d1d22; }
        .contact-item.active { background: rgba(229, 9, 20, 0.18); border: 1px solid rgba(229, 9, 20, 0.45); }
        .contact-avatar {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
            background: #222228;
        }
        .contact-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .contact-info h4 {
            margin: 0 0 3px 0;
            font-size: 0.92rem;
            color: #fff;
        }
        .contact-info small {
            color: #a1a1aa;
            font-size: 0.78rem;
        }
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: #0f0f12;
        }
        .chat-header {
            padding: 14px 20px;
            background: #141417;
            border-bottom: 1px solid #26262c;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .chat-header h3 {
            margin: 0;
            font-size: 1rem;
            color: #fff;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .message-bubble {
            max-width: min(72%, 520px);
            padding: 10px 14px;
            border-radius: 14px;
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .message-bubble.sent {
            align-self: flex-end;
            background: #e50914;
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }
        .message-bubble.received {
            align-self: flex-start;
            background: #1f1f24;
            color: #f4f4f5;
            border: 1px solid #2e2e36;
            border-bottom-left-radius: 4px;
        }
        .message-text {
            font-size: 0.94rem;
            white-space: pre-line;
        }
        .message-time {
            margin-top: 4px;
            font-size: 0.7rem;
            opacity: 0.75;
            text-align: right;
        }
        .chat-input-form {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            background: #141417;
            border-top: 1px solid #26262c;
            width: 100%;
        }
        .chat-input-form input[type="text"] {
            flex: 1;
            min-width: 0;
            height: 44px;
            padding: 0 16px;
            border-radius: 10px;
            border: 1px solid #2f2f38;
            background: #1c1c21;
            color: #ffffff;
            font-size: 0.94rem;
            outline: none;
        }
        .chat-input-form input[type="text"]:focus {
            border-color: #e50914;
        }
        .chat-input-form button[type="submit"] {
            width: 46px;
            height: 44px;
            border: none;
            border-radius: 10px;
            background: #e50914;
            color: #ffffff;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .chat-input-form button[type="submit"]:hover {
            background: #c40812;
        }
        .empty-chat-state {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a1a1aa;
        }
    </style>
</head>
<body>
    <div class="chat-wrapper">
        <aside class="chat-sidebar">
            <div class="sidebar-header">
                <a href="dashboardf.php" class="btn-volver"><i class="fas fa-chevron-left"></i> Volver</a>
                <h2>Mensajes</h2>
            </div>
            <div class="contact-list">
                <?php if (empty($contactos)): ?>
                    <div class="no-contacts" style="padding:16px;color:#a1a1aa;"><p>No tienes conversaciones activas.</p></div>
                <?php else: ?>
                    <?php foreach ($contactos as $key => $c): ?>
                        <a href="mensajes.php?contacto=<?php echo urlencode($key); ?>" class="contact-item <?php echo ($contacto_activo_key === $key) ? 'active' : ''; ?>">
                            <div class="contact-avatar"><img src="<?php echo htmlspecialchars($c['imagen_auto']); ?>" alt="Vehículo" onerror="this.src='unnamed.png'"></div>
                            <div class="contact-info">
                                <h4><?php echo htmlspecialchars($c['nombre_auto']); ?></h4>
                                <small>Contacto: <?php echo htmlspecialchars($c['interlocutor']); ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <main class="chat-main">
            <?php if (!empty($_GET['msg'])): ?>
                <div style="background: rgba(16, 185, 129, 0.15); border-left: 4px solid #10b981; color: #fff; padding: 10px 16px; margin: 10px 16px 0; border-radius: 6px; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($_GET['msg']); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($contacto_activo_key)): ?>
                <div class="empty-chat-state">
                    <h3>Selecciona una conversación</h3>
                </div>
            <?php else: ?>
                <div class="chat-header">
                    <div class="contact-avatar"><img src="<?php echo htmlspecialchars($imagen_vehiculo_activo); ?>" alt="Vehículo" onerror="this.src='unnamed.png'"></div>
                    <div style="flex-grow: 1;">
                        <h3><?php echo htmlspecialchars($nombre_vehiculo_activo); ?></h3>
                        <small style="color: #a1a1aa;">Conversación con: <strong><?php echo htmlspecialchars($interlocutor_real); ?></strong></small>
                    </div>

                    <?php
                        $estadoAlq = strtolower((string)($alquilerChatActivo['estado'] ?? 'pendiente'));
                        $confCliente = intval($alquilerChatActivo['confirmar_cliente'] ?? 0);
                        $confPropietario = intval($alquilerChatActivo['confirmar_propietario'] ?? 0);
                        $finCliente = intval($alquilerChatActivo['finalizar_cliente'] ?? 0);
                        $finPropietario = intval($alquilerChatActivo['finalizar_propietario'] ?? 0);

                        $yaConfirmoEsteRol = $esPropietarioVista ? ($confPropietario === 1) : ($confCliente === 1);
                        $yaFinalizoEsteRol = $esPropietarioVista ? ($finPropietario === 1) : ($finCliente === 1);
                        $faltaTextoConfirmar = $esPropietarioVista ? 'Falta cliente' : 'Falta propietario';

                        $totalConfirmacion = min(2, ($confCliente ? 1 : 0) + ($confPropietario ? 1 : 0));
                        $totalFinalizacion = min(2, ($finCliente ? 1 : 0) + ($finPropietario ? 1 : 0));
                    ?>

                    <?php if ($estadoAlq === 'activo'): ?>
                        <span style="background: #242429; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; border: 1px solid #383842; margin-right: 8px;">
                            Finalización: <?php echo $totalFinalizacion; ?>/2
                        </span>

                        <?php if ($yaFinalizoEsteRol): ?>
                            <span style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; border: 1px solid #f59e0b;">
                                <i class="fas fa-clock"></i> Ya confirmaste finalizar (<?php echo $faltaTextoConfirmar; ?>)
                            </span>
                        <?php else: ?>
                            <a href="mensajes.php?contacto=<?php echo urlencode($contacto_activo_key); ?>&accion=solicitar_finalizar&id_vehiculo=<?php echo $id_vehiculo_activo; ?>&interlocutor=<?php echo urlencode($interlocutor_real); ?>"
                               style="background: #e50914; color: white; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 6px;"
                               onclick="return confirm('¿Solicitar finalizar este alquiler?');">
                                <i class="fas fa-flag-checkered"></i> Finalizar Alquiler
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="background: #242429; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; border: 1px solid #383842; margin-right: 8px;">
                            Confirmación: <?php echo $totalConfirmacion; ?>/2
                        </span>

                        <?php if ($yaConfirmoEsteRol): ?>
                            <span style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; border: 1px solid #3b82f6;">
                                <i class="fas fa-clock"></i> Ya confirmaste (<?php echo $faltaTextoConfirmar; ?>)
                            </span>
                        <?php else: ?>
                            <a href="mensajes.php?contacto=<?php echo urlencode($contacto_activo_key); ?>&accion=confirmar_inicio&id_vehiculo=<?php echo $id_vehiculo_activo; ?>&interlocutor=<?php echo urlencode($interlocutor_real); ?>"
                               style="background: #10b981; color: white; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 6px;"
                               onclick="return confirm('¿Confirmar el inicio de este alquiler?');">
                                <i class="fas fa-file-contract"></i> Confirmar Alquiler
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <?php foreach ($mensajes_chat as $m): ?>
                        <?php $esMio = ($m['remitente'] === $nombre_usuario); ?>
                        <div class="message-bubble <?php echo $esMio ? 'sent' : 'received'; ?>">
                            <div class="message-text"><?php echo htmlspecialchars($m['mensaje']); ?></div>
                            <div class="message-time"><?php echo date('H:i', strtotime($m['fecha'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form class="chat-input-form" id="formEnviarMensaje" onsubmit="enviarMensaje(event)">
                    <input type="text" id="inputMensaje" placeholder="Escribe un mensaje..." autocomplete="off" required>
                    <button type="submit" aria-label="Enviar mensaje"><i class="fas fa-paper-plane"></i></button>
                </form>
            <?php endif; ?>
        </main>
    </div>

    <script>
    const idVehiculoActual = <?php echo json_encode($id_vehiculo_activo); ?>;
    const destinatarioActual = <?php echo json_encode($interlocutor_real); ?>;

    function enviarMensaje(e) {
        e.preventDefault();
        const input = document.getElementById('inputMensaje');
        if (!input) return;
        const texto = input.value.trim();
        if (!texto || !idVehiculoActual || !destinatarioActual) return;

        const formData = new URLSearchParams();
        formData.append('accion', 'enviar');
        formData.append('id_vehiculo', idVehiculoActual);
        formData.append('id_item', idVehiculoActual);
        formData.append('destinatario', destinatarioActual);
        formData.append('cliente', destinatarioActual);
        formData.append('mensaje', texto);

        fetch('chat_backend.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                input.value = '';
                window.location.reload();
            } else {
                alert(data.message || 'No se pudo enviar el mensaje.');
            }
        })
        .catch(err => console.error(err));
    }

    const box = document.getElementById('chatMessages');
    if (box) box.scrollTop = box.scrollHeight;
    </script>
</body>
</html>