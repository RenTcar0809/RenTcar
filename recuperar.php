<?php
session_start();
require_once 'conexion.php';

// Incluir PHPMailer de forma manual asegurando los archivos principales
require 'src/Exception.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
                $idUsuario = $usuario['id_usuario'];

                $update = $pdo->prepare("UPDATE usuario SET token_recuperacion = :token, token_expiracion = :exp WHERE id_usuario = :id");
                $update->execute([':token' => $token, ':exp' => $expiracion, ':id' => $idUsuario]);

                $enlace = "https://rentcar-g542.onrender.com/restablecer.php?token=" . $token;

                // Configuración de PHPMailer
                $mail = new PHPMailer(true);

                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';             
                $mail->SMTPAuth   = true;
                $mail->Username   = 'tu-correo@gmail.com';          // <--- Tu correo real de Gmail
                $mail->Password   = 'tu-contraseña-de-aplicacion';  // <--- Tu contraseña de aplicación de Google
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Solución para errores de certificado SSL en entornos locales (XAMPP)
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                $mail->setFrom('tu-correo@gmail.com', 'RentCar Soporte');
                $mail->addAddress($correo);

                $mail->isHTML(true);
                $mail->Subject = 'Recuperación de Contraseña - RentCar';
                $mail->CharSet = 'UTF-8';
                
                $mail->Body    = '
                    <div style="font-family: Arial, sans-serif; background-color: #000; color: #fff; padding: 20px; border-radius: 8px;">
                        <h2 style="color: #df0000;">RENTCAR - Recuperación de Cuenta</h2>
                        <p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente botón para continuar:</p>
                        <p style="margin: 25px 0;">
                            <a href="' . $enlace . '" style="background-color: #df0000; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Restablecer Contraseña</a>
                        </p>
                        <p style="color: #a1a1aa; font-size: 0.9rem;">Si tú no solicitaste este cambio, puedes ignorar este mensaje. Este enlace expirará en 1 hora.</p>
                    </div>
                ';

                $mail->send();

                $mensaje = "¡Listo! Se han enviado las instrucciones de recuperación a tu correo electrónico.";
                $tipoAlerta = "success";
            } else {
                $mensaje = "Si el correo está registrado, se han enviado las instrucciones de recuperación.";
                $tipoAlerta = "success";
            }
        } catch (Exception $e) {
            $errorDetalle = isset($mail) ? $mail->ErrorInfo : $e->getMessage();
            $mensaje = "Error al enviar el correo. Intente más tarde. (Detalle: " . $errorDetalle . ")";
            $tipoAlerta = "error";
        } catch (PDOException $e) {
            $mensaje = "Error en el sistema de base de datos.";
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