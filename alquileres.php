<?php
session_start();
require_once 'conexion.php';

// 1. SEGURIDAD: Verificar sesión activa
if (
    !isset($_SESSION['IdUsuario']) &&
    !isset($_SESSION['id_proveedor']) &&
    !isset($_SESSION['nombre_empresa']) &&
    !isset($_SESSION['usuario_nombre'])
) {
    header("Location: inicioSesion.php");
    exit();
}

// Prevenir almacenamiento en caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Datos de la empresa
$idProveedor    = intval($_SESSION['IdUsuario'] ?? $_SESSION['id_proveedor'] ?? 0);
$nombreEmpresa  = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Empresa';
$inicialEmpresa = strtoupper(substr($nombreEmpresa, 0, 1));
$errorMsg       = '';

function normalizarImagenVehiculo(?string $ruta): string {
    $ruta = trim(str_replace('\\', '/', (string)$ruta));
    if ($ruta === '') return '';
    if (strpos($ruta, 'http://') === 0 || strpos($ruta, 'https://') === 0) return $ruta;
    if (strpos($ruta, 'uploads/') === 0 || strpos($ruta, 'imagenes/') === 0) return $ruta;
    if (file_exists('uploads/' . $ruta)) return 'uploads/' . $ruta;
    if (file_exists('imagenes/' . $ruta)) return 'imagenes/' . $ruta;
    return 'uploads/' . $ruta;
}

$alquileres = [];
$totalAlquileres = 0;
$totalActivos = 0;
$totalFinalizados = 0;

try {
    $sql = "SELECT
                p.id_prestamo AS id_alquiler,
                p.id_vehiculo,
                p.fecha_inicio,
                p.fecha_fin,
                p.estado,
                COALESCE(p.monto, 0) AS precio_total,
                v.marca,
                v.modelo,
                v.placa,
                COALESCE(
                    (SELECT f.ruta_imagen FROM fotos_vehiculos f WHERE f.id_vehiculo = v.id_v ORDER BY f.id_foto ASC LIMIT 1),
                    v.imagen
                ) AS imagen
            FROM prestamo p
            INNER JOIN vehiculo v ON p.id_vehiculo = v.id_v
            WHERE v.id_proveedor = :id
            ORDER BY p.id_prestamo DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idProveedor]);
    $alquileres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($alquileres as $alq) {
        $totalAlquileres++;
        $est = strtolower(trim((string)($alq['estado'] ?? '')));

        if (in_array($est, ['activo', 'activa', 'en curso'], true)) {
            $totalActivos++;
        } elseif (in_array($est, ['finalizado', 'finalizada', 'completado', 'completada'], true)) {
            $totalFinalizados++;
        }
    }
} catch (PDOException $e) {
    $alquileres = [];
    $errorMsg = "Error al consultar alquileres: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alquileres - <?php echo htmlspecialchars($nombreEmpresa); ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="alquileresstile.css?v=<?php echo time(); ?>">
</head>
<body>

    <header class="top-navbar">
        <div class="logo-container">
            <h1 class="logo-text"><span class="red-t">REN</span>T<span class="red-t">CAR</span></h1>
        </div>

        <nav class="nav-menu">
            <a href="dashboardE.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Panel</span>
            </a>
            <a href="mensajese.php" class="nav-link">
                <i class="fa-solid fa-comments"></i>
                <span>Mensajes</span>
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
            <a href="panel_propietario_licencias.php" class="nav-link">
                <i class="fa-solid fa-id-card"></i>
                <span>Licencias</span>
            </a>
            <a href="perfil_empresa.php" class="nav-link highlight-company">
                <i class="fa-solid fa-building"></i>
                <span>Mi Empresa</span>
            </a>
        </nav>

        <div class="user-container">
            <div class="user-profile" id="userProfileBtn">
                <div class="user-avatar"><?php echo htmlspecialchars($inicialEmpresa); ?></div>
                <span><?php echo htmlspecialchars($nombreEmpresa); ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem; color: #8e8e93;"></i>
            </div>

            <div class="dropdown-content" id="userDropdown">
                <a href="perfil_empresa.php"><i class="fa-solid fa-user-gear"></i> Configuración</a>
                <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <main class="main-content">

        <div class="header-banner">
            <div class="header-info">
                <h2>Historial de Alquileres</h2>
                <p>Monitorea los contratos de renta, fechas y estados de tus vehículos.</p>
            </div>
        </div>

        <?php if (!empty($errorMsg)): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <!-- Tarjetas de Estadísticas / Resumen -->
        <div class="rental-stats-bar">
            <div class="stat-pill">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Total Alquileres: <strong><?php echo number_format($totalAlquileres); ?></strong></span>
            </div>
            <div class="stat-pill status-active">
                <i class="fa-solid fa-key"></i>
                <span>En Curso: <strong><?php echo number_format($totalActivos); ?></strong></span>
            </div>
            <div class="stat-pill status-completed">
                <i class="fa-solid fa-circle-check"></i>
                <span>Finalizados: <strong><?php echo number_format($totalFinalizados); ?></strong></span>
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
                                <th>Fechas (Inicio / Fin)</th>
                                <th>Monto Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alquileres as $item): ?>
                                <?php
                                    $estado = strtolower(trim((string)($item['estado'] ?? 'pendiente')));
                                    $esEnCurso = in_array($estado, ['activo', 'activa', 'en curso'], true);
                                    $esFinalizado = in_array($estado, ['finalizado', 'finalizada', 'completado', 'completada'], true);
                                    $badgeClass = 'badge-pendiente';
                                    $etiquetaEstado = ucfirst($estado ?: 'Pendiente');

                                    if ($esEnCurso) {
                                        $badgeClass = 'badge-activo';
                                        $etiquetaEstado = 'En curso';
                                    } elseif ($esFinalizado) {
                                        $badgeClass = 'badge-finalizado';
                                        $etiquetaEstado = 'Finalizado';
                                    } elseif ($estado === 'cancelado' || $estado === 'cancelada') {
                                        $badgeClass = 'badge-cancelado';
                                        $etiquetaEstado = 'Cancelado';
                                    }

                                    $rutaImg = normalizarImagenVehiculo($item['imagen'] ?? '');
                                ?>
                                <tr>
                                    <td class="col-id">
                                        #<?php echo str_pad((int)$item['id_alquiler'], 4, '0', STR_PAD_LEFT); ?>
                                    </td>

                                    <td class="col-vehicle">
                                        <div class="car-thumb">
                                            <?php if ($rutaImg !== ''): ?>
                                                <img src="<?php echo htmlspecialchars($rutaImg); ?>" alt="Foto" onerror="this.src='unnamed.png'">
                                            <?php else: ?>
                                                <i class="fa-solid fa-car"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="car-details">
                                            <span class="car-title"><?php echo htmlspecialchars(trim(($item['marca'] ?? '') . ' ' . ($item['modelo'] ?? ''))); ?></span>
                                            <small class="plate-text">Placa: <?php echo htmlspecialchars($item['placa'] ?? 'N/A'); ?></small>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="dates-box">
                                            <span><i class="fa-regular fa-calendar-check"></i> <?php echo htmlspecialchars($item['fecha_inicio'] ?? 'N/A'); ?></span>
                                            <?php if ($esFinalizado && !empty($item['fecha_fin'])): ?>
                                                    <small><i class="fa-regular fa-calendar-xmark"></i> <?php echo htmlspecialchars($item['fecha_fin']); ?></small>
                                            <?php else: ?>
                                                    <small><i class="fa-regular fa-clock"></i> Fin: Pendiente (2/2)</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="price-tag">$<?php echo number_format((float)($item['precio_total'] ?? 0), 2); ?></span>
                                    </td>

                                    <td>
                                        <span class="badge-status <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($etiquetaEstado); ?>
                                        </span>
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
                    <p>Cuando se registren alquileres de tus vehículos, aparecerán aquí.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

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