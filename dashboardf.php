<?php
session_start();
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
$nombreUsuario = $_SESSION['usuario_nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentCar - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard_premium.css">
</head>
<body>

    <aside class="sidebar">
        <div class="logo-container">
            <h1 class="logo-text">REN<span class="red-t">T</span>CAR</h1>
        </div>
        
        <nav class="nav-menu">
            <a href="seleccion.html" class="nav-link">
                <i class="fa-solid fa-calendar-check"></i> <span>RESERVAR</span>
            </a>
            <a href="sucursales.php" class="nav-link">
                <i class="fa-solid fa-location-dot"></i> <span>SUCURSALES</span>
            </a>
            <a href="indexV.php" class="nav-link">
                <i class="fa-solid fa-car-side"></i> <span>RENTA TU VEHÍCULO</span>
            </a>
            <a href="historial_vehiculo.php" class="nav-link">
                <i class="fa-solid fa-folder-open"></i> <span>MIS VEHÍCULOS</span>
            </a>
            <!-- Enlace de Mensajes con la burbuja roja de notificación -->
            <a href="mensajes.php" class="nav-link" style="position: relative;">
                <i class="fa-solid fa-comments"></i> <span>MENSAJES</span>
                <span id="badge-menu-mensajes" style="display: none; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); width: 10px; height: 10px; background: #e74c3c; border-radius: 50%; box-shadow: 0 0 5px rgba(231,76,60,0.8);"></span>
            </a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="user-container">
                <div class="user-profile user-trigger" onclick="toggleDropdown()">
                    <span>Hola, <strong><?php echo htmlspecialchars($nombreUsuario); ?></strong></span>
                    <div class="user-avatar"><?php echo strtoupper(substr($nombreUsuario, 0, 1)); ?></div>
                </div>
                
                <div class="dropdown-content" id="myDropdown">
                    <a href="configuracion.php"><i class="fa-solid fa-gear"></i> Ajustes / Mi Perfil</a>
                    <a href="reservas.php"><i class="fa-solid fa-list"></i> Mis Reservas</a>
                    <a href="mensajes.php"><i class="fa-solid fa-comments"></i> Mensajes</a>
                    <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
                </div>
            </div>
        </header>

        <section class="hero-section">
            <h2 class="section-title">¿QUÉ DESEAS HOY?</h2>
            
            <div class="hero-card">
                <div class="hero-overlay"></div>
                <div class="hero-content">
                    <div class="logo-central">
                        <span class="rc-initials">RC</span>
                    </div>
                    <p class="slogan">"CON RENTCAR<br>HAZ DEL CAMINO TU PRÓXIMO DESTINO"</p>
                </div>
            </div>
        </section>
    </main>

<script>

function toggleDropdown() { 
    document.getElementById("myDropdown").classList.toggle("show"); 
}

// Cerrar el dropdown si el usuario hace clic fuera de él
window.onclick = function(event) {
    if (!event.target.matches('.user-trigger') && !event.target.closest('.user-trigger')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) { 
                openDropdown.classList.remove('show'); 
            }
        }
    }
}

// Función global para verificar mensajes nuevos y mostrar la burbuja en el menú lateral
function verificarNotificacionesGlobales() {
    fetch('chat_backend.php?accion=verificar_nuevos')
    .then(res => res.json())
    .then(data => {
        const badgeMenu = document.getElementById('badge-menu-mensajes');
        if (!badgeMenu) return;

        // Determinamos si hay mensajes nuevos de varias formas posibles según tu backend:
        let hayMensajesNuevos = false;

        if (data.status === 'success') {
            // Caso A: Si el backend devuelve un número directo (ej: data.cantidad o data.total)
            if (typeof data.cantidad !== 'undefined' && data.cantidad > 0) {
                hayMensajesNuevos = true;
            }
            // Caso B: Si el backend devuelve un arreglo y queremos asegurarnos de que tenga elementos reales no leídos
            else if (Array.isArray(data.notificaciones) && data.notificaciones.length > 0) {
                // Filtramos por si el arreglo trae elementos pero con estado leido
                const noLeidos = data.notificaciones.filter(n => n.leido == 0 || n.leido === false || n.estado === 'no_leido');
                if (noLeidos.length > 0 || data.notificaciones.length > 0) {
                    hayMensajesNuevos = true;
                }
            }
        }

        // Mostramos u ocultamos según el resultado real
        if (hayMensajesNuevos) {
            badgeMenu.style.display = 'block';
        } else {
            badgeMenu.style.display = 'none';
        }
    })
    .catch(err => console.error('Error al verificar notificaciones globales:', err));
}

// Revisar cada 5 segundos en segundo plano
setInterval(verificarNotificacionesGlobales, 5000);
// Ejecutar inmediatamente al cargar la página
verificarNotificacionesGlobales();

</script>
</body>
</html>