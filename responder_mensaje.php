
<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {

    // Verificar sesión de empresa
    if (
        !isset($_SESSION['id_proveedor']) &&
        !isset($_SESSION['IdUsuario']) &&
        !isset($_SESSION['nombre_empresa']) &&
        !isset($_SESSION['usuario_nombre'])
    ) {
        echo json_encode([
            'success' => false,
            'message' => 'Sesión no válida. Inicia sesión nuevamente.'
        ]);
        exit();
    }

    // IMPORTANTE: utilizar el nombre real de la empresa
    $remitente = trim(
        $_SESSION['nombre_empresa']
        ?? $_SESSION['usuario_nombre']
        ?? ''
    );

    $destinatario = trim($_POST['destinatario'] ?? '');
    $idVehiculo = filter_var(
        $_POST['id_vehiculo'] ?? null,
        FILTER_VALIDATE_INT
    );
    $mensaje = trim($_POST['mensaje'] ?? '');

    // Validaciones
    if ($remitente === '' || $destinatario === '' ||
        $mensaje === '' || !$idVehiculo || $idVehiculo <= 0) {

        echo json_encode([
            'success' => false,
            'message' => 'Faltan datos para enviar el mensaje.'
        ]);
        exit();
    }

    // Guardar mensaje
    $sql = "INSERT INTO mensajes_chat
            (id_vehiculo, remitente, destinatario, mensaje, fecha, leido)
            VALUES
            (:id_vehiculo, :remitente, :destinatario, :mensaje, NOW(), 0)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':id_vehiculo' => $idVehiculo,
        ':remitente' => $remitente,
        ':destinatario' => $destinatario,
        ':mensaje' => $mensaje
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado correctamente.'
    ]);

} catch (PDOException $e) {

    error_log('Error al enviar mensaje: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar el mensaje en la base de datos.'
    ]);
}
