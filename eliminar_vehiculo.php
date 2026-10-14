<?php
session_start();
require_once 'conexion.php';

// Validar sesión activa (compatible con id_usuario e IdUsuario)
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['IdUsuario'] ?? null;

if (!$id_usuario) {
    header("Location: login.php");
    exit();
}

// Verificar que la petición sea por método POST y contenga el ID del vehículo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_v'])) {
    $id_v = intval($_POST['id_v']);

    try {
        // 1. Opcional pero recomendado: Verificar que el vehículo pertenezca realmente al usuario logueado
        // Esto evita que alguien intente borrar el vehículo de otro proveedor manipulando el formulario.
        $stmt_check = $pdo->prepare("SELECT id_v FROM vehiculo WHERE id_v = ? AND id_proveedor = ?");
        $stmt_check->execute([$id_v, $id_usuario]);

        if ($stmt_check->rowCount() > 0) {
            // 2. Eliminar registros relacionados si tienes llaves foráneas sin cascada automática
            // (Por ejemplo: fotos, comentarios, etc. Si tu base de datos ya tiene ON DELETE CASCADE, 
            // PostgreSQL borrará las dependencias automáticamente, pero puedes limpiar por seguridad)
            
            $stmt_fotos = $pdo->prepare("DELETE FROM fotos_vehiculos WHERE id_vehiculo = ?");
            $stmt_fotos->execute([$id_v]);

            $stmt_comentarios = $pdo->prepare("DELETE FROM comentarios_vehiculos WHERE id_vehiculo = ?");
            $stmt_comentarios->execute([$id_v]);

            // 3. Eliminar el vehículo de la tabla principal
            $stmt_delete = $pdo->prepare("DELETE FROM vehiculo WHERE id_v = ? AND id_proveedor = ?");
            $stmt_delete->execute([$id_v, $id_usuario]);

            // Redirigir de vuelta al panel con éxito (puedes crear una variable de sesión para un mensaje si gustas)
            $_SESSION['mensaje_exito'] = "El vehículo ha sido retirado del mercado exitosamente.";
        } else {
            $_SESSION['error_msg'] = "No tienes permisos para eliminar este vehículo o no existe.";
        }

    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Error al eliminar el vehículo: " . $e->getMessage();
    }
}

// Redireccionar siempre al panel principal de gestión de vehículos
header("Location: historial_vehiculo.php"); // Cambia 'panel_vehiculos.php' por el nombre de tu archivo de panel si es diferente
exit();
?>
