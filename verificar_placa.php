<?php
// verificar_placa.php
<<<<<<< HEAD
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
=======
header('Content-Type: application/json; charset=utf-8');

// Desactivar la visualización directa de errores HTML para proteger el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // 1. Incluimos la conexión unificada basada en PDO (compatible con Local y Render)
    require_once 'conexion.php';

    // 2. Recibir la placa enviada por JavaScript
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['placa']) && !empty(trim($data['placa']))) {
        $placa = trim($data['placa']);

        // 3. Buscar si la placa ya existe usando PDO y la tabla vehiculo
        $stmt = $pdo->prepare('SELECT id_v FROM vehiculo WHERE placa = :placa');
        $stmt->execute([':placa' => $placa]);
        
        // Si fetch encuentra un registro, significa que la placa ya está registrada
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            echo json_encode(['existe' => true]);
        } else {
            echo json_encode(['existe' => false]);
        }
    } else {
        echo json_encode(['existe' => false, 'error' => 'No se recibió la placa']);
    }

} catch (PDOException $e) {
    // Capturar cualquier error de base de datos de forma limpia en JSON
    echo json_encode(['existe' => false, 'error' => $e->getMessage()]);
}
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
?>