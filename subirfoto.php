<?php
session_start();
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga de Imágenes - RentCar</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="subirfoto.css">
</head>
<body>
    <main class="premium-container">
        <div class="form-card">
            <header class="card-header">
                <h1 class="main-title">REGISTRO DE IMÁGENES</h1>
                <p class="subtitle">Unidad: <span class="vehicle-id-badge">ID - <?php echo htmlspecialchars($_GET['id'] ?? '0'); ?></span></p>
                <p style="color: #e74c3c; font-size: 0.9rem;"> <span>* Es obligatorio cargar exactamente 4 fotos.</span></p>
            </header>

            <form action="procesarFotos.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_vehiculo" value="<?php echo htmlspecialchars($_GET['id'] ?? '0'); ?>">
                
                <section class="file-upload-zone">
                    <input type="file" name="fotos[]" id="fotos" multiple accept="image/*" class="hidden-file-input">
                    <label for="fotos" class="upload-label">
                        <span class="upload-text">Haz clic para seleccionar las fotos</span>
                        <span class="upload-hint">Debe seleccionar 4 imágenes (JPG, PNG, WEBP)</span>
                    </label>
                </section>

                <div id="preview-container" class="preview-list"></div>

                <button type="submit" class="btn-submit-premium" disabled>GUARDAR FOTOGRAFÍAS</button>
            </form>
        </div>
    </main>
    <script src="fotos.js"></script>
</body>
</html>