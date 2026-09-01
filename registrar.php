<?php
ob_start(); 
session_start();

// Incluimos la conexión unificada basada en PDO
require_once 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Recibir datos del formulario exactamente como los manda el HTML
    $nombre           = trim($_POST['nombre'] ?? '');
    $apellido         = trim($_POST['apellido'] ?? '');
    $fecha            = trim($_POST['fechaDeNacimiento'] ?? '');
    $telefono         = trim($_POST['numTelefono'] ?? '');
    $documento        = trim($_POST['documento'] ?? '');
    $confirmDocumento = trim($_POST['confirm_documento'] ?? '');
    $correo           = trim($_POST['correo'] ?? '');
    $confirmCorreo    = trim($_POST['confirm_correo'] ?? '');
    $password         = $_POST['contraseña'] ?? ''; // Ojo: usa 'contraseña' con ñ tal cual tu HTML
    $confirmPassword  = $_POST['confirm_password'] ?? '';

    // 2. Validar que no haya campos vacíos obligatorios
    if (empty($nombre) || empty($apellido) || empty($fecha) || empty($telefono) || 
        empty($documento) || empty($correo) || empty($password)) {
        echo "<script>
                alert('Por favor complete todos los campos obligatorios.');
                window.history.back();
              </script>";
        exit();
    }

    // 3. Validar que las confirmaciones coincidan
    if ($correo !== $confirmCorreo) {
        echo "<script>
                alert('Los correos electrónicos no coinciden.');
                window.history.back();
              </script>";
        exit();
    }

    if ($documento !== $confirmDocumento) {
        echo "<script>
                alert('Los documentos de identidad no coinciden.');
                window.history.back();
              </script>";
        exit();
    }

    if ($password !== $confirmPassword) {
        echo "<script>
                alert('Las contraseñas no coinciden.');
                window.history.back();
              </script>";
        exit();
    }

    try {
        // 4. Verificar si el correo o documento ya existen
        $sql_revisar = "SELECT * FROM usuario WHERE correo = :correo OR documento = :documento LIMIT 1";
        $stmt_revisar = $pdo->prepare($sql_revisar);
        $stmt_revisar->execute([
            ':correo'    => $correo,
            ':documento' => $documento
        ]);
        
        if ($stmt_revisar->rowCount() > 0) {
            echo "<script>
                    alert('El correo o documento ya están registrados. Intenta con otros.');
                    window.history.back();
                  </script>";
            exit();
        }

        // Encriptar la contraseña de forma segura
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 5. Insertar datos (con comillas dobles en columnas con mayúsculas para PostgreSQL)
        $sql_insertar = "INSERT INTO usuario (nombre, apellido, \"fechaDeNacimiento\", \"numTelefono\", documento, correo, contraseña)
                         VALUES (:nombre, :apellido, :fecha, :telefono, :documento, :correo, :password)";
        
        $stmt_insertar = $pdo->prepare($sql_insertar);
        $stmt_insertar->execute([
            ':nombre'    => $nombre,
            ':apellido'  => $apellido,
            ':fecha'     => $fecha,
            ':telefono'  => $telefono,
            ':documento' => $documento,
            ':correo'    => $correo,
            ':password'  => $passwordHash
        ]);

        $_SESSION['usuario_nombre'] = $nombre;
        echo "<script>
                alert('¡Registro exitoso! Bienvenido.');
                window.location.href='dashboardf.php';
              </script>";
        exit();

    } catch (PDOException $e) {
        $mensajeError = json_encode('Error en el sistema: ' . $e->getMessage());
        echo "<script>
                alert($mensajeError);
                window.history.back();
              </script>";
        exit();
    }
} else {
    header('Location: registrar.html'); // O el nombre de tu archivo HTML de registro
    exit();
}

ob_end_flush();
?>