<?php
session_start();
require_once 'conexion.php';

// 1. Validar que el usuario tenga sesión activa
if (!isset($_SESSION['usuario_nombre'])) {
    header("Location: inicioSesion.php");
    exit();
}

$mensaje = "";
$tipoAlerta = "";

// 2. Procesar acciones de Aprobar o Rechazar de forma segura
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $accion = $_GET['accion'];
    $idUsuario = intval($_GET['id']);

    try {
        if ($accion === 'aprobar') {
            // Aprobar licencia (cambia a 1)
            $stmt = $pdo->prepare("UPDATE usuario SET licencia_verificada = 1 WHERE id_usuario = ?");
            $stmt->execute([$idUsuario]);
            $mensaje = "¡Licencia aprobada con éxito!";
            $tipoAlerta = "success";
        } elseif ($accion === 'rechazar') {
            // Rechazar licencia: limpiamos los datos para que el usuario deba subirla de nuevo
            $stmt = $pdo->prepare("UPDATE usuario SET licencia_numero = NULL, licencia_imagen = NULL, licencia_verificada = 0 WHERE id_usuario = ?");
            $stmt->execute([$idUsuario]);
            $mensaje = "Licencia rechazada. Se le ha solicitado al usuario que la suba nuevamente.";
            $tipoAlerta = "error";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al procesar la solicitud: " . $e->getMessage();
        $tipoAlerta = "error";
    }
}

// 3. Obtener todos los usuarios con licencias pendientes de aprobación (licencia_verificada = 0 pero que ya tienen número/imagen)
try {
    $stmtPendientes = $pdo->prepare("
        SELECT id_usuario, nombre, empresa, correo, licencia_numero, licencia_imagen, licencia_verificada 
        FROM usuario 
        WHERE licencia_numero IS NOT NULL AND licencia_numero != '' AND (licencia_verificada = 0 OR licencia_verificada IS NULL)
        ORDER BY id_usuario DESC
    ");
    $stmtPendientes->execute();
    $pendientes = $stmtPendientes->fetchAll(PDO::FETCH_ASSOC);

    // Obtener también las ya aprobadas para un historial rápido
    $stmtAprobadas = $pdo->prepare("
        SELECT id_usuario, nombre, empresa, correo, licencia_numero, licencia_imagen 
        FROM usuario 
        WHERE licencia_verificada = 1 
        ORDER BY id_usuario DESC LIMIT 10
    ");
    $stmtAprobadas->execute();
    $aprobadas = $stmtAprobadas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al consultar la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Verificación de Licencias | RentCar</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --rojo: #e50914;
            --negro: #141414;
            --gris-oscuro: #1f1f1f;
            --gris-claro: #f4f4f4;
            --texto: #333;
            --blanco: #fff;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--gris-claro); margin: 0; padding: 0; color: var(--texto); }
        header { background-color: var(--negro); color: var(--blanco); padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 1.5rem; color: var(--rojo); }
        header a { color: var(--blanco); text-decoration: none; font-size: 0.9rem; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .alert { padding: 12px 20px; border-radius: 5px; margin-bottom: 20px; font-weight: 600; }
        .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        h2 { border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-top: 40px; color: var(--negro); }
        .table-responsive { overflow-x: auto; background: var(--blanco); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; font-size: 0.95rem; }
        th { background-color: #fafafa; color: #555; font-weight: 600; }
        tr:hover { background-color: #f9f9f9; }
        .btn { padding: 8px 14px; border-radius: 4px; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; border: none; }
        .btn-approve { background-color: #28a745; color: white; }
        .btn-approve:hover { background-color: #218838; }
        .btn-reject { background-color: var(--rojo); color: white; }
        .btn-reject:hover { background-color: #b20710; }
        .btn-view { background-color: #007bff; color: white; }
        .btn-view:hover { background-color: #0056b3; }
        .empty-state { text-align: center; padding: 30px; color: #778; font-style: italic; }
    </style>
</head>
<body>

<header>
    <h1>RentCar | Panel de Administración</h1>
    <a href="dashboardf.php"><i class="fas fa-arrow-left"></i> Volver al sitio</a>
</header>

<div class="container">
    <h2><i class="fas fa-id-card-alt"></i> Licencias Pendientes de Verificación</h2>

    <?php if (!empty($mensaje)): ?>
        <div class="alert <?php echo $tipoAlerta; ?>"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID Usuario</th>
                    <th>Nombre / Empresa</th>
                    <th>Nro. Licencia</th>
                    <th>Documento (Cloudinary)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendientes)): ?>
                    <tr><td colspan="5" class="empty-state">No hay licencias pendientes de revisión en este momento.</td></tr>
                <?php else: ?>
                    <?php foreach ($pendientes as $p): ?>
                        <tr>
                            <td><?php echo $p['id_usuario']; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['nombre'] ?: $p['empresa']); ?></strong><br><small style="color: #666;"><?php echo htmlspecialchars($p['correo'] ?? ''); ?></small></td>
                            <td><code><?php echo htmlspecialchars($p['licencia_numero']); ?></code></td>
                            <td>
                                <?php if (!empty($p['licencia_imagen'])): ?>
                                    <a href="<?php echo htmlspecialchars($p['licencia_imagen']); ?>" target="_blank" class="btn btn-view">
                                        <i class="fas fa-external-link-alt"></i> Ver Imagen
                                    </a>
                                <?php else: ?>
                                    <span style="color: red;">Sin archivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="admin_licencias.php?accion=aprobar&id=<?php echo $p['id_usuario']; ?>" class="btn btn-approve" onclick="return confirm('¿Estás seguro de aprobar la licencia de este usuario?');">
                                        <i class="fas fa-check"></i> Aprobar
                                    </a>
                                    <a href="admin_licencias.php?accion=rechazar&id=<?php echo $p['id_usuario']; ?>" class="btn btn-reject" onclick="return confirm('¿Rechazar esta licencia? El usuario tendrá que subirla de nuevo.');">
                                        <i class="fas fa-times"></i> Rechazar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2><i class="fas fa-check-circle" style="color: #28a745;"></i> Últimas Licencias Aprobadas</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nro. Licencia</th>
                    <th>Documento</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($aprobadas)): ?>
                    <tr><td colspan="4" class="empty-state">Aún no hay registros aprobados.</td></tr>
                <?php else: ?>
                    <?php foreach ($aprobadas as $ap): ?>
                        <tr>
                            <td><?php echo $ap['id_usuario']; ?></td>
                            <td><strong><?php echo htmlspecialchars($ap['nombre'] ?: $ap['empresa']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($ap['licencia_numero']); ?></code></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($ap['licencia_imagen']); ?>" target="_blank" class="btn btn-view" style="padding: 4px 8px; font-size: 0.75rem;">
                                    Ver Archivo
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>