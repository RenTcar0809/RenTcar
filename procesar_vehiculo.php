<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pagina_formulario = "indexV.php"; 

if (!isset($_SESSION['IdUsuario'])) {
    die("❌ ERROR: No hay una sesión activa.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_registro_v'])) {
    
    $id_proveedor = $_SESSION['IdUsuario']; 
    $marca        = trim($_POST['marca']);
    $modelo       = trim($_POST['modelo']);
    $tipo         = $_POST['tipo'];         
    $color        = trim($_POST['color']);
    $placa        = strtoupper(trim($_POST['placa']));
    $motor        = trim($_POST['motor']);
    $transmision  = $_POST['transmision']; 
    $traccion     = trim($_POST['traccion'] ?? '');
    $num_motor    = trim($_POST['num_motor']);  
    $num_chasis   = trim($_POST['num_chasis']); 
    $asientos     = intval($_POST['asientos'] ?? 0);   
    $precio       = floatval($_POST['precio']); 

    // Validación de formato de placa
    if (!preg_match('/^[A-Z]{3}[0-9]{3}$/', $placa)) {
        $_SESSION['error_placa'] = "El formato debe ser de 3 letras y 3 números (Ej: ABC123).";
        header("Location: " . $pagina_formulario);
        exit();
    }

    // 💡 GUARDAMOS TEMPORALMENTE LOS DATOS EN LA SESIÓN (Aún no van a la BD)
    $_SESSION['vehiculo_pendiente'] = [
        'id_proveedor' => $id_proveedor,
        'tipo'         => $tipo,
        'num_motor'    => $num_motor,
        'num_chasis'   => $num_chasis,
        'traccion'     => $traccion,
        'motor'        => $motor,
        'transmision'  => $transmision,
        'color'        => $color,
        'marca'        => $marca,
        'placa'        => $placa,
        'modelo'       => $modelo,
        'precio'       => $precio,
        'asientos'     => $asientos
    ];

    // Redirigimos a la carga de fotos SIN haber guardado nada todavía en la BD
    header("Location: subirfoto.php");
    exit();

} else {
    header("Location: dashboardf.php");
    exit();
}
?>