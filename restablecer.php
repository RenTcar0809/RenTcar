<?php
session_start();
require_once 'conexion.php';

$token = $_GET['token'] ?? '';
$valido = false;
$mensaje = "";

if (empty($token)) {
    die("Token no proporcionado.");
}

try {
    $stmt = $pdo->prepare("SELECT * FROM usuario WHERE token_recuperacion = :token AND token_expiracion > NOW() LIMIT 1");
    $stmt->execute([':token' => $token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $valido = true;
    } else {
        $mensaje = "El enlace de recuperación es inválido o ha expirado.";
    }
} catch (PDOException $e) {
    $mensaje = "Error en el sistema.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valido) {
    $passNueva = $_POST['pass_nueva'] ?? '';
    $passConfirmar = $_POST['pass_confirmar'] ?? '';

    if (empty($passNueva) || strlen($passNueva) < 6) {
        $errorForm = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($passNueva !== $passConfirmar) {
        $errorForm = "Las contraseñas no coinciden.";
    } else {
        $nuevoHash = password_hash($passNueva, PASSWORD_DEFAULT);
        $idUsuario = $usuario['IdUsuario'] ?? $usuario['idusuario'] ?? $usuario['id'];

        $update = $pdo->prepare("UPDATE usuario SET contraseña = :pass, token_recuperacion = NULL, token_expiracion = NULL WHERE IdUsuario = :id OR id = :id");
        $update->execute([':pass' => $nuevoHash, ':id' => $idUsuario]);

        echo "<script>alert('¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.'); window.location.href='inicioSesion.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña | RentCar</title>
    <link rel="stylesheet" href="confi.css">
    <link rel="stylesheet" href="auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">

    <div class="auth-container">
        <div class="auth-card">
            <h2><i class="fa-solid fa-lock-open" style="color: #10b981;"></i> Nueva Contraseña</h2>
            
            <?php if (!$valido): ?>
                <div class="auth-alert auth-alert-error">
                    <?php echo $mensaje; ?>
                </div>
                <a href="inicioSesion.php" class="btn-save" style="display: block; text-align: center; text-decoration: none;">Ir al Inicio de Sesión</a>
            <?php else: ?>
                <p>Ingresa y confirma tu nueva contraseña de acceso segura.</p>

                <?php if (!empty($errorForm)): ?>
                    <div class="auth-alert auth-alert-error">
                        <?php echo $errorForm; ?>
                    </div>
                <?php endif; ?>

                <form action="restablecer.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>Nueva Contraseña</label>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="pass_nueva" placeholder="••••••••••••" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label>Confirmar Nueva Contraseña</label>
                        <div class="input-with-icon">
                            <i class="fa-solid fa-lock-open"></i>
                            <input type="password" name="pass_confirmar" placeholder="••••••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-save" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-floppy-disk"></i> Cambiar Contraseña
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>