<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['tipo']) && !isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

$tipoUsuario = $_SESSION['tipo'] ?? 0;
$idUsuario = $_SESSION['IdUsuario'] ?? $_SESSION['id_proveedor'] ?? $_SESSION['id_usuario'] ?? 0;
$nombreUsuario = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Propietario';
$inicialUsuario = strtoupper(substr($nombreUsuario, 0, 1));

$mensaje = "";
$tipoAlerta = "";

// 2. Procesar acciones de aprobación individual
if (isset($_GET['accion']) && isset($_GET['id_solicitante'])) {
    $accion = $_GET['accion'];
    $idSolicitante = intval($_GET['id_solicitante']);

    try {
        if ($accion === 'aprobar') {
            $stmt = $pdo->prepare("
                INSERT INTO aprobaciones_licencias (id_proveedor, id_usuario, estado, fecha_actualizacion) 
                VALUES (:prov, :cli, 1, CURRENT_TIMESTAMP)
                ON CONFLICT (id_proveedor, id_usuario) 
                DO UPDATE SET estado = 1, fecha_actualizacion = CURRENT_TIMESTAMP
            ");
            $stmt->execute([':prov' => $idUsuario, ':cli' => $idSolicitante]);
            $mensaje = "Has aprobado la licencia de este usuario correctamente.";
            $tipoAlerta = "success";
        } elseif ($accion === 'rechazar') {
            $stmt = $pdo->prepare("
                INSERT INTO aprobaciones_licencias (id_proveedor, id_usuario, estado, fecha_actualizacion) 
                VALUES (:prov, :cli, 2, CURRENT_TIMESTAMP)
                ON CONFLICT (id_proveedor, id_usuario) 
                DO UPDATE SET estado = 2, fecha_actualizacion = CURRENT_TIMESTAMP
            ");
            $stmt->execute([':prov' => $idUsuario, ':cli' => $idSolicitante]);
            $mensaje = "Has rechazado la licencia de este usuario.";
            $tipoAlerta = "error";
        } elseif ($accion === 'eliminar') {
            $stmt = $pdo->prepare("DELETE FROM aprobaciones_licencias WHERE id_proveedor = :prov AND id_usuario = :cli");
            $stmt->execute([':prov' => $idUsuario, ':cli' => $idSolicitante]);
            $mensaje = "La solicitud ha sido retirada de tu panel.";
            $tipoAlerta = "success";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar: " . $e->getMessage();
        $tipoAlerta = "error";
    }
}

// 3. Consulta optimizada: Muestra usuarios que te escribieron por tus vehículos O tienen una solicitud contigo
// 3. Consulta directa sobre solicitudes registradas para este propietario
$solicitantes = [];
$errorConsulta = null;

try {
    $sql = "
        SELECT DISTINCT 
            u.id_usuario, 
            u.nombre, 
            u.correo, 
            u.num_telefono,
            u.licencia_numero, 
            u.licencia_imagen,
            a.estado AS licencia_verificada,
            COALESCE(v.marca, 'Flota') AS marca, 
            COALESCE(v.modelo, 'General') AS modelo, 
            COALESCE(v.placa, 'N/A') AS placa
        FROM aprobaciones_licencias a
        INNER JOIN usuario u ON a.id_usuario = u.id_usuario
        LEFT JOIN vehiculo v ON v.id_proveedor = a.id_proveedor
        WHERE a.id_proveedor = :id_ref
          AND u.id_usuario != :id_self
          AND u.licencia_numero IS NOT NULL 
          AND u.licencia_numero != ''
        ORDER BY a.estado ASC, u.id_usuario DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_ref' => $idUsuario, ':id_self' => $idUsuario]);
    $solicitantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $errorConsulta = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Licencias - <?php echo htmlspecialchars($nombreUsuario); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admindashboard.css">
    <link rel="stylesheet" href="panel_propietario.css">
    <style>
        .badge-estado { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 6px; font-size: 0.82rem; font-weight: 600; }
        .badge-estado.pendiente { background-color: rgba(255, 159, 10, 0.15); color: #fbbf24; border: 1px solid rgba(255, 159, 10, 0.3); }
        .badge-estado.aprobado { background-color: rgba(52, 199, 89, 0.15); color: #4ade80; border: 1px solid rgba(52, 199, 89, 0.3); }
        .badge-estado.rechazado { background-color: rgba(229, 9, 20, 0.15); color: #f87171; border: 1px solid rgba(229, 9, 20, 0.3); }
        .btn-eliminar { background-color: #3f3f46; color: #fff; }
        .btn-eliminar:hover { background-color: #52525b; }
    </style>
</head>
<body>

    <header class="top-navbar">
        <div class="logo-container"><h1 class="logo-text"><span class="red-t">REN</span>T<span class="red-t">CAR</span></h1></div>
        <nav class="nav-menu">
            <?php if ($tipoUsuario == 1): ?>
                <a href="dashboardE.php" class="nav-link"><i class="fa-solid fa-chart-pie"></i> <span>Panel</span></a>
                <a href="mensajese.php" class="nav-link"><i class="fa-solid fa-comments"></i> <span>Mensajes</span></a>
                <a href="vehiculosE.php" class="nav-link"><i class="fa-solid fa-car-side"></i> <span>Mi Flota</span></a>
                <a href="alquileres.php" class="nav-link"><i class="fa-solid fa-calendar-check"></i> <span>Alquileres</span></a>
                <a href="panel_propietario_licencias.php" class="nav-link active"><i class="fa-solid fa-id-card"></i> <span>Licencias</span></a>
                <a href="clientes.php" class="nav-link"><i class="fa-solid fa-users"></i> <span>Clientes</span></a>
                <a href="perfil_empresa.php" class="nav-link highlight-company"><i class="fa-solid fa-building"></i> <span>Mi Empresa</span></a>
            <?php else: ?>
                <a href="dashboardf.php" class="nav-link"><i class="fa-solid fa-house"></i> <span>Inicio</span></a>
                <a href="panel_propietario_licencias.php" class="nav-link active"><i class="fa-solid fa-id-card"></i> <span>Licencias</span></a>
            <?php endif; ?>
        </nav>
        <div class="user-container">
            <div class="user-profile" id="userProfileBtn">
                <div class="user-avatar"><?php echo htmlspecialchars($inicialUsuario); ?></div>
                <span><?php echo htmlspecialchars($nombreUsuario); ?></span>
                <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem; color: #8e8e93;"></i>
            </div>
            <div class="dropdown-content" id="userDropdown">
                <?php if ($tipoUsuario == 1): ?><a href="perfil_empresa.php"><i class="fa-solid fa-user-gear"></i> Configuración</a><?php endif; ?>
                <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <h2 class="section-title" style="margin-bottom: 15px;">
                <i class="fa-solid fa-id-card-clip" style="color: #e50914;"></i> Verificación Individual de Licencias
            </h2>

            <div class="runt-notice">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <h4>Verificación en el RUNT</h4>
                    <p>Valida la autenticidad de la licencia en el <strong>RUNT</strong> antes de aprobar la solicitud para tus vehículos.</p>
                    <a href="https://portalpublico.runt.gov.co/#/consulta-ciudadano-documento/consulta/consulta-ciudadano-documento" target="_blank" rel="noopener noreferrer" class="btn-runt">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Consultar en el RUNT
                    </a>
                </div>
            </div>

            <?php if ($errorConsulta): ?>
                <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> Error: <?php echo htmlspecialchars($errorConsulta); ?></div>
            <?php endif; ?>

            <?php if (!empty($mensaje)): ?>
                <div class="alert <?php echo $tipoAlerta; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <?php if (empty($solicitantes)): ?>
                <div class="card empty-card">
                    <i class="fa-regular fa-folder-open" style="font-size: 2.2rem; color: #555; margin-bottom: 10px;"></i>
                    <p>No tienes solicitantes con licencias pendientes para tus vehículos por el momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($solicitantes as $sol): ?>
                    <div class="card">
                        <div class="info">
                            <span class="vehiculo-tag">
                                <i class="fa-solid fa-car"></i> Interesado en: <?php echo htmlspecialchars($sol['marca'] . ' ' . $sol['modelo'] . ' (' . $sol['placa'] . ')'); ?>
                            </span>
                            <h3><?php echo htmlspecialchars($sol['nombre']); ?></h3>
                            <p><strong>Correo:</strong> <?php echo htmlspecialchars($sol['correo']); ?> | <strong>Tel:</strong> <?php echo htmlspecialchars($sol['num_telefono'] ?? 'N/A'); ?></p>
                            <p><strong>Nro. Licencia:</strong> <code><?php echo htmlspecialchars($sol['licencia_numero']); ?></code></p>
                            <p style="margin-top: 8px; display: flex; align-items: center; gap: 8px;">
                                <strong>Estado para ti:</strong> 
                                <?php if ((int)$sol['licencia_verificada'] === 1): ?>
                                    <span class="badge-estado aprobado"><i class="fa-solid fa-circle-check"></i> Aprobada por ti</span>
                                <?php elseif ((int)$sol['licencia_verificada'] === 2): ?>
                                    <span class="badge-estado rechazado"><i class="fa-solid fa-circle-xmark"></i> Rechazada</span>
                                <?php else: ?>
                                    <span class="badge-estado pendiente"><i class="fa-solid fa-clock"></i> Pendiente de revisión</span>
                                <?php endif; ?>
                            </p>
                        </div>

                        <div class="acciones">
                            <?php if (!empty($sol['licencia_imagen'])): ?>
                                <a href="<?php echo htmlspecialchars($sol['licencia_imagen']); ?>" target="_blank" class="btn btn-ver">
                                    <i class="fa-solid fa-eye"></i> Ver Licencia
                                </a>
                            <?php endif; ?>

                            <?php if ((int)$sol['licencia_verificada'] !== 1): ?>
                                <a href="panel_propietario_licencias.php?accion=aprobar&id_solicitante=<?php echo $sol['id_usuario']; ?>" 
                                   class="btn btn-aprobar" onclick="return confirm('¿Aprobar esta licencia para tus vehículos?');">
                                    <i class="fa-solid fa-check"></i> Aprobar
                                </a>
                                <a href="panel_propietario_licencias.php?accion=rechazar&id_solicitante=<?php echo $sol['id_usuario']; ?>" 
                                   class="btn btn-rechazar" onclick="return confirm('¿Rechazar esta licencia?');">
                                    <i class="fa-solid fa-xmark"></i> Rechazar
                                </a>
                            <?php else: ?>
                                <a href="panel_propietario_licencias.php?accion=eliminar&id_solicitante=<?php echo $sol['id_usuario']; ?>" 
                                   class="btn btn-eliminar" onclick="return confirm('¿Quitar esta tarjeta de tu panel?');">
                                    <i class="fa-solid fa-trash-can"></i> Limpiar / Borrar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
    <script>
        const btn = document.getElementById('userProfileBtn');
        const dropdown = document.getElementById('userDropdown');
        btn.addEventListener('click', (e) => { e.stopPropagation(); dropdown.classList.toggle('show'); });
        window.addEventListener('click', () => { if (dropdown.classList.contains('show')) dropdown.classList.remove('show'); });
    </script>
</body>
</html>