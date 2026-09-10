<?php
session_start();
require_once 'conexion.php';

// 1. Verificar sesión activa
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

// 2. Obtener los parámetros enviados por GET de forma segura
$id_comentario = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_vehiculo = isset($_GET['vehiculo']) ? intval($_GET['vehiculo']) : 0;

if ($id_comentario > 0) {
    try {
        // 3. Verificar que el comentario pertenezca al usuario logueado antes de borrar
        $chk = $pdo->prepare("SELECT usuario_nombre FROM comentarios_vehiculos WHERE id_comentario = ?");
        $chk->execute([$id_comentario]);
        $comentario = $chk->fetch(PDO::FETCH_ASSOC);

        if ($comentario && $comentario['usuario_nombre'] === $_SESSION['usuario_nombre']) {
            // 4. Proceder a eliminar el comentario
            $del = $pdo->prepare("DELETE FROM comentarios_vehiculos WHERE id_comentario = ?");
            $del->execute([$id_comentario]);
        }
    } catch (PDOException $e) {
        // Opcional: Manejar el error de base de datos si ocurre
        // die("Error al eliminar: " . $e->getMessage());
    }
}

// 5. Redirigir de regreso a la vista de detalles del vehículo en la sección de comentarios
if ($id_vehiculo > 0) {
    header("Location: detalles_vehiculo.php?id=$id_vehiculo&msg=deleted#comentarios");
} else {
    header("Location: automoviles.php");
}
exit();
?>