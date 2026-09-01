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
</script>
</body>
</html>