<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'conexion.php'; // Tu conexión PDO

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idUsuario = $_SESSION['IdUsuario'];
    
    // Procesar campos de texto normales
    $nombre   = !empty(trim($_POST['nombre'] ?? '')) ? trim($_POST['nombre']) : ($_SESSION['nombre'] ?? '');
    $apellido = !empty(trim($_POST['apellido'] ?? '')) ? trim($_POST['apellido']) : ($_SESSION['apellido'] ?? '');
    $correo   = !empty(trim($_POST['correo'] ?? '')) ? trim($_POST['correo']) : ($_SESSION['correo'] ?? '');
    
    // Procesar el teléfono (campo numérico bigint): si está vacío, enviamos NULL para evitar el error de sintaxis
    $telefonoInput = trim($_POST['numTelefono'] ?? '');
    if ($telefonoInput !== '') {
        $telefono = $telefonoInput;
    } else {
        // Si viene vacío, intentamos usar el de la sesión o dejamos NULL si la BD lo permite
        $telefono = !empty(trim($_SESSION['telefono'] ?? '')) ? trim($_SESSION['telefono']) : null;
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
            ':telefono' => $telefono, // Si es null, PDO lo enviará como NULL de SQL correctamente
            ':id'       => $idUsuario
        ]);

        // Actualizamos las variables de sesión
        $_SESSION['nombre']   = $nombre;
        $_SESSION['apellido'] = $apellido;
        $_SESSION['correo']   = $correo;
        $_SESSION['telefono'] = $telefono;

        header("Location: configuracion.php?status=success");
        exit();

    } catch (PDOException $e) {
        echo "<div style='color: red; padding: 20px; font-family: sans-serif;'>";
        echo "<h3>Error al actualizar la base de datos:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='configuracion.php'>Regresar a configuración</a>";
        echo "</div>";
        exit();
    }
} else {
    header("Location: configuracion.php");
    exit();
}
?>