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

    try {
        // 1. Consultar los datos actuales del usuario en la base de datos
        $stmtSelect = $pdo->prepare("SELECT nombre, apellido, correo, num_telefono FROM usuario WHERE id_usuario = :id");
        $stmtSelect->execute([':id' => $idUsuario]);
        $currentUser = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        if (!$currentUser) {
            echo "<script>alert('Usuario no encontrado.'); window.location.href='configuracion.php';</script>";
            exit();
        }

        // 2. Si el usuario envió texto nuevo, lo usamos; si dejó el campo vacío, mantenemos el valor actual de la BD
        $nombre   = !empty(trim($_POST['nombre'] ?? '')) ? trim($_POST['nombre']) : ($currentUser['nombre'] ?? '');
        $apellido = !empty(trim($_POST['apellido'] ?? '')) ? trim($_POST['apellido']) : ($currentUser['apellido'] ?? '');
        $correo   = !empty(trim($_POST['correo'] ?? '')) ? trim($_POST['correo']) : ($currentUser['correo'] ?? '');
        $telefono = !empty(trim($_POST['numTelefono'] ?? '')) ? trim($_POST['numTelefono']) : ($currentUser['num_telefono'] ?? '');

        // 3. Validación flexible: Solo exigimos que Nombre y Correo tengan contenido obligatorio
        if (empty($nombre) || empty($correo)) {
            echo "<script>alert('El nombre y el correo electrónico son obligatorios.'); window.history.back();</script>";
            exit();
        }

        // 4. Ejecutar la actualización en PostgreSQL
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

        // 5. Actualizar las variables de sesión
       // 5. Actualizar las variables de sesión (ambas versiones para evitar desincronizaciones)
        $_SESSION['nombre']         = $nombre;
        $_SESSION['usuario_nombre'] = $nombre; // <- ¡Esta es la que lee tu dashboard!
        $_SESSION['apellido']       = $apellido;
        $_SESSION['correo']         = $correo;
        $_SESSION['telefono']       = $telefono;

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