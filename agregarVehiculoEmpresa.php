<?php
session_start();
require_once 'conexion.php'; // Aquí se debe definir la variable $pdo

// 1. Verificar si el usuario ha iniciado sesión
// Si no existe la sesión 'id_proveedor', redirige al login
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php"); 
    exit();
}

// 2. Extraer datos básicos de la sesión para usar en la página
$idUsuarioLogueado = $_SESSION['id_proveedor'];
$nombreEmpresa     = $_SESSION['nombre_empresa'] ?? 'Mi Empresa';
$inicialEmpresa    = strtoupper(substr($nombreEmpresa, 0, 1));

// A partir de aquí colocarías el resto de tu lógica o el HTML de la página
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Vehículo - RentCar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="AgVe.css">
</head>
<body>

    <div class="main-container">
        <!-- Encabezado -->
        <header class="header">
            <div class="header-left">
                <a href="vehiculosE.php" class="btn-back" title="Volver a mis vehículos">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div class="logo-title">
                    <h1><i class="fa-solid fa-car-side"></i> RentCar</h1>
                    <p class="subtitle">Añadir nuevo vehículo al catálogo</p>
                </div>
            </div>

            <div class="user-profile">
                <div class="avatar"><?php echo $inicialEmpresa; ?></div>
                <span class="company-name"><?php echo htmlspecialchars($nombreEmpresa); ?></span>
            </div>
        </header>

        <!-- Mensajes de Estado / Alertas -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipoMensaje; ?>">
                <i class="fa-solid <?php echo ($tipoMensaje === 'error') ? 'fa-triangle-exclamation' : 'fa-circle-check'; ?>"></i>
                <span><?php echo htmlspecialchars($mensaje); ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario Principal -->
        <form id="carForm" action="agregar_vehiculo.php" method="POST" enctype="multipart/form-data" class="form-card">
            
            <div class="form-section-title">
                <i class="fa-solid fa-sliders"></i> Datos Básicos del Vehículo
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="tipo"><i class="fa-solid fa-layer-group"></i> Tipo de Vehículo *</label>
                    <select id="tipo" name="tipo" required>
                        <option value="Carro">Carro (Automóvil)</option>
                        <option value="Motocicleta">Motocicleta</option>
                        <option value="Camioneta">Camioneta / SUV</option>
                        <option value="Van">Van / Microbús</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="marca"><i class="fa-solid fa-copyright"></i> Marca *</label>
                    <input type="text" id="marca" name="marca" placeholder="Ej: Toyota, Yamaha, Chevrolet" required>
                </div>

                <div class="form-group">
                    <label for="modelo"><i class="fa-solid fa-car"></i> Modelo / Línea *</label>
                    <input type="text" id="modelo" name="modelo" placeholder="Ej: Corolla 2023" required>
                </div>

                <div class="form-group">
                    <label for="placa"><i class="fa-solid fa-id-card"></i> Placa *</label>
                    <input type="text" id="placa" name="placa" placeholder="Ej: ABC123" maxlength="10" required>
                </div>

                <div class="form-group">
                    <label for="precio"><i class="fa-solid fa-dollar-sign"></i> Precio por Día ($) *</label>
                    <input type="number" id="precio" name="precio" min="0" step="1" required>
                </div>

                <div class="form-group">
                    <label for="color"><i class="fa-solid fa-palette"></i> Color *</label>
                    <input type="text" id="color" name="color" required>
                </div>
            </div>

            <div class="form-section-title">
                <i class="fa-solid fa-gears"></i> Especificaciones Técnicas
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="transmision"><i class="fa-solid fa-gears"></i> Transmisión *</label>
                    <select id="transmision" name="transmision" required>
                        <option value="Automática">Automática</option>
                        <option value="Manual">Manual</option>
                        <option value="Semiautomática">Semiautomática</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="motor"><i class="fa-solid fa-microchip"></i> Cilindraje / Motor *</label>
                    <input type="text" id="motor" name="motor" required>
                </div>

                <div class="form-group">
                    <label for="traccion"><i class="fa-solid fa-dharmachakra"></i> Tracción *</label>
                    <input type="text" id="traccion" name="traccion" required>
                </div>

                <div class="form-group">
                    <label for="asientos"><i class="fa-solid fa-chair"></i> N° de Asientos</label>
                    <input type="number" id="asientos" name="asientos">
                </div>

                <div class="form-group">
                    <label for="num_motor"><i class="fa-solid fa-barcode"></i> N° de Motor</label>
                    <input type="text" id="num_motor" name="num_motor">
                </div>

                <div class="form-group">
                    <label for="num_chasis"><i class="fa-solid fa-shield-halved"></i> N° de Chasis</label>
                    <input type="text" id="num_chasis" name="num_chasis">
                </div>
            </div>

            <div class="photos-section">
                <div class="photos-header">
                    <label class="photos-title"><i class="fa-solid fa-images"></i> Fotografías del Vehículo *</label>
                    <span id="photoCounter" class="photo-badge">0 / 4 Seleccionadas</span>
                </div>

                <label class="dropzone" id="dropzone" for="inputImagenes">
                    <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
                    <p class="dropzone-text">Arrastra tus 4 fotos aquí o haz clic</p>
                </label>

                <input type="file" id="inputImagenes" name="imagenes[]" multiple accept="image/*" style="display:none;">
                <div class="preview-grid" id="previewGrid"></div>
            </div>

            <div class="form-actions">
                <a href="mis_vehiculos.php" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-submit">Registrar Vehículo</button>
            </div>
        </form>
    </div>

    <!-- Asegúrate de que el nombre del archivo JS sea correcto -->
    <script src="AgVe.js"></script>
</body>
</html>