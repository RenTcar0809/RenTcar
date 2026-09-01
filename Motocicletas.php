<?php
session_start();
require_once 'conexion.php';

// Validar la sesión activa del usuario
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

$errorBD = "";

try {
    // Consulta para traer las motocicletas y su primera foto desde 'fotos_vehiculos'
    $sql = "SELECT v.*, 
                   (SELECT f.ruta_imagen FROM fotos_vehiculos f WHERE f.id_vehiculo = v.id_v ORDER BY f.id_foto ASC LIMIT 1) AS imagen_relacionada
            FROM vehiculo v 
            WHERE LOWER(v.tipo) LIKE '%moto%' 
            ORDER BY v.id_v DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $vehiculos = [];
    $errorBD = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Motocicletas | RentCar</title>
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
            <span class="auto">MOTO</span><span class="movil">CICLETA</span>
        </h1>

        <button class="filter-btn">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                <path d="M3 6h18M6 12h12M10 18h4"/>
            </svg>
            FILTROS
        </button>
    </header>

    <main class="list-container">
        <?php if (!empty($errorBD)): ?>
            <div style="grid-column: 1 / -1; background-color: #e50914; color: #fff; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px;">
                <strong>Error en la base de datos:</strong> <?php echo htmlspecialchars($errorBD); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($vehiculos)): ?>
            <?php foreach ($vehiculos as $moto): ?>
                <?php 
                    // 1. Obtener la ruta o nombre grabado en BD
                    $fotoBD = $moto['imagen_relacionada'] ?? $moto['imagen'] ?? '';
                    
                    // 2. Normalizar la ruta (reemplazar '\' por '/')
                    $fotoBD = str_replace('\\', '/', trim($fotoBD));

                    // 3. Construir la ruta hacia la carpeta 'imagenes'
                    if (!empty($fotoBD)) {
                        if (strpos($fotoBD, 'imagenes/') === 0) {
                            $srcImagen = $fotoBD;
                        } else {
                            $srcImagen = 'imagenes/' . $fotoBD;
                        }
                    } else {
                        $srcImagen = 'moto.png';
                    }
                ?>
                <article class="product-card-vertical">
                    <div class="image-wrapper">
                        <img src="<?php echo htmlspecialchars($srcImagen); ?>" 
                             alt="Motocicleta" 
                             onerror="this.onerror=null; this.src='moto.png';">
                    </div>
                    <div class="info-wrapper">
                        <h3><?php echo htmlspecialchars(strtoupper(($moto['marca'] ?? '') . ' ' . ($moto['modelo'] ?? ''))); ?></h3>
                        <p class="price">PRECIO DIA: $<?php echo number_format($moto['precio'] ?? 0, 2); ?></p>
                        <a href="detalles_motocicleta.php?id=<?php echo $moto['id_v']; ?>" class="view-btn">VER DETALLES</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; color: #fff; padding: 40px;">
                <h3>No hay motocicletas disponibles en este momento.</h3>
                <p style="color: #8e8e93; margin-top: 10px;">Vuelve más tarde o registra nuevas motos desde el panel de empresa.</p>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>