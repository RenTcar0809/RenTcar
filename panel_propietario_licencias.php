<?php
session_start();
require_once 'conexion.php';

// 1. Validar que cualquier tipo de usuario propietario/empresa tenga sesión activa
if (!isset($_SESSION['tipo']) && !isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

// Identificar datos del usuario conectado (sea empresa o particular)
$tipoUsuario = $_SESSION['tipo'] ?? 0;
$idUsuario = $_SESSION['IdUsuario'] ?? $_SESSION['id_proveedor'] ?? $_SESSION['id_usuario'] ?? 0;
$nombreUsuario = $_SESSION['nombre_empresa'] ?? $_SESSION['usuario_nombre'] ?? 'Propietario';
$inicialUsuario = strtoupper(substr($nombreUsuario, 0, 1));

$mensaje = "";
$tipoAlerta = "";

// 2. Procesar la decisión (Aprobar o Rechazar)
if (isset($_GET['accion']) && isset($_GET['id_solicitante'])) {
    $accion = $_GET['accion'];
    $idSolicitante = intval($_GET['id_solicitante']);

    try {
        if ($accion === 'aprobar') {
            $stmt = $pdo->prepare("UPDATE usuario SET licencia_verificada = 1 WHERE id_usuario = :id");
            $stmt->execute([':id' => $idSolicitante]);
            $mensaje = "Has aprobado la licencia del solicitante correctamente.";
            $tipoAlerta = "success";
        } elseif ($accion === 'rechazar') {
            $stmt = $pdo->prepare("UPDATE usuario SET licencia_numero = NULL, licencia_imagen = NULL, licencia_verificada = 0 WHERE id_usuario = :id");
            $stmt->execute([':id' => $idSolicitante]);
            $mensaje = "Has rechazado la licencia. Se le solicitará al usuario que cargue un documento válido.";
            $tipoAlerta = "error";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar el estado: " . $e->getMessage();
        $tipoAlerta = "error";
    }
}

// 3. Consultar solicitantes utilizando los nombres exactos de tu PostgreSQL (id_usuario, num_telefono)
$solicitantes = [];
$errorConsulta = null;

try {
    if ($tipoUsuario == 1) {
        // Consulta para EMPRESAS (usan id_proveedor en la tabla vehiculo)
        $sql = "
            SELECT DISTINCT 
                u.id_usuario, u.nombre, u.correo, u.num_telefono,
                u.licencia_numero, u.licencia_imagen, u.licencia_verificada,
                v.marca, v.modelo, v.placa
            FROM vehiculo v
            INNER JOIN mensajes_chat m ON m.id_vehiculo = v.id_v
            INNER JOIN usuario u ON (u.nombre = m.remitente OR u.empresa = m.remitente)
            WHERE v.id_proveedor = :id_ref
              AND u.id_usuario != :id_self
              AND u.licencia_numero IS NOT NULL 
              AND u.licencia_numero != ''
            ORDER BY u.licencia_verificada ASC, u.id_usuario DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_ref' => $idUsuario, ':id_self' => $idUsuario]);

    } else {
        // Consulta para USUARIOS PARTICULARES
        $sql = "
            SELECT DISTINCT 
                u.id_usuario, u.nombre, u.correo, u.num_telefono,
                u.licencia_numero, u.licencia_imagen, u.licencia_verificada,
                v.marca, v.modelo, v.placa
            FROM vehiculo v
            INNER JOIN mensajes_chat m ON m.id_vehiculo = v.id_v
            INNER JOIN usuario u ON (u.nombre = m.remitente OR u.empresa = m.remitente)
            WHERE v.id_arrendatario = :id_ref_arrendatario
              AND u.id_usuario != :id_self_2
              AND u.licencia_numero IS NOT NULL 
              AND u.licencia_numero != ''
            ORDER BY u.licencia_verificada ASC, u.id_usuario DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_ref_arrendatario' => $idUsuario, ':id_self_2' => $idUsuario]);
    }

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
</head>
<body>

    <!-- BARRA DE NAVEGACIÓN DINÁMICA -->
    <header class="top-navbar">
        <div class="logo-container">
            <h1 class="logo-text">
                <span class="red-t">REN</span>T<span class="red-t">CAR</span>
            </h1>
        </div>

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
                <?php if ($tipoUsuario == 1): ?>
                    <a href="perfil_empresa.php"><i class="fa-solid fa-user-gear"></i> Configuración</a>
                <?php endif; ?>
                <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
            </div>
        </div>
    </header>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">
        <div class="container">
            <h2 class="section-title" style="margin-bottom: 15px;">
                <i class="fa-solid fa-id-card-clip" style="color: #e50914;"></i>
                Verificación de Licencias de Solicitantes
            </h2>

            <!-- AVISO RUNT -->
            <div class="runt-notice">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <h4>Verificación Recomendada en el RUNT antes de Aprobar</h4>
                    <p>Como propietario del vehículo, valida la autenticidad, vigencia y estado de la licencia de conducción del solicitante directamente en la plataforma oficial del <strong>RUNT (Registro Único Nacional de Tránsito)</strong> usando su número de documento antes de autorizar el alquiler.</p>
                    <a href="https://www.runt.gov.co/consultaCiudadana/#/consultaPersona" target="_blank" rel="noopener noreferrer" class="btn-runt">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Consultar en el RUNT
                    </a>
                </div>
            </div>

            <?php if ($errorConsulta): ?>
                <div class="alert error">
                    <i class="fa-solid fa-triangle-exclamation"></i> Error en la consulta: <?php echo htmlspecialchars($errorConsulta); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensaje)): ?>
                <div class="alert <?php echo $tipoAlerta; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <?php if (empty($solicitantes)): ?>
                <div class="card empty-card">
                    <i class="fa-regular fa-folder-open" style="font-size: 2.2rem; color: #555; margin-bottom: 10px;"></i>
                    <p>No tienes solicitantes con licencias pendientes o registradas en tus vehículos por el momento.</p>
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
                            <p><strong>Nro. Licencia / Documento:</strong> <code><?php echo htmlspecialchars($sol['licencia_numero']); ?></code></p>
                            <p style="margin-top: 8px;">
                                <strong>Estado:</strong> 
                                <?php if ((int)$sol['licencia_verificada'] === 1): ?>
                                    <span class="badge-aprobado"><i class="fa-solid fa-circle-check"></i> Licencia Aprobada</span>
                                <?php else: ?>
                                    <span class="badge-pendiente"><i class="fa-solid fa-clock"></i> Pendiente de revisión</span>
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
                                   class="btn btn-aprobar" 
                                   onclick="return confirm('¿Confirmas que verificaste los datos en el RUNT y deseas APROBAR esta licencia?');">
                                    <i class="fa-solid fa-check"></i> Aprobar
                                </a>
                                <a href="panel_propietario_licencias.php?accion=rechazar&id_solicitante=<?php echo $sol['id_usuario']; ?>" 
                                   class="btn btn-rechazar" 
                                   onclick="return confirm('¿Estás seguro de RECHAZAR esta licencia? El solicitante deberá subirla nuevamente.');">
                                    <i class="fa-solid fa-xmark"></i> Rechazar
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