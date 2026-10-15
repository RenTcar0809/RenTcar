<?php
session_start();
require_once 'conexion.php'; // Asegúrate de tener tu conexión PDO

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = $_SESSION['IdUsuario'];
    
    // Recibimos los datos del formulario (si no vienen, mantenemos el valor actual de la sesión)
    $nombre   = trim($_POST['nombre'] ?? $_SESSION['nombre']);
    $apellido = trim($_POST['apellido'] ?? $_SESSION['apellido']);
    $correo   = trim($_POST['correo'] ?? $_SESSION['correo']);
    $telefono = trim($_POST['numTelefono'] ?? $_SESSION['telefono']);

    // Validación básica de campos obligatorios
    if (empty($nombre) || empty($apellido) || empty($correo)) {
        echo "<script>alert('Por favor complete los campos obligatorios.'); window.history.back();</script>";
        exit();
    }

    try {
        // Consulta SQL para actualizar únicamente los campos del usuario logueado
        $sql = "UPDATE usuario 
                SET nombre = :nombre, 
                    apellido = :apellido, 
                    correo = :correo, 
                    numTelefono = :telefono 
                WHERE IdUsuario = :id OR id = :id"; // Ajusta 'IdUsuario' o 'id' según tu base de datos

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'   => $nombre,
            ':apellido' => $apellido,
            ':correo'   => $correo,
            ':telefono' => $telefono,
            ':id'       => $idUsuario
        ]);

        // Actualizamos también las variables de sesión para que reflejen el cambio de inmediato
        $_SESSION['nombre']   = $nombre;
        $_SESSION['apellido'] = $apellido;
        $_SESSION['correo']   = $correo;
        $_SESSION['telefono'] = $telefono;

        // Redirigir de regreso con un mensaje de éxito (puedes usar un parámetro GET si deseas mostrar una alerta)
        echo "<script>alert('¡Perfil actualizado con éxito!'); window.location.href='configuracion.php';</script>";
        exit();

    } catch (PDOException $e) {
        echo "<script>alert('Error al actualizar: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit();
    }
} else {
    header("Location: configuracion.php");
    exit();
}
?>