<?php
session_start();
require_once 'conexion.php';

// 1. Verificar sesión
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

$id_vehiculo = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_msg = ""; 

function limpiarInsultos($texto) {
    $blacklist = [
        'puto', 'pene', 'puta', 'mierda', 'hpta', 'hdp', 'malparido', 'gonorrea', 'pendejo', 
        'pendeja', 'culiao', 'concha', 'marica', 'perra', 'pirobo', 'carechimba', 
        'estupido', 'idiota', 'imbecil', 'basura', 'cabron', 'boludo', 'estafa', 
        'fraude', 'robo', 'rateros', 'chupar'
    ];
    $patron = '/\b(' . implode('|', array_map('preg_quote', $blacklist)) . ')\b/i';
    return preg_replace($patron, '****', $texto);
}

// 3. Procesar NUEVO comentario al presionar el botón
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comentario'])) {
    $comentario_bruto = trim($_POST['comentario']);
    $puntuacion = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    $comentario_final = limpiarInsultos($comentario_bruto);

    if (!empty($comentario_final)) {
        try {
            $sql = "INSERT INTO comentarios_vehiculos (id_vehiculo, usuario_nombre, comentario, puntuacion) VALUES (?, ?, ?, ?)";
            $ins = $pdo->prepare($sql);
            $ins->execute([$id_vehiculo, $_SESSION['usuario_nombre'], $comentario_final, $puntuacion]);
            
            header("Location: detalles_vehiculo.php?id=$id_vehiculo&msg=ok#comentarios");
            exit();
        } catch (PDOException $e) { 
            $error_msg = "Error en la base de datos: " . $e->getMessage();
        }
    } else {
        $error_msg = "El comentario no puede estar vacío.";
    }
}

// 3.1. Procesar ACTUALIZACIÓN de comentario (Edición en la misma página)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_comentario'])) {
    $id_com = intval($_POST['id_comentario']);
    $comentario_edit_bruto = trim($_POST['comentario_edit']);
    $puntuacion_edit = isset($_POST['rating_edit']) ? intval($_POST['rating_edit']) : 5;
    $comentario_edit_final = limpiarInsultos($comentario_edit_bruto);

    if (!empty($comentario_edit_final)) {
        try {
            $chk = $pdo->prepare("SELECT usuario_nombre FROM comentarios_vehiculos WHERE id_comentario = ?");
            $chk->execute([$id_com]);
            $c_data = $chk->fetch(PDO::FETCH_ASSOC);

            if ($c_data && $c_data['usuario_nombre'] === $_SESSION['usuario_nombre']) {
                $upd = $pdo->prepare("UPDATE comentarios_vehiculos SET comentario = ?, puntuacion = ? WHERE id_comentario = ?");
                $upd->execute([$comentario_edit_final, $puntuacion_edit, $id_com]);
            }
            header("Location: detalles_vehiculo.php?id=$id_vehiculo&msg=updated#comentarios");
            exit();
        } catch (PDOException $e) {
            $error_msg = "Error al actualizar: " . $e->getMessage();
        }
    } else {
        $error_msg = "El comentario editado no puede estar vacío.";
    }
}

// 4. Obtener datos para la página
try {
    $stmt = $pdo->prepare("SELECT * FROM vehiculo WHERE id_v = ?");
    $stmt->execute([$id_vehiculo]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo) { header("Location: automoviles.php"); exit(); }

    $stmtCom = $pdo->prepare("SELECT * FROM comentarios_vehiculos WHERE id_vehiculo = ? ORDER BY fecha DESC");
    $stmtCom->execute([$id_vehiculo]);
    $comentarios = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

    $total_p = 0;
    foreach($comentarios as $c) { $total_p += ($c['puntuacion'] ?? 5); }
    $promedio = count($comentarios) > 0 ? round($total_p / count($comentarios), 1) : 0;

} catch (PDOException $e) { 
    die("Error crítico: " . $e->getMessage()); 
}

$fotosFinales = glob("imagenes/*" . $id_vehiculo . "_*");
$imagenPrincipal = !empty($fotosFinales) ? $fotosFinales[0] : 'unnamed.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentCar | Detalle de Vehículo</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="detalles_vehiculo.css">
</head>
<body>

<nav class="top-nav">
    <div class="nav-container">
        <a href="dashboardf.php" class="logo">REN<span>T</span>CAR</a>
        <a href="automoviles.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</nav>

<main class="main-wrapper">
    <!-- SECCIÓN 1: FICHA TÉCNICA -->
    <section class="hero-section">
        <div class="gallery-side">
            <div class="main-img-wrap">
                <img id="mainImage" src="<?php echo $imagenPrincipal; ?>" onerror="this.src='unnamed.png'">
            </div>
            <?php if (count($fotosFinales) > 1): ?>
            <div class="thumbs-grid">
                <?php foreach ($fotosFinales as $index => $ruta): ?>
                    <img src="<?php echo $ruta; ?>" class="thumb <?php echo ($index===0)?'active':''; ?>" onclick="cambiarImagen(this, '<?php echo $ruta; ?>')">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="info-side">
            <h1 class="v-title"><?php echo strtoupper(htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo'])); ?></h1>
            <div class="v-price">$<?php echo number_format($vehiculo['precio'], 2); ?> <span>/ día</span></div>
            
            <div class="specs-box">
                <div class="s-item"><i class="fas fa-cog"></i> <div><span>Transmisión</span><strong><?php echo $vehiculo['transmision'] ?: 'N/A'; ?></strong></div></div>
                <div class="s-item"><i class="fas fa-bolt"></i> <div><span>Motor</span><strong><?php echo $vehiculo['motor'] ?: 'N/A'; ?></strong></div></div>
                <div class="s-item"><i class="fas fa-id-card"></i> <div><span>Placa</span><strong><?php echo $vehiculo['placa'] ?: 'S/N'; ?></strong></div></div>
                <div class="s-item"><i class="fas fa-palette"></i> <div><span>Color</span><strong><?php echo $vehiculo['color'] ?: 'N/A'; ?></strong></div></div>
                <div class="s-item"><i class="fas fa-chair"></i> <div><span>Asientos</span><strong><?php echo $vehiculo['asientos'] ?: '0'; ?></strong></div></div>
                <div class="s-item"><i class="fas fa-road"></i> <div><span>Tracción</span><strong><?php echo strtoupper($vehiculo['traccion'] ?: 'N/A'); ?></strong></div></div>
            </div>

            <button class="main-btn" onclick="location.href='reservar.php?id=<?php echo $id_vehiculo; ?>'">
                RESERVAR AHORA <i class="fas fa-key"></i>
            </button>
        </div>
    </section>

    <!-- SECCIÓN 2: BANNER DE RECOGIDA -->
    <section class="pickup-banner">
        <div class="banner-content">
            <div class="b-header"><i class="fas fa-info-circle"></i> IMPORTANTE PARA TU RECOGIDA</div>
            <div class="b-items">
                <div class="b-item"><i class="fas fa-id-badge"></i> Pasaporte o DNI</div>
                <div class="b-item"><i class="fas fa-address-card"></i> Licencia Vigente</div>
                <div class="b-item"><i class="fas fa-credit-card"></i> Tarjeta de Crédito</div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN 3: COMENTARIOS -->
    <section id="comentarios" class="reviews-section">
        <div class="reviews-header">
            <div class="avg-box">
                <span class="avg-num"><?php echo number_format($promedio, 1); ?></span>
                <div class="avg-stars">
                    <?php for($i=1; $i<=5; $i++) echo ($i <= round($promedio)) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                    <p><?php echo count($comentarios); ?> opiniones</p>
                </div>
            </div>
            <div class="write-box">
                <h3>¿Qué te pareció este vehículo?</h3>
                <?php if(!empty($error_msg)): ?>
                    <p style="color: var(--rojo); font-size: 0.85rem; margin-bottom: 10px;"><?php echo $error_msg; ?></p>
                <?php endif; ?>
                <form method="POST">
                    <div class="rating-selector">
                        <?php for($i=5; $i>=1; $i--): ?>
                            <input type="radio" id="r<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $i==5?'checked':''; ?>>
                            <label for="r<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                    <textarea name="comentario" placeholder="Escribe tu reseña aquí..." required></textarea>
                    <button type="submit" name="enviar_comentario">Publicar Opinión</button>
                </form>
            </div>
        </div>

        <div class="reviews-feed">
            <?php if (empty($comentarios)): ?>
                <p style="color: var(--texto-gris); text-align: center; padding: 20px;">Sé el primero en dejar una opinión sobre este vehículo.</p>
            <?php else: ?>
                <?php foreach ($comentarios as $c): ?>
                    <div class="review-card">
                        <div class="r-avatar"><?php echo strtoupper(substr($c['usuario_nombre'], 0, 1)); ?></div>
                        <div class="r-body">
                            
                            <!-- VISTA NORMAL DEL COMENTARIO -->
                            <div id="view-mode-<?php echo $c['id_comentario']; ?>">
                                <div class="r-meta">
                                    <strong><?php echo htmlspecialchars($c['usuario_nombre']); ?></strong>
                                    <span><?php echo date('d M, Y', strtotime($c['fecha'])); ?></span>
                                </div>
                                <div class="r-stars">
                                    <?php for($i=1; $i<=5; $i++) echo ($i <= ($c['puntuacion']??5)) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($c['comentario'])); ?></p>
                                
                                <div class="r-footer-card">
                                    <span style="font-size: 0.8rem; color: var(--texto-gris);">RentCar Review</span>
                                    
                                    <?php if(isset($_SESSION['usuario_nombre']) && $_SESSION['usuario_nombre'] === $c['usuario_nombre']): ?>
                                        <div class="r-actions">
                                            <button type="button" class="btn-action edit" onclick="mostrarEditor(<?php echo $c['id_comentario']; ?>)">
                                                <i class="fas fa-pen"></i> Editar
                                            </button>
                                            <a href="eliminar_comentario.php?id=<?php echo $c['id_comentario']; ?>&vehiculo=<?php echo $id_vehiculo; ?>" class="btn-action delete" onclick="return confirm('¿Estás seguro de borrar este comentario?');">
                                                <i class="fas fa-trash"></i> Borrar
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- FORMULARIO DE EDICIÓN (OCULTO POR DEFECTO) -->
                            <div id="edit-mode-<?php echo $c['id_comentario']; ?>" style="display: none;">
                                <div class="r-meta">
                                    <strong>Editando tu opinión</strong>
                                    <span><?php echo date('d M, Y', strtotime($c['fecha'])); ?></span>
                                </div>
                                <form method="POST" class="edit-form-box">
                                    <input type="hidden" name="id_comentario" value="<?php echo $c['id_comentario']; ?>">
                                    
                                    <div class="rating-selector-edit">
                                        <?php for($i=5; $i>=1; $i--): ?>
                                            <input type="radio" id="edit_r<?php echo $c['id_comentario'] . '_' . $i; ?>" name="rating_edit" value="<?php echo $i; ?>" <?php echo ($i == ($c['puntuacion'] ?? 5)) ? 'checked' : ''; ?>>
                                            <label for="edit_r<?php echo $c['id_comentario'] . '_' . $i; ?>"><i class="fas fa-star"></i></label>
                                        <?php endfor; ?>
                                    </div>

                                    <textarea name="comentario_edit" required><?php echo htmlspecialchars($c['comentario']); ?></textarea>
                                    
                                    <div class="edit-actions">
                                        <button type="submit" name="actualizar_comentario" class="btn-save">Guardar Cambios</button>
                                        <button type="button" class="btn-cancel" onclick="ocultarEditor(<?php echo $c['id_comentario']; ?>)">Cancelar</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
function cambiarImagen(el, ruta) {
    document.getElementById('mainImage').src = ruta;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

function mostrarEditor(id) {
    document.getElementById('view-mode-' + id).style.display = 'none';
    document.getElementById('edit-mode-' + id).style.display = 'block';
}

function ocultarEditor(id) {
    document.getElementById('view-mode-' + id).style.display = 'block';
    document.getElementById('edit-mode-' + id).style.display = 'none';
}
</script>
</body>
</html>