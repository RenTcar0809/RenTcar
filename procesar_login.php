<?php
session_start();
require_once 'conexion.php'; // Usa tu archivo de conexión PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recibe los datos del formulario de inicioSesion.php
    $identificador = trim($_POST['usuario_identificador'] ?? '');
    $password      = $_POST['password'] ?? '';

    if (empty($identificador) || empty($password)) {
        echo "<script>alert('Por favor ingrese todos los campos.'); window.history.back();</script>";
        exit();
    }

    try {
        // Búsqueda por correo o NIT
        $sql = "SELECT * FROM usuario WHERE correo = :login OR nit = :login LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':login' => $identificador]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Obtenemos la contraseña y limpiamos espacios con trim
            $hashGuardado = trim($usuario['contraseña'] ?? $usuario['contrasena'] ?? '');
            $passwordIngresada = trim($password);
            
            // Comparamos la contraseña en texto plano de forma segura
            if ($passwordIngresada === $hashGuardado) {
                session_regenerate_id(true);

                // Asignamos las variables de sesión unificadas (respetando 'IdUsuario')
                $_SESSION['IdUsuario']      = $usuario['IdUsuario'];
                $_SESSION['id_proveedor']   = $usuario['IdUsuario']; // Para compatibilidad antigua
                $_SESSION['tipo']           = $usuario['tipo'];      // 1 = Empresa, 2 = Usuario

                // Validamos según el tipo guardado en la tabla unificada
                if ($usuario['tipo'] == 1) {
                    $_SESSION['nombre_empresa'] = $usuario['empresa'];
                    $_SESSION['usuario_nombre'] = $usuario['empresa'];
                    $_SESSION['rol']            = 'proveedor';

                    header("Location: dashboardE.php");
                    exit();
                } else {
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['rol']            = 'cliente';

                    header("Location: dashboardf.php");
                    exit();
                }
            }
        }

        // SI NO SE ENCONTRÓ O LA CONTRASEÑA ES INCORRECTA
        echo "<script>alert('Correo o NIT incorrectos, o contraseña inválida.'); window.history.back();</script>";
        exit();

    } catch (PDOException $e) {
        echo "<script>alert('Error en el sistema: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit();
    }

} else {
    die("Debes enviar el formulario primero.");
}
?>