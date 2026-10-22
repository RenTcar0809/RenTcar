<?php
session_start();
require_once 'conexion.php'; // Archivo de conexión PDO

// 1. SEGURIDAD: Validar sesión activa
if (!isset($_SESSION['id_proveedor']) && !isset($_SESSION['nombre_empresa'])) {
    header("Location: inicioSesion.php");
    exit();
}

// Prevenir caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$idProveedor    = $_SESSION['id_proveedor'] ?? 0;
$nombreEmpresa = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Empresa';
$inicialEmpresa = strtoupper(substr($nombreEmpresa, 0, 1));

$mensajesClientes = [];
$errorConsulta    = null;

try {
    // Consultar mensajes AGRUPADOS (Solo el último mensaje de cada cliente por vehículo)
    $sqlMensajes = "SELECT DISTINCT ON (m.remitente, m.id_vehiculo) 
                           m.id_mensaje, m.mensaje, m.fecha, m.leido, 
                           m.remitente AS cliente, 
                           u.id_usuario, u.num_telefono AS telefono, 
                           v.id_v AS id_vehiculo, v.marca, v.modelo, v.placa 
                    FROM mensajes_chat m
                    INNER JOIN vehiculo v ON m.id_vehiculo = v.id_v
                    LEFT JOIN usuario u ON u.nombre = m.remitente OR u.correo = m.remitente
                    WHERE v.id_proveedor = :id
                    ORDER BY m.remitente, m.id_vehiculo, m.fecha DESC";
    
    $stmtMensajes = $pdo->prepare($sqlMensajes);
    $stmtMensajes->execute([':id' => $idProveedor]);
    $mensajesClientes = $stmtMensajes->fetchAll(PDO::FETCH_ASSOC);

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
        .messages-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
            gap: 20px; 
            margin-top: 20px; 
        }
        .message-card { 
            background: #1e1e1e; 
            border: 1px solid #333; 
            border-radius: 10px; 
            padding: 20px; 
            color: #fff; 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }
        .message-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 10px; 
            border-bottom: 1px solid #2a2a2a; 
            padding-bottom: 8px; 
        }
        .message-header strong { color: #fff; font-size: 1rem; }
        .message-header small { color: #8e8e93; }
        .message-vehicle { font-size: 0.85rem; color: #e50914; margin-bottom: 10px; font-weight: bold; }
        .message-body { font-size: 0.95rem; color: #ccc; line-height: 1.4; margin-bottom: 15px; }

        .btn-delete-msg {
            background: transparent;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .btn-delete-msg:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        /* Modal de Respuesta */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.75);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: #1e1e1e;
            border: 1px solid #333;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            padding: 25px;
            color: #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .modal-header h3 { margin: 0; color: #e50914; }
        .close-modal {
            background: transparent;
            border: none;
            color: #888;
            font-size: 1.2rem;
            cursor: pointer;
        }
        .close-modal:hover { color: #fff; }
        .modal-body textarea {
            width: 100%;
            height: 120px;
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 6px;
            color: #fff;
            padding: 10px;
            font-size: 0.95rem;
            resize: vertical;
            box-sizing: border-box;
            margin-top: 10px;
        }
        .modal-body textarea:focus { outline: none; border-color: #e50914; }
        .btn-reply {
            background: #e50914;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-reply:hover { background: #b80710; }
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
            <!-- Opción Activa en esta pantalla -->
            <a href="mensajes.php" class="nav-link active">
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
    <main class="main-content">
        
        <?php if ($errorConsulta): ?>
            <div style="background: #2b0000; color: #ff6b6b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ff4d4d;">
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Error SQL detectado:</strong> <?php echo htmlspecialchars($errorConsulta); ?>
            </div>
        <?php endif; ?>

        <!-- VISTA DE CHATS COMPLETA -->
        <section class="table-container">
            <div class="table-header-title">
                <h3><i class="fa-solid fa-comments"></i> Centro de Mensajes y Dudas de Clientes</h3>
            </div>

            <?php if (!empty($mensajesClientes)): ?>
                <div class="messages-grid">
                    <?php foreach ($mensajesClientes as $msg): ?>
                        <div class="message-card" id="card-msg-<?php echo $msg['id_mensaje']; ?>">
                            <div>
                                <div class="message-header">
                                    <strong><?php echo htmlspecialchars($msg['cliente']); ?></strong>
                                    <small><?php echo date('d/m/Y H:i', strtotime($msg['fecha'])); ?></small>
                                </div>
                                <div class="message-vehicle">
                                    <i class="fa-solid fa-car-side"></i> Vehículo: <?php echo htmlspecialchars(($msg['marca'] ?? '') . ' ' . ($msg['modelo'] ?? '')); ?> (Placa: <?php echo htmlspecialchars($msg['placa'] ?? 'N/D'); ?>)
                                </div>
                                <div class="message-body">
                                    "<?php echo nl2br(htmlspecialchars($msg['mensaje'])); ?>"
                                </div>
                            </div>

                            <div class="message-footer" style="border-top: 1px solid #2a2a2a; padding-top: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <button type="button" class="btn-delete-msg" onclick="eliminarChat('<?php echo htmlspecialchars($msg['cliente']); ?>', '<?php echo $msg['id_vehiculo']; ?>', '<?php echo $msg['id_mensaje']; ?>')" title="Eliminar conversación">
                                    <i class="fa-solid fa-trash-can"></i> Borrar
                                </button>
                                <button type="button" class="btn-reply" onclick="abrirModalRespuesta('<?php echo htmlspecialchars($msg['cliente']); ?>', '<?php echo $msg['id_vehiculo']; ?>')">
                                    <i class="fa-solid fa-reply"></i> Responder en la App
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data" style="text-align: center; padding: 40px; color: #888;">
                    <i class="fa-solid fa-envelope-open" style="font-size: 2.5rem; margin-bottom: 12px;"></i>
                    <p>No tienes conversaciones o dudas pendientes por responder.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- --- MODAL DE RESPUESTA DIRECTA --- -->
    <div class="modal-overlay" id="replyModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-paper-plane"></i> Responder Mensaje</h3>
                <button type="button" class="close-modal" onclick="cerrarModalRespuesta()">&times;</button>
            </div>
            <form id="replyForm" onsubmit="enviarRespuestaInterna(event)">
                <div class="modal-body">
                    <p style="margin-bottom: 5px; color: #aaa;">Para: <strong id="modalClienteNombre" style="color: #fff;">-</strong></p>
                    <input type="hidden" id="modalDestinatario" name="destinatario">
                    <input type="hidden" id="modalIdVehiculo" name="id_vehiculo">

                    <label for="modalMensajeTexto" style="font-size: 0.9rem; color: #888;">Tu respuesta:</label>
                    <textarea id="modalMensajeTexto" name="mensaje" placeholder="Escribe tu mensaje para el cliente aquí..." required></textarea>
                </div>
                <div style="text-align: right; margin-top: 15px;">
                    <button type="button" onclick="cerrarModalRespuesta()" style="background: #444; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin-right: 8px;">Cancelar</button>
                    <button type="submit" class="btn-reply"><i class="fa-solid fa-paper-plane"></i> Enviar Respuesta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const btn = document.getElementById('userProfileBtn');
        const dropdown = document.getElementById('userDropdown');

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        window.addEventListener('click', () => {
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });

        function abrirModalRespuesta(clienteNombre, idVehiculo) {
            document.getElementById('modalClienteNombre').innerText = clienteNombre;
            document.getElementById('modalDestinatario').value = clienteNombre;
            document.getElementById('modalIdVehiculo').value = idVehiculo;
            document.getElementById('modalMensajeTexto').value = '';
            document.getElementById('replyModal').classList.add('active');
        }

        function cerrarModalRespuesta() {
            document.getElementById('replyModal').classList.remove('active');
        }

        function enviarRespuestaInterna(e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('replyForm'));

            fetch('responder_mensaje.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert('¡Respuesta enviada exitosamente!');
                    cerrarModalRespuesta();
                    location.reload();
                } else {
                    alert('Error al enviar el mensaje: ' + (data.message || 'Intente nuevamente'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Ocurrió un error al enviar la respuesta.');
            });
        }

        function eliminarChat(remitente, idVehiculo, idCard) {
            if (!confirm('¿Deseas borrar esta conversación con el cliente?')) return;

            const formData = new FormData();
            formData.append('remitente', remitente);
            formData.append('id_vehiculo', idVehiculo);

            fetch('eliminar_mensaje.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const card = document.getElementById('card-msg-' + idCard);
                    if (card) card.remove();
                } else {
                    alert('Error al eliminar: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al procesar la eliminación.');
            });
        }
    </script>

</body>
</html>