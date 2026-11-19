<?php
ob_start();
session_start();

// Incluimos la conexión unificada basada en PDO
require_once 'conexion.php';

function alertaYVolver(string $mensaje): void
{
    $jsonMsg = json_encode($mensaje, JSON_UNESCAPED_UNICODE);
    echo "<script>
            alert($jsonMsg);
            window.history.back();
          </script>";
    exit();
}

function esPasswordDebil(string $password): bool
{
    if (mb_strlen($password, 'UTF-8') < 8) {
        return true;
    }

    $clavesBloqueadas = [
        '1234', '12345', '123456', '1234567', '12345678', '123456789', '1234567890',
        '00000000', '11111111', 'password', 'password1', 'password123',
        'qwerty', 'qwerty123', 'abc12345', 'admin123', 'usuario123', 'rentcar123'
    ];

    $normalizada = mb_strtolower(trim($password), 'UTF-8');
    if (in_array($normalizada, $clavesBloqueadas, true)) {
        return true;
    }

    // Mismo carácter repetido toda la cadena
    if (preg_match('/^(.)\1+$/u', $password)) {
        return true;
    }

    // Solo números o solo letras
    if (ctype_digit($normalizada) || ctype_alpha($normalizada)) {
        return true;
    }

    return false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Recibir datos del formulario exactamente como los manda el HTML
    $nombre           = trim($_POST['nombre'] ?? '');
    $apellido         = trim($_POST['apellido'] ?? '');
    $fecha            = trim($_POST['fechaDeNacimiento'] ?? '');
    $telefonoRaw      = trim($_POST['numTelefono'] ?? '');
    $documento        = trim($_POST['documento'] ?? '');
    $confirmDocumento = trim($_POST['confirm_documento'] ?? '');
    $correo           = trim($_POST['correo'] ?? '');
    $confirmCorreo    = trim($_POST['confirm_correo'] ?? '');
    $password         = $_POST['contraseña'] ?? ''; // El HTML manda el input con ñ
    $confirmPassword  = $_POST['confirm_password'] ?? '';

    $telefonoLimpio = preg_replace('/\D+/', '', $telefonoRaw);

    // 2. Validar que no haya campos vacíos obligatorios
    if (
        $nombre === '' || $apellido === '' || $fecha === '' ||
        $telefonoLimpio === '' || $documento === '' || $correo === '' || $password === ''
    ) {
        alertaYVolver('Por favor complete todos los campos obligatorios.');
    }

    // 3. Validar formato de correo y fecha
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        alertaYVolver('Ingresa un correo electrónico válido.');
    }

    // 4. Validar que las confirmaciones coincidan
    if (mb_strtolower($correo, 'UTF-8') !== mb_strtolower($confirmCorreo, 'UTF-8')) {
        alertaYVolver('Los correos electrónicos no coinciden.');
    }

    if ($documento !== $confirmDocumento) {
        alertaYVolver('Los documentos de identidad no coinciden.');
    }

    if (mb_strlen($password, 'UTF-8') < 8) {
        alertaYVolver('La contraseña debe tener al menos 8 caracteres.');
    }

    if (esPasswordDebil($password)) {
        alertaYVolver('La contraseña es muy débil o común. Elige otra más segura.');
    }

    if ($password !== $confirmPassword) {
        alertaYVolver('Las contraseñas no coinciden.');
    }

    try {
        // 5. Verificar si el correo o documento ya existen
        $sql_revisar = "SELECT 1 FROM usuario WHERE LOWER(correo) = LOWER(:correo) OR documento = :documento LIMIT 1";
        $stmt_revisar = $pdo->prepare($sql_revisar);
        $stmt_revisar->execute([
            ':correo'    => $correo,
            ':documento' => $documento
        ]);

        if ($stmt_revisar->fetchColumn() !== false) {
            alertaYVolver('El correo o documento ya están registrados en el sistema. Intenta con otros.');
        }

        // Encriptar la contraseña de forma segura
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 6. Insertar datos usando los nombres exactos de tu tabla `usuario`
        $sql_insertar = "INSERT INTO usuario (
                            nombre,
                            apellido,
                            fecha_de_nacimiento,
                            num_telefono,
                            documento,
                            correo,
                            contrasena
                         ) VALUES (
                            :nombre,
                            :apellido,
                            :fecha,
                            :telefono,
                            :documento,
                            :correo,
                            :password
                         )";

        $stmt_insertar = $pdo->prepare($sql_insertar);
        $stmt_insertar->execute([
            ':nombre'    => $nombre,
            ':apellido'  => $apellido,
            ':fecha'     => $fecha,
            ':telefono'  => (int)$telefonoLimpio,
            ':documento' => $documento,
            ':correo'    => $correo,
            ':password'  => $passwordHash
        ]);

        $nuevoId = $pdo->lastInsertId('usuario_id_usuario_seq');
        if ($nuevoId !== false) {
            $_SESSION['id_usuario'] = (int)$nuevoId;
        }
        $_SESSION['usuario_nombre'] = $nombre;

        $jsonExito = json_encode('¡Registro exitoso! Bienvenido.', JSON_UNESCAPED_UNICODE);
        echo "<script>
                alert($jsonExito);
                window.location.href = 'dashboardf.php';
              </script>";
        exit();

    } catch (PDOException $e) {
        error_log("Error en registrar.php: " . $e->getMessage());
        alertaYVolver('Los datos ya están registrados en el sistema.');
    }
} else {
    header('Location: registrar.html');
    exit();
}

ob_end_flush();