<?php
session_start();
require_once 'conexion.php'; // Tu conexión PDO

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = $_SESSION['IdUsuario'];
    
    // Si el campo viene vacío o con espacios, mantenemos el valor actual de la sesión
    $nombre   = !empty(trim($_POST['nombre'] ?? '')) ? trim($_POST['nombre']) : $_SESSION['nombre'];
    $apellido = !empty(trim($_POST['apellido'] ?? '')) ? trim($_POST['apellido']) : $_SESSION['apellido'];
    $correo   = !empty(trim($_POST['correo'] ?? '')) ? trim($_POST['correo']) : $_SESSION['correo'];
    $telefono = !empty(trim($_POST['numTelefono'] ?? '')) ? trim($_POST['numTelefono']) : $_SESSION['telefono'];

    // Validación por seguridad para asegurar que no queden vacíos
    if (empty($nombre) || empty($apellido) || empty($correo)) {
        echo "<script>alert('Por favor complete los campos obligatorios.'); window.history.back();</script>";
        exit();
    }

    try {
        // Consulta SQL para actualizar los datos en PostgreSQL
        $sql = "UPDATE usuario 
                SET nombre = :nombre, 
                    apellido = :apellido, 
                    correo = :correo, 
                    num_telefono = :telefono 
                WHERE id_usuario = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nombre'   => $nombre,
            ':apellido' => $apellido,
            ':correo'   => $correo,
            ':telefono' => $telefono,
            ':id'       => $idUsuario
        ]);

        // Actualizamos las variables de sesión con los nuevos valores
        $_SESSION['nombre']   = $nombre;
        $_SESSION['apellido'] = $apellido;
        $_SESSION['correo']   = $correo;
        $_SESSION['telefono'] = $telefono;

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