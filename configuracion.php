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
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        /* Estilos adicionales modernos integrados para una mejor experiencia visual */
        .settings-card {
            background: #1e1e2f;
            border: 1px solid #2a2a3eb5;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            margin-bottom: 25px;
        }
        .settings-card h2 {
            font-size: 1.25rem;
            color: #fff;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .settings-card p.subtitle {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 24px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
        }
        .input-with-icon {
            position: relative;
        }
        .input-with-icon i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }
        .input-with-icon input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            background: #141421;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #fff;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .input-with-icon input:focus {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        .input-with-icon input[readonly] {
            background: #0f0f17;
            color: #64748b;
            border-color: #1e293b;
            cursor: not-allowed;
        }
        .btn-save {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity 0.2s ease, transform 0.1s ease;
        }
        .btn-save:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="settings-page">

    <div class="settings-layout">
        <!-- BARRA LATERAL -->
        <aside class="settings-sidebar">
            <a href="dashboardf.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Volver al Dashboard</a>
            <h2>Ajustes</h2>
            <nav class="settings-nav">
                <a href="#perfil" class="active" onclick="switchTab('perfil', this)"><i class="fa-solid fa-user"></i> Perfil Personal</a>
                <a href="#seguridad" onclick="switchTab('seguridad', this)"><i class="fa-solid fa-lock"></i> Seguridad</a>
                <a href="#pagos" onclick="switchTab('pagos', this)"><i class="fa-solid fa-credit-card"></i> Métodos de Pago</a>
            </nav>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="settings-main">
            
            <!-- SECCIÓN: PERFIL PERSONAL -->
            <section id="perfil" class="settings-section">
                <div class="settings-card">
                    <h2><i class="fa-solid fa-user-pen" style="color: #6366f1;"></i> Información Personal</h2>
                    <p class="subtitle">Actualiza los datos de tu cuenta para mantener tu perfil al día.</p>
                    
                    <form action="actualizar_perfil.php" method="POST">
                        <input type="hidden" name="IdUsuario" value="<?php echo htmlspecialchars($idUsuario); ?>">

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nombre</label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-signature"></i>
                                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Apellido</label>
                                <div class="input-with-icon">
                                    <i class="fa-solid id-card"></i>
                                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($apellido); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Correo Electrónico</label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-envelope"></i>
                                    <input type="email" name="correo" value="<?php echo htmlspecialchars($correo); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Número de Teléfono</label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-phone"></i>
                                    <input type="tel" name="numTelefono" value="<?php echo htmlspecialchars($telefono); ?>" required>
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
                    <p class="subtitle">Asegúrate de utilizar una contraseña robusta para proteger tu cuenta.</p>
                    
                    <form action="actualizar_seguridad.php" method="POST">
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
                                    <input type="password" name="pass_nueva" placeholder="••••••••••••" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirmar Nueva Contraseña</label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-lock-open"></i>
                                    <input type="password" name="pass_confirmar" placeholder="••••••••••••" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fa-solid fa-key"></i> Actualizar Contraseña</button>
                    </form>
                </div>
            </section>

            <!-- SECCIÓN: PAGOS -->
            <section id="pagos" class="settings-section" style="display: none;">
                <div class="settings-card">
                    <h2><i class="fa-solid fa-credit-card" style="color: #f59e0b;"></i> Métodos de Pago</h2>
                    <p class="subtitle">Tus datos de pago se procesan de forma segura y cifrada a través de Stripe.</p>
                    
                    <form id="payment-form" action="guardar_pago.php" method="POST">
                        <div class="form-group">
                            <label>Titular de la tarjeta</label>
                            <div class="input-with-icon">
                                <i class="fa-solid fa-user-tag"></i>
                                <input type="text" name="titular" placeholder="Nombre completo como aparece en la tarjeta" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Detalles de la tarjeta</label>
                            <div id="card-element" style="padding: 14px; background: #141421; border: 1px solid #334155; border-radius: 8px;"></div>
                        </div>

                        <button type="submit" class="btn-save"><i class="fa-solid fa-credit-card"></i> Guardar Tarjeta</button>
                    </form>
                </div>
            </section>

        </main>
    </div>
    
    <!-- Script sencillo para alternar pestañas dinámicamente si no lo tienes en confi.js -->
    <script>
        function switchTab(sectionId, element) {
            // Ocultar todas las secciones
            document.querySelectorAll('.settings-main > section').forEach(sec => {
                sec.style.display = 'none';
            });
            // Mostrar la seleccionada
            document.getElementById(sectionId).style.display = 'block';

            // Actualizar clases activas en la barra lateral
            document.querySelectorAll('.settings-nav a').forEach(nav => {
                nav.classList.remove('active');
            });
            element.classList.add('active');
        }
    </script>

    <script src="confi.js"></script>

    <script>
        var stripe = Stripe('pk_test_51TioIu2Km1bRrhgffYk9jvu1w7ljZqVZobvPKcvZGqW3zKqu2iAEom38arTTZr4zao9kGBJpN8bmo5VzJLEao4Ym00xflZ9eFS');
        var elements = stripe.elements();

        var card = elements.create('card', {
            style: {
                base: {
                    color: '#ffffff',
                    fontFamily: 'Inter, sans-serif',
                    fontSize: '16px',
                    '::placeholder': { color: '#64748b' }
                }
            }
        });
        card.mount('#card-element');
    </script>
</body>
</html>