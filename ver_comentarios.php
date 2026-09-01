<?php
session_start();
require_once 'conexion.php';

// 1. Verificar sesión del proveedor
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php");
    exit();
}

$id_v = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_proveedor = $_SESSION['IdUsuario'];

try {
    // 2. Verificar que el vehículo pertenece al proveedor logueado (Seguridad)
    $stmt = $pdo->prepare("SELECT * FROM vehiculo WHERE id_v = ? AND id_proveedor = ?");
    $stmt->execute([$id_v, $id_proveedor]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo) {
        die("Acceso denegado o vehículo no encontrado.");
    }

    // 3. Obtener todos los comentarios
    $stmtCom = $pdo->prepare("SELECT * FROM comentarios_vehiculos WHERE id_vehiculo = ? ORDER BY fecha DESC");
    $stmtCom->execute([$id_v]);
    $comentarios = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

    // 4. Calcular estadísticas
    $total = count($comentarios);
    $promedio = 0;
    $estrellas = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

    if ($total > 0) {
        $suma = 0;
        foreach ($comentarios as $c) {
            $p = $c['puntuacion'] ?? 5; // Por defecto 5 si no hay puntuación
            $suma += $p;
            $estrellas[$p]++;
        }
        $promedio = round($suma / $total, 1);
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Lógica de imagen para la cabecera
$fotosFisicas = glob("imagenes/*" . $id_v . "_*");
$imgCabecera = !empty($fotosFisicas) ? $fotosFisicas[0] : 'unnamed.png';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Opiniones - <?php echo $vehiculo['marca']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --rojo: #e50914; --oscuro: #0b0b0b; --card: #1a1a1a; }
        body { background: var(--oscuro); color: white; font-family: 'Roboto', sans-serif; margin: 0; }
        
        .container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }

        /* CABECERA RESUMEN */
        .vehicle-header { 
            display: flex; align-items: center; gap: 25px; background: var(--card); 
            padding: 20px; border-radius: 15px; border: 1px solid #333; margin-bottom: 30px;
        }
        .img-mini { width: 120px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #444; }
        .vehicle-header h1 { font-family: 'Bangers'; font-size: 2.2rem; margin: 0; letter-spacing: 1px; }
        .btn-back { margin-left: auto; color: #888; text-decoration: none; font-weight: bold; border: 1px solid #444; padding: 10px 20px; border-radius: 50px; transition: 0.3s; }
        .btn-back:hover { background: var(--rojo); color: white; border-color: var(--rojo); }

        /* DASHBOARD DE COMENTARIOS */
        .reviews-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }

        .stats-box { background: var(--card); padding: 30px; border-radius: 15px; height: fit-content; text-align: center; }
        .big-number { font-size: 4.5rem; font-weight: bold; display: block; line-height: 1; color: white; }
        .stars-main { color: #f1c40f; font-size: 1.4rem; margin: 15px 0; }
        
        /* Barras de progreso */
        .bar-container { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 0.85rem; color: #aaa; }
        .bar-bg { flex: 1; height: 8px; background: #333; border-radius: 4px; overflow: hidden; }
        .bar-fill { height: 100%; background: #f1c40f; border-radius: 4px; }

        /* LISTA DE COMENTARIOS */
        .comment-card { 
            background: var(--card); padding: 25px; border-radius: 15px; margin-bottom: 20px; 
            border: 1px solid #252525; transition: 0.3s;
        }
        .comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .user-info { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 45px; height: 45px; background: linear-gradient(45deg, #e50914, #ff5f6d); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
        .user-name b { display: block; font-size: 1rem; }
        .user-name small { color: #666; font-size: 0.75rem; }
        .comment-stars { color: #f1c40f; font-size: 0.8rem; }
        .comment-text { color: #ccc; line-height: 1.6; font-size: 0.95rem; margin: 0; }

        @media (max-width: 768px) {
            .reviews-layout { grid-template-columns: 1fr; }
            .vehicle-header { flex-direction: column; text-align: center; }
            .btn-back { margin: 10px auto; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Cabecera del Vehículo -->
    <div class="vehicle-header">
        <img src="<?php echo $imgCabecera; ?>" class="img-mini" onerror="this.src='unnamed.png'">
        <div>
            <h1><?php echo strtoupper($vehiculo['marca'] . " " . $vehiculo['modelo']); ?></h1>
            <p style="color: #666; margin: 5px 0 0 0;">Placa: <?php echo $vehiculo['placa']; ?></p>
        </div>
        <a href="historial_vehiculo.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <div class="reviews-layout">
        <!-- Columna Izquierda: Estadísticas -->
        <aside class="stats-box">
            <span class="big-number"><?php echo $promedio; ?></span>
            <div class="stars-main">
                <?php 
                for($i=1; $i<=5; $i++) {
                    echo ($i <= round($promedio)) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                }
                ?>
            </div>
            <p style="color: #888;"><?php echo $total; ?> opiniones totales</p>
            
            <div style="margin-top: 30px;">
                <?php foreach([5,4,3,2,1] as $num): 
                    $porcentaje = ($total > 0) ? ($estrellas[$num] / $total) * 100 : 0;
                ?>
                <div class="bar-container">
                    <span><?php echo $num; ?> <i class="fas fa-star" style="font-size: 0.7rem;"></i></span>
                    <div class="bar-bg"><div class="bar-fill" style="width: <?php echo $porcentaje; ?>%"></div></div>
                    <span style="width: 25px;"><?php echo $estrellas[$num]; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Columna Derecha: Lista de Comentarios -->
        <section class="comments-list">
            <?php if (empty($comentarios)): ?>
                <div class="comment-card" style="text-align: center; padding: 50px;">
                    <i class="fas fa-comment-slash" style="font-size: 3rem; color: #333; margin-bottom: 15px;"></i>
                    <p style="color: #666;">Aún no hay opiniones de clientes para este vehículo.</p>
                </div>
            <?php else: ?>
                <?php foreach ($comentarios as $c): ?>
                <div class="comment-card">
                    <div class="comment-header">
                        <div class="user-info">
                            <div class="avatar"><?php echo strtoupper(substr($c['usuario_nombre'], 0, 1)); ?></div>
                            <div class="user-name">
                                <b><?php echo htmlspecialchars($c['usuario_nombre']); ?></b>
                                <small><?php echo date('d M, Y', strtotime($c['fecha'])); ?></small>
                            </div>
                        </div>
                        <div class="comment-stars">
                            <?php 
                            $p = $c['puntuacion'] ?? 5;
                            for($i=1; $i<=5; $i++) echo ($i <= $p) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                            ?>
                        </div>
                    </div>
                    <p class="comment-text">"<?php echo nl2br(htmlspecialchars($c['comentario'])); ?>"</p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

</body>
</html>