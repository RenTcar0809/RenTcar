<?php
ob_start(); 
session_start();

// 1. Incluimos la conexión unificada basada en PDO (detecta local o Render)
require_once 'conexion.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Recibir datos del formulario (con PDO ya no se necesita mysqli_real_escape_string)
    $nombre     = trim($_POST['nombre'] ?? '');
    $apellido   = trim($_POST['apellido'] ?? '');
    $fecha      = trim($_POST['fechaDeNacimiento'] ?? '');
    $telefono   = trim($_POST['numTelefono'] ?? '');
    $documento  = trim($_POST['documento'] ?? '');
    $correo     = trim($_POST['correo'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($nombre) || empty($apellido) || empty($correo) || empty($password)) {
        echo "<script>
                alert('Por favor complete los campos obligatorios.');
                window.history.back();
              </script>";
        exit();
    }

    try {
        // 3. VERIFICAR si el correo o documento ya existen usando Consultas Preparadas ($pdo)
        $sql_revisar = "SELECT * FROM usuario WHERE correo = :correo OR documento = :documento LIMIT 1";
        $stmt_revisar = $pdo->prepare($sql_revisar);
        $stmt_revisar->execute([
            ':correo' => $correo,
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

        // 4. INSERTAR los datos de forma segura
        $sql_insertar = "INSERT INTO usuario (nombre, apellido, fechaDeNacimiento, numTelefono, documento, correo, contraseña)
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
        echo "<script>
                alert('Error en el sistema: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
        exit();
    }
}

ob_end_flush();
?>