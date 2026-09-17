<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluimos tu conexión centralizada (detecta PostgreSQL en Render o MySQL local)
require_once 'conexion.php';

$pagina_formulario = "indexV.php"; 

// Validar que el usuario haya iniciado sesión
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_registro_v'])) {
    
    // 1. RECOGER Y SANGRIAR DATOS DEL FORMULARIO
    $id_proveedor = $_SESSION['IdUsuario']; 
    $tipo         = trim($_POST['tipo'] ?? '');
    $marca        = trim($_POST['marca'] ?? '');
    $modelo       = trim($_POST['modelo'] ?? '');
    $color        = trim($_POST['color'] ?? '');
    $placa        = strtoupper(trim($_POST['placa'] ?? ''));
    $motor        = trim($_POST['motor'] ?? ''); 
    $transmision  = trim($_POST['transmision'] ?? '');
    $traccion     = trim($_POST['traccion'] ?? '');
    $num_motor    = trim($_POST['num_motor'] ?? '');
    $num_chasis   = trim($_POST['num_chasis'] ?? '');
    $asientos     = intval($_POST['asientos'] ?? 5);
    $precio       = floatval($_POST['precio'] ?? 0);

    // 2. VALIDACIÓN DE PLACA PARA COLOMBIA (Carros ABC123 y Motos ABC12F / ABC123)
    $patron_placa = '/^([A-Z]{3}[0-9]{3}|[A-Z]{3}[0-9]{2}[A-Z0-9])$/';
    if (!preg_match($patron_placa, $placa)) {
        $_SESSION['error_placa'] = "Formato de placa inválido. Carro (ABC123) o Moto (ABC12F).";
        header("Location: " . $pagina_formulario);
        exit();
    }

    try {
        // 3. VERIFICAR SI LA PLACA YA EXISTE (Usando id_v)
        $stmt_check = $pdo->prepare("SELECT id_v FROM vehiculo WHERE placa = ?");
        $stmt_check->execute([$placa]);
        
        if ($stmt_check->rowCount() > 0) {
            $_SESSION['error_placa'] = "La placa '$placa' ya se encuentra registrada en el sistema.";
            header("Location: " . $pagina_formulario);
            exit();
        }

        // 4. INSERTAR DATOS TÉCNICOS EN LA BASE DE DATOS
        $sql = "INSERT INTO vehiculo (id_proveedor, tipo, marca, modelo, color, placa, motor, transmision, traccion, num_motor, num_chasis, asientos, precio_dia) 
                VALUES (:id_proveedor, :tipo, :marca, :modelo, :color, :placa, :motor, :transmision, :traccion, :num_motor, :num_chasis, :asientos, :precio)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_proveedor' => $id_proveedor,
            ':tipo'         => $tipo,
            ':marca'        => $marca,
            ':modelo'       => $modelo,
            ':color'        => $color,
            ':placa'        => $placa,
            ':motor'        => $motor,
            ':transmision'  => $transmision,
            ':traccion'     => ($tipo === 'Motocicleta') ? 'N/A' : $traccion,
            ':num_motor'    => $num_motor,
            ':num_chasis'   => $num_chasis,
            ':asientos'     => ($tipo === 'Motocicleta') ? 2 : $asientos,
            ':precio'       => $precio
        ]);

        // 5. CAPTURAR EL ID RECIÉN CREADO (Compatible con PostgreSQL y su llave id_v)
        // Nota: En PostgreSQL se suele especificar el nombre de la secuencia (ej: vehiculos_id_v_seq). 
        // Si estás en local con MySQL, PDO ignorará el parámetro de la secuencia automáticamente.
        try {
            $id_vehiculo_nuevo = $pdo->lastInsertId('vehiculos_id_v_seq');
        } catch (Exception $ex) {
            $id_vehiculo_nuevo = $pdo->lastInsertId(); // Fallback por si la secuencia tiene otro nombre o es MySQL
        }

        // Si por alguna razón no capturó el ID, intentamos consultarlo por la placa recién insertada
        if (!$id_vehiculo_nuevo) {
            $stmt_id = $pdo->prepare("SELECT id_v FROM vehiculos WHERE placa = ?");
            $stmt_id->execute([$placa]);
            $vehiculo_encontrado = $stmt_id->fetch();
            $id_vehiculo_nuevo = $vehiculo_encontrado['id_v'] ?? 0;
        }

        // 6. REDIRECCIÓN PROFESIONAL AL PASO 2 (SUBIR FOTOS)
        header("Location: subirfoto.php?id=" . $id_vehiculo_nuevo);
        exit();

    } catch (PDOException $e) {
        $_SESSION['error_placa'] = "Error en base de datos: " . $e->getMessage();
        header("Location: " . $pagina_formulario);
        exit();
    }

} else {
    header("Location: indexV.php");
    exit();
}
?>