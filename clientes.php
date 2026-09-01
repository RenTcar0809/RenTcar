<?php
session_start();
require_once 'conexion.php';

// 1. SEGURIDAD: Verificar sesión activa
if (!isset($_SESSION['id_proveedor']) && !isset($_SESSION['nombre_empresa'])) {
    header("Location: inicioSesion.php");
    exit();
}

// Prevenir almacenamiento en caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Datos de la empresa
$idProveedor    = $_SESSION['id_proveedor'] ?? 0;
$nombreEmpresa  = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Empresa';
$inicialEmpresa = strtoupper(substr($nombreEmpresa, 0, 1));

$clientes = [];
$totalClientes = 0;

try {
    // Consulta SQL para obtener los clientes que le han alquilado a esta empresa
    $sql = "SELECT 
                u.nombre, 
                u.apellido, 
                u.correo, 
                u.numTelefono, 
                u.documento,
                COUNT(a.id_alquiler) AS total_alquileres,
                MAX(a.fecha_inicio) AS ultimo_alquiler
            FROM alquiler a
            INNER JOIN vehiculo v ON a.id_vehiculo = v.id_vehiculo
            INNER JOIN usuario u ON a.correo = u.correo OR a.id_usuario = u.id_usuario
            WHERE v.id_proveedor = :id
            GROUP BY u.correo
            ORDER BY total_alquileres DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idProveedor]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalClientes = count($clientes);

} catch (PDOException $e) {
    $clientes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - <?php echo htmlspecialchars($nombreEmpresa); ?></title>
    
    <!-- Iconos FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    
    <!-- Hoja de Estilos -->
    <link rel="stylesheet" href="clientesStile.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- --- BARRA SUPERIOR (NAVBAR) --- -->
    <header class="top-navbar">
        <div class="logo-container">
            <h1 class="logo-text"><span class="red-t">REN</span>T<span class="red-t">CAR</span></h1>
        </div>

        <nav class="nav-menu">
            <a href="dashboardE.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Panel</span>
            </a>
            <a href="vehiculosE.php" class="nav-link">
                <i class="fa-solid fa-car-side"></i>
                <span>Mi Flota</span>
            </a>
            <a href="alquileres.php" class="nav-link">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Alquileres</span>
            </a>
            <a href="clientes.php" class="nav-link active">
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
        
        <!-- Encabezado -->
        <div class="header-banner">
            <div class="header-info">
                <h2>Directorio de Clientes</h2>
                <p>Lista de clientes que han alquilado vehículos de tu flota.</p>
            </div>
        </div>

        <!-- Tarjeta de Estadísticas -->
        <div class="client-stats-bar">
            <div class="stat-pill">
                <i class="fa-solid fa-users"></i>
                <span>Total de Clientes Registrados: <strong><?php echo $totalClientes; ?></strong></span>
            </div>
        </div>

        <!-- Tabla de Clientes -->
        <section class="client-list-container">
            <?php if (!empty($clientes)): ?>
                <div class="table-responsive">
                    <table class="client-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th>Correo Electrónico</th>
                                <th>Teléfono</th>
                                <th>Alquileres Realizados</th>
                                <th>Último Alquiler</th>
                                <th style="text-align: center;">Contacto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $cliente): ?>
                                <?php 
                                    $nombreCompleto = trim(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? ''));
                                    $inicialCliente = strtoupper(substr($cliente['nombre'] ?? 'C', 0, 1));
                                    $telefonoLimpio = preg_replace('/[^0-9]/', '', $cliente['numTelefono'] ?? '');
                                ?>
                                <tr>
                                    <!-- Cliente -->
                                    <td class="col-client">
                                        <div class="client-avatar">
                                            <?php echo $inicialCliente; ?>
                                        </div>
                                        <div class="client-name">
                                            <span><?php echo htmlspecialchars($nombreCompleto ?: 'Cliente General'); ?></span>
                                        </div>
                                    </td>

                                    <!-- Documento -->
                                    <td>
                                        <span class="doc-tag">
                                            <?php echo htmlspecialchars($cliente['documento'] ?? 'Sin Documento'); ?>
                                        </span>
                                    </td>

                                    <!-- Correo -->
                                    <td class="col-email">
                                        <i class="fa-regular fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($cliente['correo'] ?? 'N/A'); ?></span>
                                    </td>

                                    <!-- Teléfono -->
                                    <td class="col-phone">
                                        <i class="fa-solid fa-phone"></i>
                                        <span><?php echo htmlspecialchars($cliente['numTelefono'] ?? 'N/A'); ?></span>
                                    </td>

                                    <!-- Total Alquileres -->
                                    <td>
                                        <span class="rental-count-badge">
                                            <?php echo $cliente['total_alquileres']; ?> reserva(s)
                                        </span>
                                    </td>

                                    <!-- Último Alquiler -->
                                    <td class="col-date">
                                        <?php echo htmlspecialchars($cliente['ultimo_alquiler'] ?? 'N/A'); ?>
                                    </td>

                                    <!-- Botón rápido de WhatsApp -->
                                    <td style="text-align: center;">
                                        <?php if (!empty($telefonoLimpio)): ?>
                                            <a href="https://wa.me/<?php echo $telefonoLimpio; ?>" target="_blank" class="btn-icon btn-whatsapp" title="Enviar WhatsApp">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-users-slash"></i>
                    <h3>No tienes clientes registrados aún</h3>
                    <p>Cuando los usuarios alquilen tus vehículos, sus datos aparecerán automáticamente en esta tabla.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Script Menú Desplegable -->
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
    </script>

</body>
</html>