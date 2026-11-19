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

function obtenerOcrearIdArrendatario(PDO $pdo, int $idUsuarioCliente, string $nombreCliente): int {
    $stmtArr = $pdo->prepare("SELECT id_arrendatario FROM arrendatario WHERE id_usuario = ? ORDER BY id_arrendatario ASC LIMIT 1");
    $stmtArr->execute([$idUsuarioCliente]);
    $idArr = $stmtArr->fetchColumn();
    if ($idArr) {
        return intval($idArr);
    }

    $nombreReal = trim($nombreCliente) !== '' ? trim($nombreCliente) : 'Cliente';
    $apellidoReal = 'N/A';
    $correoReal = 'cliente_' . $idUsuarioCliente . '@rentcar.com';

    try {
        $stmtU = $pdo->prepare("SELECT nombre, apellido, correo FROM usuario WHERE id_usuario = ? LIMIT 1");
        $stmtU->execute([$idUsuarioCliente]);
        $uRow = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($uRow) {
            if (!empty(trim((string)($uRow['nombre'] ?? '')))) {
                $nombreReal = trim((string)$uRow['nombre']);
            }
            if (!empty(trim((string)($uRow['apellido'] ?? '')))) {
                $apellidoReal = trim((string)$uRow['apellido']);
            }
            if (!empty(trim((string)($uRow['correo'] ?? '')))) {
                $correoReal = trim((string)$uRow['correo']);
            }
        }
    } catch (Throwable $e) {
    }

    $correosAProbar = [
        $correoReal,
        'cliente_' . $idUsuarioCliente . '_' . time() . '@rentcar.com'
    ];
    $cedulasAProbar = [
        -$idUsuarioCliente,
        -(1000000 + $idUsuarioCliente + ((int)(microtime(true) * 100) % 100000))
    ];

    foreach ($correosAProbar as $idx => $correoIntento) {
        $cedulaIntento = $cedulasAProbar[$idx] ?? (-($idUsuarioCliente * 100 + $idx + 1));
        try {
            $stmtInsArr = $pdo->prepare("
                INSERT INTO arrendatario (id_usuario, nombre, apellido, cedula, telefono, correo, direccion)
                VALUES (?, ?, ?, ?, 0, ?, 'N/A')
                RETURNING id_arrendatario
            ");
            $stmtInsArr->execute([$idUsuarioCliente, $nombreReal, $apellidoReal, $cedulaIntento, $correoIntento]);
            $idNuevo = $stmtInsArr->fetchColumn();
            if ($idNuevo) {
                return intval($idNuevo);
            }
        } catch (Throwable $e) {
        }
    }

    $stmtArr2 = $pdo->prepare("SELECT id_arrendatario FROM arrendatario WHERE id_usuario = ? ORDER BY id_arrendatario ASC LIMIT 1");
    $stmtArr2->execute([$idUsuarioCliente]);
    $idArr2 = $stmtArr2->fetchColumn();
    if ($idArr2) {
        return intval($idArr2);
    }

    return $idUsuarioCliente;
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

    $cliente_param = trim(
        $_POST['cliente']
        ?? $_GET['cliente']
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
    // 4. VERIFICAR ESTADO DEL PRÉSTAMO (Doble Confirmación)
    // ==========================================

    if ($accion === 'verificar_alquiler') {
        if ($id_item <= 0) {
            responder('error', 'Identificador de vehículo no válido.');
        }

        $prestamo = null;
        $clienteConsulta = $cliente_param !== '' ? $cliente_param : $interlocutor_enviado;

        if ($clienteConsulta !== '') {
            $stmtArr = $pdo->prepare("
                SELECT a.id_arrendatario
                FROM arrendatario a
                LEFT JOIN usuario u ON a.id_usuario = u.id_usuario
                WHERE TRIM(a.nombre) ILIKE TRIM(:cliente)
                   OR TRIM(u.nombre) ILIKE TRIM(:cliente)
                   OR TRIM(u.empresa) ILIKE TRIM(:cliente)
                ORDER BY a.id_arrendatario DESC
                LIMIT 1
            ");
            $stmtArr->execute([':cliente' => $clienteConsulta]);
            $idArr = $stmtArr->fetchColumn();

            if ($idArr) {
                $stmtPrestamo = $pdo->prepare("
                    SELECT * FROM prestamo
                    WHERE id_vehiculo = :vehiculo
                      AND id_arrendatario = :arrendatario
                      AND LOWER(estado) IN ('pendiente', 'activo')
                    ORDER BY id_prestamo DESC
                    LIMIT 1
                ");
                $stmtPrestamo->execute([
                    ':vehiculo' => $id_item,
                    ':arrendatario' => $idArr
                ]);
                $prestamo = $stmtPrestamo->fetch(PDO::FETCH_ASSOC);
            }
        }

        if (!$prestamo) {
            $stmtPrestamo = $pdo->prepare("
                SELECT * FROM prestamo
                WHERE id_vehiculo = :vehiculo
                  AND LOWER(estado) IN ('pendiente', 'activo')
                ORDER BY id_prestamo DESC
                LIMIT 1
            ");
            $stmtPrestamo->execute([':vehiculo' => $id_item]);
            $prestamo = $stmtPrestamo->fetch(PDO::FETCH_ASSOC);
        }

        if ($prestamo) {
            responder('success', '', [
                'alquiler' => [
                    'estado'                => $prestamo['estado'] ?? 'pendiente',
                    'confirmar_cliente'     => (int)($prestamo['confirmar_cliente'] ?? 0),
                    'confirmar_propietario' => (int)($prestamo['confirmar_propietario'] ?? 0),
                    'finalizar_cliente'     => (int)($prestamo['finalizar_cliente'] ?? 0),
                    'finalizar_propietario' => (int)($prestamo['finalizar_propietario'] ?? 0)
                ]
            ]);
        } else {
            responder('success', '', ['alquiler' => null]);
        }
    }

    // ==========================================
    // 5. OBTENER EMPRESA PROPIETARIA Y DETERMINAR INTERLOCUTOR
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

    $otro_usuario = '';
    if ($usuario_actual === $dueno) {
        $otro_usuario = $interlocutor_enviado;
    } else {
        $otro_usuario = $dueno;
    }

    // ==========================================
    // 6. ELIMINAR CONVERSACIÓN
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
    // 7. ENVIAR MENSAJE
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

        $destinatario_final = '';
        if ($usuario_actual === $dueno) {
            $destinatario_final = $interlocutor_enviado;
            if ($destinatario_final === '') {
                responder('error', 'No se especificó el cliente destinatario.');
            }
        } else {
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
    // 8. OBTENER MENSAJES DE LA CONVERSACIÓN
    // ==========================================

    if ($accion === 'obtener') {

        if ($id_item <= 0) {
            responder('error', 'Identificador del vehículo inválido.');
        }

        if ($otro_usuario === '') {
            responder('error', 'No se pudo identificar al interlocutor de la conversación.');
        }

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
    // 9. CONVERTIR CONVERSACIÓN EN PRÉSTAMO / RESERVA
    // ==========================================

    if ($accion === 'crear_reserva') {

        if ($id_item <= 0) {
            responder('error', 'Identificador de vehículo no válido.');
        }

        if ($interlocutor_enviado === '') {
            responder('error', 'No se especificó el cliente para la reserva.');
        }

        try {
            $pdo->beginTransaction();

            $stmtCli = $pdo->prepare("
                SELECT id_usuario, licencia_verificada, licencia_numero 
                FROM usuario 
                WHERE TRIM(nombre) ILIKE TRIM(:cliente) OR TRIM(empresa) ILIKE TRIM(:cliente) 
                ORDER BY id_usuario ASC
                LIMIT 1
            ");
            $stmtCli->execute([':cliente' => $interlocutor_enviado]);
            $arrendatario = $stmtCli->fetch(PDO::FETCH_ASSOC);

            if (!$arrendatario) {
                throw new Exception("No se encontró el usuario cliente en la base de datos.");
            }

            if (empty($arrendatario['licencia_numero'])) {
                throw new Exception("El usuario no ha registrado un número de licencia de conducir en su perfil.");
            }

            $idUsuarioCliente = (int)$arrendatario['id_usuario'];
            $id_arrendatario = obtenerOcrearIdArrendatario($pdo, $idUsuarioCliente, $interlocutor_enviado);

            $stmtVeh = $pdo->prepare("SELECT precio FROM vehiculo WHERE id_v = :id");
            $stmtVeh->execute([':id' => $id_item]);
            $vehData = $stmtVeh->fetch(PDO::FETCH_ASSOC);
            $monto_vehiculo = $vehData['precio'] ?? 0.00;

            $stmtCheck = $pdo->prepare("SELECT id_prestamo, estado FROM prestamo WHERE id_vehiculo = :vehiculo ORDER BY id_prestamo DESC LIMIT 1");
            $stmtCheck->execute([':vehiculo' => $id_item]);
            $prestamoExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($prestamoExistente && strtolower((string)($prestamoExistente['estado'] ?? '')) === 'activo') {
                throw new Exception("Este vehículo ya cuenta con un préstamo activo.");
            }

            $stmtPrestamo = $pdo->prepare("
                INSERT INTO prestamo (id_vehiculo, id_arrendatario, fecha_inicio, fecha_fin, estado, monto, confirmar_cliente, confirmar_propietario)
                VALUES (:vehiculo, :arrendatario, CURRENT_DATE, CURRENT_DATE + INTERVAL '7 days', 'activo', :monto, 1, 1)
            ");
            $stmtPrestamo->execute([
                ':vehiculo'     => $id_item,
                ':arrendatario' => $id_arrendatario,
                ':monto'        => $monto_vehiculo
            ]);

            $stmtVehiculoUpd = $pdo->prepare("
                UPDATE vehiculo 
                SET estado = 'Alquilado' 
                WHERE id_v = :vehiculo
            ");
            $stmtVehiculoUpd->execute([':vehiculo' => $id_item]);

            $pdo->commit();

            responder('success', 'Préstamo generado correctamente.');

        } catch (Exception $e) {
            $pdo->rollBack();
            responder('error', 'No se pudo formalizar el alquiler: ' . $e->getMessage());
        }
    }

    // ==========================================
    // 10. ACCIÓN NO RECONOCIDA
    // ==========================================

    responder('error', 'Acción no reconocida.');

} catch (PDOException $e) {

    error_log('Error chat_backend.php: ' . $e->getMessage());
    responder('error', 'Error al procesar la solicitud en la base de datos.');
    
} catch (Throwable $e) {

    error_log('Error general chat_backend.php: ' . $e->getMessage());
    responder('error', 'Ocurrió un error inesperado.');
}