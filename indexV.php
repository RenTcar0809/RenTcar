<?php
// ¡IMPORTANTE! Esto siempre debe ir en la línea 1 para que funcionen las sesiones y mensajes de error
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentCar - Registro de Vehículo Inteligente</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="indexv.css?v=<?php echo filemtime('indexv.css'); ?>">
</head>
<body>

    <header class="navbar">
        <div class="nav-content">
            <a href="dashboardf.php" class="logo-container" aria-label="Ir al panel principal">
                <span class="logo-text">
                    <span class="txt-red">REN</span><span class="txt-black">T</span><span class="txt-red">CAR</span>
                </span>
                <img src="unnamed.png" alt="Logo Carro" class="logo-img">
            </a>
        </div>
    </header>

    <main class="container">
        <div class="form-card">
            <header class="card-header">
                <h1 class="main-title">REGISTRO INTELIGENTE DE VEHÍCULO</h1>
                <p class="subtitle">Bienvenido, <strong><?php echo isset($_SESSION['usuario_nombre']) ? htmlspecialchars($_SESSION['usuario_nombre']) : 'Usuario'; ?></strong>. Sube la foto de la matrícula y selecciona los datos correspondientes.</p>
            </header>

            <!-- EL FORMULARIO DEBE TENER enctype="multipart/form-data" PARA ENVIAR ARCHIVOS -->
            <form action="procesar_vehiculo.php" method="POST" enctype="multipart/form-data">
                
                <!-- SECCIÓN DE FOTO DE MATRÍCULA Y VISTA PREVIA -->
                <div style="background: rgba(255, 0, 0, 0.05); border: 2px dashed #ff4444; padding: 20px; border-radius: 12px; margin-bottom: 25px; text-align: center;">
                    <h3 style="color: #fff; margin-bottom: 8px;">📷 Tarjeta de Propiedad / Matrícula</h3>
                    <p style="color: #aaa; font-size: 0.9rem; margin-bottom: 15px;">Sube una foto clara del documento oficial del vehículo.</p>
                    
                    <!-- Input sin 'required' para evitar bloqueos por estar oculto (PHP lo valida de forma segura) -->
                    <input type="file" name="imagen_matricula" id="imagenMatricula" accept="image/*" style="display: none;">
                    
                    <button type="button" onclick="document.getElementById('imagenMatricula').click()" style="background: #ff4444; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer;">
                        📁 Seleccionar Imagen de Matrícula
                    </button>
                    
                    <p id="nombreArchivo" style="color: #4CAF50; font-weight: bold; margin-top: 10px; font-size: 0.9rem;"></p>

                    <!-- CONTENEDOR DE LA VISTA PREVIA DE LA IMAGEN -->
                    <div id="previewContainer" style="margin-top: 15px; display: none;">
                        <p style="color: #bbb; font-size: 0.85rem; margin-bottom: 5px;">Vista previa:</p>
                        <img id="imagenPreview" src="" alt="Vista previa de matrícula" style="max-width: 200px; max-height: 150px; border-radius: 8px; border: 2px solid #ff4444; object-fit: cover;">
                    </div>
                </div>

                <section class="form-section">
                    <h2 class="section-title"><span>01</span> Información General y Referencia Colombia</h2>
                    <div class="grid-row">
                        <div class="input-field">
                            <label for="tipo">Tipo de Vehículo</label>
                            <select name="tipo" id="tipo" required onchange="actualizarModelosColombia()">
                                <option value="Carro">Carro</option>
                                <option value="Motocicleta">Motocicleta</option>
                            </select>
                        </div>

                        <!-- Selector de Marcas -->
                        <div class="input-field">
                            <label for="marca">Marca</label>
                            <select name="marca" id="marca" required onchange="cargarReferencias()">
                                <option value="">Seleccione Marca...</option>
                            </select>
                        </div>

                        <!-- Selector de Modelos / Líneas -->
                        <div class="input-field">
                            <label for="modelo">Línea / Referencia</label>
                            <select name="modelo" id="modelo" required>
                                <option value="">Seleccione Modelo...</option>
                            </select>
                        </div>

                        <div class="input-field">
                            <label for="color">Color</label>
                            <input type="text" name="color" id="color" required>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h2 class="section-title"><span>02</span> Detalles Técnicos</h2>
                    <div class="grid-row">
                        <div class="input-field">
                            <label for="placa">Placa</label>
                            <input type="text" name="placa" id="placa" maxlength="6" style="text-transform: uppercase;" required>
                            <?php
                            if (isset($_SESSION['error_placa'])) {
                                echo "<span style='display:block; color:#ff4444; font-size:14px; margin-top:5px; font-weight:bold;'>❌ " . htmlspecialchars($_SESSION['error_placa']) . "</span>";
                                unset($_SESSION['error_placa']); 
                            }
                            ?>
                        </div>

                        <!-- MOTOR / CILINDRAJE DINÁMICO -->
                        <div class="input-field">
                            <label for="motor" id="label-motor">Motor (Ej: 1.6L)</label>
                            <select name="motor" id="motor" required>
                                <option value="">Seleccione opción...</option>
                            </select>
                        </div>

                        <!-- TRANSMISIÓN DINÁMICA -->
                        <div class="input-field">
                            <label for="transmision">Transmisión</label>
                            <select name="transmision" id="transmision" required>
                                <option value="">Seleccione Transmisión...</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h2 class="section-title"><span>03</span> Administración</h2>
                    <div class="grid-row">
                        <div class="input-field" id="contenedor-asientos">
                            <label for="asientos">Asientos</label>
                            <input type="number" name="asientos" id="asientos" value="5" required>
                        </div>
                        <div class="input-field">
                            <label for="precio">Precio Día ($COP)</label>
                            <input type="number" step="0.01" name="precio" id="precio" required>
                        </div>
                    </div>
                </section>

                <div class="form-footer">
                    <button type="submit" name="enviar_registro_v" class="btn-primary">GUARDAR VEHÍCULO</button>
                </div>
            </form>
        </div>
    </main>

    <!-- Scripts JavaScript -->
    <script src="registro_vehiculo.js"></script>
    <script src="valida_placa.js"></script>

    <!-- Script visual complementario para la vista previa de la foto de matrícula -->
    <script>
        document.getElementById('imagenMatricula').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                document.getElementById('nombreArchivo').textContent = "📄 " + file.name;
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagenPreview');
                    preview.src = e.target.result;
                    document.getElementById('previewContainer').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>