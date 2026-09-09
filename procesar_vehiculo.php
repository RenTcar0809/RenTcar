<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pagina_formulario = "indexV.php"; 

if (!isset($_SESSION['IdUsuario'])) {
    die("❌ ERROR: No hay una sesión activa.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_registro_v'])) {
    
    // Incluimos la conexión unificada PDO
    require_once 'conexion.php';

    $id_proveedor = $_SESSION['IdUsuario']; 
    $marca        = trim($_POST['marca']);
    $modelo       = trim($_POST['modelo']);
    $tipo         = $_POST['tipo'];         
    $color        = trim($_POST['color']);
    $placa        = strtoupper(trim($_POST['placa']));
    $motor        = trim($_POST['motor']);
    $transmision  = $_POST['transmision']; 
    $traccion     = trim($_POST['traccion'] ?? '');
    $num_motor    = trim($_POST['num_motor']);  // Cambiado a texto por si contiene letras (VIN)
    $num_chasis   = trim($_POST['num_chasis']); // Cambiado a texto por si contiene letras (VIN)
    $asientos     = intval($_POST['asientos'] ?? 0);   
    $precio       = floatval($_POST['precio']); 

    // Validación de formato de placa
    if (!preg_match('/^[A-Z]{3}[0-9]{3}$/', $placa)) {
        $_SESSION['error_placa'] = "El formato debe ser de 3 letras y 3 números (Ej: ABC123).";
        header("Location: " . $pagina_formulario);
        exit();
    }

    try {
        // Consulta de inserción con PDO y parámetros seguros
        $sql = "INSERT INTO vehiculo (id_proveedor, tipo, num_motor, num_chasis, traccion, motor, transmision, color, marca, placa, modelo, precio, asientos) 
                VALUES (:id_proveedor, :tipo, :num_motor, :num_chasis, :traccion, :motor, :transmision, :color, :marca, :placa, :modelo, :precio, :asientos)";

        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':id_proveedor' => $id_proveedor,
            ':tipo'         => $tipo,
            ':num_motor'    => $num_motor,
            ':num_chasis'   => $num_chasis,
            ':traccion'     => $traccion,
            ':motor'        => $motor,
            ':transmision'  => $transmision,
            ':color'        => $color,
            ':marca'        => $marca,
            ':placa'        => $placa,
            ':modelo'       => $modelo,
            ':precio'       => $precio,
            ':asientos'     => $asientos
        ]);

        // Capturamos el ID generado por PostgreSQL de forma segura con PDO
        $id_nuevo_vehiculo = $pdo->lastInsertId();
        
        // Redirigimos a la carga de fotos con el ID del vehículo
        header("Location: subirfoto.php?id=" . $id_nuevo_vehiculo);
        exit();

    } catch (PDOException $e) {
        // En PostgreSQL el código de violación de llave única (duplicado) suele ser '23505'
        // (En MySQL era 1062, adaptamos esto para que funcione perfecto en Render)
        if ($e->getCode() == '23505' || strpos($e->getMessage(), 'unique') !== false) {
            $_SESSION['error_placa'] = "Esta placa ya se encuentra registrada en el sistema.";
        } else {
            $_SESSION['error_placa'] = "Error inesperado en la BD: " . $e->getMessage();
        }
        
        header("Location: " . $pagina_formulario);
        exit();
    }

} else {
    header("Location: dashboard.php");
    exit();
}
?>