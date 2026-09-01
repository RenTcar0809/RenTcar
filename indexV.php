<?php
// ¡IMPORTANTE! Esto siempre debe ir en la línea 1 para que funcionen los mensajes
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentCar - Registro de Vehículo</title>
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
                <h1 class="main-title">REGISTRO DE VEHÍCULO</h1>
                <p class="subtitle">Bienvenido, <strong><?php echo isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Usuario'; ?></strong>. Agregue una nueva unidad.</p>
            </header>

            <form action="procesar_vehiculo.php" method="POST">
                
                <section class="form-section">
                    <h2 class="section-title"><span>01</span> Información General</h2>
                    <div class="grid-row">
                        <div class="input-field"><label for="marca">Marca</label><input type="text" name="marca" id="marca" required></div>
                        <div class="input-field"><label for="modelo">Modelo</label><input type="text" name="modelo" id="modelo" required></div>
                        <div class="input-field">
                            <label for="tipo">Tipo de Vehículo</label>
                            <select name="tipo" id="tipo" required>
                                <option value="Carro">Carro</option>
                                <option value="Motocicleta">Motocicleta</option>
                            </select>
                        </div>
                        <div class="input-field"><label for="color">Color</label><input type="text" name="color" id="color" required></div>
                    </div>
                </section>

                <section class="form-section">
                    <h2 class="section-title"><span>02</span> Detalles Técnicos</h2>
                    <div class="grid-row">
                        
                        <div class="input-field">
                            <label for="placa">Placa</label>
                            <input type="text" name="placa" id="placa" maxlength="6" required>
                            <?php
                            if (isset($_SESSION['error_placa'])) {
                                echo "<span style='display:block; color:#ff4444; font-size:14px; margin-top:5px; font-weight:bold;'>
                                        ❌ " . $_SESSION['error_placa'] . "
                                      </span>";
                                unset($_SESSION['error_placa']); 
                            }
                            ?>
                        </div>
                        <div class="input-field"><label for="motor">Motor</label><input type="text" name="motor" id="motor" required></div>
                        <div class="input-field">
                            <label for="transmision">Transmisión</label>
                            <select name="transmision" id="transmision">
                                <option value="Automática">Automática</option>
                                <option value="Manual">Manual</option>
                            </select>
                        </div>
                        
                        <!-- CAMPO EXCLUSIVO DE CARRO -->
                        <div class="input-field" id="contenedor-traccion">
                            <label for="traccion">Tracción (Ej: 4x2, 4x4)</label>
                            <input type="text" name="traccion" id="traccion" required>
                        </div>

                        <!-- CAMPO EXCLUSIVO DE MOTO (Oculto por defecto) -->
                        <div class="input-field" id="contenedor-cilindraje" style="display: none;">
                            <label for="cilindraje">Cilindraje (CC)</label>
                            <input type="number" name="cilindraje" id="cilindraje">
                        </div>

                        <div class="input-field"><label for="num_motor">Nº Motor</label><input type="text" name="num_motor" id="num_motor" required></div>
                        <div class="input-field"><label for="num_chasis">Nº Chasis</label><input type="text" name="num_chasis" id="num_chasis" required></div>
                    </div>
                </section>

                <section class="form-section">
                    <h2 class="section-title"><span>03</span> Administración</h2>
                    <div class="grid-row">
                        
                        <!-- CAMPO EXCLUSIVO DE CARRO -->
                        <div class="input-field" id="contenedor-asientos">
                            <label for="asientos">Asientos</label>
                            <input type="number" name="asientos" id="asientos" required>
                        </div>

                        <div class="input-field"><label for="precio">Precio Día</label><input type="number" step="0.01" name="precio" id="precio" required></div>
                    </div>
                </section>
                <section class="form-section">
                    <h2 class="section-title"><span>03</span> Administración</h2>
                    <div class="grid-row">
                        <div class="input-field"><label for="asientos">Asientos</label><input type="number" name="asientos" id="asientos" required></div>
                        <div class="input-field"><label for="precio">Precio Día</label><input type="number" step="0.01" name="precio" id="precio" required></div>
                    </div>
                </section>

                <div class="form-footer">
                    <button type="submit" name="enviar_registro_v" class="btn-primary">GUARDAR VEHÍCULO</button>
                </div>
            </form>
        </div>
    </main>
    <script src="valida_placa.js"></script>
</body>
</html>