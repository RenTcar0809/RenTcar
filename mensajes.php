<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexion.php'; 

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php");
    exit();
}

$id_usuario = $_SESSION['IdUsuario'];
$nombre_usuario = '';
$error_msg = '';
$contactos = []; 

try {
    // Obtener la identidad del usuario actual
    $stmtU = $pdo->prepare('SELECT nombre, empresa, tipo FROM usuario WHERE "IdUsuario" = ?');
    $stmtU->execute([$id_usuario]);
    $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
    
    if ($uData) {
        $nombre_usuario = ($uData['tipo'] == 1) ? $uData['empresa'] : $uData['nombre'];
    }

    // Guardar el nombre en sesión para compatibilidad con chat_backend.php
    $_SESSION['usuario_nombre'] = $nombre_usuario;

    // Consulta para traer los mensajes
    $stmt_chats = $pdo->prepare('
        SELECT 
            m.id_vehiculo, 
            m.remitente, 
            m.destinatario,
            v.id_v,
            v.marca,
            v.modelo,
            v.tipo,
            v.imagen AS imagen_principal_vehiculo,
            (SELECT f.ruta_imagen FROM fotos_vehiculos f WHERE f.id_vehiculo = m.id_vehiculo LIMIT 1) AS imagen_fotos_tabla
        FROM mensajes_chat m
        LEFT JOIN vehiculo v ON m.id_vehiculo = v.id_v
        WHERE m.destinatario = ? OR m.remitente = ?
        ORDER BY m.fecha DESC
    ');
    $stmt_chats->execute([$nombre_usuario, $nombre_usuario]);
    $todos_mensajes = $stmt_chats->fetchAll(PDO::FETCH_ASSOC);

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

    foreach ($todos_mensajes as $msg) {
        $interlocutor = ($msg['remitente'] === $nombre_usuario) ? $msg['destinatario'] : $msg['remitente'];
        
        if (!empty($interlocutor) && $interlocutor !== $nombre_usuario) {
            $id_v_val = !empty($msg['id_vehiculo']) ? $msg['id_vehiculo'] : 0;
            $clave_contacto = $id_v_val . '_' . $interlocutor;
            
            if (!empty($msg['marca']) || !empty($msg['modelo'])) {
                $nombre_vehiculo = trim(($msg['marca'] ?? '') . ' ' . ($msg['modelo'] ?? ''));
            } else {
                $nombre_vehiculo = $id_v_val > 0 ? "Vehículo #" . $id_v_val : "Conversación General";
            }
            
            $url_foto_bruta = !empty($msg['imagen_fotos_tabla']) ? $msg['imagen_fotos_tabla'] : ($msg['imagen_principal_vehiculo'] ?? '');
            $imagen_cloudinary = obtenerUrlImagen($url_foto_bruta);
            
            $tipo_v = strtolower($msg['tipo'] ?? 'auto');
            $tipo_formateado = (strpos($tipo_v, 'moto') !== false) ? 'moto' : 'auto';

            if (!isset($contactos[$clave_contacto])) {
                $contactos[$clave_contacto] = [
                    'interlocutor'  => $interlocutor,
                    'id_vehiculo'   => $id_v_val,
                    'nombre_auto'   => $nombre_vehiculo,
                    'imagen_auto'   => $imagen_cloudinary,
                    'tipo_vehiculo' => $tipo_formateado
                ];
            }
        }
    }

} catch (PDOException $e) {$error_msg = "Error en la base de datos: " . $e->getMessage();
}

$keys_contacto = array_keys($contactos);
$contacto_activo_key =$_GET['contacto'] ?? (!empty($keys_contacto) ?$keys_contacto[0] : '');

$id_vehiculo_activo = 0;
$nombre_vehiculo_activo = '';$imagen_vehiculo_activo = '';
$interlocutor_real = '';$tipo_vehiculo_activo = 'auto';

if (!empty($contacto_activo_key) && isset($contactos[$contacto_activo_key])) {$id_vehiculo_activo = $contactos[$contacto_activo_key]['id_vehiculo'];
    $nombre_vehiculo_activo =$contactos[$contacto_activo_key]['nombre_auto'];$imagen_vehiculo_activo = $contactos[$contacto_activo_key]['imagen_auto'];
    $interlocutor_real =$contactos[$contacto_activo_key]['interlocutor'];$tipo_vehiculo_activo = $contactos[$contacto_activo_key]['tipo_vehiculo'];
}

// Obtener mensajes de la conversación activa
$mensajes_chat = [];
if (!empty($interlocutor_real)) {
    try {
        if ($id_vehiculo_activo > 0) {
            $stmt_h =$pdo->prepare('
                SELECT * FROM mensajes_chat 
                WHERE id_vehiculo = ? 
                AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?))
                ORDER BY fecha ASC
            ');
            $stmt_h->execute([$id_vehiculo_activo, $nombre_usuario,$interlocutor_real, $interlocutor_real,$nombre_usuario]);
        } else {
            $stmt_h =$pdo->prepare('
                SELECT * FROM mensajes_chat 
                WHERE (id_vehiculo IS NULL OR id_vehiculo = 0)
                AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?))
                ORDER BY fecha ASC
            ');
            $stmt_h->execute([$nombre_usuario,$interlocutor_real, $interlocutor_real,$nombre_usuario]);
        }
        $mensajes_chat =$stmt_h->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {$error_msg = "Error al cargar el chat: " . $e->getMessage();
    }
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
        <!-- BARRA LATERAL DE CONVERSACIONES -->
        <aside class="chat-sidebar">
            <div class="sidebar-header">
                <a href="dashboardf.php" class="btn-volver">
                    <i class="fas fa-chevron-left"></i> Volver
                </a>
                <h2>Mensajes</h2>
            </div>
            
            <div class="contact-list">
                <?php if (empty($contactos)): ?>
                    <div class="no-contacts">
                        <i class="fas fa-comment-slash"></i>
                        <p>No tienes conversaciones activas con clientes aún.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($contactos as $key =>$c): ?>
                        <a href="mensajes.php?contacto=<?php echo urlencode($key); ?>" 
                           class="contact-item <?php echo ($contacto_activo_key ===$key) ? 'active' : ''; ?>"
                           data-key="<?php echo htmlspecialchars($key); ?>">
                            <div class="contact-avatar" style="position: relative;">
                                <img src="<?php echo htmlspecialchars($c['imagen_auto']); ?>" alt="Vehículo" onerror="this.src='unnamed.png'">
                                <!-- Indicador de mensaje nuevo -->
                                <span class="badge-nuevo" style="display: none; position: absolute; top: -2px; right: -2px; width: 14px; height: 14px; background: #e74c3c; border-radius: 50%; border: 2px solid #1a1a1a; box-shadow: 0 0 5px rgba(231,76,60,0.8);"></span>
                            </div>
                            <div class="contact-info">
                                <h4><?php echo htmlspecialchars($c['nombre_auto']); ?></h4>
                                <small>Cliente: <?php echo htmlspecialchars($c['interlocutor']); ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- CONTENEDOR PRINCIPAL DEL CHAT -->
        <main class="chat-main">
            <?php if (!empty($error_msg)): ?>
                <div style="padding: 20px; color: #ff6b6b; background: rgba(255,0,0,0.1); border-bottom: 1px solid #333;">
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($contacto_activo_key)): ?>
                <div class="empty-chat-state">
                    <i class="fas fa-comments" style="font-size: 4rem; color: #444; margin-bottom: 15px;"></i>
                    <h3>Selecciona una conversación</h3>
                    <p>Elige un chat de la lista izquierda para ver el vehículo y responder al cliente.</p>
                </div>
            <?php else: ?>
                <div class="chat-header">
                    <div class="contact-avatar">
                        <img src="<?php echo htmlspecialchars($imagen_vehiculo_activo); ?>" alt="Vehículo" onerror="this.src='unnamed.png'">
                    </div>
                    <div style="flex-grow: 1;">
                        <h3><?php echo htmlspecialchars($nombre_vehiculo_activo); ?></h3>
                        <small style="color: #aaa; font-size: 0.85rem;">Conversación con: <strong><?php echo htmlspecialchars($interlocutor_real); ?></strong></small>
                    </div>
                    <button type="button" onclick="eliminarConversacion(<?php echo $id_vehiculo_activo; ?>, '<?php echo htmlspecialchars($interlocutor_real); ?>')" style="background: #e74c3c; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem;" title="Terminar y eliminar chat">
                        <i class="fas fa-trash-alt"></i> Terminar Chat
                    </button>
                </div>

                <div class="chat-messages" id="chatBox">
                    <?php foreach ($mensajes_chat as $msg):$es_mio = ($msg['remitente'] ===$nombre_usuario);
                    ?>
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
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        const formEnviar = document.getElementById('formEnviarMensaje');
        if (formEnviar) {
            formEnviar.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('chat_backend.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload(); 
                    } else {
                        alert(data.message || 'Ocurrió un error al enviar el mensaje.');
                    }
                })
                .catch(err => console.error('Error al enviar:', err));
            });
        }

        function eliminarConversacion(idVehiculo, interlocutor) {
            if (!confirm('¿Estás seguro de que deseas terminar y eliminar esta conversación? Se borrarán todos los mensajes de este chat.')) {
                return;
            }

            const formData = new URLSearchParams();
            formData.append('accion', 'eliminar_chat');
            formData.append('id_vehiculo', idVehiculo);
            formData.append('destinatario', interlocutor);

            fetch('chat_backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.href = 'mensajes.php';
                } else {
                    alert('Error al eliminar: ' + (data.message || 'No se pudo completar la acción.'));
                }
            })
            .catch(err => console.error('Error de red:', err));
        }

        // Revisar si hay nuevos mensajes periódicamente (polling)
        function revisarNotificaciones() {
            fetch('chat_backend.php?accion=verificar_nuevos')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.notificaciones) {
                    data.notificaciones.forEach(notif => {
                        let claveContacto = notif.id_vehiculo + '_' + notif.remitente;
                        let elementoContacto = document.querySelector(`.contact-item[data-key="${claveContacto}"]`);
                        
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

        // Ejecutar cada 5 segundos
        setInterval(revisarNotificaciones, 5000);
        // Primera ejecución inmediata
        revisarNotificaciones();
    </script>
</body>
</html>