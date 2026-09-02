<?php
session_start();
require_once 'conexion.php'; // Archivo de conexión PDO

// 1. SEGURIDAD: Validar que la empresa tenga una sesión activa
if (!isset($_SESSION['id_proveedor']) && !isset($_SESSION['nombre_empresa'])) {
    header("Location: inicioSesion.php");
    exit();
}

// Prevenir caché para que no se pueda regresar con el botón de "Atrás" tras cerrar sesión
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Obtención de datos del usuario
$idProveedor   = $_SESSION['id_proveedor'] ?? 0;
$nombreEmpresa = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Empresa';
$inicialEmpresa = strtoupper(substr($nombreEmpresa, 0, 1)); // Primera letra para el avatar

// Variables para estadísticas (valores por defecto)
$totalVehiculos  = 0;
$rentasActivas   = 0;
$gananciasMes    = 0;
$totalClientes   = 0;
$historialRentas = [];

try {
    // 1. Contar vehículos de la empresa
    $stmtVeh = $pdo->prepare("SELECT COUNT(*) FROM vehiculo WHERE id_proveedor = :id");
    $stmtVeh->execute([':id' => $idProveedor]);
    $totalVehiculos = $stmtVeh->fetchColumn();

    // 2. Contar rentas activas
    $stmtActivas = $pdo->prepare("SELECT COUNT(*) FROM reserva r 
                                  INNER JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo 
                                  WHERE v.id_proveedor = :id AND r.estado = 'activa'");
    $stmtActivas->execute([':id' => $idProveedor]);
    $rentasActivas = $stmtActivas->fetchColumn();

    // 3. Obtener ganancias del mes
    $stmtGanancias = $pdo->prepare("SELECT COALESCE(SUM(r.monto_total), 0) FROM reserva r 
                                     INNER JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo 
                                     WHERE v.id_proveedor = :id AND r.estado = 'completada'");
    $stmtGanancias->execute([':id' => $idProveedor]);
    $gananciasMes = $stmtGanancias->fetchColumn();

    // 4. Contar clientes únicos que han alquilado
    $stmtClientes = $pdo->prepare("SELECT COUNT(DISTINCT r.id_usuario) FROM reserva r 
                                   INNER JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo 
                                   WHERE v.id_proveedor = :id");
    $stmtClientes->execute([':id' => $idProveedor]);
    $totalClientes = $stmtClientes->fetchColumn();

    // 5. Consultar los últimos usuarios que han usado los vehículos
    $sqlRentas = "SELECT u.nombre AS cliente, u.correo, u.telefono, 
                         v.marca, v.modelo, v.placa, 
                         r.fecha_inicio, r.fecha_fin, r.estado, r.monto_total
                  FROM reserva r
                  INNER JOIN usuario u ON r.id_usuario = u.IdUsuario
                  INNER JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
                  WHERE v.id_proveedor = :id
                  ORDER BY r.fecha_inicio DESC LIMIT 10";
    
    $stmtRentas = $pdo->prepare($sqlRentas);
    $stmtRentas->execute([':id' => $idProveedor]);
    $historialRentas = $stmtRentas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si ocurre un error con las consultas, las variables se mantienen en 0 o vacías
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - <?php echo htmlspecialchars($nombreEmpresa); ?></title>
    <!-- Iconos FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos CSS -->
    <link rel="stylesheet" href="admindashboard.css">
</head>
<body>

    <!-- --- BARRA DE NAVEGACIÓN SUPERIOR (HORIZONTAL) --- -->
    <header class="top-navbar">
        <!-- Logo con el resalte en rojo -->
        <div class="logo-container">
            <h1 class="logo-text"><span class="red-t">REN</span>T<span class="red-t">CAR</span></h1>
        </div>

        <!-- Opciones del menú -->
        <nav class="nav-menu">
            <a href="dashboardE.php" class="nav-link active">
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
            <a href="clientes.php" class="nav-link">
                <i class="fa-solid fa-users"></i>
                <span>Clientes</span>
            </a>
            <a href="perfil_empresa.php" class="nav-link highlight-company">
                <i class="fa-solid fa-building"></i>
                <span>Mi Empresa</span>
            </a>
        </nav>

        <!-- Perfil dinámico del Proveedor / Empresa -->
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
        
        <h2 class="section-title">Resumen General</h2>

        <!-- Tarjetas de métricas (KPIs) con datos reales de la BD -->
        <section class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Vehículos Totales</h3>
                    <p><?php echo number_format($totalVehiculos); ?></p>
                </div>
                <div class="metric-icon">
                    <i class="fa-solid fa-car"></i>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Alquileres Activos</h3>
                    <p><?php echo number_format($rentasActivas); ?></p>
                </div>
                <div class="metric-icon">
                    <i class="fa-solid fa-key"></i>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Clientes Totales</h3>
                    <p><?php echo number_format($totalClientes); ?></p>
                </div>
                <div class="metric-icon">
                    <i class="fa-solid fa-user-check"></i>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Ingresos del Mes</h3>
                    <p>$<?php echo number_format($gananciasMes, 2); ?></p>
                </div>
                <div class="metric-icon">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
            </div>
        </section>

        <!-- Registro de Usuarios y Alquileres -->
        <section class="table-container">
            <div class="table-header-title">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Registro de Usuarios y Alquileres</h3>
            </div>

            <?php if (!empty($historialRentas)): ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Vehículo</th>
                            <th>Placa</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Monto</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historialRentas as $renta): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($renta['cliente']); ?></strong><br>
                                    <small style="color: #8e8e93;"><?php echo htmlspecialchars($renta['correo']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($renta['marca'] . ' ' . $renta['modelo']); ?></td>
                                <td><code><?php echo htmlspecialchars($renta['placa']); ?></code></td>
                                <td><?php echo date('d/m/Y', strtotime($renta['fecha_inicio'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($renta['fecha_fin'])); ?></td>
                                <td><strong>$<?php echo number_format($renta['monto_total'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($renta['estado']); ?>">
                                        <?php echo htmlspecialchars($renta['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fa-solid fa-folder-open"></i>
                    <p>Aún no hay usuarios que hayan alquilado vehículos de tu flota.</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Script del Menú Desplegable -->
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