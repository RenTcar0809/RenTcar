<?php
session_start();
require_once 'conexion.php';

// Validar la sesión activa del usuario
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

try {
    // Consulta mejorada: Intenta traer la primera foto de la tabla 'fotos_vehiculos' 
    // y si no hay, usaremos la de la tabla 'vehiculo'.
    $sql = "SELECT v.*, 
                   (SELECT f.ruta_imagen FROM fotos_vehiculos f WHERE f.id_vehiculo = v.id_v ORDER BY f.id_foto ASC LIMIT 1) AS foto_galeria
            FROM vehiculo v 
            WHERE tipo != 'Motocicleta' 
            ORDER BY id_v DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $vehiculos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Automóviles | RentCar</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="automoviles.css">
</head>
<body>

    <header class="catalog-header">
        <a href="dashboardf.php" class="logo-link">
            <div class="logo">
                <span class="ren">REN</span><span class="t">T</span><span class="car">CAR</span>
            </div>
        </a>

        <h1 class="page-title">
            <span class="auto">AUTO</span><span class="movil">MÓVIL</span>
        </h1>

        <button class="filter-btn">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                <path d="M3 6h18M6 12h12M10 18h4"/>
            </svg>
            FILTROS
        </button>
    </header>

    <main class="list-container">
        <?php if (!empty($vehiculos)): ?>
            <?php foreach ($vehiculos as $carro): ?>
                <?php 
                    // --- LÓGICA DE DETECCIÓN DE IMAGEN ---
                    
                    // 1. Prioridad: Foto de la galería, si no, foto de la tabla vehículo
                    $nombreImagen = !empty($carro['foto_galeria']) ? $carro['foto_galeria'] : (!empty($carro['imagen']) ? $carro['imagen'] : '');
                    
                    // 2. Limpiar la ruta (quitar espacios y corregir barras de Windows)
                    $nombreImagen = str_replace('\\', '/', trim($nombreImagen));
                    
                    $srcFinal = "nissan.png"; // Imagen por defecto

                    if (!empty($nombreImagen)) {
                        // Caso A: La ruta ya viene con carpeta (ej: "uploads/carro.jpg" o "imagenes/carro.jpg")
                        if (strpos($nombreImagen, 'uploads/') === 0 || strpos($nombreImagen, 'imagenes/') === 0) {
                            $srcFinal = $nombreImagen;
                        } 
                        // Caso B: Es solo el nombre del archivo, buscamos en qué carpeta existe
                        else {
                            if (file_exists('uploads/' . $nombreImagen)) {
                                $srcFinal = 'uploads/' . $nombreImagen;
                            } else {
                                $srcFinal = 'imagenes/' . $nombreImagen;
                            }
                        }
                    }
                ?>
                <article class="product-card-vertical">
                    <div class="image-wrapper">
                        <!-- Usamos srcFinal que ya tiene la ruta corregida -->
                        <img src="<?php echo htmlspecialchars($srcFinal); ?>" 
                             alt="<?php echo htmlspecialchars($carro['marca']); ?>"
                             onerror="this.src='nissan.png';">
                    </div>
                    <div class="info-wrapper">
                        <h3><?php echo htmlspecialchars(strtoupper(($carro['marca'] ?? '') . ' ' . ($carro['modelo'] ?? ''))); ?></h3>
                        <p class="price">PRECIO DIA: $<?php echo number_format($carro['precio_dia'] ?? $carro['precio'] ?? 0, 2); ?></p>
                        <a href="detalles_vehiculo.php?id=<?php echo $carro['id_v']; ?>" class="view-btn">VER DETALLES</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; color: #fff; padding: 40px;">
                <h3>No hay automóviles disponibles en este momento.</h3>
                <p style="color: #8e8e93; margin-top: 10px;">Vuelve más tarde o registra nuevos autos desde el panel de empresa.</p>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>