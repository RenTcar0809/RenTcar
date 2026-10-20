<?php
session_start();
require_once 'conexion.php';

$mensaje = "";
$tipoAlerta = "";

// Función para enviar correos usando la API HTTP de Brevo vía cURL
function enviarCorreoBrevo($correoDestino, $enlace) {
    // Aquí pegas tu API Key de Brevo (o la configuras en una variable de entorno en Render: BREVO_API_KEY)
    $apiKey = getenv('BREVO_API_KEY'); 
    
    $data = [
        'sender' => [
            'name' => 'RentCar Soporte',
            'email' => 'RenTcarCop@gmail.com' // El correo con el que abriste tu cuenta en Brevo
        ],
        'to' => [
            [
                'email' => $correoDestino
            ]
        ],
        'subject' => 'Recuperación de Contraseña - RentCar',
        'htmlContent' => '
            <div style="font-family: Arial, sans-serif; background-color: #000; color: #fff; padding: 20px; border-radius: 8px;">
                <h2 style="color: #df0000;">RENTCAR - Recuperación de Cuenta</h2>
                <p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente botón para continuar:</p>
                <p style="margin: 25px 0;">
                    <a href="' . $enlace . '" style="background-color: #df0000; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Restablecer Contraseña</a>
                </p>
                <p style="color: #a1a1aa; font-size: 0.9rem;">Si tú no solicitaste este cambio, puedes ignorar este mensaje. Este enlace expirará en 1 hora.</p>
            </div>
        '
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api-key: ' . $apiKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    // Si en local (XAMPP) te llega a dar problemas de SSL, puedes descomentar la siguiente línea:
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err || $httpCode >= 400) {
        error_log("Error cURL Brevo: " . $err . " | Respuesta: " . $response);
        return false;
    }

    return true;
}

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

                // Envío de correo mediante Brevo
                $enviado = enviarCorreoBrevo($correo, $enlace);

                if ($enviado) {
                    $mensaje = "¡Listo! Se han enviado las instrucciones de recuperación a tu correo electrónico.";
                    $tipoAlerta = "success";
                } else {
                    $mensaje = "Error al enviar el correo con Brevo. Intente más tarde.";
                    $tipoAlerta = "error";
                }
            } else {
                $mensaje = "Si el correo está registrado, se han enviado las instrucciones de recuperación.";
                $tipoAlerta = "success";
            }
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