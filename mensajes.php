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

$id_usuario = $_SESSION['IdUsuario'];
$nombre_usuario = '';
$error_msg = '';
$contactos = []; 

try {
    $stmtU = $pdo->prepare('SELECT nombre, empresa, tipo FROM usuario WHERE id_usuario = ?');
    $stmtU->execute([$id_usuario]);
    $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
    if ($uData) {
        $nombre_usuario = ($uData['tipo'] == 1) ? $uData['empresa'] : $uData['nombre'];
    }
    $_SESSION['usuario_nombre'] = $nombre_usuario;

    // PROCESAR CONFIRMAR INICIO DE PRÉSTAMO POR PARTE DEL CLIENTE
    if (isset($_GET['accion']) && $_GET['accion'] === 'confirmar_inicio' && isset($_GET['id_vehiculo'])) {
        $idVehiculoAccion = intval($_GET['id_vehiculo']);
        $interlocutorAccion = $_GET['interlocutor'] ?? '';

        // 1. Obtener o asegurar el id_arrendatario correspondiente a este usuario cliente
        $idArrendatario = null;
        $stmtArr = $pdo->prepare("SELECT id_arrendatario FROM arrendatario WHERE id_usuario = ? LIMIT 1");
        $stmtArr->execute([$id_usuario]);
        $idArrendatario = $stmtArr->fetchColumn();

        if (!$idArrendatario) {
            try {
                $stmtInsArr = $pdo->prepare("
                    INSERT INTO arrendatario (id_usuario, nombre, apellido, cedula, telefono, correo, direccion) 
                    VALUES (?, ?, 'N/A', 0, 0, 'cliente@rentcar.com', 'N/A')
                ");
                $stmtInsArr->execute([$id_usuario, $nombre_usuario]);
                $idArrendatario = $pdo->lastInsertId();

                if (!$idArrendatario) {
                    $stmtArr2 = $pdo->prepare("SELECT id_arrendatario FROM arrendatario WHERE id_usuario = ? LIMIT 1");
                    $stmtArr2->execute([$id_usuario]);
                    $idArrendatario = $stmtArr2->fetchColumn();
                }
            } catch (Exception $ex) {}
        }

        if (!$idArrendatario) {
            $stmtFallback = $pdo->prepare("SELECT id_arrendatario FROM arrendatario ORDER BY id_arrendatario ASC LIMIT 1");
            $stmtFallback->execute();
            $idArrendatario = $stmtFallback->fetchColumn();
        }

        // 2. Obtener el precio del vehículo
        $stmtPrecio = $pdo->prepare("SELECT precio FROM vehiculo WHERE id_v = ? LIMIT 1");
        $stmtPrecio->execute([$idVehiculoAccion]);
        $montoVehiculo = $stmtPrecio->fetchColumn() ?: 0.00;

        $stmtAlq = $pdo->prepare("SELECT * FROM prestamo WHERE id_vehiculo = ? AND estado IN ('pendiente', 'Pendiente', 'activo', 'Activo') ORDER BY id_prestamo DESC LIMIT 1");
        $stmtAlq->execute([$idVehiculoAccion]);
        $alquilerData = $stmtAlq->fetch(PDO::FETCH_ASSOC);

        if (!$alquilerData) {
            $stmtCreate = $pdo->prepare("
                INSERT INTO prestamo (id_vehiculo, id_arrendatario, estado, confirmar_cliente, fecha_inicio, fecha_fin, monto) 
                VALUES (?, ?, 'pendiente', 1, CURRENT_DATE, CURRENT_DATE + INTERVAL '7 days', ?)
            ");
            $stmtCreate->execute([$idVehiculoAccion, $idArrendatario, $montoVehiculo]);
            $msgFinal = "Has confirmado el alquiler. Esperando confirmación del propietario.";
        } else {
            $idAlquiler = $alquilerData['id_prestamo'];
            $confCliente = 1;
            $confPropietario = intval($alquilerData['confirmar_propietario'] ?? 0);

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
                        fecha_inicio = COALESCE(fecha_inicio, CURRENT_DATE),
                        fecha_fin = COALESCE(fecha_fin, CURRENT_DATE + INTERVAL '7 days'),
                        monto = COALESCE(monto, ?),
                        id_arrendatario = COALESCE(id_arrendatario, ?) 
                    WHERE id_prestamo = ?
                ");
                $updParcial->execute([$confCliente, $montoVehiculo, $idArrendatario, $idAlquiler]);

                $msgFinal = "Has confirmado el alquiler. Esperando confirmación del propietario.";
            }
        }

        header("Location: mensajes.php?contacto=" . urlencode($idVehiculoAccion . '_' . $interlocutorAccion) . "&msg=" . urlencode($msgFinal));
        exit();
    }

    // PROCESAR FINALIZAR ALQUILER POR PARTE DEL CLIENTE
    if (isset($_GET['accion']) && $_GET['accion'] === 'solicitar_finalizar' && isset($_GET['id_vehiculo'])) {
        $idVehiculoAccion = intval($_GET['id_vehiculo']);
        $interlocutorAccion = $_GET['interlocutor'] ?? '';

        $stmtAlq = $pdo->prepare("SELECT * FROM prestamo WHERE id_vehiculo = ? AND estado IN ('activo', 'Activo') ORDER BY id_prestamo DESC LIMIT 1");
        $stmtAlq->execute([$idVehiculoAccion]);
        $alquilerData = $stmtAlq->fetch(PDO::FETCH_ASSOC);

        if ($alquilerData) {
            $idAlquiler = $alquilerData['id_prestamo'];
            $finCliente = 1;
            $finPropietario = intval($alquilerData['finalizar_propietario'] ?? 0);

            if ($finCliente === 1 && $finPropietario === 1) {
                $updAlq = $pdo->prepare("UPDATE prestamo SET estado = 'finalizado', finalizar_cliente = 1, finalizar_propietario = 1 WHERE id_prestamo = ?");
                $updAlq->execute([$idAlquiler]);

                $updVeh = $pdo->prepare("UPDATE vehiculo SET estado = 'Disponible' WHERE id_v = ?");
                $updVeh->execute([$idVehiculoAccion]);

                $msgFinal = "¡Ambas partes han confirmado finalizar (2/2)! El alquiler ha finalizado con éxito.";
            } else {
                $updParcial = $pdo->prepare("UPDATE prestamo SET finalizar_cliente = ? WHERE id_prestamo = ?");
                $updParcial->execute([$finCliente, $idAlquiler]);

                $msgFinal = "Has solicitado finalizar. Esperando confirmación del propietario.";
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
        $ruta = trim(str_replace('\\', '/', $ruta));
        if (empty($ruta)) return 'unnamed.png';
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
            $id_v_val = !empty($msg['id_vehiculo']) ? $msg['id_vehiculo'] : 0;
            $clave_contacto = $id_v_val . '_' . $interlocutor;
            $nombre_vehiculo = (!empty($msg['marca']) || !empty($msg['modelo'])) ? trim($msg['marca'] . ' ' . $msg['modelo']) : ($id_v_val > 0 ? "Vehículo #" . $id_v_val : "Conversación General");
            $url_foto_bruta = !empty($msg['imagen_fotos_tabla']) ? $msg['imagen_fotos_tabla'] : ($msg['imagen_principal_vehiculo'] ?? '');
            
            $tipo_v = strtolower($msg['tipo'] ?? 'auto');
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
    $id_vehiculo_activo = $contactos[$contacto_activo_key]['id_vehiculo'];
    $nombre_vehiculo_activo = $contactos[$contacto_activo_key]['nombre_auto'];
    $imagen_vehiculo_activo = $contactos[$contacto_activo_key]['imagen_auto'];
    $interlocutor_real = $contactos[$contacto_activo_key]['interlocutor'];
    $tipo_vehiculo_activo = $contactos[$contacto_activo_key]['tipo_vehiculo'] ?? 'auto';
}

$alquilerChatActivo = null;
if ($id_vehiculo_activo > 0) {
    try {
        $stmtAlqCheck = $pdo->prepare("SELECT * FROM prestamo WHERE id_vehiculo = ? AND estado IN ('pendiente', 'Pendiente', 'activo', 'Activo') ORDER BY id_prestamo DESC LIMIT 1");
        $stmtAlqCheck->execute([$id_vehiculo_activo]);
        $alquilerChatActivo = $stmtAlqCheck->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$mensajes_chat = [];
if (!empty($interlocutor_real)) {
    try {
        if ($id_vehiculo_activo > 0) {
            $pdo->prepare('UPDATE mensajes_chat SET leido = 1 WHERE destinatario = ? AND remitente = ? AND id_vehiculo = ? AND leido = 0')->execute([$nombre_usuario, $interlocutor_real, $id_vehiculo_activo]);
            $stmt_h = $pdo->prepare('SELECT * FROM mensajes_chat WHERE id_vehiculo = ? AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?)) ORDER BY fecha ASC');
            $stmt_h->execute([$id_vehiculo_activo, $nombre_usuario, $interlocutor_real, $interlocutor_real, $nombre_usuario]);
        } else {
            $pdo->prepare('UPDATE mensajes_chat SET leido = 1 WHERE destinatario = ? AND remitente = ? AND (id_vehiculo IS NULL OR id_vehiculo = 0) AND leido = 0')->execute([$nombre_usuario, $interlocutor_real]);
            $stmt_h = $pdo->prepare('SELECT * FROM mensajes_chat WHERE (id_vehiculo IS NULL OR id_vehiculo = 0) AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?)) ORDER BY fecha ASC');
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
                    <div class="no-contacts"><p>No tienes conversaciones activas.</p></div>
                <?php else: ?>
                    <?php foreach ($contactos as $key => $c): ?>
                        <a href="mensajes.php?contacto=<?php echo urlencode($key); ?>" class="contact-item <?php echo ($contacto_activo_key === $key) ? 'active' : ''; ?>">
                            <div class="contact-avatar"><img src="<?php echo htmlspecialchars($c['imagen_auto']); ?>" alt="Vehículo" onerror="this.src='unnamed.png'"></div>
                            <div class="contact-info">
                                <h4><?php echo htmlspecialchars($c['nombre_auto']); ?></h4>
                                <small>Propietario: <?php echo htmlspecialchars($c['interlocutor']); ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <main class="chat-main">
            <?php if (empty($contacto_activo_key)): ?>
                <div class="empty-chat-state">
                    <h3>Selecciona una conversación</h3>
                </div>
            <?php else: ?>
                <div class="chat-header">
                    <div class="contact-avatar"><img src="<?php echo htmlspecialchars($imagen_vehiculo_activo); ?>" alt="Vehículo" onerror="this.src='unnamed.png'"></div>
                    <div style="flex-grow: 1;">
                        <h3><?php echo htmlspecialchars($nombre_vehiculo_activo); ?></h3>
                        <small style="color: #aaa;">Conversación con: <strong><?php echo htmlspecialchars($interlocutor_real); ?></strong></small>
                    </div>

                    <!-- BOTONES DE ACCIÓN Y RECUADRO CONTADOR (X/2) PARA EL CLIENTE -->
                    <?php 
                        $estadoAlq = $alquilerChatActivo['estado'] ?? 'pendiente';
                        $confCliente = intval($alquilerChatActivo['confirmar_cliente'] ?? 0);
                        $confPropietario = intval($alquilerChatActivo['confirmar_propietario'] ?? 0);
                        $finCliente = intval($alquilerChatActivo['finalizar_cliente'] ?? 0);
                        $finPropietario = intval($alquilerChatActivo['finalizar_propietario'] ?? 0);

                        $totalConfirmacion = $confCliente + $confPropietario;
                        $totalFinalizacion = $finCliente + $finPropietario;
                    ?>

                    <?php if (strtolower($estadoAlq) === 'activo'): ?>
                        <span style="background: #2a2a2a; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; border: 1px solid #444; margin-right: 8px;">
                            Finalización: <?php echo $totalFinalizacion; ?>/2
                        </span>

                        <?php if ($finCliente === 1): ?>
                            <span style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; border: 1px solid #f59e0b;">
                                <i class="fas fa-clock"></i> Ya confirmaste finalizar (Falta propietario)
                            </span>
                        <?php else: ?>
                            <a href="mensajes.php?contacto=<?php echo urlencode($contacto_activo_key); ?>&accion=solicitar_finalizar&id_vehiculo=<?php echo $id_vehiculo_activo; ?>&interlocutor=<?php echo urlencode($interlocutor_real); ?>" 
                               style="background: #e50914; color: white; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 6px;" 
                               onclick="return confirm('¿Solicitar finalizar este alquiler?');">
                                <i class="fas fa-flag-checkered"></i> Finalizar Alquiler
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="background: #2a2a2a; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; border: 1px solid #444; margin-right: 8px;">
                            Confirmación: <?php echo $totalConfirmacion; ?>/2
                        </span>

                        <?php if ($confCliente === 1): ?>
                            <span style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 6px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; border: 1px solid #3b82f6;">
                                <i class="fas fa-clock"></i> Ya confirmaste (Falta propietario)
                            </span>
                        <?php else: ?>
                            <a href="mensajes.php?contacto=<?php echo urlencode($contacto_activo_key); ?>&accion=confirmar_inicio&id_vehiculo=<?php echo $id_vehiculo_activo; ?>&interlocutor=<?php echo urlencode($interlocutor_real); ?>" 
                               style="background: #10b981; color: white; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 6px;" 
                               onclick="return confirm('¿Confirmar el inicio de este alquiler?');">
                                <i class="fas fa-file-contract"></i> Confirmar Alquiler
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>

                    <button type="button" onclick="eliminarConversacion(<?php echo $id_vehiculo_activo; ?>, '<?php echo htmlspecialchars($interlocutor_real); ?>')" style="background: #e74c3c; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; margin-left: 8px;">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #10b981; padding: 10px 15px; margin: 10px; border-radius: 6px; font-size: 0.9rem; font-weight: 600;">
                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
                    </div>
                <?php endif; ?>

                <div class="chat-messages" id="chatBox">
                    <?php foreach ($mensajes_chat as $msg): $es_mio = ($msg['remitente'] === $nombre_usuario); ?>
                        <div class="message-bubble <?php echo $es_mio ? 'sent' : 'received'; ?>">
                            <p><?php echo nl2br(htmlspecialchars($msg['mensaje'])); ?></p>
                            <span class="msg-time"><?php echo date('H:i', strtotime($msg['fecha'] ?? 'now')); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form class="chat-input-area" id="formEnviarMensaje">
                    <input type="hidden" name="accion" value="enviar">
                    <input type="hidden" name="tipo_vehiculo" value="<?php echo htmlspecialchars($tipo_vehiculo_activo); ?>">
                    <input type="hidden" name="id_vehiculo" value="<?php echo $id_vehiculo_activo; ?>">
                    <input type="hidden" name="destinatario" value="<?php echo htmlspecialchars($interlocutor_real); ?>">
                    <input type="text" name="mensaje" id="inputMensaje" placeholder="Escribe un mensaje..." autocomplete="off" required>
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            <?php endif; ?>
        </main>
    </div>

    <script>
        const chatBox = document.getElementById('chatBox');
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

        const formEnviar = document.getElementById('formEnviarMensaje');
        if (formEnviar) {
            formEnviar.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('chat_backend.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => { if (data.status === 'success') location.reload(); });
            });
        }

        function eliminarConversacion(idVehiculo, interlocutor) {
            if (!confirm('¿Deseas terminar y eliminar esta conversación?')) return;
            const formData = new URLSearchParams();
            formData.append('accion', 'eliminar_chat');
            formData.append('id_vehiculo', idVehiculo);
            formData.append('destinatario', interlocutor);
            fetch('chat_backend.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData.toString() })
            .then(res => res.json())
            .then(data => { if (data.status === 'success') window.location.href = 'mensajes.php'; });
        }
    </script>
</body>
</html>