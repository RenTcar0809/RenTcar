<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluimos tu conexión centralizada (que ya detecta PostgreSQL en Render o MySQL local)
require_once 'conexion.php';

$pagina_formulario = "indexV.php"; 

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_registro_v'])) {
    
    // RECOGER DATOS DEL FORMULARIO
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

    // Validación de placa para Colombia (Carros ABC123 y Motos ABC12F / ABC123)
    $patron_placa = '/^([A-Z]{3}[0-9]{3}|[A-Z]{3}[0-9]{2}[A-Z0-9])$/';
    if (!preg_match($patron_placa, $placa)) {
        $_SESSION['error_placa'] = "Formato de placa inválido. Carro (ABC123) o Moto (ABC12F).";
        header("Location: " . $pagina_formulario);
        exit();
    }

    try {
        // Validar si la placa ya existe en la base de datos (funciona igual en Postgres y MySQL)
        $stmt_check = $pdo->prepare("SELECT id FROM vehiculo WHERE placa = ?");
        $stmt_check->execute([$placa]);
        if ($stmt_check->rowCount() > 0) {
            $_SESSION['error_placa'] = "La placa '$placa' ya se encuentra registrada en el sistema.";
            header("Location: " . $pagina_formulario);
            exit();
        }

        // Insertar datos técnicos vinculados al usuario logueado
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

        // Capturar el ID único del vehículo recién creado
        // Nota: Si estás usando PostgreSQL en Render, asegúrate de que tu tabla tenga la secuencia configurada para que lastInsertId() funcione sin problemas.
        $id_vehiculo_nuevo = $pdo->lastInsertId();

        // REDIRECCIÓN PROFESIONAL A SUBIR FOTOS
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

/*

  $cloud_name = "bsd1wma1"; 
    $api_key    = "219554281638733";
    $api_secret = "RTx7SRXjxf0eBi5nWoqQMrxkuv8";
/*