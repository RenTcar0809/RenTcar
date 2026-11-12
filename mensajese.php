<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexion.php'; 

// 1. SEGURIDAD: Verificar sesión y que sea tipo Empresa (tipo = 1)
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] != 1) {
    if (isset($_SESSION['tipo']) && $_SESSION['tipo'] == 2) {
        header("Location: dashboardf.php");
        exit();
    }
    header("Location: inicioSesion.php");
    exit();
}

// Prevenir caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$idProveedor    = $_SESSION['IdUsuario'] ?? $_SESSION['id_proveedor'] ?? 0;
$nombreEmpresa  = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Empresa';

// Sincronizar con la variable que usa chat_backend.php
$_SESSION['usuario_nombre'] = $nombreEmpresa;

$inicialEmpresa = strtoupper(substr($nombreEmpresa, 0, 1));
$errorConsulta  = null;
$conversaciones = [];

function obtenerUrlImagen($ruta) {
    $ruta = trim(str_replace('\\', '/', $ruta));
    if (empty($ruta)) {
        return 'unnamed.png';
    }
    if (strpos($ruta, 'http://') === 0 || strpos($ruta, 'https://') === 0) {
        return $ruta;
    }
    if (strpos($ruta, 'imagenes/') === 0 || strpos($ruta, 'uploads/') === 0) {
        return $ruta;
    }
    if (file_exists('uploads/' . $ruta)) {
        return 'uploads/' . $ruta;
    }
    if (file_exists('imagenes/' . $ruta)) {
        return 'imagenes/' . $ruta;
    }
    if (file_exists($ruta)) {
        return $ruta;
    }
    return 'unnamed.png';
}

// 2. CONSULTAR LISTA DE CONVERSACIONES (Búsqueda robusta por ID de proveedor o nombre)
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
    $stmtLista->execute([
        ':id_proveedor' => $idProveedor, 
        ':empresa'      => $nombreEmpresa
    ]);
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
        /* LAYOUT DE CHAT TIPO PRO */
        .chat-app-container {
            display: flex;
            height: calc(100vh - 100px);
            background: #121212;
            border-radius: 12px;
            border: 1px solid #2a2a2a;
            overflow: hidden;
            margin-top: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        /* SIDEBAR IZQUIERDA: LISTA DE CHATS */
        .chat-sidebar {
            width: 320px;
            background: #181818;
            border-right: 1px solid #2a2a2a;
            display: flex;
            flex-direction: column;
        }

        .chat-sidebar-header {
            padding: 18px 20px;
            background: #1f1f1f;
            border-bottom: 1px solid #2a2a2a;
            font-weight: bold;
            font-size: 1.1rem;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chat-list {
            flex: 1;
            overflow-y: auto;
        }

        .chat-item {
            display: flex;
            align-items: center;
            padding: 15px 18px;
            gap: 12px;
            border-bottom: 1px solid #222;
            cursor: pointer;
            transition: background 0.2s;
            position: relative;
        }

        .chat-item:hover { background: #242424; }
        .chat-item.active { background: #2d1a1a; border-left: 4px solid #e50914; }

        .chat-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #e50914;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            flex-shrink: 0;
            overflow: hidden;
        }

        .chat-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .chat-info {
            flex: 1;
            min-width: 0;
        }

        .chat-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .chat-name {
            font-weight: bold;
            color: #fff;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-time {
            font-size: 0.75rem;
            color: #777;
        }

        .chat-subtext {
            font-size: 0.8rem;
            color: #e50914;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .chat-last-msg {
            font-size: 0.82rem;
            color: #888;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ÁREA PRINCIPAL DERECHA: CONVERSACIÓN */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #0d0d0d;
        }

        .chat-main-header {
            padding: 15px 20px;
            background: #181818;
            border-bottom: 1px solid #2a2a2a;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-delete-chat {
            background: #2b1111;
            color: #ef4444;
            border: 1px solid #5a1a1a;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: bold;
            transition: 0.2s;
        }
        .btn-delete-chat:hover { background: #ef4444; color: #fff; }

        /* MENSAJES */
        .chat-messages-area {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .msg-bubble {
            max-width: 65%;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.93rem;
            line-height: 1.4;
            position: relative;
            word-wrap: break-word;
        }

        /* Recibido (Cliente) */
        .msg-bubble.received {
            align-self: flex-start;
            background: #222222;
            color: #eeeeee;
            border-bottom-left-radius: 2px;
            border: 1px solid #333;
        }

        /* Enviado (Empresa) */
        .msg-bubble.sent {
            align-self: flex-end;
            background: linear-gradient(135deg, #e50914, #b80710);
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }

        .msg-time {
            font-size: 0.7rem;
            margin-top: 6px;
            text-align: right;
            opacity: 0.7;
        }

        /* FOOTER: INPUT DE MENSAJE */
        .chat-input-container {
            padding: 15px 20px;
            background: #181818;
            border-top: 1px solid #2a2a2a;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .chat-input-container input {
            flex: 1;
            background: #262626;
            border: 1px solid #3d3d3d;
            padding: 12px 16px;
            border-radius: 25px;
            color: #fff;
            font-size: 0.95rem;
            outline: none;
        }

        .chat-input-container input:focus {
            border-color: #e50914;
        }

        .btn-send-msg {
            background: #e50914;
            color: #fff;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-send-msg:hover {
            background: #b80710;
            transform: scale(1.05);
        }

        .empty-chat-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #666;
            text-align: center;
        }

        .empty-chat-state i { font-size: 3.5rem; margin-bottom: 15px; color: #333; }
    </style>
</head>
<body>

    <!-- --- BARRA DE NAVEGACIÓN SUPERIOR --- -->
    <header class="top-navbar">
        <div class="logo-container">
            <h1 class="logo-text"><span class="red-t">REN</span>T<span class="red-t">CAR</span></h1>
        </div>

        <nav class="nav-menu">
            <a href="dashboardE.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Panel</span>
            </a>
            <a href="mensajese.php" class="nav-link active">
                <i class="fa-solid fa-comments"></i>
                <span>Mensajes</span>
            </a>
            <a href="vehiculosE.php" class="nav-link">
                <i class="fa-solid fa-car-side"></i>
                <span>Mi Flota</span>
            </a>
            <a href="alquileres.php" class="nav-link">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Alquileres</span>
            </a>
            <a href="clientes.php" class="nav-link">
                <i class="fa-solid fa-users"></i>
                <span>Clientes</span>
            </a>
            <a href="perfil_empresa.php" class="nav-link highlight-company">
                <i class="fa-solid fa-building"></i>
                <span>Mi Empresa</span>
            </a>
        </nav>

        <div class="user-container">
            <div class="user-profile" id="userProfileBtn">
                <div class="user-avatar"><?php echo $inicialEmpresa; ?></div>
                <span><?php echo htmlspecialchars($nombreEmpresa); ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem; color: #8e8e93;"></i>
            </div>
            
            <div class="dropdown-content" id="userDropdown">
                <a href="perfil_empresa.php"><i class="fa-solid fa-user-gear"></i> Configuración</a>
                <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <!-- --- CONTENIDO PRINCIPAL --- -->
    <main class="main-content" style="padding-top: 10px;">
        
        <?php if (!empty($errorConsulta)): ?>
            <div style="padding: 15px; background: rgba(231,76,60,0.2); color: #e74c3c; border-radius: 8px; margin-bottom: 15px;">
                Error en la consulta: <?php echo htmlspecialchars($errorConsulta); ?>
            </div>
        <?php endif; ?>

        <div class="chat-app-container">
            
            <!-- LISTA IZQUIERDA DE CONVERSACIONES -->
            <div class="chat-sidebar">
                <div class="chat-sidebar-header">
                    <i class="fa-solid fa-comments" style="color: #e50914;"></i> Mensajes Recibidos
                </div>

                <div class="chat-list">
                    <?php if (!empty($conversaciones)): ?>
                        <?php foreach ($conversaciones as $index => $chat): 
                            $claveKey = $chat['id_vehiculo'] . '_' . $chat['cliente'];
                            $url_foto_bruta = !empty($chat['imagen_fotos_tabla']) ? $chat['imagen_fotos_tabla'] : ($chat['imagen_principal_vehiculo'] ?? '');
                            $imagenFinal = obtenerUrlImagen($url_foto_bruta);
                        ?>
                            <div class="chat-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 data-key="<?php echo htmlspecialchars($claveKey); ?>"
                                 onclick="cargarChat('<?php echo htmlspecialchars($chat['cliente'], ENT_QUOTES); ?>', '<?php echo $chat['id_vehiculo']; ?>', '<?php echo htmlspecialchars($chat['marca'] . ' ' . $chat['modelo'], ENT_QUOTES); ?>', this)">
                                
                                <div class="chat-avatar" style="position: relative;">
                                    <img src="<?php echo htmlspecialchars($imagenFinal); ?>" alt="Vehículo" onerror="this.src='unnamed.png'">
                                    <span class="badge-nuevo" style="display: none; position: absolute; top: -2px; right: -2px; width: 12px; height: 12px; background: #e50914; border-radius: 50%; border: 2px solid #181818;"></span>
                                </div>

                                <div class="chat-info">
                                    <div class="chat-title-row">
                                        <span class="chat-name"><?php echo htmlspecialchars($chat['cliente']); ?></span>
                                        <span class="chat-time"><?php echo date('H:i', strtotime($chat['fecha'])); ?></span>
                                    </div>
                                    <div class="chat-subtext">
                                        <i class="fa-solid fa-car"></i> <?php echo htmlspecialchars($chat['marca'] . ' ' . $chat['modelo']); ?>
                                    </div>
                                    <div class="chat-last-msg">
                                        <?php echo htmlspecialchars($chat['mensaje']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 30px 15px; text-align: center; color: #666;">
                            <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <p>No tienes mensajes pendientes.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ÁREA DERECHA DE CHAT ACTIVO -->
            <div class="chat-main">
                
                <div class="chat-main-header" id="chatMainHeader" style="display: none;">
                    <div class="chat-header-user">
                        <div class="chat-avatar" id="activeAvatar">A</div>
                        <div>
                            <div style="font-weight: bold; color: #fff;" id="activeCliente">-</div>
                            <div style="font-size: 0.8rem; color: #e50914;" id="activeVehiculo">-</div>
                        </div>
                    </div>
                    <button class="btn-delete-chat" onclick="eliminarChatActual()"><i class="fa-solid fa-trash-can"></i> Terminar Chat</button>
                </div>

                <!-- Lista de Burbujas de Mensaje -->
                <div class="chat-messages-area" id="chatMessagesArea">
                    <div class="empty-chat-state" id="emptyChatState">
                        <i class="fa-solid fa-paper-plane"></i>
                        <p>Selecciona una conversación para interactuar con el cliente</p>
                    </div>
                </div>

                <!-- Campo de Entrada para Responder -->
                <form class="chat-input-container" id="chatInputForm" style="display: none;" onsubmit="enviarMensajePro(event)">
                    <input type="hidden" id="inputDestinatario">
                    <input type="hidden" id="inputIdVehiculo">
                    <input type="text" id="inputTextoMensaje" placeholder="Escribe un mensaje..." autocomplete="off" required>
                    <button type="submit" class="btn-send-msg"><i class="fa-solid fa-paper-plane"></i></button>
                </form>

            </div>

        </div>

    </main>

    <!-- JS -->
    <script>
        // Dropdown del Perfil
        const btnProfile = document.getElementById('userProfileBtn');
        const dropdownProfile = document.getElementById('userDropdown');

        if (btnProfile && dropdownProfile) {
            btnProfile.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownProfile.classList.toggle('show');
            });

            window.addEventListener('click', () => {
                if (dropdownProfile.classList.contains('show')) {
                    dropdownProfile.classList.remove('show');
                }
            });
        }

        let clienteActual = '';
        let vehiculoActualId = '';
        let intervaloEmpresa = null;

        // Cargar primera conversación al iniciar si existe
        document.addEventListener('DOMContentLoaded', () => {
            const primerChat = document.querySelector('.chat-item');
            if (primerChat) {
                primerChat.click();
            }
        });

        function cargarChat(cliente, idVehiculo, vehiculoNombre, elemento) {
            clienteActual = cliente;
            vehiculoActualId = idVehiculo;

            // Marcar ítem activo en la lista y ocultar su notificación si la tenía
            document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
            if (elemento) {
                elemento.classList.add('active');
                let badge = elemento.querySelector('.badge-nuevo');
                if (badge) badge.style.display = 'none';
            }

            // Actualizar Cabecera
            document.getElementById('chatMainHeader').style.display = 'flex';
            document.getElementById('chatInputForm').style.display = 'flex';
            document.getElementById('emptyChatState').style.display = 'none';

            document.getElementById('activeCliente').innerText = cliente;
            document.getElementById('activeVehiculo').innerText = 'Vehículo: ' + vehiculoNombre;
            document.getElementById('activeAvatar').innerText = cliente.charAt(0).toUpperCase();

            document.getElementById('inputDestinatario').value = cliente;
            document.getElementById('inputIdVehiculo').value = idVehiculo;

            // Iniciar carga de mensajes y loop de actualización usando chat_backend.php
            obtenerMensajesEmpresa();
            
            if (intervaloEmpresa) clearInterval(intervaloEmpresa);
            intervaloEmpresa = setInterval(obtenerMensajesEmpresa, 3000); // Actualiza cada 3 segundos
        }

        function obtenerMensajesEmpresa() {
            if (!clienteActual || !vehiculoActualId) return;

            fetch(`chat_backend.php?accion=obtener&id_item=${vehiculoActualId}&destinatario=${encodeURIComponent(clienteActual)}`)
                .then(res => res.json())
                .then(data => {
                    const area = document.getElementById('chatMessagesArea');
                    if (!area) return;
                    
                    area.innerHTML = '';

                    if (data.status === 'success' && data.mensajes && data.mensajes.length > 0) {
                        data.mensajes.forEach(msg => {
                            const esEnviado = (msg.remitente === data.usuario_actual);
                            const hora = new Date(msg.fecha).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                            const bubble = document.createElement('div');
                            bubble.className = `msg-bubble ${esEnviado ? 'sent' : 'received'}`;
                            bubble.innerHTML = `
                                <div>${escapeHtml(msg.mensaje)}</div>
                                <div class="msg-time">${hora}</div>
                            `;
                            area.appendChild(bubble);
                        });
                        area.scrollTop = area.scrollHeight;
                    }
                })
                .catch(err => console.error('Error al cargar chat:', err));
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

            fetch('chat_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    mensajeInput.value = '';
                    obtenerMensajesEmpresa(); // Recargar chat de inmediato
                } else {
                    alert('Error al enviar: ' + (data.message || 'Intente nuevamente'));
                }
            })
            .catch(err => console.error(err));
        }

        function eliminarChatActual() {
            if (!confirm(`¿Estás seguro de eliminar el historial de chat con ${clienteActual}?`)) return;

            const formData = new URLSearchParams();
            formData.append('accion', 'eliminar_chat');
            formData.append('id_item', vehiculoActualId);
            formData.append('destinatario', clienteActual);

            fetch('chat_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert('Error al eliminar chat: ' + (data.message || ''));
                }
            })
            .catch(err => console.error(err));
        }

        function revisarNotificaciones() {
            fetch('chat_backend.php?accion=verificar_nuevos')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.notificaciones) {
                    data.notificaciones.forEach(notif => {
                        let claveContacto = notif.id_vehiculo + '_' + notif.remitente;
                        let elementoContacto = document.querySelector(`.chat-item[data-key="${claveContacto}"]`);
                        
                        if (elementoContacto && !elementoContacto.classList.contains('active')) {
                            let badge = elementoContacto.querySelector('.badge-nuevo');
                            if (badge) {
                                badge.style.display = 'block';
                            }
                        }
                    });
                }
            })
            .catch(err => console.error('Error al revisar notificaciones:', err));
        }

        setInterval(revisarNotificaciones, 5000);

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>