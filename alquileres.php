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

// Variables de contadores
$alquileres = [];
$totalAlquileres = 0;
$totalActivos = 0;
$totalFinalizados = 0;

try {
    // Consulta SQL uniendo la tabla 'alquiler' con 'vehiculo'
    $sql = "SELECT a.*, v.marca, v.modelo, v.placa, v.imagen 
            FROM alquiler a 
            INNER JOIN vehiculo v ON a.id_vehiculo = v.id_vehiculo 
            WHERE v.id_proveedor = :id 
            ORDER BY a.id_alquiler DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idProveedor]);
    $alquileres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contadores para el panel superior
    foreach ($alquileres as $alq) {
        $totalAlquileres++;
        $est = strtolower($alq['estado'] ?? 'activo');
        if ($est === 'activo') {
            $totalActivos++;
        } elseif ($est === 'finalizado' || $est === 'completado') {
            $totalFinalizados++;
        }
    }
} catch (PDOException $e) {
    $alquileres = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alquileres - <?php echo htmlspecialchars($nombreEmpresa); ?></title>
    
    <!-- Iconos FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    
    <!-- Hoja de Estilos Independiente -->
    <link rel="stylesheet" href="alquileresstile.css?v=<?php echo time(); ?>">
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
            <a href="alquileres.php" class="nav-link active">
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
        
        <!-- Encabezado de la sección -->
        <div class="header-banner">
            <div class="header-info">
                <h2>Historial de Alquileres</h2>
                <p>Monitorea los contratos de renta, fechas y estados de tus vehículos.</p>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas / Resumen -->
        <div class="rental-stats-bar">
            <div class="stat-pill">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Total Alquileres: <strong><?php echo $totalAlquileres; ?></strong></span>
            </div>
            <div class="stat-pill status-active">
                <i class="fa-solid fa-key"></i>
                <span>En Curso: <strong><?php echo $totalActivos; ?></strong></span>
            </div>
            <div class="stat-pill status-completed">
                <i class="fa-solid fa-circle-check"></i>
                <span>Finalizados: <strong><?php echo $totalFinalizados; ?></strong></span>
            </div>
        </div>

        <!-- Tabla de Alquileres -->
        <section class="rental-list-container">
            <?php if (!empty($alquileres)): ?>
                <div class="table-responsive">
                    <table class="rental-table">
                        <thead>
                            <tr>
                                <th>N° Reserva</th>
                                <th>Vehículo</th>
                                <th>Cliente</th>
                                <th>Fechas (Inicio / Fin)</th>
                                <th>Monto Total</th>
                                <th>Estado</th>
                                <th style="text-align: center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alquileres as $item): ?>
                                <tr>
                                    <!-- ID Reserva -->
                                    <td class="col-id">
                                        #<?php echo str_pad($item['id_alquiler'], 4, '0', STR_PAD_LEFT); ?>
                                    </td>

                                    <!-- Vehículo -->
                                    <td class="col-vehicle">
                                        <div class="car-thumb">
                                            <?php if (!empty($item['imagen'])): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($item['imagen']); ?>" alt="Foto">
                                            <?php else: ?>
                                                <i class="fa-solid fa-car"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="car-details">
                                            <span class="car-title"><?php echo htmlspecialchars(($item['marca'] ?? '') . ' ' . ($item['modelo'] ?? '')); ?></span>
                                            <small class="plate-text">Placa: <?php echo htmlspecialchars($item['placa'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>

                                    <!-- Cliente -->
                                    <td>
                                        <div class="client-info">
                                            <i class="fa-solid fa-user-circle"></i>
                                            <span><?php echo htmlspecialchars($item['nombre_cliente'] ?? $item['cliente'] ?? 'Cliente General'); ?></span>
                                        </div>
                                    </td>

                                    <!-- Fechas -->
                                    <td>
                                        <div class="dates-box">
                                            <span><i class="fa-regular fa-calendar-check"></i> <?php echo htmlspecialchars($item['fecha_inicio'] ?? 'N/A'); ?></span>
                                            <small><i class="fa-regular fa-calendar-xmark"></i> <?php echo htmlspecialchars($item['fecha_fin'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>

                                    <!-- Precio Total -->
                                    <td>
                                        <span class="price-tag">$<?php echo number_format($item['precio_total'] ?? $item['total'] ?? 0, 2); ?></span>
                                    </td>

                                    <!-- Estado -->
                                    <td>
                                        <?php 
                                            $estado = strtolower($item['estado'] ?? 'activo');
                                            $badgeClass = 'badge-activo';

                                            if ($estado === 'finalizado' || $estado === 'completado') {
                                                $badgeClass = 'badge-finalizado';
                                            } elseif ($estado === 'cancelado') {
                                                $badgeClass = 'badge-cancelado';
                                            } elseif ($estado === 'pendiente') {
                                                $badgeClass = 'badge-pendiente';
                                            }
                                        ?>
                                        <span class="badge-status <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars(ucfirst($item['estado'] ?? 'Activo')); ?>
                                        </span>
                                    </td>

                                    <!-- Acciones -->
                                    <td style="text-align: center;">
                                        <a href="detalle_alquiler.php?id=<?php echo $item['id_alquiler']; ?>" class="btn-icon btn-view" title="Ver Detalles">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <h3>No hay registros de alquiler</h3>
                    <p>Cuando los clientes renten tus vehículos, aparecerán en este historial.</p>
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