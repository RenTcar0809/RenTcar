
<?php
session_start();
require_once 'conexion.php';

// Verificar sesión y tipo de usuario Empresa
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

$idProveedor = $_SESSION['IdUsuario'] ?? $_SESSION['id_proveedor'] ?? 0;
$nombreEmpresa = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Empresa';
$inicialEmpresa = strtoupper(substr($nombreEmpresa, 0, 1));

// Variables para estadísticas
$totalVehiculos = 0;
$rentasActivas = 0;
$gananciasMes = 0;
$totalClientes = 0;
$historialRentas = [];
$errorConsulta = null;

try {
    // 1. Contar vehículos de la empresa
    $stmtVeh = $pdo->prepare(
        "SELECT COUNT(*)
         FROM vehiculo
         WHERE id_proveedor = :id"
    );
    $stmtVeh->execute([':id' => $idProveedor]);
    $totalVehiculos = $stmtVeh->fetchColumn();

    // 2. Contar alquileres activos
    $stmtActivas = $pdo->prepare(
        "SELECT COUNT(*)
         FROM prestamo r
         INNER JOIN vehiculo v ON r.id_vehiculo = v.id_v
         WHERE v.id_proveedor = :id
         AND r.estado = 'activa'"
    );
    $stmtActivas->execute([':id' => $idProveedor]);
    $rentasActivas = $stmtActivas->fetchColumn();

    // 3. Obtener ingresos de alquileres completados
    $stmtGanancias = $pdo->prepare(
        "SELECT COALESCE(SUM(r.monto), 0)
         FROM prestamo r
         INNER JOIN vehiculo v ON r.id_vehiculo = v.id_v
         WHERE v.id_proveedor = :id
         AND r.estado = 'completada'"
    );
    $stmtGanancias->execute([':id' => $idProveedor]);
    $gananciasMes = $stmtGanancias->fetchColumn();

    // 4. Contar clientes únicos
    $stmtClientes = $pdo->prepare(
        "SELECT COUNT(DISTINCT r.id_arrendatario)
         FROM prestamo r
         INNER JOIN vehiculo v ON r.id_vehiculo = v.id_v
         WHERE v.id_proveedor = :id"
    );
    $stmtClientes->execute([':id' => $idProveedor]);
    $totalClientes = $stmtClientes->fetchColumn();

    // 5. Consultar historial de alquileres
    $sqlRentas = "SELECT
                     r.id_prestamo AS id_reserva,
                     u.nombre AS cliente,
                     u.correo,
                     u.num_telefono AS telefono,
                     v.marca,
                     v.modelo,
                     v.placa,
                     r.fecha_inicio,
                     r.fecha_fin,
                     r.estado,
                     r.monto AS monto_total
                  FROM prestamo r
                  INNER JOIN usuario u
                      ON r.id_arrendatario = u.id_usuario
                  INNER JOIN vehiculo v
                      ON r.id_vehiculo = v.id_v
                  WHERE v.id_proveedor = :id
                  ORDER BY r.fecha_inicio DESC
                  LIMIT 10";

    $stmtRentas = $pdo->prepare($sqlRentas);
    $stmtRentas->execute([':id' => $idProveedor]);
    $historialRentas = $stmtRentas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Registrar el error para mostrarlo en el panel
    $errorConsulta = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Panel de Control - <?php echo htmlspecialchars($nombreEmpresa); ?>
    </title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="admindashboard.css">

    <style>
        /* Selector para cambiar el estado del alquiler */
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

        /* DISTRIBUCIÓN PRINCIPAL */
        .dashboard-layout {
            display: flex;
            align-items: flex-start;
            gap: 25px;
            width: 100%;
            margin-top: 25px;
        }

        /* ESTADÍSTICAS EN COLUMNA IZQUIERDA */
        .analytics-pills-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            width: 270px;
            flex: 0 0 270px;
            margin-bottom: 0;
        }

        /* HISTORIAL A LA DERECHA */
        .dashboard-layout .table-container {
            flex: 1 1 auto;
            min-width: 0;
            margin-top: 0 !important;
            overflow-x: auto;
        }

        .dashboard-layout .custom-table {
            width: 100%;
            min-width: 760px;
        }

        /* TARJETAS DE ESTADÍSTICAS */
        .pill-card {
            background: #141416;
            border: 1px solid #222226;
            border-radius: 10px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease,
                        border-color 0.2s ease;
        }

        .pill-card:hover {
            transform: translateY(-2px);
            border-color: #3f3f46;
        }

        /* Línea de color superior */
        .pill-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
        }

        .pill-card.c-red::before {
            background-color: #e50914;
        }

        .pill-card.c-blue::before {
            background-color: #0071e3;
        }

        .pill-card.c-green::before {
            background-color: #34c759;
        }

        .pill-card.c-yellow::before {
            background-color: #ff9f0a;
        }

        .pill-info span {
            display: block;
            font-size: 0.75rem;
            color: #8e8e93;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .pill-info h4 {
            font-size: 1.4rem;
            color: #ffffff;
            font-weight: 700;
            margin: 0;
        }

        .pill-icon {
            font-size: 1.5rem;
            opacity: 0.8;
        }

        .pill-card.c-red .pill-icon {
            color: #e50914;
        }

        .pill-card.c-blue .pill-icon {
            color: #0071e3;
        }

        .pill-card.c-green .pill-icon {
            color: #34c759;
        }

        .pill-card.c-yellow .pill-icon {
            color: #ff9f0a;
        }

        /* TABLETAS */
        @media (max-width: 1050px) {
            .dashboard-layout {
                gap: 18px;
            }

            .analytics-pills-container {
                width: 235px;
                flex-basis: 235px;
            }
        }

        /* MÓVILES Y PANTALLAS PEQUEÑAS */
        @media (max-width: 760px) {
            .dashboard-layout {
                flex-direction: column;
            }

            .analytics-pills-container {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                width: 100%;
                flex-basis: auto;
            }

            .dashboard-layout .table-container {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .analytics-pills-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- BARRA DE NAVEGACIÓN SUPERIOR -->
    <header class="top-navbar">

        <div class="logo-container">
            <h1 class="logo-text">
                <span class="red-t">REN</span>T<span class="red-t">CAR</span>
            </h1>
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

            <a href="perfil_empresa.php"
               class="nav-link highlight-company">
                <i class="fa-solid fa-building"></i>
                <span>Mi Empresa</span>
            </a>

        </nav>

        <div class="user-container">

            <div class="user-profile" id="userProfileBtn">
                <div class="user-avatar">
                    <?php echo htmlspecialchars($inicialEmpresa); ?>
                </div>

                <span>
                    <?php echo htmlspecialchars($nombreEmpresa); ?>
                </span>

                <i class="fa-solid fa-chevron-down"
                   style="font-size: 0.8rem; color: #8e8e93;"></i>
            </div>

            <div class="dropdown-content" id="userDropdown">
                <a href="perfil_empresa.php">
                    <i class="fa-solid fa-user-gear"></i>
                    Configuración
                </a>

                <a href="logout.php" class="logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Cerrar Sesión
                </a>
            </div>

        </div>
    </header>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">

        <?php if ($errorConsulta): ?>
            <div style="background: #2b0000; color: #ff6b6b;
                        padding: 15px; border-radius: 8px;
                        margin-bottom: 20px; border: 1px solid #ff4d4d;">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <strong>
                    Algo salió mal con la base de datos:
                </strong>

                <?php echo htmlspecialchars($errorConsulta); ?>
            </div>
        <?php endif; ?>

        <!-- SALUDO Y BIENVENIDA -->
        <div style="margin-bottom: 20px;">

            <h2 class="section-title" style="margin-bottom: 5px;">
                ¡Hola, <?php echo htmlspecialchars($nombreEmpresa); ?>! 👋
            </h2>

            <p style="color: #8e8e93; font-size: 0.95rem;">

                <?php if ($rentasActivas > 0): ?>

                    Tienes
                    <strong>
                        <?php echo number_format($rentasActivas); ?>
                        vehículo(s)
                    </strong>
                    en ruta actualmente. Aquí tienes un resumen rápido
                    de tu actividad:

                <?php else: ?>

                    Tu flota se encuentra disponible y lista para
                    recibir nuevas reservas.

                <?php endif; ?>

            </p>
        </div>

        <!-- DISTRIBUCIÓN: ESTADÍSTICAS IZQUIERDA / HISTORIAL DERECHA -->
        <div class="dashboard-layout">

            <!-- COLUMNA IZQUIERDA: ESTADÍSTICAS -->
            <section class="analytics-pills-container">

                <!-- TOTAL DE VEHÍCULOS -->
                <div class="pill-card c-red">

                    <div class="pill-info">
                        <span>Vehículos en Flota</span>

                        <h4>
                            <?php echo number_format($totalVehiculos); ?>
                        </h4>
                    </div>

                    <div class="pill-icon">
                        <i class="fa-solid fa-car"></i>
                    </div>

                </div>

                <!-- ALQUILERES ACTIVOS -->
                <div class="pill-card c-blue">

                    <div class="pill-info">
                        <span>Alquileres Activos</span>

                        <h4>
                            <?php echo number_format($rentasActivas); ?>
                        </h4>
                    </div>

                    <div class="pill-icon">
                        <i class="fa-solid fa-key"></i>
                    </div>

                </div>

                <!-- TOTAL DE CLIENTES -->
                <div class="pill-card c-green">

                    <div class="pill-info">
                        <span>Clientes Totales</span>

                        <h4>
                            <?php echo number_format($totalClientes); ?>
                        </h4>
                    </div>

                    <div class="pill-icon">
                        <i class="fa-solid fa-user-check"></i>
                    </div>

                </div>

                <!-- INGRESOS -->
                <div class="pill-card c-yellow">

                    <div class="pill-info">
                        <span>Ingresos del Mes</span>

                        <h4>
                            $<?php echo number_format($gananciasMes, 2); ?>
                        </h4>
                    </div>

                    <div class="pill-icon">
                        <i class="fa-solid fa-dollar-sign"></i>
                    </div>

                </div>

            </section>

            <!-- COLUMNA DERECHA: HISTORIAL DE ALQUILERES -->
            <section class="table-container">

                <div class="table-header-title">
                    <h3>
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Actividad reciente de tus clientes
                    </h3>
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
                                        <strong>
                                            <?php echo htmlspecialchars($renta['cliente']); ?>
                                        </strong>
                                        <br>

                                        <small style="color: #8e8e93;">
                                            <?php echo htmlspecialchars($renta['correo']); ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $renta['marca'] . ' ' . $renta['modelo']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <code>
                                            <?php echo htmlspecialchars($renta['placa']); ?>
                                        </code>
                                    </td>

                                    <td>
                                        <?php
                                        echo date(
                                            'd/m/Y',
                                            strtotime($renta['fecha_inicio'])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo date(
                                            'd/m/Y',
                                            strtotime($renta['fecha_fin'])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <strong>
                                            $<?php echo number_format($renta['monto_total'], 2); ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($renta['estado']); ?>">
                                            <?php echo htmlspecialchars($renta['estado']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <select
                                            class="select-estado"
                                            onchange="cambiarEstadoReserva(
                                                <?php echo (int) $renta['id_reserva']; ?>,
                                                this.value
                                            )">

                                            <option value="activa"
                                                <?php echo ($renta['estado'] === 'activa') ? 'selected' : ''; ?>>
                                                Activa (Rentado)
                                            </option>

                                            <option value="completada"
                                                <?php echo ($renta['estado'] === 'completada') ? 'selected' : ''; ?>>
                                                Completada (Disponible)
                                            </option>

                                            <option value="cancelada"
                                                <?php echo ($renta['estado'] === 'cancelada') ? 'selected' : ''; ?>>
                                                Cancelada (Disponible)
                                            </option>

                                        </select>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>
                    </table>

                <?php else: ?>

                    <div class="no-data"
                         style="text-align: center; padding: 30px; color: #888;">

                        <i class="fa-solid fa-face-smile"
                           style="font-size: 2.5rem; margin-bottom: 10px; color: #555;">
                        </i>

                        <p>
                            Todavía no registras alquileres.
                            ¡Tus próximos clientes están por llegar!
                        </p>

                    </div>

                <?php endif; ?>

            </section>

        </div>
        <!-- FIN DE LA DISTRIBUCIÓN PRINCIPAL -->

    </main>

    <!-- SCRIPTS -->
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
                    alert(
                        'No pudimos actualizar el estado: ' +
                        (data.message || 'Inténtalo de nuevo')
                    );
                }
            })
            .catch(error => {
                console.error('Error:', error);

                alert(
                    'Ocurrió un pequeño problema al procesar tu solicitud.'
                );
            });
        }
    </script>

</body>
</html>
