<?php
session_start();
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
    // Obtener la identidad del proveedor actual respetando el case de PostgreSQL
    $stmtU = $pdo->prepare('SELECT nombre, empresa, tipo FROM usuario WHERE "IdUsuario" = ?');
    $stmtU->execute([$id_usuario]);
    $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
    
    if ($uData) {
        $nombre_usuario = ($uData['tipo'] == 1) ? $uData['empresa'] : $uData['nombre'];
    }

  $stmt_chats = $pdo->prepare('
        SELECT DISTINCT 
            m.id_vehiculo, 
            m.remitente, 
            m.destinatario,
            v.marca,
            v.modelo,
            (SELECT f.ruta_imagen FROM fotos_vehiculos f WHERE f.id_vehiculo = m.id_vehiculo LIMIT 1) AS imagen
        FROM mensajes_chat m
        LEFT JOIN vehiculo v ON m.id_vehiculo = v.id_v
        WHERE m.destinatario = ? OR m.remitente = ?
    ');
    $stmt_chats->execute([$nombre_usuario, $nombre_usuario]);
    $todos_mensajes = $stmt_chats->fetchAll(PDO::FETCH_ASSOC);
    $stmt_chats->execute([$nombre_usuario, $nombre_usuario]);
    $todos_mensajes = $stmt_chats->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar chats únicos
    foreach ($todos_mensajes as $msg) {
        $interlocutor = ($msg['remitente'] === $nombre_usuario) ? $msg['destinatario'] : $msg['remitente'];
        if (!empty($interlocutor) && $interlocutor !== $nombre_usuario) {
            $clave_contacto = $msg['id_vehiculo'] . '_' . $interlocutor;
            
            $nombre_vehiculo = trim(($msg['marca'] ?? '') . ' ' . ($msg['modelo'] ?? 'Vehículo #' . $msg['id_vehiculo']));
            
            // Como está en Cloudinary, la URL viene lista o ponemos una por defecto si está vacía
            $imagen_cloudinary = !empty($msg['imagen']) ? $msg['imagen'] : 'https://via.placeholder.com/40?text=Auto';
            
            $contactos[$clave_contacto] = [
                'interlocutor' => $interlocutor,
                'id_vehiculo'  => $msg['id_vehiculo'],
                'nombre_auto'  => $nombre_vehiculo ?: 'Vehículo #' . $msg['id_vehiculo'],
                'imagen_auto'  => $imagen_cloudinary
            ];
        }
    }

} catch (PDOException $e) {
    $error_msg = "Error en la base de datos: " . $e->getMessage();
}

$keys_contacto = array_keys($contactos);
$contacto_activo_key = $_GET['contacto'] ?? (!empty($keys_contacto) ? $keys_contacto[0] : '');

$id_vehiculo_activo = '';
$nombre_vehiculo_activo = '';
$imagen_vehiculo_activo = '';
$interlocutor_real = '';

if (!empty($contacto_activo_key) && isset($contactos[$contacto_activo_key])) {
    $id_vehiculo_activo = $contactos[$contacto_activo_key]['id_vehiculo'];
    $nombre_vehiculo_activo = $contactos[$contacto_activo_key]['nombre_auto'];
    $imagen_vehiculo_activo = $contactos[$contacto_activo_key]['imagen_auto'];
    $interlocutor_real = $contactos[$contacto_activo_key]['interlocutor'];
}

// Obtener mensajes de la conversación activa
$mensajes_chat = [];
if (!empty($id_vehiculo_activo) && !empty($interlocutor_real)) {
    try {
        $stmt_h = $pdo->prepare('
            SELECT * FROM mensajes_chat 
            WHERE id_vehiculo = ? 
            AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?))
            ORDER BY fecha ASC
        ');
        $stmt_h->execute([$id_vehiculo_activo, $nombre_usuario, $interlocutor_real, $interlocutor_real, $nombre_usuario]);
        $mensajes_chat = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_msg = "Error al cargar el chat: " . $e->getMessage();
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
                    <?php foreach ($contactos as $key => $c): ?>
                        <a href="mensajes.php?contacto=<?php echo urlencode($key); ?>" 
                           class="contact-item <?php echo ($contacto_activo_key === $key) ? 'active' : ''; ?>">
                            <div class="contact-avatar">
                                <img src="<?php echo htmlspecialchars($c['imagen_auto']); ?>" alt="Vehículo" onerror="this.src='https://via.placeholder.com/40?text=Auto'">
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
                        <img src="<?php echo htmlspecialchars($imagen_vehiculo_activo); ?>" alt="Vehículo" onerror="this.src='https://via.placeholder.com/40?text=Auto'">
                    </div>
                    <div>
                        <h3><?php echo htmlspecialchars($nombre_vehiculo_activo); ?></h3>
                        <small style="color: #aaa; font-size: 0.85rem;">Conversación con: <strong><?php echo htmlspecialchars($interlocutor_real); ?></strong></small>
                    </div>
                </div>

                <div class="chat-messages" id="chatBox">
                    <?php foreach ($mensajes_chat as $msg): 
                        $es_mio = ($msg['remitente'] === $nombre_usuario);
                    ?>
                        <div class="message-bubble <?php echo $es_mio ? 'sent' : 'received'; ?>">
                            <p><?php echo nl2br(htmlspecialchars($msg['mensaje'])); ?></p>
                            <span class="msg-time"><?php echo date('H:i', strtotime($msg['fecha'] ?? 'now')); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form class="chat-input-area" id="formEnviarMensaje">
                    <input type="hidden" name="accion" value="enviar">
                    <input type="hidden" name="tipo_vehiculo" value="auto">
                    <input type="hidden" name="id_item" value="<?php echo $id_vehiculo_activo; ?>">
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
                    }
                })
                .catch(err => console.error('Error al enviar:', err));
            });
        }
    </script>
</body>
</html>