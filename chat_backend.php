<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Evitar que PHP muestre errores dentro de la respuesta JSON
ini_set('display_errors', '0');

function responder($status, $message = '', $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit();
}

try {

    // ==========================================
    // 1. IDENTIFICAR AL USUARIO ACTUAL
    // ==========================================

    $usuario_actual = trim(
        $_SESSION['usuario_nombre']
        ?? $_SESSION['usuario']
        ?? ''
    );

    if ($usuario_actual === '') {
        responder('error', 'No hay una sesión activa. Inicia sesión nuevamente.');
    }

    // ==========================================
    // 2. OBTENER ACCIÓN Y DATOS
    // ==========================================

    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

    $id_item = filter_var(
        $_POST['id_item']
        ?? $_POST['id_vehiculo']
        ?? $_GET['id_item']
        ?? $_GET['id_vehiculo']
        ?? 0,
        FILTER_VALIDATE_INT
    );

    $id_item = $id_item ?: 0;

    $interlocutor_enviado = trim(
        $_POST['destinatario']
        ?? $_GET['destinatario']
        ?? ''
    );

    // ==========================================
    // 3. VERIFICAR NOTIFICACIONES
    // ==========================================

    if ($accion === 'verificar_nuevos') {

        $stmtN = $pdo->prepare("
            SELECT
                id_vehiculo,
                remitente,
                COUNT(*) AS total
            FROM mensajes_chat
            WHERE destinatario = :usuario
              AND leido = 0
            GROUP BY id_vehiculo, remitente
        ");

        $stmtN->execute([
            ':usuario' => $usuario_actual
        ]);

        $conteo = $stmtN->fetchAll(PDO::FETCH_ASSOC);

        responder('success', '', [
            'notificaciones' => $conteo
        ]);
    }

    // ==========================================
    // 4. OBTENER EMPRESA PROPIETARIA Y DETERMINAR INTERLOCUTOR
    // ==========================================

    $dueno = '';

    if ($id_item > 0) {

        $stmtV = $pdo->prepare("
            SELECT id_v, id_proveedor
            FROM vehiculo
            WHERE id_v = :id
        ");

        $stmtV->execute([
            ':id' => $id_item
        ]);

        $vehiculo = $stmtV->fetch(PDO::FETCH_ASSOC);

        if ($vehiculo && !empty($vehiculo['id_proveedor'])) {
            $stmtU = $pdo->prepare("
                SELECT nombre, empresa, tipo
                FROM usuario
                WHERE id_usuario = :id
            ");

            $stmtU->execute([
                ':id' => $vehiculo['id_proveedor']
            ]);

            $proveedor = $stmtU->fetch(PDO::FETCH_ASSOC);

            if ($proveedor) {
                if ((int)$proveedor['tipo'] === 1) {
                    $dueno = trim($proveedor['empresa'] ?? '');
                } else {
                    $dueno = trim($proveedor['nombre'] ?? '');
                }
            }
        }
    }

    // Definir quién es el otro usuario según quién esté realizando la petición (la empresa o el cliente)
    $otro_usuario = '';
    if ($usuario_actual === $dueno) {
        // Si el usuario actual es la empresa, el interlocutor es el cliente enviado desde la interfaz
        $otro_usuario = $interlocutor_enviado;
    } else {
        // Si el usuario actual es el cliente, el interlocutor es la empresa propietaria
        $otro_usuario = $dueno;
    }

    // ==========================================
    // 5. ELIMINAR CONVERSACIÓN
    // ==========================================

    if ($accion === 'eliminar_chat') {

        if ($id_item <= 0 || $interlocutor_enviado === '') {
            responder('error', 'Faltan datos para eliminar la conversación.');
        }

        $stmtDel = $pdo->prepare("
            DELETE FROM mensajes_chat
            WHERE id_vehiculo = :vehiculo
              AND (
                    (remitente = :usuario1 AND destinatario = :destino1)
                    OR
                    (remitente = :destino2 AND destinatario = :usuario2)
              )
        ");

        $stmtDel->execute([
            ':vehiculo' => $id_item,
            ':usuario1' => $usuario_actual,
            ':destino1' => $interlocutor_enviado,
            ':destino2' => $interlocutor_enviado,
            ':usuario2' => $usuario_actual
        ]);

        responder('success', 'Conversación eliminada correctamente.');
    }

    // ==========================================
    // 6. ENVIAR MENSAJE
    // ==========================================

    if ($accion === 'enviar') {

        $mensaje = trim($_POST['mensaje'] ?? '');

        if ($id_item <= 0) {
            responder('error', 'El identificador del vehículo no es válido.');
        }

        if ($mensaje === '') {
            responder('error', 'No puedes enviar un mensaje vacío.');
        }

        if ($dueno === '') {
            responder('error', 'No se encontró la empresa propietaria del vehículo.');
        }

        // Si la empresa es la que envía, el destinatario debe ser el cliente con el que está chateando
        $destinatario_final = '';
        if ($usuario_actual === $dueno) {
            $destinatario_final = $interlocutor_enviado;
            if ($destinatario_final === '') {
                responder('error', 'No se especificó el cliente destinatario.');
            }
        } else {
            // Si el cliente es el que envía, el destinatario es la empresa
            $destinatario_final = $dueno;
        }

        if ($usuario_actual === $destinatario_final) {
            responder('error', 'No puedes enviarte mensajes a ti mismo.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO mensajes_chat (
                id_vehiculo,
                remitente,
                destinatario,
                mensaje,
                fecha,
                leido
            )
            VALUES (
                :vehiculo,
                :remitente,
                :destinatario,
                :mensaje,
                NOW(),
                0
            )
            RETURNING id_mensaje
        ");

        $stmt->execute([
            ':vehiculo' => $id_item,
            ':remitente' => $usuario_actual,
            ':destinatario' => $destinatario_final,
            ':mensaje' => $mensaje
        ]);

        $idMensaje = $stmt->fetchColumn();

        responder('success', 'Mensaje enviado correctamente.', [
            'id_mensaje' => $idMensaje,
            'destinatario' => $destinatario_final,
            'id_vehiculo' => $id_item
        ]);
    }

    // ==========================================
    // 7. OBTENER MENSAJES DE LA CONVERSACIÓN
    // ==========================================

    if ($accion === 'obtener') {

        if ($id_item <= 0) {
            responder('error', 'Identificador del vehículo inválido.');
        }

        if ($otro_usuario === '') {
            responder('error', 'No se pudo identificar al interlocutor de la conversación.');
        }

        // Marcar como leídos únicamente los mensajes recibidos por el usuario actual de parte de este interlocutor
        $stmtMarcar = $pdo->prepare("
            UPDATE mensajes_chat
            SET leido = 1
            WHERE destinatario = :usuario
              AND remitente = :interlocutor
              AND id_vehiculo = :vehiculo
              AND leido = 0
        ");

        $stmtMarcar->execute([
            ':usuario' => $usuario_actual,
            ':interlocutor' => $otro_usuario,
            ':vehiculo' => $id_item
        ]);

        // Obtener la conversación bidireccional entre ambos usuarios
        $sql = "
            SELECT
                id_mensaje,
                id_vehiculo,
                remitente,
                destinatario,
                mensaje,
                fecha,
                leido
            FROM mensajes_chat
            WHERE id_vehiculo = :vehiculo
              AND (
                    (remitente = :usuario1 AND destinatario = :interlocutor1)
                    OR
                    (remitente = :interlocutor2 AND destinatario = :usuario2)
              )
            ORDER BY fecha ASC, id_mensaje ASC
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':vehiculo' => $id_item,
            ':usuario1' => $usuario_actual,
            ':interlocutor1' => $otro_usuario,
            ':interlocutor2' => $otro_usuario,
            ':usuario2' => $usuario_actual
        ]);

        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        responder('success', '', [
            'mensajes' => $mensajes,
            'usuario_actual' => $usuario_actual,
            'dueno' => $otro_usuario
        ]);
    }

    // ==========================================
    // 8. ACCIÓN NO RECONOCIDA
    // ==========================================

    responder('error', 'Acción no reconocida.');

} catch (PDOException $e) {

    error_log('Error chat_backend.php: ' . $e->getMessage());
    responder('error', 'Error al procesar el mensaje en la base de datos.');
    
} catch (Throwable $e) {

    error_log('Error general chat_backend.php: ' . $e->getMessage());
    responder('error', 'Ocurrió un error inesperado.');
}
?>