<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexion.php';

<<<<<<< HEAD
// 1. Verificar sesión activa (Usando IdUsuario que es el que usas en tu historial)
=======
// 1. Verificar sesión activa
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
if (!isset($_SESSION['IdUsuario'])) {
    header("Location: inicioSesion.php");
    exit();
}

$idVehiculo = isset($_GET['id']) ? intval($_GET['id']) : 0;
$idProveedor = $_SESSION['IdUsuario'];
$error = "";

if ($idVehiculo <= 0) {
    header("Location: historial_vehiculo.php");
    exit();
}

// --- LÓGICA PARA ELIMINAR UNA FOTO ---
if (isset($_GET['borrar'])) {
    $idFotoBorrar = intval($_GET['borrar']);
    
    $stmtCheck = $pdo->prepare("
        SELECT f.ruta_imagen FROM fotos_vehiculos f 
        JOIN vehiculo v ON f.id_vehiculo = v.id_v 
        WHERE f.id_foto = ? AND v.id_proveedor = ?
    ");
    $stmtCheck->execute([$idFotoBorrar, $idProveedor]);
    $foto = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($foto) {
<<<<<<< HEAD
        // Limpiamos el nombre para buscarlo en la carpeta 'imagenes'
        $nombreArchivo = basename($foto['ruta_imagen']);
        $rutaFisica = 'imagenes/' . $nombreArchivo;
        
        if (file_exists($rutaFisica)) {
            unlink($rutaFisica);
        }
        
=======
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
        $del = $pdo->prepare("DELETE FROM fotos_vehiculos WHERE id_foto = ?");
        $del->execute([$idFotoBorrar]);
    }
    
<<<<<<< HEAD
    header("Location: procesar_editar.php?id=" . $idVehiculo);
=======
    header("Location: " . basename($_FILE['PHP_SELF'] ?? 'procesar_editar.php') . "?id=" . $idVehiculo);
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
    exit();
}

// --- OBTENER DATOS ACTUALES DEL VEHÍCULO ---
$stmt = $pdo->prepare("SELECT * FROM vehiculo WHERE id_v = ? AND id_proveedor = ?");
$stmt->execute([$idVehiculo, $idProveedor]);
$v = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$v) {
    header("Location: historial_vehiculo.php");
    exit();
}

// --- PROCESAR GUARDADO DE CAMBIOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
<<<<<<< HEAD
    // Contar fotos actuales
=======
    // Contar fotos actuales en la BD
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM fotos_vehiculos WHERE id_vehiculo = ?");
    $stmtCount->execute([$idVehiculo]);
    $cantidadActualEnBD = (int) $stmtCount->fetchColumn();

<<<<<<< HEAD
    // Contar fotos nuevas
    $fotosNuevasCount = 0;
    if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
        foreach ($_FILES['imagenes']['error'] as $err) {
            if ($err === UPLOAD_ERR_OK) $fotosNuevasCount++;
=======
    // Contar fotos nuevas seleccionadas de manera estricta
    $fotosNuevasCount = 0;
    $archivosValidos = [];
    if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
        foreach ($_FILES['imagenes']['error'] as $i => $err) {
            if ($err === UPLOAD_ERR_OK) {
                $fotosNuevasCount++;
                $archivosValidos[] = $i;
            }
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
        }
    }

    $totalFotosFinales = $cantidadActualEnBD + $fotosNuevasCount;

<<<<<<< HEAD
    if ($totalFotosFinales != 4) {
        $error = "Error: El vehículo debe tener exactamente 4 fotografías. Actualmente tienes $cantidadActualEnBD y seleccionaste $fotosNuevasCount nuevas.";
    } else {
        // Actualizar datos de texto
=======
    // Validación estricta: Obliga a que sean exactamente 4 fotos en total
    if ($totalFotosFinales != 4) {
        $error = "Error: El vehículo debe tener exactamente 4 fotografías. Actualmente tienes $cantidadActualEnBD en la base de datos y seleccionaste $fotosNuevasCount nuevas (Total: $totalFotosFinales).";
    } else {
        // 1. Actualizar datos de texto primero
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
        $nuevoPrecio = $_POST['precio'];
        $nuevoEstado = $_POST['estado'];

        $stmtUp = $pdo->prepare("UPDATE vehiculo SET precio = ?, estado = ? WHERE id_v = ? AND id_proveedor = ?");
        $stmtUp->execute([$nuevoPrecio, $nuevoEstado, $idVehiculo, $idProveedor]);

<<<<<<< HEAD
        // Subir nuevas fotos
        if ($fotosNuevasCount > 0) {
            $archivos = $_FILES['imagenes'];
            foreach ($archivos['name'] as $i => $name) {
                if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $nuevoNombre = "IDV_".$idVehiculo."_".time()."_$i.".$ext;
                    
                    if (move_uploaded_file($archivos['tmp_name'][$i], "imagenes/" . $nuevoNombre)) {
                        $insF = $pdo->prepare("INSERT INTO fotos_vehiculos (id_vehiculo, id_usuario, ruta_imagen) VALUES (?, ?, ?)");
                        $insF->execute([$idVehiculo, $idProveedor, $nuevoNombre]);
                    }
                }
            }
        }
        header("Location: historial_vehiculo.php?success=1");
        exit();
    }
}

// Consultar fotos para mostrar en la galería
=======
        // 2. Subir nuevas fotos a Cloudinary si el usuario seleccionó alguna
        $subidaExitosa = true;
        if ($fotosNuevasCount > 0) {
            $archivos = $_FILES['imagenes'];
            
            // ⚠️ REEMPLAZA ESTOS 2 DATOS CON LOS DE TU PANEL DE CLOUDINARY ⚠️
            $cloudName = "bsd1wma1";       
            $uploadPreset = "xkzfwqa0"; // Debe estar configurado como "Unsigned" en Cloudinary

            foreach ($archivosValidos as $i) {
                $tmpFilePath = $archivos['tmp_name'][$i];
                $name = $archivos['name'][$i];

                $cfile = new CURLFile($tmpFilePath, $archivos['type'][$i], $name);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/$cloudName/image/upload");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, [
                    'file' => $cfile,
                    'upload_preset' => $uploadPreset
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);

                $responseData = json_decode($response, true);

                if (isset($responseData['secure_url'])) {
                    $urlSegura = $responseData['secure_url'];
                    
                    $insF = $pdo->prepare("INSERT INTO fotos_vehiculos (id_vehiculo, id_usuario, ruta_imagen) VALUES (?, ?, ?)");
                    $insF->execute([$idVehiculo, $idProveedor, $urlSegura]);
                } else {
                    $subidaExitosa = false;
                    $error = "Error al conectar con Cloudinary. Revisa tu cloud_name y upload_preset.";
                    break;
                }
            }
        }

        // Solo redirige si todo salió bien y no hubo errores con Cloudinary
        if ($subidaExitosa && empty($error)) {
            header("Location: historial_vehiculo.php?success=1");
            exit();
        }
    }
}

// Consultar fotos actualizadas para mostrar en la galería
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
$stmtFotos = $pdo->prepare("SELECT * FROM fotos_vehiculos WHERE id_vehiculo = ?");
$stmtFotos->execute([$idVehiculo]);
$fotosActuales = $stmtFotos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Vehículo | RentCar</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --rojo: #e50914; --oscuro: #121212; --card: #1e1e1e; }
        body { background: var(--oscuro); color: white; font-family: 'Roboto', sans-serif; padding: 20px; }
        .edit-container { max-width: 700px; margin: auto; background: var(--card); padding: 30px; border-radius: 15px; border: 1px solid #333; }
        h2 { font-family: 'Bangers'; color: var(--rojo); font-size: 2.5rem; letter-spacing: 2px; margin-top:0; }
        .vehicle-info { background: #252525; padding: 15px; border-radius: 10px; margin-bottom: 20px; border-left: 5px solid var(--rojo); }
<<<<<<< HEAD
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #bbb; font-weight: bold; }
        input[type="number"], select { width: 100%; padding: 12px; background: #2a2a2a; border: 1px solid #444; color: white; border-radius: 8px; }
=======
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #bbb; font-weight: bold; }
        input[type="number"], select { width: 100%; padding: 12px; background: #2a2a2a; border: 1px solid #444; color: white; border-radius: 8px; box-sizing: border-box; }
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
        
        /* Galería */
        .gallery-preview { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 15px; }
        .thumb-container { position: relative; width: 100%; height: 100px; background: #000; border-radius: 8px; overflow: hidden; border: 1px solid #444; }
        .thumb-container img { width: 100%; height: 100%; object-fit: cover; }
<<<<<<< HEAD
        .btn-delete { position: absolute; top: 5px; right: 5px; background: var(--rojo); color: white; text-decoration: none; width: 22px; height: 22px; border-radius: 50%; text-align: center; font-weight: bold; line-height: 20px; font-size: 14px; }

        .btn-submit { background: var(--rojo); color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1.1rem; }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; color: #888; text-decoration: none; }
        .error-msg { background: rgba(229, 9, 20, 0.2); color: #ff6b6b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--rojo); }
=======
        .btn-delete { position: absolute; top: 5px; right: 5px; background: var(--rojo); color: white; text-decoration: none; width: 22px; height: 22px; border-radius: 50%; text-align: center; font-weight: bold; line-height: 20px; font-size: 14px; z-index: 10; }
        .btn-submit { background: var(--rojo); color: white; border: none; padding: 15px; width: 100%; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 1.1rem; }
        .btn-cancel { display: block; text-align: center; margin-top: 15px; color: #888; text-decoration: none; }
        .error-msg { background: rgba(229, 9, 20, 0.2); color: #ff6b6b; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--rojo); }
        .no-fotos { color: #888; font-style: italic; font-size: 0.9rem; grid-column: span 4; text-align: center; padding: 20px; background: #252525; border-radius: 8px;}
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
    </style>
</head>
<body>

<div class="edit-container">
    <h2>MODIFICAR UNIDAD</h2>
    
<<<<<<< HEAD
    <?php if ($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
=======
    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="vehicle-info">
<<<<<<< HEAD
            <strong>Vehículo:</strong> <?php echo $v['marca'] . ' ' . $v['modelo']; ?><br>
            <strong>Placa:</strong> <?php echo $v['placa']; ?>
=======
            <strong>Vehículo:</strong> <?php echo htmlspecialchars($v['marca'] . ' ' . $v['modelo']); ?><br>
            <strong>Placa:</strong> <?php echo htmlspecialchars($v['placa']); ?>
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
        </div>

        <div class="form-group">
            <label>Precio por Día ($)</label>
<<<<<<< HEAD
            <input type="number" name="precio" value="<?php echo $v['precio']; ?>" required>
=======
            <input type="number" name="precio" value="<?php echo htmlspecialchars($v['precio']); ?>" required>
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
        </div>

        <div class="form-group">
            <label>Estado</label>
            <select name="estado">
                <option value="Disponible" <?php if($v['estado']=='Disponible') echo 'selected'; ?>>Disponible</option>
                <option value="Alquilado" <?php if($v['estado']=='Alquilado') echo 'selected'; ?>>Alquilado</option>
                <option value="Mantenimiento" <?php if($v['estado']=='Mantenimiento') echo 'selected'; ?>>Mantenimiento</option>
            </select>
        </div>

        <div class="form-group">
<<<<<<< HEAD
            <label>Fotos actuales (Deben ser 4)</label>
            <div class="gallery-preview">
                <?php foreach ($fotosActuales as $foto): 
                    $rutaFinal = "imagenes/" . basename($foto['ruta_imagen']);
                ?>
                    <div class="thumb-container">
                        <img src="<?php echo $rutaFinal; ?>" onerror="this.src='unnamed.png'">
                        <a href="?id=<?php echo $idVehiculo; ?>&borrar=<?php echo $foto['id_foto']; ?>" class="btn-delete" onclick="return confirm('¿Borrar foto?')">×</a>
                    </div>
                <?php endforeach; ?>
=======
            <label>Fotos actuales en Cloudinary (Obligatorio: 4 en total)</label>
            <div class="gallery-preview">
                <?php if (count($fotosActuales) > 0): ?>
                    <?php foreach ($fotosActuales as $foto): 
                        $urlCloudinary = htmlspecialchars($foto['ruta_imagen']);
                    ?>
                        <div class="thumb-container">
                            <img src="<?php echo $urlCloudinary; ?>" alt="Foto vehículo" onerror="this.src='unnamed.png'">
                            <a href="?id=<?php echo $idVehiculo; ?>&borrar=<?php echo $foto['id_foto']; ?>" class="btn-delete" onclick="return confirm('¿Borrar esta foto?')">×</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-fotos">No hay fotos registradas. Sube tus fotos pendientes.</div>
                <?php endif; ?>
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
            </div>
        </div>

        <div class="form-group">
<<<<<<< HEAD
            <label>Subir nuevas fotos (Si borraste alguna)</label>
=======
            <label>Subir nuevas fotos a Cloudinary</label>
>>>>>>> 09c48612e41ee8923941c3b2e65befe7ef2ab948
            <input type="file" name="imagenes[]" multiple accept="image/*" style="color: #888;">
        </div>

        <button type="submit" class="btn-submit">GUARDAR CAMBIOS</button>
        <a href="historial_vehiculo.php" class="btn-cancel">Cancelar y volver</a>
    </form>
</div>

</body>
</html>