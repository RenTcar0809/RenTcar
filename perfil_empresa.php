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

$idProveedor = $_SESSION['id_proveedor'] ?? 0;
$mensaje = "";
$tipoMensaje = "";

// 2. PROCESAR ACTUALIZACIÓN DEL PERFIL
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreEmpresa = trim($_POST['nombre_empresa'] ?? '');
    $correo        = trim($_POST['correo'] ?? '');
    $telefono      = trim($_POST['telefono'] ?? '');
    $direccion     = trim($_POST['direccion'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? '');

    if (!empty($nombreEmpresa) && !empty($correo)) {
        try {
            $sql = "UPDATE proveedor 
                    SET nombre_empresa = :nombre, correo = :correo, telefono = :telefono, direccion = :direccion, descripcion = :descripcion 
                    WHERE id_proveedor = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre'      => $nombreEmpresa,
                ':correo'      => $correo,
                ':telefono'    => $telefono,
                ':direccion'   => $direccion,
                ':descripcion' => $descripcion,
                ':id'          => $idProveedor
            ]);

            // Actualizar la sesión
            $_SESSION['nombre_empresa'] = $nombreEmpresa;
            
            $mensaje = "¡Datos de la empresa actualizados correctamente!";
            $tipoMensaje = "exito";
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar los datos en la base de datos.";
            $tipoMensaje = "error";
        }
    } else {
        $mensaje = "El nombre de la empresa y el correo son obligatorios.";
        $tipoMensaje = "error";
    }
}

// 3. OBTENER DATOS ACTUALES
try {
    $sql = "SELECT * FROM proveedor WHERE id_proveedor = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $idProveedor]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $empresa = [];
}

// Variables para vista
$nombreEmpresa  = $empresa['nombre_empresa'] ?? $_SESSION['nombre_empresa'] ?? 'Mi Empresa';
$inicialEmpresa = strtoupper(substr($nombreEmpresa, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de Empresa - <?php echo htmlspecialchars($nombreEmpresa); ?></title>
    
    <!-- Iconos FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    
    <!-- Hoja de Estilos -->
    <link rel="stylesheet" href="DatosEmpresa.css?v=<?php echo time(); ?>">
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
            <a href="clientes.php" class="nav-link">
                <i class="fa-solid fa-users"></i>
                <span>Clientes</span>
            </a>
            <a href="perfil_empresa.php" class="nav-link active highlight-company">
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
                <h2>Perfil de la Empresa</h2>
                <p>Gestiona la información pública y de contacto de tu negocio de alquiler.</p>
            </div>
        </div>

        <!-- Alerta de Respuesta -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert-box <?php echo $tipoMensaje; ?>">
                <i class="fa-solid <?php echo ($tipoMensaje === 'exito') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                <span><?php echo $mensaje; ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario de Edición -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="big-avatar"><?php echo $inicialEmpresa; ?></div>
                <div class="profile-title">
                    <h3><?php echo htmlspecialchars($nombreEmpresa); ?></h3>
                    <p>Proveedor de Vehículos Registrado</p>
                </div>
            </div>

            <form action="perfil_empresa.php" method="POST" class="profile-form">
                <div class="form-grid">
                    
                    <!-- Nombre de Empresa -->
                    <div class="input-group">
                        <label for="nombre_empresa"><i class="fa-solid fa-building"></i> Nombre de la Empresa</label>
                        <input type="text" id="nombre_empresa" name="nombre_empresa" value="<?php echo htmlspecialchars($empresa['nombre_empresa'] ?? ''); ?>" required>
                    </div>

                    <!-- Correo Electrónico -->
                    <div class="input-group">
                        <label for="correo"><i class="fa-regular fa-envelope"></i> Correo Electrónico</label>
                        <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($empresa['correo'] ?? ''); ?>" required>
                    </div>

                    <!-- Teléfono -->
                    <div class="input-group">
                        <label for="telefono"><i class="fa-solid fa-phone"></i> Teléfono / WhatsApp</label>
                        <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($empresa['telefono'] ?? ''); ?>">
                    </div>

                    <!-- Dirección -->
                    <div class="input-group">
                        <label for="direccion"><i class="fa-solid fa-location-dot"></i> Dirección Física</label>
                        <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($empresa['direccion'] ?? ''); ?>">
                    </div>

                </div>

                <!-- Descripción -->
                <div class="input-group full-width">
                    <label for="descripcion"><i class="fa-solid fa-align-left"></i> Descripción de la Empresa</label>
                    <textarea id="descripcion" name="descripcion" rows="4" placeholder="Cuéntale a tus clientes sobre tu empresa..."><?php echo htmlspecialchars($empresa['descripcion'] ?? ''); ?></textarea>
                </div>

                <!-- Botón Guardar -->
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

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