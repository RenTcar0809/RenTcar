<?php
session_start();
// Cambia esto por tu archivo de conexión real si usas PDO, aquí seguimos con mysqli por tu ejemplo
$conexion = mysqli_connect("localhost", "root", "", "rentcar");

if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['IdUsuario'];

// Consulta mejorada para traer los vehículos
$query = mysqli_query($conexion, "SELECT * FROM vehiculo WHERE id_proveedor = '$id_usuario' ORDER BY id_v DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Mis Vehículos | RentCar</title>
    <link rel="stylesheet" href="historialV.css">
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Iconos para mejor accesibilidad -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos rápidos para complementar tu historialV.css */
        :root { --rojo: #e50914; --oscuro: #111; --card: #1a1a1a; }
        body { background-color: #0b0b0b; color: white; font-family: 'Inter', sans-serif; }
        
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .vehiculo-card { 
            background: var(--card); border-radius: 15px; overflow: hidden; 
            border: 1px solid #333; transition: 0.3s; margin-bottom: 20px;
        }
        .vehiculo-card:hover { border-color: var(--rojo); transform: translateY(-5px); }

        .stats-badges { display: flex; gap: 10px; margin: 10px 0; }
        .badge { 
            padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; 
            background: #252525; display: flex; align-items: center; gap: 5px; 
        }
        .badge.comments { color: #3498db; }
        .badge.price { color: #2ecc71; }

        .btn-grid-actions { 
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px; 
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
        .btn-comments { background: #1a1a1a; color: #3498db; border: 1px solid #3498db; grid-column: span 2; }
        .btn-comments:hover { background: #3498db; color: white; }
        .btn-delete { background: rgba(229, 9, 20, 0.1); color: var(--rojo); border: 1px solid var(--rojo); grid-column: span 2; }
        .btn-delete:hover { background: var(--rojo); color: white; }

        .galeria-grid { display: grid; grid-template-columns: repeat(2, 1fr); height: 180px; background: #000; }
        .img-box img { width: 100%; height: 90px; object-fit: cover; }
    </style>
</head>
<body>

    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        
        <div class="header-actions">
            <a href="dashboardf.php" class="btn-volver" style="text-decoration: none; color: #777;">
                <i class="fas fa-chevron-left"></i> Volver
            </a>
            <h1 class="titulo-principal" style="font-family: 'Bangers'; font-size: 2.5rem; margin: 0;">GESTIÓN DE FLOTA</h1>
            <a href="indexV.php" class="btn-accion" style="background: var(--rojo); padding: 12px 25px; border-radius: 8px; text-decoration: none; color: white; font-weight: bold;">
                <i class="fas fa-plus"></i> NUEVA UNIDAD
            </a>
        </div>

        <?php if (mysqli_num_rows($query) == 0): ?>
            <div class="empty-state-container" style="text-align: center; padding: 80px 20px;">
                <i class="fas fa-car-side" style="font-size: 4rem; color: #333; margin-bottom: 20px;"></i>
                <h2>No tienes vehículos activos</h2>
                <p>Publica un vehículo para empezar a generar ingresos.</p>
            </div>
        <?php else: ?>
            <div class="vehiculos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
                <?php while($row = mysqli_fetch_assoc($query)): 
                    $id_v = $row['id_v'];
                    
                    // 1. Obtener fotos (limitadas a 4 para el diseño de rejilla)
                    $fotos_query = mysqli_query($conexion, "SELECT ruta_imagen FROM fotos_vehiculos WHERE id_vehiculo = '$id_v' LIMIT 4");
                    
                    // 2. Contar comentarios para este vehículo
                    $count_comentarios_query = mysqli_query($conexion, "SELECT COUNT(*) as total FROM comentarios_vehiculos WHERE id_vehiculo = '$id_v'");
                    $total_comentarios = mysqli_fetch_assoc($count_comentarios_query)['total'];
                ?>
                <div class="vehiculo-card">
                    <div class="galeria-grid">
                        <?php 
                        $cont = 0;
                        while($foto = mysqli_fetch_assoc($fotos_query)): $cont++; ?>
                            <div class="img-box">
                                <img src="<?php echo htmlspecialchars($foto['ruta_imagen']); ?>" 
                                     onerror="this.src='imagenes/nissan.png';">
                            </div>
                        <?php endwhile; 
                        // Rellenar espacios vacíos si el vehículo tiene menos de 4 fotos
                        for($i = $cont; $i < 4; $i++) echo '<div class="img-box" style="background:#222; display:flex; align-items:center; justify-content:center; color:#444;"><i class="fas fa-image"></i></div>';
                        ?>
                    </div>

                    <div class="info-container" style="padding: 20px;">
                        <h3 style="margin: 0 0 10px 0; font-size: 1.4rem;">
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
                            <strong>Estado:</strong> <span style="color: #2ecc71;"><?php echo $row['estado']; ?></span>
                        </p>
                        
                        <div class="btn-grid-actions">
                            <!-- Opción para modificar datos técnicos -->
                            <a href="procesar_editar.php?id=<?php echo $id_v; ?>" class="btn-mini btn-edit">
                                <i class="fas fa-edit"></i> Editar Datos
                            </a>
                            
                            <!-- Opción para las imágenes -->
                            <a href="subirfotos.php?id=<?php echo $id_v; ?>" class="btn-mini btn-photos">
                                <i class="fas fa-camera"></i> Fotos
                            </a>

                            <!-- Ver lo que dicen los clientes -->
                            <a href="ver_comentarios.php?id=<?php echo $id_v; ?>" class="btn-mini btn-comments">
                                <i class="fas fa-star"></i> Ver Comentarios del Cliente
                            </a>

                            <!-- Retirar -->
                            <form action="eliminar_vehiculo.php" method="POST" onsubmit="return confirm('¿Está seguro de retirar este vehículo? Esta acción no se puede deshacer.');" style="grid-column: span 2;">
                                <input type="hidden" name="id_v" value="<?php echo $id_v; ?>">
                                <button type="submit" class="btn-mini btn-delete" style="width: 100%;">
                                    <i class="fas fa-trash-alt"></i> Retirar del Mercado
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>