<?php
session_start();
require_once 'conexion.php'; // Usa tu archivo de conexión PDO

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Limpiamos espacios y convertimos el identificador ingresado a minúsculas para estandarizarlo
    $identificador = trim(mb_strtolower($_POST['usuario_identificador'] ?? ''));
    $password      = $_POST['password'] ?? '';

    if (empty($identificador) || empty($password)) {
        echo "<script>alert('Por favor ingrese todos los campos.'); window.history.back();</script>";
        exit();
    }

    try {
        // Forzamos LOWER() tanto en los campos de la BD como en el parámetro PHP ya minúsculo
        $sql = "SELECT * FROM usuario WHERE LOWER(TRIM(correo)) = :login OR LOWER(TRIM(nit)) = :login LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':login' => $identificador]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // 1. Buscamos la contraseña de forma flexible
            $hashGuardado = $usuario['contraseña'] ?? $usuario['contrasena'] ?? '';
            
            // Compara contraseña encriptada o en texto plano
            if (password_verify($password, $hashGuardado) || $password === $hashGuardado) {
                session_regenerate_id(true);

                // 2. Buscamos el ID del usuario de forma flexible
                $idUsuarioEncontrado = $usuario['IdUsuario'] ?? $usuario['idusuario'] ?? $usuario['id_usuario'] ?? $usuario['id'] ?? null;

                if (!$idUsuarioEncontrado) {
                    echo "<script>alert('Error: No se encontró la columna de identificación del usuario en la base de datos.'); window.history.back();</script>";
                    exit();
                }

                // Asignamos las variables de sesión unificadas de forma segura
                $_SESSION['IdUsuario']      = $idUsuarioEncontrado;
                $_SESSION['id_proveedor']   = $idUsuarioEncontrado; 
                $_SESSION['tipo']           = $usuario['tipo'];     

                // Validamos según el tipo guardado en la tabla unificada
                if ($usuario['tipo'] == 1) {
                    $_SESSION['nombre_empresa'] = $usuario['empresa'] ?? '';
                    $_SESSION['usuario_nombre'] = $usuario['empresa'] ?? '';
                    $_SESSION['rol']            = 'proveedor';

                    header("Location: dashboardE.php");
                    exit();
                } else {
                    $_SESSION['usuario_nombre'] = $usuario['nombre'] ?? '';
                    $_SESSION['rol']            = 'cliente';

                    header("Location: dashboardf.php");
                    exit();
                }
            }
        }

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