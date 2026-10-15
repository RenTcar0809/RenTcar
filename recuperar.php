<?php
session_start();
require_once 'conexion.php';

$mensaje = "";
$tipoAlerta = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');

    if (empty($correo)) {
        $mensaje = "Por favor ingrese su correo electrónico.";
        $tipoAlerta = "error";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuario WHERE LOWER(TRIM(correo)) = LOWER(TRIM(:correo)) LIMIT 1");
            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                $token = bin2hex(random_bytes(32));
                $expiracion = date("Y-m-d H:i:s", strtotime("+1 hour"));

                // CORREGIDO: Usamos id_usuario que es el nombre real en tu tabla
                $idUsuario = $usuario['id_usuario'];

                $update = $pdo->prepare("UPDATE usuario SET token_recuperacion = :token, token_expiracion = :exp WHERE id_usuario = :id");
                $update->execute([':token' => $token, ':exp' => $expiracion, ':id' => $idUsuario]);

                $enlace = "restablecer.php?token=" . $token;

                $mensaje = "¡Solicitud exitosa! Enlace de recuperación generado: <a href='$enlace' style='color: #df0000; font-weight: bold; text-decoration: underline;'>Hacer clic aquí para restablecer</a>";
                $tipoAlerta = "success";
            } else {
                $mensaje = "Si el correo está registrado, se han enviado las instrucciones de recuperación.";
                $tipoAlerta = "success";
            }
        } catch (PDOException $e) {
            $mensaje = "Error en el sistema: " . $e->getMessage();
            $tipoAlerta = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña | RentCar</title>
    <link rel="stylesheet" href="confi.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">

    <div class="auth-container">
        <div class="auth-card">
            <a href="inicioSesion.php" class="auth-back-link">
                <i class="fa-solid fa-arrow-left"></i> Volver al Login
            </a>

            <h2><i class="fa-solid fa-key" style="color: #df0000;"></i> Recuperar Contraseña</h2>
            <p>Ingresa tu correo electrónico registrado y te enviaremos los pasos para restablecer tu acceso.</p>

            <?php if (!empty($mensaje)): ?>
                <div class="auth-alert auth-alert-<?php echo $tipoAlerta; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <form action="recuperar.php" method="POST">
                <div class="form-group" style="margin-bottom: 25px;">
                    <label>Correo Electrónico</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="correo" placeholder="ejemplo@correo.com" required>
                    </div>
                </div>

                <button type="submit" class="btn-save" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Enlace de Recuperación
                </button>
            </form>
        </div>
    </div>

</body>
</html>