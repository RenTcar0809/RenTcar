document.addEventListener('DOMContentLoaded', () => {
    const inputArchivo = document.getElementById('licencia_imagen');
    const imagenPreview = document.getElementById('imagePreview');

    if (inputArchivo) {
        inputArchivo.addEventListener('change', function(event) {
            const archivo = event.target.files[0];
            
            if (archivo) {
                // Validar si es una imagen para previsualizar
                if (archivo.type.startsWith('image/')) {
                    const lector = new FileReader();
                    
                    lector.onload = function(e) {
                        imagenPreview.src = e.target.result;
                        imagenPreview.style.display = 'block';
                    }
                    
                    lector.readAsDataURL(archivo);
                } else {
                    // Si es PDF u otro archivo no imagen
                    imagenPreview.style.display = 'none';
                }
            }
        });
    }
});