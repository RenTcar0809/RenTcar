<?php
session_start();
// Importamos la conexión con PDO
require 'conexion.php'; 

// Obtenemos el ID del usuario logueado
$id_usuario = isset($_SESSION['IdUsuario']) ? $_SESSION['IdUsuario'] : 1; 

try {
    // Consulta SQL con parámetros preparados de PDO
    $sql = "SELECT r.id_reserva, r.fecha_inicio, r.fecha_fin, r.estado_pago, v.marca, v.modelo, v.placa, v.imagen 
            FROM reservas r 
            INNER JOIN vehiculos v ON r.id_vehiculo = v.id_vehiculo 
            WHERE r.id_usuario = :id_usuario 
            ORDER BY r.fecha_inicio DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_usuario' => $id_usuario]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al consultar las reservas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentCar - Mis Reservas</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    
    <!-- Archivo CSS separado -->
    <link rel="stylesheet" href="historial.css">
</head>
<body>

    <header class="navbar">
        <a href="dashboardf.php" class="logo-container">
            <span class="logo-text">
                <span class="txt-red">REN</span><span class="txt-black">T</span><span class="txt-red">CAR</span>
            </span>
        </a>
    </header>

    <main class="container">
        <h1 class="main-title">HISTORIAL DE <span>RESERVAS</span></h1>

        <?php
        // Verificamos si la lista de reservas no está vacía
        if (!empty($reservas)) {
            
            $fecha_actual = date('Y-m-d'); 

            // Recorremos cada registro devuelto por PDO
            foreach ($reservas as $reserva) {
                
                // LÓGICA DE ESTADOS
                $estado_clase = "";
                $estado_texto = "";

                if (isset($reserva['estado_pago']) && $reserva['estado_pago'] == 'Cancelado') {
                    $estado_clase = "status-inactive";
                    $estado_texto = "INACTIVO (Cancelado)";
                } elseif ($fecha_actual >= $reserva['fecha_inicio'] && $fecha_actual <= $reserva['fecha_fin']) {
                    $estado_clase = "status-active";
                    $estado_texto = "ACTIVO (En curso)";
                } elseif ($fecha_actual > $reserva['fecha_fin']) {
                    $estado_clase = "status-expired";
                    $estado_texto = "VENCIDO (Finalizado)";
                } else {
                    $estado_clase = "status-inactive";
                    $estado_texto = "INACTIVO (Pendiente)";
                }
        ?>
                <!-- Tarjeta de Historial -->
                <div class="history-card">
                    
                    <img src="<?php echo !empty($reserva['imagen']) ? htmlspecialchars($reserva['imagen']) : 'unnamed.png'; ?>" alt="Vehículo" class="vehicle-img">
                    
                    <div class="details">
                        <div class="vehicle-name"><?php echo htmlspecialchars($reserva['marca'] . " " . $reserva['modelo']); ?></div>
                        <div class="info-grid">
                            <div>Placa: <strong><?php echo htmlspecialchars($reserva['placa']); ?></strong></div>
                            <div>Inicio: <strong><?php echo htmlspecialchars($reserva['fecha_inicio']); ?></strong></div>
                            <div>Fin: <strong><?php echo htmlspecialchars($reserva['fecha_fin']); ?></strong></div>
                        </div>
                    </div>

                    <div class="status-badge <?php echo $estado_clase; ?>">
                        <?php echo $estado_texto; ?>
                    </div>
                </div>
        <?php
            }
        } else {
            echo "<p style='text-align:center; color:#888;'>No tienes reservas registradas en tu historial.</p>";
        }
        ?>

    </main>

</body>
</html>