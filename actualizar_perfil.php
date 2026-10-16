<?php
// Asegurar que no haya espacios antes de esta etiqueta
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
    
    // Si el campo viene vacío, recurrimos a la sesión; si tampoco existe, ponemos un string vacío
    $nombre   = !empty(trim($_POST['nombre'] ?? '')) ? trim($_POST['nombre']) : ($_SESSION['nombre'] ?? '');
    $apellido = !empty(trim($_POST['apellido'] ?? '')) ? trim($_POST['apellido']) : ($_SESSION['apellido'] ?? '');
    $correo   = !empty(trim($_POST['correo'] ?? '')) ? trim($_POST['correo']) : ($_SESSION['correo'] ?? '');
    $telefono = !empty(trim($_POST['numTelefono'] ?? '')) ? trim($_POST['numTelefono']) : ($_SESSION['telefono'] ?? $_SESSION['numTelefono'] ?? '');


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

        // Actualizamos las variables de sesión
        $_SESSION['nombre']   = $nombre;
        $_SESSION['apellido'] = $apellido;
        $_SESSION['correo']   = $correo;
        $_SESSION['telefono'] = $telefono;

        // Redirección limpia por cabecera HTTP en lugar de JS
        header("Location: configuracion.php?status=success");
        exit();

    } catch (PDOException $e) {
        // Si hay un error de base de datos, lo mostramos en pantalla en lugar de dejarla en blanco
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