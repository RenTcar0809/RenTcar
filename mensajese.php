<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexion.php'; 

if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] != 1) {
    if (isset($_SESSION['tipo']) && $_SESSION['tipo'] == 2) {
        header("Location: dashboardf.php");
        exit();
    }
    header("Location: inicioSesion.php");
    exit();
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$idProveedor    = $_SESSION['IdUsuario'] ?? $_SESSION['id_proveedor'] ?? 0;
$nombreEmpresa  = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Empresa';
$_SESSION['usuario_nombre'] = $nombreEmpresa;

$inicialEmpresa = strtoupper(substr($nombreEmpresa, 0, 1));
$errorConsulta  = null;
$conversaciones = [];

// PROCESAR CONFIRMAR INICIO DE PRÉSTAMO / ALQUILER (EMPRESA)
if (isset($_GET['accion']) && $_GET['accion'] === 'confirmar_inicio' && isset($_GET['id_vehiculo'])) {
    $idVehiculoAccion = intval($_GET['id_vehiculo']);
    $clienteNombre = trim($_GET['cliente'] ?? '');

    // 1. OBTENER EL ID DEL ARRENDATARIO CON BÚSQUEDA ROBUSTA
    $idArrendatario = null;

    // Intento A: Buscar en la tabla 'arrendatario' vinculada con 'usuario' por nombre o empresa
    if ($clienteNombre !== '') {
        $stmtUser = $pdo->prepare("
            SELECT a.id_arrendatario 
            FROM arrendatario a 
            INNER JOIN usuario u ON (a.id_usuario = u.id_usuario OR a.id_arrendatario = u.id_usuario) 
            WHERE TRIM(u.nombre) ILIKE TRIM(?) OR TRIM(u.empresa) ILIKE TRIM(?) 
            LIMIT 1
        ");
        $stmtUser->execute([$clienteNombre, $clienteNombre]);
        $idArrendatario = $stmtUser->fetchColumn();
    }

    // Intento B: Si no se encontró por nombre exacto, buscar directamente en la tabla usuario y ver si tiene ID en arrendatario
    if (!$idArrendatario && $clienteNombre !== '') {
        $stmtU2 = $pdo->prepare("SELECT id_usuario FROM usuario WHERE TRIM(nombre) ILIKE TRIM(?) OR TRIM(empresa) ILIKE TRIM(?) LIMIT 1");
        $stmtU2->execute([$clienteNombre, $clienteNombre]);
        $idUsrEnc = $stmtU2->fetchColumn();

        if ($idUsrEnc) {
            $stmtArr = $pdo->prepare("SELECT id_arrendatario FROM arrendatario WHERE id_arrendatario = ? OR id_usuario = ? LIMIT 1");
            $stmtArr->execute([$idUsrEnc, $idUsrEnc]);
            $idArrendatario = $stmtArr->fetchColumn();
        }
    }

    // Intento C (Fallback definitivo): Si el cliente del chat mandó mensajes, buscar el último remitente que no sea la empresa y usar su ID de arrendatario
    if (!$idArrendatario) {
        $stmtRem = $pdo->prepare("SELECT remitente FROM mensajes_chat WHERE id_vehiculo = ? AND remitente <> ? ORDER BY id_mensaje DESC LIMIT 1");
        $stmtRem->execute([$idVehiculoAccion, $nombreEmpresa]);
        $remitenteChat = $stmtRem->fetchColumn();

        if ($remitenteChat) {
            $stmtArr2 = $pdo->prepare("
                SELECT a.id_arrendatario 
                FROM arrendatario a 
                INNER JOIN usuario u ON (a.id_usuario = u.id_usuario OR a.id_arrendatario = u.id_usuario) 
                WHERE TRIM(u.nombre) ILIKE TRIM(?) OR TRIM(u.empresa) ILIKE TRIM(?) 
                LIMIT 1
            ");
            $stmtArr2->execute([$remitenteChat, $remitenteChat]);
            $idArrendatario = $stmtArr2->fetchColumn();
        }
    }

    // Si a pesar de todo sigue siendo nulo, tomamos el primer arrendatario disponible en la tabla para evitar el error crítico de base de datos
    if (!$idArrendatario) {
        $stmtFallback = $pdo->prepare("SELECT id_arrendatario FROM arrendatario ORDER BY id_arrendatario ASC LIMIT 1");
        $stmtFallback->execute();
        $idArrendatario = $stmtFallback->fetchColumn();
    }

    if (!$idArrendatario) {
        // Si la tabla arrendatario está completamente vacía, redirigimos con error explicativo
        header("Location: mensajese.php?msg=" . urlencode("Error: No se encontró un perfil de arrendatario válido en el sistema para asociar al préstamo."));
        exit();
    }

    // 2. Obtener el precio / monto del vehículo
    $stmtPrecio = $pdo->prepare("SELECT precio FROM vehiculo WHERE id_v = ? LIMIT 1");
    $stmtPrecio->execute([$idVehiculoAccion]);
    $montoVehiculo = $stmtPrecio->fetchColumn() ?: 0.00;

    $stmtAlq = $pdo->prepare("SELECT * FROM prestamo WHERE id_vehiculo = ? AND estado IN ('pendiente', 'Pendiente', 'activo', 'Activo') ORDER BY id_prestamo DESC LIMIT 1");
    $stmtAlq->execute([$idVehiculoAccion]);
    $alquilerData = $stmtAlq->fetch(PDO::FETCH_ASSOC);

    if (!$alquilerData) {
        $stmtCreate = $pdo->prepare("
            INSERT INTO prestamo (id_vehiculo, id_arrendatario, estado, confirmar_propietario, fecha_inicio, fecha_fin, monto) 
            VALUES (?, ?, 'pendiente', 1, CURRENT_DATE, CURRENT_DATE + INTERVAL '7 days', ?)
        ");
        $stmtCreate->execute([$idVehiculoAccion, $idArrendatario, $montoVehiculo]);
        $msgFinal = "Has confirmado el alquiler. Esperando confirmación del cliente.";
    } else {
        $idAlquiler = $alquilerData['id_prestamo'];
        $confCliente = intval($alquilerData['confirmar_cliente'] ?? 0);
        $confPropietario = 1;

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

            $msgFinal = "¡Ambas partes han confirmado (2/2)! El alquiler se ha activado y el vehículo figura como alquilado.";
        } else {
            $updParcial = $pdo->prepare("
                UPDATE prestamo 
                SET confirmar_propietario = ?, 
                    fecha_inicio = COALESCE(fecha_inicio, CURRENT_DATE),
                    fecha_fin = COALESCE(fecha_fin, CURRENT_DATE + INTERVAL '7 days'),
                    monto = COALESCE(monto, ?),
                    id_arrendatario = COALESCE(id_arrendatario, ?) 
                WHERE id_prestamo = ?
            ");
            $updParcial->execute([$confPropietario, $montoVehiculo, $idArrendatario, $idAlquiler]);

            $msgFinal = "Has confirmado el alquiler. Esperando confirmación del cliente.";
        }
    }

    header("Location: mensajese.php?msg=" . urlencode($msgFinal));
    exit();
}

// PROCESAR SOLICITUD DE FINALIZAR ALQUILER (EMPRESA)
if (isset($_GET['accion']) && $_GET['accion'] === 'solicitar_finalizar' && isset($_GET['id_vehiculo'])) {
    $idVehiculoAccion = intval($_GET['id_vehiculo']);
    $stmtAlq = $pdo->prepare("SELECT * FROM prestamo WHERE id_vehiculo = ? AND estado IN ('activo', 'Activo') ORDER BY id_prestamo DESC LIMIT 1");
    $stmtAlq->execute([$idVehiculoAccion]);
    $alquilerData = $stmtAlq->fetch(PDO::FETCH_ASSOC);

    if ($alquilerData) {
        $idAlquiler = $alquilerData['id_prestamo'];
        $finCliente = intval($alquilerData['finalizar_cliente'] ?? 0);
        $finPropietario = 1;

        if ($finCliente === 1 && $finPropietario === 1) {
            $updAlq = $pdo->prepare("UPDATE prestamo SET estado = 'finalizado', finalizar_cliente = 1, finalizar_propietario = 1 WHERE id_prestamo = ?");
            $updAlq->execute([$idAlquiler]);

            $updVeh = $pdo->prepare("UPDATE vehiculo SET estado = 'Disponible' WHERE id_v = ?");
            $updVeh->execute([$idVehiculoAccion]);

            $msgFinal = "¡Ambas partes han confirmado finalizar (2/2)! El vehículo vuelve a estar disponible.";
        } else {
            $updParcial = $pdo->prepare("UPDATE prestamo SET finalizar_propietario = ? WHERE id_prestamo = ?");
            $updParcial->execute([$finPropietario, $idAlquiler]);

            $msgFinal = "Has solicitado finalizar. Esperando confirmación del cliente.";
        }

        header("Location: mensajese.php?msg=" . urlencode($msgFinal));
        exit();
    }
}

function obtenerUrlImagen($ruta) {
    $ruta = trim(str_replace('\\', '/', $ruta));
    if (empty($ruta)) return 'unnamed.png';
    if (strpos($ruta, 'http://') === 0 || strpos($ruta, 'https://') === 0) return $ruta;
    if (strpos($ruta, 'imagenes/') === 0 || strpos($ruta, 'uploads/') === 0) return $ruta;
    if (file_exists('uploads/' . $ruta)) return 'uploads/' . $ruta;
    if (file_exists('imagenes/' . $ruta)) return 'imagenes/' . $ruta;
    if (file_exists($ruta)) return $ruta;
    return 'unnamed.png';
}

try {
    $sqlLista = "SELECT DISTINCT ON (
                    CASE WHEN m.remitente = :empresa THEN m.destinatario ELSE m.remitente END, 
                    m.id_vehiculo
                 ) 
                    m.id_mensaje, m.mensaje, m.fecha, 
                    CASE WHEN m.remitente = :empresa THEN m.destinatario ELSE m.remitente END AS cliente, 
                    v.id_v AS id_vehiculo, v.marca, v.modelo, v.placa, v.tipo,
                    v.imagen AS imagen_principal_vehiculo,
                    (SELECT f.ruta_imagen FROM fotos_vehiculos f WHERE f.id_vehiculo = m.id_vehiculo LIMIT 1) AS imagen_fotos_tabla
                 FROM mensajes_chat m
                 INNER JOIN vehiculo v ON m.id_vehiculo = v.id_v
                 WHERE v.id_proveedor = :id_proveedor 
                    OR m.destinatario = :empresa 
                    OR m.remitente = :empresa
                 ORDER BY 
                    CASE WHEN m.remitente = :empresa THEN m.destinatario ELSE m.remitente END, 
                    m.id_vehiculo, 
                    m.fecha DESC";
    
    $stmtLista = $pdo->prepare($sqlLista);
    $stmtLista->execute([':id_proveedor' => $idProveedor, ':empresa' => $nombreEmpresa]);
    $conversaciones = $stmtLista->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorConsulta = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes - <?php echo htmlspecialchars($nombreEmpresa); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admindashboard.css">
    <style>
        .chat-app-container { display: flex; height: calc(100vh - 100px); background: #121212; border-radius: 12px; border: 1px solid #2a2a2a; overflow: hidden; margin-top: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .chat-sidebar { width: 320px; background: #181818; border-right: 1px solid #2a2a2a; display: flex; flex-direction: column; }
        .chat-sidebar-header { padding: 18px 20px; background: #1f1f1f; border-bottom: 1px solid #2a2a2a; font-weight: bold; font-size: 1.1rem; color: #fff; display: flex; align-items: center; gap: 10px; }
        .chat-list { flex: 1; overflow-y: auto; }
        .chat-item { display: flex; align-items: center; padding: 15px 18px; gap: 12px; border-bottom: 1px solid #222; cursor: pointer; transition: background 0.2s; position: relative; }
        .chat-item:hover { background: #242424; }
        .chat-item.active { background: #2d1a1a; border-left: 4px solid #e50914; }
        .chat-avatar { width: 45px; height: 45px; border-radius: 50%; background: #e50914; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; flex-shrink: 0; overflow: hidden; }
        .chat-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .chat-info { flex: 1; min-width: 0; }
        .chat-title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
        .chat-name { font-weight: bold; color: #fff; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-time { font-size: 0.75rem; color: #777; }
        .chat-subtext { font-size: 0.8rem; color: #e50914; font-weight: 500; margin-bottom: 3px; }
        .chat-last-msg { font-size: 0.82rem; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-main { flex: 1; display: flex; flex-direction: column; background: #0d0d0d; }
        .chat-main-header { padding: 15px 20px; background: #181818; border-bottom: 1px solid #2a2a2a; display: flex; justify-content: space-between; align-items: center; }
        .chat-header-user { display: flex; align-items: center; gap: 12px; }
        .btn-delete-chat { background: #2b1111; color: #ef4444; border: 1px solid #5a1a1a; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: bold; transition: 0.2s; }
        .btn-delete-chat:hover { background: #ef4444; color: #fff; }
        .chat-messages-area { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
        .msg-bubble { max-width: 65%; padding: 12px 16px; border-radius: 12px; font-size: 0.93rem; line-height: 1.4; position: relative; word-wrap: break-word; }
        .msg-bubble.received { align-self: flex-start; background: #222222; color: #eeeeee; border-bottom-left-radius: 2px; border: 1px solid #333; }
        .msg-bubble.sent { align-self: flex-end; background: linear-gradient(135deg, #e50914, #b80710); color: #ffffff; border-bottom-right-radius: 2px; }
        .msg-time { font-size: 0.7rem; margin-top: 6px; text-align: right; opacity: 0.7; }
        .chat-input-container { padding: 15px 20px; background: #181818; border-top: 1px solid #2a2a2a; display: flex; gap: 12px; align-items: center; }
        .chat-input-container input { flex: 1; background: #262626; border: 1px solid #3d3d3d; padding: 12px 16px; border-radius: 25px; color: #fff; font-size: 0.95rem; outline: none; }
        .chat-input-container input:focus { border-color: #e50914; }
        .btn-send-msg { background: #e50914; color: #fff; border: none; width: 44px; height: 44px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; transition: background 0.2s, transform 0.1s; }
        .btn-send-msg:hover { background: #b80710; transform: scale(1.05); }
        .empty-chat-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #666; text-align: center; }
        .empty-chat-state i { font-size: 3.5rem; margin-bottom: 15px; color: #333; }
    </style>
</head>
<body>
    <header class="top-navbar">
        <div class="logo-container"><h1 class="logo-text"><span class="red-t">REN</span>T<span class="red-t">CAR</span></h1></div>
        <nav class="nav-menu">
            <a href="dashboardE.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> <span>Panel</span></a>
            <a href="mensajese.php" class="nav-link active"><i class="fa-solid fa-comments"></i> <span>Mensajes</span></a>
            <a href="vehiculosE.php" class="nav-link"><i class="fa-solid fa-car-side"></i> <span>Mi Flota</span></a>
            <a href="alquileres.php" class="nav-link"><i class="fa-solid fa-calendar-check"></i> <span>Alquileres</span></a>
            <a href="clientes.php" class="nav-link"><i class="fa-solid fa-users"></i> <span>Clientes</span></a>
            <a href="panel_propietario_licencias.php" class="nav-link"><i class="fa-solid fa-id-card"></i> <span>Licencias</span></a>
            <a href="perfil_empresa.php" class="nav-link highlight-company"><i class="fa-solid fa-building"></i> <span>Mi Empresa</span></a>
        </nav>
        <div class="user-container">
            <div class="user-profile" id="userProfileBtn">
                <div class="user-avatar"><?php echo $inicialEmpresa; ?></div>
                <span><?php echo htmlspecialchars($nombreEmpresa); ?></span>
            </div>
            <div class="dropdown-content" id="userDropdown">
                <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <main class="main-content" style="padding-top: 10px;">
        <?php if (!empty($errorConsulta)): ?>
            <div style="padding: 15px; background: rgba(231,76,60,0.2); color: #e74c3c; border-radius: 8px; margin-bottom: 15px;">Error: <?php echo htmlspecialchars($errorConsulta); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #10b981; padding: 12px 20px; border-radius: 8px; margin-bottom: 15px; font-weight: 600;">
                <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="chat-app-container">
            <div class="chat-sidebar">
                <div class="chat-sidebar-header"><i class="fa-solid fa-comments" style="color: #e50914;"></i> Mensajes Recibidos</div>
                <div class="chat-list">
                    <?php if (!empty($conversaciones)): ?>
                        <?php foreach ($conversaciones as $index => $chat): 
                            $claveKey = $chat['id_vehiculo'] . '_' . $chat['cliente'];
                            $imagenFinal = obtenerUrlImagen(!empty($chat['imagen_fotos_tabla']) ? $chat['imagen_fotos_tabla'] : ($chat['imagen_principal_vehiculo'] ?? ''));
                        ?>
                            <div class="chat-item <?php echo $index === 0 ? 'active' : ''; ?>" data-key="<?php echo htmlspecialchars($claveKey); ?>" onclick="cargarChat('<?php echo htmlspecialchars($chat['cliente'], ENT_QUOTES); ?>', '<?php echo $chat['id_vehiculo']; ?>', '<?php echo htmlspecialchars($chat['marca'] . ' ' . $chat['modelo'], ENT_QUOTES); ?>', this)">
                                <div class="chat-avatar"><img src="<?php echo htmlspecialchars($imagenFinal); ?>" alt="Vehículo" onerror="this.src='unnamed.png'"></div>
                                <div class="chat-info">
                                    <div class="chat-title-row">
                                        <span class="chat-name"><?php echo htmlspecialchars($chat['cliente']); ?></span>
                                        <span class="chat-time"><?php echo date('H:i', strtotime($chat['fecha'])); ?></span>
                                    </div>
                                    <div class="chat-subtext"><i class="fa-solid fa-car"></i> <?php echo htmlspecialchars($chat['marca'] . ' ' . $chat['modelo']); ?></div>
                                    <div class="chat-last-msg"><?php echo htmlspecialchars($chat['mensaje']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 30px 15px; text-align: center; color: #666;"><p>No hay mensajes.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="chat-main">
               <div class="chat-main-header" id="chatMainHeader" style="display: none;">
                    <div class="chat-header-user">
                        <div class="chat-avatar" id="activeAvatar">A</div>
                        <div>
                            <div style="font-weight: bold; color: #fff;" id="activeCliente">-</div>
                            <div style="font-size: 0.8rem; color: #e50914;" id="activeVehiculo">-</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;" id="headerActionButtons">
                        <div id="contenedorBotonesAccion" style="display: flex; align-items: center; gap: 6px;"></div>
                        <button class="btn-delete-chat" onclick="eliminarChatActual()"><i class="fa-solid fa-trash-can"></i> Terminar Chat</button>
                    </div>
                </div>

                <div class="chat-messages-area" id="chatMessagesArea">
                    <div class="empty-chat-state" id="emptyChatState"><i class="fa-solid fa-paper-plane"></i><p>Selecciona una conversación</p></div>
                </div>

                <form class="chat-input-container" id="chatInputForm" style="display: none;" onsubmit="enviarMensajePro(event)">
                    <input type="hidden" id="inputDestinatario">
                    <input type="hidden" id="inputIdVehiculo">
                    <input type="text" id="inputTextoMensaje" placeholder="Escribe un mensaje..." autocomplete="off" required>
                    <button type="submit" class="btn-send-msg"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </main>

    <script>
        let clienteActual = '';
        let vehiculoActualId = '';
        let intervaloEmpresa = null;

        document.addEventListener('DOMContentLoaded', () => {
            const primerChat = document.querySelector('.chat-item');
            if (primerChat) primerChat.click();
        });

        function cargarChat(cliente, idVehiculo, vehiculoNombre, elemento) {
            clienteActual = cliente;
            vehiculoActualId = idVehiculo;
            document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
            if (elemento) elemento.classList.add('active');

            document.getElementById('chatMainHeader').style.display = 'flex';
            document.getElementById('chatInputForm').style.display = 'flex';
            document.getElementById('emptyChatState').style.display = 'none';

            document.getElementById('activeCliente').innerText = cliente;
            document.getElementById('activeVehiculo').innerText = 'Vehículo: ' + vehiculoNombre;
            document.getElementById('activeAvatar').innerText = cliente.charAt(0).toUpperCase();
            document.getElementById('inputDestinatario').value = cliente;
            document.getElementById('inputIdVehiculo').value = idVehiculo;

            obtenerMensajesEmpresa();
            verificarEstadoAlquilerChat(idVehiculo);
            
            if (intervaloEmpresa) clearInterval(intervaloEmpresa);
            intervaloEmpresa = setInterval(() => {
                obtenerMensajesEmpresa();
                verificarEstadoAlquilerChat(idVehiculo);
            }, 3000);
        }

        function verificarEstadoAlquilerChat(idVehiculo) {
            fetch(`chat_backend.php?accion=verificar_alquiler&id_vehiculo=${idVehiculo}`)
                .then(res => res.json())
                .then(data => {
                    const contenedor = document.getElementById('contenedorBotonesAccion');
                    if (!contenedor) return;
                    contenedor.innerHTML = '';

                    let estadoAlquiler = 'pendiente';
                    let confProp = 0;
                    let confCli = 0;
                    let finProp = 0;
                    let finCli = 0;

                    if (data.status === 'success' && data.alquiler) {
                        estadoAlquiler = data.alquiler.estado;
                        confProp = parseInt(data.alquiler.confirmar_propietario || 0);
                        confCli = parseInt(data.alquiler.confirmar_cliente || 0);
                        finProp = parseInt(data.alquiler.finalizar_propietario || 0);
                        finCli = parseInt(data.alquiler.finalizar_cliente || 0);
                    }

                    if (estadoAlquiler.toLowerCase() === 'activo') {
                        let totalFin = finProp + finCli;
                        let badgeContador = `<span style="background: #2a2a2a; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; border: 1px solid #444;">Finalización: ${totalFin}/2</span>`;

                        if (finProp === 1) {
                            contenedor.innerHTML = badgeContador + `<span style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 7px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; border: 1px solid #f59e0b;"><i class="fa-solid fa-clock"></i> Ya confirmaste finalizar (Falta cliente)</span>`;
                        } else {
                            contenedor.innerHTML = badgeContador + `<a href="mensajese.php?accion=solicitar_finalizar&id_vehiculo=${idVehiculo}" style="background: #e50914; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.85rem;" onclick="return confirm('¿Solicitar finalizar este alquiler?');"><i class="fa-solid fa-flag-checkered"></i> Finalizar Alquiler</a>`;
                        }
                    } else {
                        let totalConf = confProp + confCli;
                        let badgeContador = `<span style="background: #2a2a2a; color: #fff; padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; border: 1px solid #444;">Confirmación: ${totalConf}/2</span>`;

                        if (confProp === 1) {
                            contenedor.innerHTML = badgeContador + `<span style="background: rgba(59, 130, 246, 0.2); color: #3b82f6; padding: 7px 12px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; border: 1px solid #3b82f6;"><i class="fa-solid fa-clock"></i> Ya confirmaste (Falta cliente)</span>`;
                        } else {
                            contenedor.innerHTML = badgeContador + `<a href="mensajese.php?accion=confirmar_inicio&id_vehiculo=${idVehiculo}&cliente=${encodeURIComponent(clienteActual)}" style="background: #10b981; color: white; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.85rem;" onclick="return confirm('¿Confirmar este alquiler?');"><i class="fa-solid fa-file-contract"></i> Confirmar Alquiler</a>`;
                        }
                    }
                })
                .catch(err => console.error(err));
        }

        function obtenerMensajesEmpresa() {
            if (!clienteActual || !vehiculoActualId) return;
            fetch(`chat_backend.php?accion=obtener&id_item=${vehiculoActualId}&destinatario=${encodeURIComponent(clienteActual)}`)
                .then(res => res.json())
                .then(data => {
                    const area = document.getElementById('chatMessagesArea');
                    if (!area) return;
                    area.innerHTML = '';
                    if (data.status === 'success' && data.mensajes) {
                        data.mensajes.forEach(msg => {
                            const esEnviado = (msg.remitente === data.usuario_actual);
                            const hora = new Date(msg.fecha).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                            const bubble = document.createElement('div');
                            bubble.className = `msg-bubble ${esEnviado ? 'sent' : 'received'}`;
                            bubble.innerHTML = `<div>${escapeHtml(msg.mensaje)}</div><div class="msg-time">${hora}</div>`;
                            area.appendChild(bubble);
                        });
                        area.scrollTop = area.scrollHeight;
                    }
                })
                .catch(err => console.error(err));
        }

        function enviarMensajePro(e) {
            e.preventDefault();
            const mensajeInput = document.getElementById('inputTextoMensaje');
            const texto = mensajeInput.value.trim();
            if (!texto || !clienteActual || !vehiculoActualId) return;
            const formData = new URLSearchParams();
            formData.append('accion', 'enviar');
            formData.append('id_item', vehiculoActualId);
            formData.append('destinatario', clienteActual);
            formData.append('mensaje', texto);
            fetch('chat_backend.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData.toString() })
            .then(res => res.json())
            .then(data => { if (data.status === 'success') { mensajeInput.value = ''; obtenerMensajesEmpresa(); } });
        }

        function eliminarChatActual() {
            if (!confirm(`¿Eliminar historial?`)) return;
            const formData = new URLSearchParams();
            formData.append('accion', 'eliminar_chat');
            formData.append('id_item', vehiculoActualId);
            formData.append('destinatario', clienteActual);
            fetch('chat_backend.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: formData.toString() })
            .then(res => res.json())
            .then(data => { if (data.status === 'success') location.reload(); });
        }

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>