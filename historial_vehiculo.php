<?php
session_start();

require_once 'conexion.php'; 

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['IdUsuario'];

try {
    $stmt = $pdo->prepare('SELECT * FROM vehiculo WHERE id_proveedor = :id_usuario ORDER BY id_v DESC');
    $stmt->execute([':id_usuario' => $id_usuario]);
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Error al cargar los vehículos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Mis Vehículos | RentCar</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --rojo: #e50914; --oscuro: #111; --card: #1a1a1a; }
        * { box-sizing: border-box; }
        body { background-color: #0b0b0b; color: white; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .titulo-principal { font-family: 'Bangers', cursive; font-size: 2.5rem; margin: 0; letter-spacing: 1px; }
        
        .btn-volver { text-decoration: none; color: #aaa; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        .btn-volver:hover { color: white; }
        
        .btn-accion { background: var(--rojo); padding: 12px 25px; border-radius: 8px; text-decoration: none; color: white; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-accion:hover { background: #b20710; }

        .vehiculos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        
        .vehiculo-card { 
            background: var(--card); border-radius: 15px; overflow: hidden; 
            border: 1px solid #333; transition: 0.3s; display: flex; flex-direction: column;
        }
        .vehiculo-card:hover { border-color: var(--rojo); transform: translateY(-5px); }

        .galeria-grid { display: grid; grid-template-columns: repeat(2, 1fr); height: 180px; background: #000; }
        .img-box img { width: 100%; height: 90px; object-fit: cover; display: block; }
        .img-placeholder { background: #222; display: flex; align-items: center; justify-content: center; color: #444; height: 90px; }

        .info-container { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
        .stats-badges { display: flex; gap: 10px; margin: 10px 0; flex-wrap: wrap; }
        .badge { 
            padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; 
            background: #252525; display: inline-flex; align-items: center; gap: 5px; 
        }
        .badge.comments { color: #3498db; }
        .badge.price { color: #2ecc71; }

        .btn-grid-actions { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: auto; padding-top: 15px;
        }
        .btn-mini { 
            padding: 10px; border-radius: 8px; text-align: center; 
            text-decoration: none; font-size: 0.85rem; font-weight: bold;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: 0.2s; border: none; cursor: pointer;
        }
        .btn-edit { background: #333; color: white; }
        .btn-edit:hover { background: #444; }
        .btn-photos { background: #333; color: white; }
        .btn-photos:hover { background: #444; }
        .btn-comments { background: #1a1a1a; color: #3498db; border: 1px solid #3498db; grid-column: span 2; }
        .btn-comments:hover { background: #3498db; color: white; }
        .btn-delete { background: rgba(229, 9, 20, 0.1); color: var(--rojo); border: 1px solid var(--rojo); width: 100%; }
        .btn-delete:hover { background: var(--rojo); color: white; }

        .empty-state-container { text-align: center; padding: 80px 20px; background: var(--card); border-radius: 15px; border: 1px solid #333; }
    </style>
</head>
<body>

    <div class="container">
        
        <div class="header-actions">
            <a href="dashboardf.php" class="btn-volver">
                <i class="fas fa-chevron-left"></i> Volver
            </a>
            <h1 class="titulo-principal">GESTIÓN DE FLOTA</h1>
            <a href="indexV.php" class="btn-accion">
                <i class="fas fa-plus"></i> NUEVA UNIDAD
            </a>
        </div>

        <?php if (isset($error_msg)): ?>
            <div style="background: rgba(229,9,20,0.2); border: 1px solid var(--rojo); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin:0;"><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        <?php elseif (empty($vehiculos)): ?>
            <div class="empty-state-container">
                <i class="fas fa-car-side" style="font-size: 4rem; color: #444; margin-bottom: 20px;"></i>
                <h2 style="margin: 10px 0; color: #fff;">No tienes vehículos activos</h2>
                <p style="color: #888; margin: 0;">Publica un vehículo para empezar a generar ingresos.</p>
            </div>
        <?php else: ?>
            <div class="vehiculos-grid">
                <?php foreach($vehiculos as $row): 
                    $id_v = $row['id_v'];
                    
                    $stmt_fotos = $pdo->prepare('SELECT ruta_imagen FROM fotos_vehiculos WHERE id_vehiculo = :id_v LIMIT 4');
                    $stmt_fotos->execute([':id_v' => $id_v]);
                    $fotos = $stmt_fotos->fetchAll(PDO::FETCH_ASSOC);
                    
                    $stmt_comentarios = $pdo->prepare('SELECT COUNT(*) as total FROM comentarios_vehiculos WHERE id_vehiculo = :id_v');
                    $stmt_comentarios->execute([':id_v' => $id_v]);
                    $total_comentarios = $stmt_comentarios->fetch(PDO::FETCH_ASSOC)['total'];
                ?>
                <div class="vehiculo-card">
                    <div class="galeria-grid">
                        <?php 
                        $cont = 0;
                        foreach($fotos as $foto): $cont++; ?>
                            <div class="img-box">
                                <img src="<?php echo htmlspecialchars($foto['ruta_imagen']); ?>" 
                                     onerror="this.src='imagenes/nissan.png';">
                            </div>
                        <?php endforeach; 
                        
                        for($i = $cont; $i < 4; $i++) {
                            echo '<div class="img-placeholder"><i class="fas fa-image"></i></div>';
                        }
                        ?>
                    </div>

                    <div class="info-container">
                        <h3 style="margin: 0 0 10px 0; font-size: 1.4rem; color: #fff;">
                            <?php echo htmlspecialchars($row['marca'] . " " . $row['modelo']); ?>
                        </h3>
                        
                        <div class="stats-badges">
                            <span class="badge comments">
                                <i class="fas fa-comment"></i> <?php echo $total_comentarios; ?> Opiniones
                            </span>
                            <span class="badge price">
                                <i class="fas fa-tag"></i> $<?php echo number_format($row['precio'], 0); ?>/día
                            </span>
                        </div>

                        <p style="font-size: 0.9rem; color: #888; margin: 15px 0;">
                            <strong>Placa:</strong> <?php echo htmlspecialchars($row['placa']); ?> | 
                            <strong>Estado:</strong> <span style="color: #2ecc71;"><?php echo htmlspecialchars($row['estado']); ?></span>
                        </p>
                        
                        <div class="btn-grid-actions">
                            <a href="procesar_editar.php?id=<?php echo $id_v; ?>" class="btn-mini btn-edit">
                                <i class="fas fa-edit"></i> Editar Datos
                            </a>
                            
                            <a href="subirfotos.php?id=<?php echo $id_v; ?>" class="btn-mini btn-photos">
                                <i class="fas fa-camera"></i> Fotos
                            </a>

                            <a href="ver_comentarios.php?id=<?php echo $id_v; ?>" class="btn-mini btn-comments">
                                <i class="fas fa-star"></i> Ver Comentarios del Cliente
                            </a>

                            <form action="eliminar_vehiculo.php" method="POST" onsubmit="return confirm('¿Está seguro de retirar este vehículo? Esta acción no se puede deshacer.');" style="grid-column: span 2; margin: 0;">
                                <input type="hidden" name="id_v" value="<?php echo $id_v; ?>">
                                <button type="submit" class="btn-mini btn-delete">
                                    <i class="fas fa-trash-alt"></i> Retirar del Mercado
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>