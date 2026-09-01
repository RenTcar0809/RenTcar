<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

$id_vehiculo = isset($_GET['id']) ? intval($_GET['id']) : 0;
$usuario_actual = $_SESSION['usuario_nombre'];
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

// --- LÓGICA: ELIMINAR COMENTARIO ---
if (isset($_POST['borrar_comentario'])) {
    $id_com = intval($_POST['id_comentario']);
    $stmt = $pdo->prepare("DELETE FROM comentarios_vehiculos WHERE id_comentario = ? AND usuario_nombre = ?");
    $stmt->execute([$id_com, $usuario_actual]);
    header("Location: detalles_vehiculo.php?id=$id_vehiculo#comentarios");
    exit();
}

// --- LÓGICA: EDITAR COMENTARIO ---
if (isset($_POST['actualizar_comentario'])) {
    $id_com = intval($_POST['id_comentario']);
    $nuevo_texto = limpiarInsultos(trim($_POST['comentario_editado']));
    if (!empty($nuevo_texto)) {
        $stmt = $pdo->prepare("UPDATE comentarios_vehiculos SET comentario = ? WHERE id_comentario = ? AND usuario_nombre = ?");
        $stmt->execute([$nuevo_texto, $id_com, $usuario_actual]);
    }
    header("Location: detalles_vehiculo.php?id=$id_vehiculo#comentarios");
    exit();
}

// --- LÓGICA: NUEVO COMENTARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comentario'])) {
    $comentario_bruto = trim($_POST['comentario']);
    $puntuacion = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    $comentario_final = limpiarInsultos($comentario_bruto);

    if (!empty($comentario_final)) {
        try {
            $ins = $pdo->prepare("INSERT INTO comentarios_vehiculos (id_vehiculo, usuario_nombre, comentario, puntuacion) VALUES (?, ?, ?, ?)");
            $ins->execute([$id_vehiculo, $usuario_actual, $comentario_final, $puntuacion]);
            header("Location: detalles_vehiculo.php?id=$id_vehiculo&msg=ok#comentarios");
            exit();
        } catch (PDOException $e) { $error_msg = "Error al publicar."; }
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM vehiculo WHERE id_v = ?");
    $stmt->execute([$id_vehiculo]);
    $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehiculo) { header("Location: automoviles.php"); exit(); }

    // Galería de fotos
    $stmtFotos = $pdo->prepare("SELECT ruta_imagen FROM fotos_vehiculos WHERE id_vehiculo = ? ORDER BY id_foto ASC");
    $stmtFotos->execute([$id_vehiculo]);
    $fotosBD = $stmtFotos->fetchAll(PDO::FETCH_COLUMN);

    $fotosFinales = [];
    function normalizarRuta($ruta) {
        $ruta = str_replace('\\', '/', trim($ruta));
        if (strpos($ruta, 'imagenes/') === 0 || strpos($ruta, 'uploads/') === 0) return $ruta;
        if (file_exists('uploads/' . $ruta)) return 'uploads/' . $ruta;
        return 'imagenes/' . $ruta;
    }
    foreach ($fotosBD as $f) { $fotosFinales[] = normalizarRuta($f); }
    if (empty($fotosFinales) && !empty($vehiculo['imagen'])) { $fotosFinales[] = normalizarRuta($vehiculo['imagen']); }
    $imagenPrincipal = !empty($fotosFinales) ? $fotosFinales[0] : 'carro_default.png';

    // Comentarios
    $stmtCom = $pdo->prepare("SELECT * FROM comentarios_vehiculos WHERE id_vehiculo = ? ORDER BY fecha DESC");
    $stmtCom->execute([$id_vehiculo]);
    $comentarios = $stmtCom->fetchAll(PDO::FETCH_ASSOC);

    $total_p = 0;
    foreach($comentarios as $c) { $total_p += ($c['puntuacion'] ?? 5); }
    $promedio = count($comentarios) > 0 ? round($total_p / count($comentarios), 1) : 0;
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentCar | <?php echo htmlspecialchars($vehiculo['marca']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="detalles_vehiculos.css">
</head>
<body>

<nav class="top-nav">
    <div class="nav-container">
        <a href="dashboardf.php" class="logo">REN<span>T</span>CAR</a>
        <a href="javascript:history.back()" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</nav>

<main class="main-wrapper">
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
            <div class="v-type-label"><?php echo strtoupper($vehiculo['tipo']); ?></div>
            <h1 class="v-title"><?php echo strtoupper(htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo'])); ?></h1>
            <div class="v-price">$<?php echo number_format($vehiculo['precio'], 2); ?> <span>/ día</span></div>
            <div class="specs-box">
                <div class="s-item"><i class="fas fa-cog"></i> <div><span>Transmisión</span><strong><?php echo $vehiculo['transmision'] ?: 'Manual'; ?></strong></div></div>
                <div class="s-item"><i class="fas fa-bolt"></i> <div><span>Motor</span><strong><?php echo $vehiculo['motor'] ?: 'N/A'; ?></strong></div></div>
                <div class="s-item"><i class="fas fa-users"></i> <div><span>Asientos</span><strong><?php echo $vehiculo['asientos'] ?: '1'; ?></strong></div></div>
                <div class="s-item"><i class="fas fa-road"></i> <div><span>Tracción</span><strong><?php echo strtoupper($vehiculo['traccion'] ?: 'N/A'); ?></strong></div></div>
            </div>
            <button class="main-btn" onclick="location.href='reservar.php?id=<?php echo $id_vehiculo; ?>'">
                ALQUILAR AHORA <i class="fas fa-key"></i>
            </button>
        </div>
    </section>

    <section id="comentarios" class="reviews-section">
        <div class="reviews-header">
            <div class="avg-box">
                <span class="avg-num"><?php echo number_format($promedio, 1); ?></span>
                <div class="avg-stars">
                    <?php for($i=1; $i<=5; $i++) echo ($i <= round($promedio)) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                    <p>Opiniones: <?php echo count($comentarios); ?></p>
                </div>
            </div>
            <div class="write-box">
                <h3>Cuéntanos tu experiencia</h3>
                <form method="POST">
                    <div class="rating-selector">
                        <?php for($i=5; $i>=1; $i--): ?>
                            <input type="radio" id="r<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo $i==5?'checked':''; ?>>
                            <label for="r<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                    <textarea name="comentario" placeholder="Escribe aquí..." required></textarea>
                    <button type="submit" name="enviar_comentario">Publicar Opinión</button>
                </form>
            </div>
        </div>

        <div class="reviews-feed">
            <?php foreach ($comentarios as $c): ?>
                <div class="review-card">
                    <?php if ($c['usuario_nombre'] === $usuario_actual): ?>
                        <div class="comment-actions">
                            <button class="btn-action btn-edit" onclick="toggleEdit(<?php echo $c['id_comentario']; ?>)">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form method="POST" onsubmit="return confirm('¿Eliminar comentario?')">
                                <input type="hidden" name="id_comentario" value="<?php echo $c['id_comentario']; ?>">
                                <button type="submit" name="borrar_comentario" class="btn-action btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="r-avatar"><?php echo strtoupper(substr($c['usuario_nombre'], 0, 1)); ?></div>
                    <div class="r-body">
                        <div class="r-meta">
                            <strong><?php echo htmlspecialchars($c['usuario_nombre']); ?></strong>
                            <span><?php echo date('d/m/Y', strtotime($c['fecha'])); ?></span>
                        </div>
                        <div class="r-stars">
                            <?php for($i=1; $i<=5; $i++) echo ($i <= ($c['puntuacion']??5)) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                        </div>
                        
                        <p id="texto-<?php echo $c['id_comentario']; ?>"><?php echo nl2br(htmlspecialchars($c['comentario'])); ?></p>

                        <div id="edit-box-<?php echo $c['id_comentario']; ?>" class="edit-box">
                            <form method="POST">
                                <input type="hidden" name="id_comentario" value="<?php echo $c['id_comentario']; ?>">
                                <textarea name="comentario_editado"><?php echo htmlspecialchars($c['comentario']); ?></textarea>
                                <div class="edit-btns">
                                    <button type="submit" name="actualizar_comentario" class="btn-save">Actualizar</button>
                                    <button type="button" class="btn-cancel" onclick="toggleEdit(<?php echo $c['id_comentario']; ?>)">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<script>
function cambiarImagen(el, ruta) {
    document.getElementById('mainImage').src = ruta;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}

function toggleEdit(id) {
    const text = document.getElementById('texto-' + id);
    const box = document.getElementById('edit-box-' + id);
    if (box.style.display === 'block') {
        box.style.display = 'none';
        text.style.display = 'block';
    } else {
        box.style.display = 'block';
        text.style.display = 'none';
    }
}
</script>
</body>
</html>