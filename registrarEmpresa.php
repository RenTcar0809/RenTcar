<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Captura de datos
    $empresa = trim($_POST['razonSocial'] ?? '');
    $nit = trim($_POST['nit'] ?? '');
    $confirmNit = trim($_POST['confirm_nit'] ?? '');
    $representanteLegal = trim($_POST['representanteLegal'] ?? '');
    $telefono = trim($_POST['telefonoEmpresa'] ?? '');
    $direccion = trim($_POST['direccionEmpresa'] ?? '');
    $correo = trim($_POST['correoEmpresa'] ?? '');
    $confirmCorreo = trim($_POST['confirm_correoEmpresa'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmContrasena = $_POST['confirm_contrasena'] ?? '';

    // Validaciones de campos
    if (empty($empresa) || empty($nit) || empty($representanteLegal) || 
        empty($telefono) || empty($direccion) || empty($correo) || empty($contrasena)) {
        echo "<script>alert('Complete todos los campos obligatorios.'); window.history.back();</script>";
        exit;
    }

    if ($nit !== $confirmNit || $correo !== $confirmCorreo || $contrasena !== $confirmContrasena) {
        echo "<script>alert('Los campos de confirmación no coinciden.'); window.history.back();</script>";
        exit;
    }

    try {
        // Verificar si ya existe el NIT o el correo
        $stmtCheck = $pdo->prepare("SELECT IdUsuario FROM usuario WHERE nit = :nit OR correo = :correo");
        $stmtCheck->execute([':nit' => $nit, ':correo' => $correo]);

        if ($stmtCheck->rowCount() > 0) {
            echo "<script>alert('El NIT o correo ya están registrados.'); window.history.back();</script>";
            exit;
        }

        // Cifrar contraseña
        $contrasenaHash = password_hash($contrasena, PASSWORD_BCRYPT);

        // Insertar usuario tipo empresa
        $sql = "INSERT INTO usuario (tipo, empresa, nit, representante_legal, numTelefono, correo, contraseña, direccion) 
                VALUES (1, :empresa, :nit, :representante_legal, :telefono, :correo, :contrasena, :direccion)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':empresa'             => $empresa,
            ':nit'                 => $nit,
            ':representante_legal' => $representanteLegal,
            ':telefono'            => $telefono,
            ':correo'              => $correo,
            ':contrasena'          => $contrasenaHash,
            ':direccion'           => $direccion
        ]);

        $id_usuario = $pdo->lastInsertId();

        // Crear sesión
        $_SESSION['IdUsuario'] = $id_usuario;
        $_SESSION['id_proveedor'] = $id_usuario;
        $_SESSION['nombre_empresa'] = $empresa;
        $_SESSION['correo_empresa'] = $correo;
        $_SESSION['rol'] = 'proveedor';

        echo "<script>
                alert('¡Empresa registrada con éxito! Bienvenido.');
                window.location.href = 'dashboardE.php';
              </script>";
        exit;

 } catch (PDOException $e) {
        // json_encode asegura que cualquier carácter o salto de línea no rompa el código JS
        $mensajeError = json_encode('Error: ' . $e->getMessage());
        echo "<script>
                alert($mensajeError);
                window.history.back();
              </script>";
        exit;
    }

} else {
    header('Location: registrarEmpresa.php');
    exit;
}
?>