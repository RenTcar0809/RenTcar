<?php
echo "<h3>1. Lista de archivos en esta carpeta:</h3>";
echo "<pre>";
print_r(scandir('.'));
echo "</pre>";

echo "<h3>2. ¿Existe la carpeta 'imagenes'?</h3>";
if (is_dir('imagenes')) {
    echo "✅ SÍ existe la carpeta 'imagenes'.<br>";
    echo "Contenido de 'imagenes':<pre>";
    print_r(scandir('imagenes'));
    echo "</pre>";
} else {
    echo "❌ NO existe una carpeta llamada 'imagenes' aquí.<br>";
    // Intentar buscar carpetas similares
    foreach (scandir('.') as $file) {
        if (is_dir($file) && $file != "." && $file != "..") {
            echo "Carpeta encontrada: <b>$file</b><br>";
        }
    }
}
?>