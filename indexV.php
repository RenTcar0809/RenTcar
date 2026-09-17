<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Vehículo - RentCar</title>
    <link rel="stylesheet" href="indexv.css">
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-content">
            <a href="#" class="logo-container">
                <span class="logo-text">RENT<span class="txt-black">CAR</span></span>
            </a>
        </div>
    </nav>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="container">
        <div class="form-card">
            
            <div class="card-header">
                <h1 class="main-title">REGISTRO DE <span class="txt-red">VEHÍCULO</span></h1>
                <p class="subtitle">Ingresa la información técnica y adjunta la tarjeta de propiedad oficial.</p>
            </div>

            <!-- ALERTA DE ERROR -->
            <?php if (isset($_SESSION['error_placa'])): ?>
                <div style="background: rgba(255,0,0,0.15); border: 1px solid var(--accent-red); color: #ff8888; padding: 12px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; font-size: 14px;">
                    ⚠️ <?php echo $_SESSION['error_placa']; unset($_SESSION['error_placa']); ?>
                </div>
            <?php endif; ?>

            <!-- FORMULARIO PRINCIPAL -->
            <form action="procesar_vehiculo.php" method="POST" enctype="multipart/form-data">
                
                <!-- SECCIÓN 01: INFORMACIÓN BÁSICA -->
                <div class="form-section">
                    <h3 class="section-title"><span>01</span> Información Básica</h3>
                    <div class="grid-row">
                        <div class="input-field">
                            <label>Tipo de Vehículo</label>
                            <select name="tipo" id="tipo" onchange="actualizarModelosColombia()" required>
                                <option value="Carro">Carro</option>
                                <option value="Motocicleta">Motocicleta</option>
                            </select>
                        </div>
                        
                        <div class="input-field">
                            <label>Marca</label>
                            <select name="marca" id="marca" onchange="cargarReferencias()" required>
                                <option value="">Seleccione Marca...</option>
                            </select>
                        </div>

                        <div class="input-field">
                            <label>Línea / Referencia</label>
                            <select name="modelo" id="modelo" required>
                                <option value="">Seleccione Modelo...</option>
                            </select>
                        </div>

                        <div class="input-field">
                            <label>Color</label>
                            <input type="text" name="color" placeholder="Ej: Negro Mate" required>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 02: DETALLES TÉCNICOS -->
                <div class="form-section">
                    <h3 class="section-title"><span>02</span> Detalles Técnicos</h3>
                    <div class="grid-row">
                        <div class="input-field">
                            <label>Placa (Ej: ABC123 o YSR13F)</label>
                            <input type="text" name="placa" id="placa" placeholder="Ej: YSR13F" style="text-transform: uppercase;" required>
                        </div>
                        
                        <div class="input-field">
                            <label>Motor / Cilindraje</label>
                            <select name="motor" id="motor" required>
                                <option value="">Seleccione Motor...</option>
                                <option value="1.0L">1.0L</option>
                                <option value="1.2L">1.2L</option>
                                <option value="1.4L">1.4L</option>
                                <option value="1.5L">1.5L</option>
                                <option value="1.6L">1.6L</option>
                                <option value="2.0L">2.0L</option>
                                <option value="Turbo">Turbo</option>
                                <option value="100CC">100CC</option>
                                <option value="125CC">125CC</option>
                                <option value="150CC">150CC</option>
                                <option value="160CC">160CC</option>
                                <option value="200CC">200CC</option>
                                <option value="250CC">250CC</option>
                                <option value="400CC">400CC o superior</option>
                            </select>
                        </div>

                        <div class="input-field">
                            <label>Transmisión</label>
                            <select name="transmision" required>
                                <option value="Automática">Automática</option>
                                <option value="Mecánica">Mecánica</option>
                            </select>
                        </div>

                        <!-- ID agregado para ocultar en motos -->
                        <div class="input-field" id="contenedor-traccion">
                            <label>Tracción</label>
                            <input type="text" name="traccion" placeholder="Ej: 4X2, FWD">
                        </div>

                        <div class="input-field">
                            <label>Nº Motor</label>
                            <input type="text" name="num_motor" id="num_motor" placeholder="Número de motor" required>
                        </div>
                        <div class="input-field">
                            <label>Nº Chasis / VIN</label>
                            <input type="text" name="num_chasis" id="num_chasis" placeholder="Número de chasis" required>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 03: ADMINISTRACIÓN -->
                <div class="form-section">
                    <h3 class="section-title"><span>03</span> Administración</h3>
                    <div class="grid-row">
                        <!-- ID agregado para ocultar en motos -->
                        <div class="input-field" id="contenedor-asientos">
                            <label>Asientos</label>
                            <input type="number" name="asientos" id="asientos" value="5" min="1" max="60">
                        </div>
                        <div class="input-field">
                            <label>Precio Día ($ COP)</label>
                            <input type="number" name="precio" placeholder="Ej: 120000" required>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 04: DOCUMENTACIÓN -->
                <div class="form-section">
                    <h3 class="section-title"><span>04</span> Documentación del Vehículo</h3>
                    
                    <div style="background: rgba(230, 0, 0, 0.03); border: 2px dashed rgba(230, 0, 0, 0.4); padding: 30px; border-radius: 16px; text-align: center;">
                        <h3 style="color: #fff; font-size: 16px; margin-bottom: 6px;">📷 Foto de la Tarjeta de Propiedad / Matrícula</h3>
                        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 15px;">Sube la foto del documento oficial del vehículo.</p>
                        
                        <input type="file" name="imagen_matricula" id="imagenMatricula" accept="image/*" required style="display: none;">
                        
                        <button type="button" onclick="document.getElementById('imagenMatricula').click()" class="btn-secondary">
                            📁 Seleccionar Archivo
                        </button>
                        <p id="nombreArchivo" style="color: #4CAF50; font-weight: bold; margin-top: 12px; font-size: 13px;"></p>
                    </div>
                </div>

                <!-- BOTÓN DE ENVÍO -->
                <div class="form-footer">
                    <button type="submit" name="enviar_registro_v" class="btn-primary">GUARDAR VEHÍCULO</button>
                </div>

            </form>
        </div>
    </div>

    <!-- SCRIPT JS -->
    <script src="registrar_vehiculo.js"></script>

    <script>
        document.getElementById('imagenMatricula').addEventListener('change', function(e) {
            if(e.target.files.length > 0) {
                document.getElementById('nombreArchivo').textContent = "📄 Archivo seleccionado: " + e.target.files[0].name;
            }
        });
    </script>
</body>
</html>