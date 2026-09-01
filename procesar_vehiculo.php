<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pagina_formulario = "indexV.php"; 

if (!isset($_SESSION['IdUsuario'])) {
    die("❌ ERROR: No hay una sesión activa.");
}

if (isset($_POST['enviar_registro_v'])) {
    
    $conexion = mysqli_connect("localhost", "root", "", "rentcar");
    if (!$conexion) { die("Fallo de conexión: " . mysqli_connect_error()); }

    $id_proveedor = $_SESSION['IdUsuario']; 
    $marca        = trim($_POST['marca']);
    $modelo       = trim($_POST['modelo']);
    $tipo         = $_POST['tipo'];         
    $color        = trim($_POST['color']);
    $placa        = strtoupper(trim($_POST['placa']));
    $motor        = trim($_POST['motor']);
    $transmision  = $_POST['transmision']; 
    $traccion     = trim($_POST['traccion']);
    $num_motor    = intval($_POST['num_motor']);  
    $num_chasis   = intval($_POST['num_chasis']); 
    $asientos     = intval($_POST['asientos']);   
    $precio       = floatval($_POST['precio']);   

    if (!preg_match('/^[A-Z]{3}[0-9]{3}$/', $placa)) {
        $_SESSION['error_placa'] = "El formato debe ser de 3 letras y 3 números (Ej: ABC123).";
        header("Location: " . $pagina_formulario);
        exit();
    }

    $sql = "INSERT INTO vehiculo (id_proveedor, tipo, num_motor, num_chasis, traccion, motor, transmision, color, marca, placa, modelo, precio, asientos) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isiisssssssdi", 
            $id_proveedor, $tipo, $num_motor, $num_chasis, $traccion, $motor, $transmision, $color, $marca, $placa, $modelo, $precio, $asientos
        );

        try {
            if (mysqli_stmt_execute($stmt)) {
                // Capturamos el ID generado antes de salir
                $id_nuevo_vehiculo = mysqli_insert_id($conexion);
                
                // Redirigimos a la carga de fotos en lugar de directo al dashboard
                header("Location: subirfoto.php?id=" . $id_nuevo_vehiculo);
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $_SESSION['error_placa'] = "Esta placa ya se encuentra registrada en el sistema.";
                header("Location: " . $pagina_formulario);
                exit();
            } else {
                $_SESSION['error_placa'] = "Error inesperado en la BD: " . $e->getMessage();
                header("Location: " . $pagina_formulario);
                exit();
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($conexion);
} else {
    header("Location: dashboard.php");
    exit();
}
?>