<?php
ob_start(); 
session_start();

// Incluimos la conexión unificada basada en PDO
require_once 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recibir y limpiar datos del formulario
    $nombre     = trim($_POST['nombre'] ?? '');
    $apellido   = trim($_POST['apellido'] ?? '');
    $fecha      = trim($_POST['fechaDeNacimiento'] ?? '');
    $telefono   = trim($_POST['numTelefono'] ?? '');
    $documento  = trim($_POST['documento'] ?? '');
    $correo     = trim($_POST['correo'] ?? '');
    $password   = $_POST['password'] ?? '';

    // Validar campos obligatorios
    if (empty($nombre) || empty($apellido) || empty($correo) || empty($password)) {
        echo "<script>
                alert('Por favor complete los campos obligatorios.');
                window.history.back();
              </script>";
        exit();
    }

    try {
        // Verificar si el correo o documento ya existen
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

        // Insertar datos (con comillas dobles en columnas con mayúsculas para PostgreSQL)
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
                alert('¡Registro exitoso!');
                window.location.href='dashboardf.php';
              </script>";
        exit();

    } catch (PDOException $e) {
        // Manejo seguro del error con json_encode
        $mensajeError = json_encode('Error en el sistema: ' . $e->getMessage());
        echo "<script>
                alert($mensajeError);
                window.history.back();
              </script>";
        exit();
    }
} else {
    header('Location: registrar.php');
    exit();
}

ob_end_flush();
?>