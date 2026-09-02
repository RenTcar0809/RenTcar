<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentCar - Acceso</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Roboto+Condensed:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="inicioSesion.css">
</head>
<body>

<header class="top-bar">
    <div class="logo-container">
        <!-- Envolvemos el texto y la imagen en un <a> -->
        <a href="antesdellogin.html" style="text-decoration: none; display: flex; align-items: center;">
            <span class="rent-text">REN<span class="t-black">T</span>CAR</span>
            <img src="unnamed.png" alt="Logo" class="car-logo">
        </a>
    </div>
</header>

<main>
    <div class="auth-card">
        <h2>INICIAR SESIÓN</h2>
        
        <form action="procesar_login.php" method="POST" class="login-form">
            <div class="input-group">
                <label>USUARIO, CORREO O NIT</label>
                <input type="text" name="usuario_identificador" placeholder="ejemplo@correo.com" required>
            </div>

            <div class="input-group">
                <label>CONTRASEÑA</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">INICIAR SESIÓN</button>
        </form>

        <div class="footer-links">
            <a href="#">¿Olvidó su contraseña?</a>
            <p>¿No tienes una cuenta? <a href="index.php">Únete</a></p>
        </div>
    </div>
</main>

</body>
</html>