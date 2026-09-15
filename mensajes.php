<?php
session_start();
require_once 'conexion.php'; 

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['IdUsuario'];
$nombre_usuario = '';

try {
    // Obtener la identidad del proveedor actual respetando el case de PostgreSQL
    $stmtU = $pdo->prepare('SELECT nombre, empresa, tipo FROM usuario WHERE "IdUsuario" = ?');
    $stmtU->execute([$id_usuario]);
    $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
    
    if ($uData) {
        $nombre_usuario = ($uData['tipo'] == 1) ? $uData['empresa'] : $uData['nombre'];
    }

    // Obtener la lista de chats agrupados o conversaciones donde participa el proveedor
    $stmt_chats = $pdo->prepare('
        SELECT DISTINCT id_vehiculo, remitente, destinatario 
        FROM mensajes_chat 
        WHERE destinatario = ? OR remitente = ?
    ');
    $stmt_chats->execute([$nombre_usuario, $nombre_usuario]);
    $todos_mensajes = $stmt_chats->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar interlocutores únicos para la barra lateral de contactos
    $contactos = [];
    foreach ($todos_mensajes as $msg) {
        $interlocutor = ($msg['remitente'] === $nombre_usuario) ? $msg['destinatario'] : $msg['remitente'];
        if (!empty($interlocutor) && $interlocutor !== $nombre_usuario) {
            $contactos[$interlocutor] = [
                'nombre' => $interlocutor,
                'id_vehiculo' => $msg['id_vehiculo']
            ];
        }
    }

} catch (PDOException $e) {
    $error_msg = "Error al cargar los mensajes: " . $e->getMessage();
}

// Interlocutor seleccionado actualmente por GET (si existe)
$contacto_activo = $_GET['contacto'] ?? (array_key_exists(0, array_keys($contactos)) ? array_keys($contactos)[0] : '');
$id_vehiculo_activo = $contactos[$contacto_activo]['id_vehiculo'] ?? 0;

// Obtener mensajes de la conversación activa si hay un contacto seleccionado
$mensajes_chat = [];
if (!empty($contacto_activo)) {
    $stmt_h = $pdo->prepare('
        SELECT * FROM mensajes_chat 
        WHERE id_vehiculo = ? 
        AND ((remitente = ? AND destinatario = ?) OR (remitente = ? AND destinatario = ?))
        ORDER BY fecha ASC
    ');
    $stmt_h->execute([$id_vehiculo_activo, $nombre_usuario, $contacto_activo, $contacto_activo, $nombre_usuario]);
    $mensajes_chat = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
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
                <a href="historial_vehiculo.php" class="btn-volver">
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
                    <?php foreach ($contactos as $c): ?>
                        <a href="mensajes.php?contacto=<?php echo urlencode($c['nombre']); ?>" 
                           class="contact-item <?php echo ($contacto_activo === $c['nombre']) ? 'active' : ''; ?>">
                            <div class="contact-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="contact-info">
                                <h4><?php echo htmlspecialchars($c['nombre']); ?></h4>
                                <small>Vehículo ID: <?php echo $c['id_vehiculo']; ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- CONTENEDOR PRINCIPAL DEL CHAT -->
        <main class="chat-main">
            <?php if (empty($contacto_activo)): ?>
                <div class="empty-chat-state">
                    <i class="fas fa-comments" style="font-size: 4rem; color: #444; margin-bottom: 15px;"></i>
                    <h3>Selecciona una conversación</h3>
                    <p>Elige un cliente de la lista izquierda para ver el historial y responderle.</p>
                </div>
            <?php else: ?>
                <div class="chat-header">
                    <div class="contact-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($contacto_activo); ?></h3>
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
                    <input type="hidden" name="destinatario" value="<?php echo htmlspecialchars($contacto_activo); ?>">
                    
                    <input type="text" name="mensaje" id="inputMensaje" placeholder="Escribe un mensaje..." autocomplete="off" required>
                    <button type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Auto-scroll al final del chat al cargar
        const chatBox = document.getElementById('chatBox');
        if (chatBox) {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Envío asíncrono con AJAX opcional para mejor fluidez
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
                        location.reload(); // Recarga para actualizar los mensajes enviados al instante
                    }
                })
                .catch(err => console.error('Error al enviar:', err));
            });
        }
    </script>
</body>
</html>