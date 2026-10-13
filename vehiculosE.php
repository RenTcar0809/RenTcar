<?php
session_start();
require_once 'conexion.php';

// 1. SEGURIDAD: Verificar sesión activa
if (!isset($_SESSION['IdUsuario']) && !isset($_SESSION['nombre_empresa'])) {
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



// Variables para vehículos y contadores
$vehiculos = [];
$totalVehiculos = 0;
$totalDisponibles = 0;
$totalAlquilados = 0;

try {
    // CORRECCIÓN: Se cambió 'id_vehiculo' por 'id_v' según la estructura de tu base de datos
    $stmt = $pdo->prepare("SELECT * FROM vehiculo WHERE id_proveedor = :id ORDER BY id_v DESC");
    $stmt->execute([':id' => $idProveedor]);
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contar según el estado para la barra resumen
    foreach ($vehiculos as $v) {
        $totalVehiculos++;
        $estado = strtolower($v['estado'] ?? 'disponible');
        if ($estado === 'disponible') {
            $totalDisponibles++;
        } elseif ($estado === 'alquilado') {
            $totalAlquilados++;
        }
    }
} catch (PDOException $e) {
    $vehiculos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Flota - <?php echo htmlspecialchars($nombreEmpresa); ?></title>
    
    <!-- Iconos FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    
    <!-- Hoja de Estilos Independiente -->
    <link rel="stylesheet" href="flota_estilos.css?v=<?php echo time(); ?>">
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
            <a href="vehiculosE.php" class="nav-link active">
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
        
        <div class="header-banner">
            <div class="header-info">
                <h2>Inventario de Flota</h2>
                <p>Gestión rápida e inventario detallado de tus autos.</p>
            </div>
            <a href="agregarVehiculoEmpresa.php" class="btn-new-car">
                <i class="fa-solid fa-plus"></i> Registrar Auto
            </a>
        </div>

        <!-- Contadores Rápidos -->
        <div class="fleet-stats-bar">
            <div class="stat-pill active">
                <i class="fa-solid fa-car"></i>
                <span>Todos: <strong><?php echo $totalVehiculos; ?></strong></span>
            </div>
            <div class="stat-pill status-ok">
                <i class="fa-solid fa-circle-check"></i>
                <span>Disponibles: <strong><?php echo $totalDisponibles; ?></strong></span>
            </div>
            <div class="stat-pill status-busy">
                <i class="fa-solid fa-key"></i>
                <span>Alquilados: <strong><?php echo $totalAlquilados; ?></strong></span>
            </div>
        </div>

        <!-- Lista Estilo Tabla de Vehículos -->
        <section class="fleet-list-container">
            <?php if (!empty($vehiculos)): ?>
                <div class="table-responsive">
                    <table class="fleet-table">
                        <thead>
                            <tr>
                                <th>Vehículo</th>
                                <th>Placa</th>
                                <th>Precio / Día</th>
                                <th>Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehiculos as $carro): ?>
                                <tr>
                                    <td class="col-vehicle">
                                        <div class="car-thumb">
                                            <?php if (!empty($carro['imagen'])): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($carro['imagen']); ?>" alt="Foto">
                                            <?php else: ?>
                                                <i class="fa-solid fa-car"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="car-details">
                                            <span class="car-title"><?php echo htmlspecialchars(($carro['marca'] ?? '') . ' ' . ($carro['modelo'] ?? '')); ?></span>
                                        </div>
                                    </td>

                                    <td><span class="plate-tag"><?php echo htmlspecialchars($carro['placa'] ?? 'N/A'); ?></span></td>

                                    <td><span class="price-tag">$<?php echo number_format($carro['precio_dia'] ?? $carro['precio'] ?? 0, 2); ?></span></td>

                                    <td>
                                        <?php 
                                            $eTexto = $carro['estado'] ?? 'Disponible';
                                            $eClass = 'badge-disponible';
                                            if (strtolower($eTexto) === 'alquilado') {
                                                $eClass = 'badge-alquilado';
                                            } elseif (strtolower($eTexto) === 'mantenimiento') {
                                                $eClass = 'badge-mantenimiento';
                                            }
                                        ?>
                                        <span class="badge-status <?php echo $eClass; ?>">
                                            <?php echo htmlspecialchars($eTexto); ?>
                                        </span>
                                    </td>

                                    <td style="text-align: center;">
                                        <div class="action-buttons">
                                            <!-- CORRECCIÓN: Se cambió 'id_vehiculo' por 'id_v' -->
                                            <a href="procesar_editar.php?id=<?php echo $carro['id_v']; ?>" class="btn-icon btn-edit" title="Editar">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <!-- CORRECCIÓN: Se cambió 'id_vehiculo' por 'id_v' -->
                                            <a href="eliminar_vehiculo.php?id=<?php echo $carro['id_v']; ?>" class="btn-icon btn-delete" title="Eliminar" onclick="return confirm('¿Seguro que deseas eliminar este vehículo?');">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-car-burst"></i>
                    <h3>No tienes autos guardados</h3>
                    <p>Agrega vehículos a tu lista para comenzar a recibir solicitudes.</p>
                    <a href="agregarVehiculoEmpresa.php" class="btn-new-car" style="margin-top: 15px;">
                        <i class="fa-solid fa-plus"></i> Registrar Vehículo
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </main>

<!-- Agrega esto justo antes de cerrar la etiqueta </body> -->
<script>
    document.querySelector('input[name="imagenes[]"]').addEventListener('change', function(e) {
        // Creamos un contenedor para las vistas previas si no existe
        let previewContainer = document.getElementById('preview-box');
        if (!previewContainer) {
            previewContainer = document.createElement('div');
            previewContainer.id = 'preview-box';
            previewContainer.style.marginTop = '10px';
            previewContainer.style.display = 'flex';
            previewContainer.style.gap = '10px';
            previewContainer.style.flexWrap = 'wrap';
            this.parentNode.appendChild(previewContainer);
        }
        
        // Limpiamos previsualizaciones anteriores
        previewContainer.innerHTML = '<div style="font-size:0.8rem; color:#888; width:100%">Nuevas imágenes seleccionadas:</div>';

        // Recorremos los archivos seleccionados
        const files = e.target.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.style.width = '90px';
                    img.style.height = '65px';
                    img.style.objectFit = 'cover';
                    img.style.borderRadius = '6px';
                    img.style.border = '1px solid #ff3b30';
                    previewContainer.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        }
    });
</script>
</body>
</html>