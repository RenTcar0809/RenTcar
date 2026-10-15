<?php
session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = trim($_POST['usuario_identificador'] ?? '');
    $password      = $_POST['password'] ?? '';

    echo "<h3>Resultados de Diagnóstico:</h3>";
    echo "<ul>";
    echo "<li><strong>Identificador ingresado:</strong> " . htmlspecialchars($identificador) . "</li>";

    try {
        // Consulta exacta ignorando mayúsculas y espacios
        $sql = "SELECT * FROM usuario WHERE LOWER(TRIM(correo)) = LOWER(TRIM(:login)) OR LOWER(TRIM(nit)) = LOWER(TRIM(:login)) LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':login' => $identificador]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            echo "<li><span style='color:green;'>✔ ¡Usuario encontrado en la base de datos!</span></li>";
            echo "<li><strong>Correo en BD:</strong> " . htmlspecialchars($usuario['correo'] ?? 'N/A') . "</li>";
            echo "<li><strong>NIT en BD:</strong> " . htmlspecialchars($usuario['nit'] ?? 'N/A') . "</li>";
            
            $hashGuardado = $usuario['contraseña'] ?? $usuario['contrasena'] ?? '';
            echo "<li><strong>¿Existe hash/contraseña en BD?:</strong> " . (!empty($hashGuardado) ? 'Sí' : 'No') . "</li>";

            $passwordValida = false;
            if (!empty($hashGuardado)) {
                if (password_verify($password, $hashGuardado) || $password === $hashGuardado) {
                    $passwordValida = true;
                }
            }

            if ($passwordValida) {
                echo "<li><span style='color:green;'>✔ ¡Contraseña correcta! El login sería exitoso.</span></li>";
            } else {
                echo "<li><span style='color:red;'>✖ La contraseña NO coincide con la guardada en la base de datos.</span></li>";
            }

        } else {
            echo "<li><span style='color:red;'>✖ No se encontró ningún registro que coincida con ese correo o NIT en la base de datos.</span></li>";
        }

    } catch (PDOException $e) {
        echo "<li><span style='color:red;'>Error de BD: " . htmlspecialchars($e->getMessage()) . "</span></li>";
    }

    echo "</ul>";
    echo '<br><a href="javascript:window.history.back();">Volver atrás</a>';
    exit();

} else {
    die("Debes enviar el formulario primero.");
}
?>