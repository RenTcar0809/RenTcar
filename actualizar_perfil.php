<?php
session_start();
require 'conexion.php'; // <--- Aquí conectas la base de datos

// Si el usuario no está logueado, lo mandamos afuera
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

// Lógica de guardado si se presionó el botón
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_actualizar'])) {
    try {
        $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, 
                correo = :correo, numTelefono = :numTelefono 
                WHERE IdUsuario = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'correo' => $_POST['correo'],
            'numTelefono' => $_POST['numTelefono'],
            'id' => $_SESSION['IdUsuario']
        ]);

        // Actualizamos la sesión para que el cambio se vea inmediato
        $_SESSION['nombre'] = $_POST['nombre'];
        $_SESSION['apellido'] = $_POST['apellido'];
        $_SESSION['correo'] = $_POST['correo'];
        $_SESSION['numTelefono'] = $_POST['numTelefono'];
        
        $mensajeExito = "Perfil actualizado correctamente.";
    } catch (PDOException $e) {
        $error = "Error al guardar: " . $e->getMessage();
    }
}
?>