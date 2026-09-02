<?php
session_start();
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php"); // O a donde envíes a los no logueados
    exit();
}
// ESTO ES LO QUE TE FALTA PARA QUE LA VARIABLE EXISTA
$nombreUsuario = $_SESSION['usuario_nombre']; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración | RentCar</title>
    <link rel="stylesheet" href="confi.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="settings-page">

    <div class="settings-layout">
        <aside class="settings-sidebar">
            <a href="dashboardf.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Volver</a>
            <h2>Ajustes</h2>
            <nav class="settings-nav">
                <a href="#perfil" class="active"><i class="fa-solid fa-user"></i> Perfil Personal</a>
                <a href="#seguridad"><i class="fa-solid fa-lock"></i> Seguridad</a>
                <a href="#pagos"><i class="fa-solid fa-credit-card"></i> Pagos</a>
            </nav>
        </aside>

        <main class="settings-main">
            <form action="actualizar_perfil.php" method="POST">
    <input type="hidden" name="IdUsuario" value="<?php echo $_SESSION['IdUsuario']; ?>">

    <div class="form-group">
        <label>Nombre</label>
        <input type="text" name="nombre" value="<?php echo htmlspecialchars($_SESSION['nombre']); ?>" required>
    </div>
    <div class="form-group">
        <label>Apellido</label>
        <input type="text" name="apellido" value="<?php echo htmlspecialchars($_SESSION['apellido']); ?>" required>
    </div>
    <div class="form-group">
        <label>Correo Electrónico</label>
        <input type="email" name="correo" value="<?php echo htmlspecialchars($_SESSION['correo']); ?>" required>
    </div>
    <div class="form-group">
        <label>Número de Teléfono</label>
        <input type="tel" name="numTelefono" value="<?php echo htmlspecialchars($_SESSION['numTelefono']); ?>" required>
    </div>

    <div class="form-group">
        <label style="color: #666;">Documento (No editable)</label>
        <input type="text" value="<?php echo htmlspecialchars($_SESSION['documento']); ?>" readonly style="background: #222; border: 1px solid #444;">
    </div>
    
    <button type="submit" class="btn-save">Guardar Cambios</button>
</form>
            <section id="seguridad" class="settings-section" style="display: none;">
    <h1>Seguridad y Contraseña</h1>
    <form action="actualizar_seguridad.php" method="POST">
        <div class="form-group">
            <label>Contraseña Actual</label>
            <input type="password" name="pass_actual" required>
        </div>
        <div class="form-group">
            <label>Nueva Contraseña</label>
            <input type="password" name="pass_nueva" required>
        </div>
        <div class="form-group">
            <label>Confirmar Nueva Contraseña</label>
            <input type="password" name="pass_confirmar" required>
        </div>
        <button type="submit" class="btn-save">Actualizar Contraseña</button>
    </form>
            </section>
         <section id="pagos" class="settings-section" style="display: none;">
    <h1>Registrar Nuevo Método</h1>
    <p style="color: #888; margin-bottom: 20px;">Tus datos de pago se procesan de forma segura y cifrada.</p>
    
    <form id="payment-form" action="guardar_pago.php" method="POST">
        <div class="form-group">
            <label>Titular de la tarjeta</label>
            <input type="text" name="titular" placeholder="Nombre completo" required>
        </div>
        
        <div id="card-element" class="form-group" style="padding: 12px; background: #1a1a1a; border: 1px solid #333; border-radius: 5px;">
            </div>

        <button type="submit" class="btn-save">Guardar Tarjeta</button>
    </form>
</section>
        </main>
    </div>
    
    <script src="confi.js"></script>

<script>
    // 1. Inicializa Stripe con tu clave pública
    var stripe = Stripe('pk_test_51TioIu2Km1bRrhgffYk9jvu1w7ljZqVZobvPKcvZGqW3zKqu2iAEom38arTTZr4zao9kGBJpN8bmo5VzJLEao4Ym00xflZ9eFS');
    var elements = stripe.elements();

    // 2. Crea el campo de tarjeta y lo inyecta en el div con id "card-element"
    var card = elements.create('card', {
        style: {
            base: {
                color: '#ffffff',
                fontFamily: 'Inter, sans-serif',
                fontSize: '16px',
                '::placeholder': { color: '#888' }
            }
        }
    });
    card.mount('#card-element');
</script>

</body>
</html>