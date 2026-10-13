<?php
session_start();
require_once 'conexion.php'; // Tu archivo de conexión con $pdo

// 1. VERIFICACIÓN DE SESIÓN
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$idUsuarioLogueado = $_SESSION['id_proveedor'];

// 2. PROCESAMIENTO DEL FORMULARIO AL HACER POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recoger datos del formulario
    $tipo         = $_POST['tipo'];
    $marca        = $_POST['marca'];
    $modelo       = $_POST['modelo'];
    $placa        = $_POST['placa'];
    $precio       = $_POST['precio'];
    $color        = $_POST['color'];
    $transmision  = $_POST['transmision'];
    $motor        = $_POST['motor'];
    $traccion     = $_POST['traccion'];
    $asientos     = !empty($_POST['asientos']) ? $_POST['asientos'] : null;
    $num_motor    = !empty($_POST['num_motor']) ? $_POST['num_motor'] : null;
    $num_chasis   = !empty($_POST['num_chasis']) ? $_POST['num_chasis'] : null;

    try {
        $pdo->beginTransaction();

        // A. Insertar el vehículo (Tabla: vehiculo, PK: id_v)
        $sqlVehiculo = "INSERT INTO vehiculo (id_proveedor, tipo, marca, modelo, placa, precio, color, transmision, motor, traccion, asientos, num_motor, num_chasis) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sqlVehiculo);
        $stmt->execute([
            $idUsuarioLogueado, $tipo, $marca, $modelo, $placa, $precio, $color, $transmision, $motor, $traccion, $asientos, $num_motor, $num_chasis
        ]);

        // Obtener el ID del vehículo recién creado
        $id_v = $pdo->lastInsertId();

        // B. Manejo de las 4 Imágenes
        $directorioUploads = 'uploads/';
        if (!file_exists($directorioUploads)) {
            mkdir($directorioUploads, 0777, true);
        }

        if (isset($_FILES['imagenes'])) {
            // Recorremos los archivos subidos
            foreach ($_FILES['imagenes']['tmp_name'] as $i => $tmp_name) {
                if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                    
                    $nombreOriginal = $_FILES['imagenes']['name'][$i];
                    $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
                    
                    // Nombre único para el archivo
                    $nombreArchivo = "img_" . $id_v . "_" . ($i + 1) . "_" . uniqid() . "." . $extension;
                    $rutaCompleta = $directorioUploads . $nombreArchivo;

                    if (move_uploaded_file($tmp_name, $rutaCompleta)) {
                        // C. Insertar en la tabla fotos_vehiculos
                        $sqlFoto = "INSERT INTO fotos_vehiculos (id_vehiculo, id_usuario, ruta_imagen) 
                                    VALUES (:id_v, :id_u, :ruta)";
                        
                        $stmtFoto = $pdo->prepare($sqlFoto);
                        $stmtFoto->execute([
                            ':id_v'   => $id_v,
                            ':id_u'   => $idUsuarioLogueado,
                            ':ruta'   => $nombreArchivo
                        ]);
                    }
                }
            }
        }

        $pdo->commit();

        // 3. REDIRECCIÓN A VEHICULOSE.PHP
        header("Location: vehiculosE.php?status=success");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Error al registrar: " . $e->getMessage();
    }
}
?>