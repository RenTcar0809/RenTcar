<?php
// 1. Iniciar la sesión para poder destruirla
session_start();

// 2. Eliminar todas las variables de sesión
$_SESSION = array();

// 3. Destruir la sesión en el servidor
session_destroy();

// 4. Forzar que la cookie expire en el navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Redirigir al login y asegurar que no siga ejecutándose nada
header("Location: inicioSesion.php");
exit();
?>