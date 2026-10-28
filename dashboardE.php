<?php
session_start();
require_once 'conexion.php'; // Archivo de conexión PDO

// 1. SEGURIDAD REFORZADA: Verificar que exista sesión Y que sea de tipo Empresa (tipo = 1)
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] != 1) {
    if (isset($_SESSION['tipo']) && $_SESSION['tipo'] == 2) {
        header("Location: dashboardf.php");
        exit();
    }
    header("Location: inicioSesion.php");
    exit();
}

// Prevenir caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$idProveedor    = $_SESSION['IdUsuario'] ?? $_SESSION['id_proveedor'] ?? 0;
$nombreEmpresa = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Empresa';
$inicialEmpresa = strtoupper(substr($nombreEmpresa, 0, 1));

// Variables para estadísticas
$totalVehiculos   = 0;
$rentasActivas    = 0;
$gananciasMes     = 0;
$totalClientes    = 0;
$historialRentas  = [];
$errorConsulta    = null;

try {
    // 1. Contar vehículos de la empresa
    $stmtVeh = $pdo->prepare("SELECT COUNT(*) FROM vehiculo WHERE id_proveedor = :id");
    $stmtVeh->execute([':id' => $idProveedor]);
    $totalVehiculos = $stmtVeh->fetchColumn();

    // 2. Contar rentas activas
    $stmtActivas = $pdo->prepare("SELECT COUNT(*) FROM prestamo r 
                                INNER JOIN vehiculo v ON r.id_vehiculo = v.id_v 
                                WHERE v.id_proveedor = :id AND r.estado = 'activa'");
    $stmtActivas->execute([':id' => $idProveedor]);
    $rentasActivas = $stmtActivas->fetchColumn();

    // 3. Obtener ganancias del mes
    $stmtGanancias = $pdo->prepare("SELECT COALESCE(SUM(r.monto), 0) FROM prestamo r 
                                   INNER JOIN vehiculo v ON r.id_vehiculo = v.id_v 
                                   WHERE v.id_proveedor = :id AND r.estado = 'completada'");
    $stmtGanancias->execute([':id' => $idProveedor]);
    $gananciasMes = $stmtGanancias->fetchColumn();

    // 4. Contar clientes únicos que han alquilado
    $stmtClientes = $pdo->prepare("SELECT COUNT(DISTINCT r.id_arrendatario) FROM prestamo r 
                                   INNER JOIN vehiculo v ON r.id_vehiculo = v.id_v 
                                   WHERE v.id_proveedor = :id");
    $stmtClientes->execute([':id' => $idProveedor]);
    $totalClientes = $stmtClientes->fetchColumn();

    // 5. Consultar el historial de rentas
    $sqlRentas = "SELECT r.id_prestamo AS id_reserva, u.nombre AS cliente, u.correo, u.num_telefono AS telefono, 
                         v.marca, v.modelo, v.placa, 
                         r.fecha_inicio, r.fecha_fin, r.estado, r.monto AS monto_total
                  FROM prestamo r
                  INNER JOIN usuario u ON r.id_arrendatario = u.id_usuario
                  INNER JOIN vehiculo v ON r.id_vehiculo = v.id_v
                  WHERE v.id_proveedor = :id
                  ORDER BY r.fecha_inicio DESC LIMIT 10";
    
    $stmtRentas = $pdo->prepare($sqlRentas);
    $stmtRentas->execute([':id' => $idProveedor]);
    $historialRentas = $stmtRentas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errorConsulta = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - <?php echo htmlspecialchars($nombreEmpresa); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admindashboard.css">
    <style>
        .select-estado {
            background-color: #2a2a2a;
            color: #fff;
            border: 1px solid #444;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.85rem;
            cursor: pointer;
        }
        .select-estado:focus {
            outline: none;
            border-color: #e50914;
        }

        /* NUEVA DISTRIBUCIÓN DE TARJETAS (GRID 2x2 en lugar de línea horizontal) */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr); /* 2 columnas */
            gap: 20px;
            max-width: 900px; /* Opcional: limita el ancho para que no se estiren demasiado */
        }

        /* Ajuste responsivo para pantallas pequeñas (se vuelven de 1 columna en móviles) */
        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- --- BARRA DE NAVEGACIÓN SUPERIOR --- -->
    <header class="top-navbar">
        <div class="logo-container">
            <h1 class="logo-text"><span class="red-t">REN</span>T<span class="red-t">CAR</span></h1>
        </div>

        <nav class="nav-menu">
            <a href="dashboardE.php" class="nav-link active">
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
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Algo salió mal con la base de datos:</strong> <?php echo htmlspecialchars($errorConsulta); ?>
            </div>
        <?php endif; ?>

        <!-- SALUDO Y BIENVENIDA -->
        <div style="margin-bottom: 25px;">
            <h2 class="section-title" style="margin-bottom: 5px;">¡Hola, <?php echo htmlspecialchars($nombreEmpresa); ?>! 👋</h2>
            <p style="color: #8e8e93; font-size: 0.95rem;">
                <?php if ($rentasActivas > 0): ?>
                    Tienes <strong><?php echo $rentasActivas; ?> vehículo(s)</strong> rodando en las calles ahora mismo. ¡Excelente trabajo!
                <?php else: ?>
                    Tu flota está lista y a la espera de su próxima gran reserva.
                <?php endif; ?>
            </p>
        </div>

        <!-- TARJETAS DISTRIBUIDAS EN CUADRÍCULA (2x2) -->
        <section class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Vehículos en Flota</h3>
                    <p><?php echo number_format($totalVehiculos); ?></p>
                </div>
                <div class="metric-icon"><i class="fa-solid fa-car"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Alquileres Activos</h3>
                    <p><?php echo number_format($rentasActivas); ?></p>
                </div>
                <div class="metric-icon"><i class="fa-solid fa-key"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Clientes Totales</h3>
                    <p><?php echo number_format($totalClientes); ?></p>
                </div>
                <div class="metric-icon"><i class="fa-solid fa-user-check"></i></div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <h3>Ingresos del Mes</h3>
                    <p>$<?php echo number_format($gananciasMes, 2); ?></p>
                </div>
                <div class="metric-icon"><i class="fa-solid fa-dollar-sign"></i></div>
            </div>
        </section>

        <!-- --- REGISTRO DE ALQUILERES --- -->
        <section class="table-container" style="margin-top: 30px;">
            <div class="table-header-title">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Actividad reciente de tus clientes</h3>
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
                            <th>Estado Actual</th>
                            <th>Acción / Cambiar Estado</th>
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
                                <td>
                                    <select class="select-estado" onchange="cambiarEstadoReserva(<?php echo $renta['id_reserva']; ?>, this.value)">
                                        <option value="activa" <?php echo ($renta['estado'] === 'activa') ? 'selected' : ''; ?>>Activa (Rentado)</option>
                                        <option value="completada" <?php echo ($renta['estado'] === 'completada') ? 'selected' : ''; ?>>Completada (Disponible)</option>
                                        <option value="cancelada" <?php echo ($renta['estado'] === 'cancelada') ? 'selected' : ''; ?>>Cancelada (Disponible)</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data" style="text-align: center; padding: 30px; color: #888;">
                    <i class="fa-solid fa-face-smile" style="font-size: 2.5rem; margin-bottom: 10px; color: #555;"></i>
                    <p>Todavía no registras alquileres. ¡Tus próximos clientes están por llegar!</p>
                </div>
            <?php endif; ?>
        </section>

    </main>

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

        function cambiarEstadoReserva(idReserva, nuevoEstado) {
            const formData = new FormData();
            formData.append('id_reserva', idReserva);
            formData.append('estado', nuevoEstado);

            fetch('actualizar_estado_reserva.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('No pudimos actualizar el estado: ' + (data.message || 'Inténtalo de nuevo'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un pequeño problema al procesar tu solicitud.');
            });
        }
    </script>

</body>
</html>