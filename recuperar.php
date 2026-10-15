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
                // Generar token único y seguro con expiración de 1 hora
                $token = bin2hex(random_bytes(32));
                $expiracion = date("Y-m-d H:i:s", strtotime("+1 hour"));

                $idUsuario = $usuario['IdUsuario'] ?? $usuario['idusuario'] ?? $usuario['id'];

                // Guardar token en la base de datos
                $update = $pdo->prepare("UPDATE usuario SET token_recuperacion = :token, token_expiracion = :exp WHERE IdUsuario = :id OR id = :id");
                $update->execute([':token' => $token, ':exp' => $expiracion, ':id' => $idUsuario]);

                // Enlace de restablecimiento
                $enlace = "restablecer.php?token=" . $token;

                // NOTA: Aquí deberías enviar el correo usando PHPMailer. 
                // Para pruebas didácticas, te mostramos el enlace directo en pantalla:
                $mensaje = "¡Solicitud exitosa! Si usas un sistema de correo real, se envió el enlace. Para pruebas, haz clic aquí: <a href='$enlace' style='color: #6366f1; font-weight: bold;'>Restablecer mi contraseña</a>";
                $tipoAlerta = "success";
            } else {
                // Por seguridad no revelamos si el correo existe o no, pero daremos un aviso genérico amigable
                $mensaje = "Si el correo está registrado, se han enviado las instrucciones de recuperación.";
                $tipoAlerta = "success";
            }
        } catch (PDOException $e) {
            $mensaje = "Error en el sistema. Intente más tarde.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .auth-card {
            background: #1e1e2f;
            border: 1px solid #2a2a3eb5;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        .alert-box {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        .alert-success { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
        .alert-error { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
    </style>
</head>
<body class="settings-page">

    <div class="auth-container">
        <div class="auth-card">
            <a href="inicioSesion.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Volver al Login</a>
            <h2 style="font-size: 1.5rem; color: #fff; margin-bottom: 8px;"><i class="fa-solid fa-key" style="color: #6366f1;"></i> Recuperar Contraseña</h2>
            <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 25px;">Ingresa tu correo electrónico registrado y te enviaremos los pasos para restablecer tu contraseña.</p>

            <?php if (!empty($mensaje)): ?>
                <div class="alert-box alert-<?php echo $tipoAlerta; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <form action="recuperar.php" method="POST">
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <div class="input-with-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="correo" placeholder="ejemplo@correo.com" required>
                    </div>
                </div>

                <button type="submit" class="btn-save" style="width: 100%; justify-content: center; margin-top: 10px;">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Enlace de Recuperación
                </button>
            </form>
        </div>
    </div>

</body>
</html>