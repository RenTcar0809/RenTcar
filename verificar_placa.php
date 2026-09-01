<?php
// verificar_placa.php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = mysqli_connect("localhost", "root", "", "rentcar");

if (!$conexion) {
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

// Recibir la placa enviada por JavaScript
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['placa'])) {
    $placa = $data['placa'];

    // Buscar si la placa ya existe en la tabla vehiculo
    $sql = "SELECT id_v FROM vehiculo WHERE placa = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $placa);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    // Si hay más de 0 filas, la placa existe
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['existe' => true]);
    } else {
        echo json_encode(['existe' => false]);
    }

    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['error' => 'No se recibió la placa']);
}

mysqli_close($conexion);
?>