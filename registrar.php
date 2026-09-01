<?php
ob_start(); 
session_start();

// 1. Conexión
$conexion = mysqli_connect("localhost", "root", "", "rentcar");

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Forzar que PHP nos diga los errores de SQL manualmente
mysqli_report(MYSQLI_REPORT_OFF);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Recibir datos y limpiar
    $nombre     = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $apellido   = mysqli_real_escape_string($conexion, $_POST['apellido']);
    $fecha      = mysqli_real_escape_string($conexion, $_POST['fechaDeNacimiento']);
    $telefono   = mysqli_real_escape_string($conexion, $_POST['numTelefono']);
    $documento  = mysqli_real_escape_string($conexion, $_POST['documento']);
    $correo     = mysqli_real_escape_string($conexion, $_POST['correo']);
    $password   = mysqli_real_escape_string($conexion, $_POST['contraseña']);

    // 3. VERIFICAR si el correo o documento ya existen
    // Importante: Revisa si en tu DB la columna se llama 'contraseña' o 'contrasena'
    $consulta_revisar = "SELECT * FROM usuario WHERE correo = '$correo' OR documento = '$documento'";
    $resultado_revisar = mysqli_query($conexion, $consulta_revisar);

    if (!$resultado_revisar) {
        // Si la consulta falla, esto nos dirá POR QUÉ (ej: nombre de columna mal escrito)
        die("Error en la base de datos al validar: " . mysqli_error($conexion));
    }

    if (mysqli_num_rows($resultado_revisar) > 0) {
        // Datos duplicados encontrados
        echo "<script>
                alert('El correo o documento ya están registrados. Intenta con otros.');
                window.history.back();
              </script>";
        exit();
    }

    // 4. INSERTAR si todo está bien
    $sql_insertar = "INSERT INTO usuario (nombre, apellido, fechaDeNacimiento, numTelefono, documento, correo, contraseña)
                     VALUES ('$nombre', '$apellido', '$fecha', '$telefono', '$documento', '$correo', '$password')";

    if (mysqli_query($conexion, $sql_insertar)) {
        $_SESSION['usuario_nombre'] = $nombre;
        echo "<script>
                alert('¡Registro exitoso!');
                window.location.href='dashboardf.php';
              </script>";
        exit();
    } else {
        // Esto nos dirá el error exacto si el INSERT falla
        echo "Error al registrar: " . mysqli_error($conexion);
    }
}

mysqli_close($conexion);
ob_end_flush();
?>