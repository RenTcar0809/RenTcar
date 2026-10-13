<?php
// verificar_placa.php
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
?>