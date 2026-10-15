<?php
session_start();

// Si no ha iniciado sesión, lo regresa al login
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php");
    exit();
}

// Recuperamos los datos directamente de la sesión
$idUsuario  = $_SESSION['IdUsuario'];
$nombre     = $_SESSION['nombre'] ?? '';
$apellido   = $_SESSION['apellido'] ?? '';
$correo     = $_SESSION['correo'] ?? '';
$telefono   = $_SESSION['telefono'] ?? '';
$documento  = $_SESSION['documento'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Cuenta | RentCar</title>
    <link rel="stylesheet" href="confi.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="settings-page">

    <div class="settings-layout">
        <!-- BARRA LATERAL -->
        <aside class="settings-sidebar">
            <a href="dashboardf.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Volver al Dashboard</a>
            <h2>Ajustes</h2>
            <nav class="settings-nav">
                <a href="#perfil" class="active" data-target="perfil"><i class="fa-solid fa-user"></i> Perfil Personal</a>
                <a href="#seguridad" data-target="seguridad"><i class="fa-solid fa-lock"></i> Seguridad</a>
            </nav>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="settings-main">
            
          <!-- SECCIÓN: PERFIL PERSONAL -->
<section id="perfil" class="settings-section active-section">
    <div class="settings-card">
        <h2><i class="fa-solid fa-user-pen" style="color: #6366f1;"></i> Información Personal</h2>
        <p class="subtitle">Puedes cambiar solo el dato que desees actualizar.</p>
        
        <form action="actualizar_perfil.php" method="POST" class="interactive-form">
            <input type="hidden" name="IdUsuario" value="<?php echo htmlspecialchars($idUsuario); ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-signature"></i>
                        <!-- SIN "required" -->
                        <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" placeholder="Dejar igual o cambiar">
                    </div>
                </div>
                <div class="form-group">
                    <label>Apellido</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-user"></i>
                        <!-- SIN "required" -->
                        <input type="text" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" placeholder="Dejar igual o cambiar">
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <!-- SIN "required" -->
                        <input type="email" name="correo" value="<?php echo htmlspecialchars($correo); ?>" placeholder="Dejar igual o cambiar">
                    </div>
                </div>
                <div class="form-group">
                    <label>Número de Teléfono</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-phone"></i>
                        <!-- SIN "required" -->
                        <input type="tel" name="numTelefono" value="<?php echo htmlspecialchars($telefono); ?>" placeholder="Dejar igual o cambiar">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Documento de Identidad <span style="color: #64748b; font-weight: normal;">(No modificable)</span></label>
                <div class="input-with-icon">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" value="<?php echo htmlspecialchars($documento); ?>" readonly>
                </div>
            </div>
            
            <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Guardar Cambios</button>
        </form>
    </div>
</section>
            <!-- SECCIÓN: SEGURIDAD -->
            <section id="seguridad" class="settings-section" style="display: none;">
                <div class="settings-card">
                    <h2><i class="fa-solid fa-shield-halved" style="color: #10b981;"></i> Seguridad y Contraseña</h2>
                    <p class="subtitle">Modifica tu contraseña de acceso cuando lo necesites.</p>
                    
                    <form action="actualizar_seguridad.php" method="POST" id="security-form" class="interactive-form">
                        <div class="form-group">
                            <label>Contraseña Actual</label>
                            <div class="input-with-icon">
                                <i class="fa-solid fa-key"></i>
                                <input type="password" name="pass_actual" placeholder="••••••••••••" required>
                            </div>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nueva Contraseña</label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" id="pass_nueva" name="pass_nueva" placeholder="••••••••••••" required>
                                </div>
                                <small id="password-strength" style="color: #94a3b8; font-size: 0.75rem; margin-top: 5px; display: block;"></small>
                            </div>
                            <div class="form-group">
                                <label>Confirmar Nueva Contraseña</label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-lock-open"></i>
                                    <input type="password" id="pass_confirmar" name="pass_confirmar" placeholder="••••••••••••" required>
                                </div>
                                <small id="password-match" style="color: #ef4444; font-size: 0.75rem; margin-top: 5px; display: block;"></small>
                            </div>
                        </div>
                        <button type="submit" class="btn-save" id="btn-seguridad"><i class="fa-solid fa-key"></i> Actualizar Contraseña</button>
                    </form>
                </div>
            </section>

        </main>
    </div>
    
    <script src="confis.js"></script>
</body>
</html>