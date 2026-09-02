
document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.getElementById('dropzone');
    const inputImagenes = document.getElementById('inputImagenes');
    const previewGrid = document.getElementById('previewGrid');
    const photoCounter = document.getElementById('photoCounter');
    const carForm = document.getElementById('carForm');

    // Elementos dinámicos del formulario
    const selectTipo = document.getElementById('tipo');
    const selectTransmision = document.getElementById('transmision');
    const inputTraccion = document.getElementById('traccion');
    const inputAsientos = document.getElementById('asientos');
    const inputMotor = document.getElementById('motor');

    const LIMITE_EXACTO = 4;
    const dataTransfer = new DataTransfer();

    // ==========================================
    // 1. ADAPTACIÓN SEGÚN TIPO DE VEHÍCULO
    // ==========================================
    function adaptarCamposPorTipo() {
        if (!selectTipo) return;
        const esMoto = selectTipo.value === 'Motocicleta';

        if (esMoto) {
            if (selectTransmision) {
                selectTransmision.innerHTML = `
                    <option value="Manual">Manual (Cambios por pedal)</option>
                    <option value="Semiautomática">Semiautomática (Sin embrague)</option>
                    <option value="Automática">Automática (CVT / Scooter)</option>
                `;
            }
            if (inputTraccion) {
                inputTraccion.value = 'Trasera (Cadena)';
            }
            if (inputAsientos) {
                inputAsientos.value = 2;
                inputAsientos.max = 2;
            }
        } else {
            if (selectTransmision) {
                selectTransmision.innerHTML = `
                    <option value="Automática">Automática</option>
                    <option value="Manual">Manual</option>
                    <option value="Semiautomática">Semiautomática</option>
                `;
            }
            if (inputTraccion) {
                inputTraccion.value = '';
            }
            if (inputAsientos) {
                inputAsientos.value = 5;
                inputAsientos.removeAttribute('max');
            }
        }
    }

    if (selectTipo) {
        selectTipo.addEventListener('change', adaptarCamposPorTipo);
        adaptarCamposPorTipo();
    }

    // ==========================================
    // 2. MANEJO DE ARCHIVOS (IMÁGENES)
    // ==========================================

    if (inputImagenes) {
        inputImagenes.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                agregarArchivos(e.target.files);
            }
        });
    }

    if (dropzone) {
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                agregarArchivos(e.dataTransfer.files);
            }
        });
    }

    function esImagenValida(file) {
        const extensiones = /\.(jpg|jpeg|png|webp)$/i;
        return file.type.startsWith('image/') || extensiones.test(file.name);
    }

    function agregarArchivos(files) {
        Array.from(files).forEach(file => {
            if (!esImagenValida(file)) return;

            const esDuplicado = Array.from(dataTransfer.files).some(existente => 
                existente.name === file.name && existente.size === file.size
            );

            if (!esDuplicado) {
                if (dataTransfer.files.length < LIMITE_EXACTO) {
                    dataTransfer.items.add(file);
                } else {
                    alert('Solo se permiten 4 fotos.');
                }
            }
        });
        actualizarVista();
    }

    // Definir eliminarFoto globalmente para que el onclick del HTML funcione
    window.eliminarFoto = function(index) {
        dataTransfer.items.remove(index);
        actualizarVista();
    };

    function actualizarVista() {
        if (!previewGrid || !photoCounter) return;

        // Sincronizar el input real
        inputImagenes.files = dataTransfer.files;

        // Actualizar contador
        const total = dataTransfer.files.length;
        photoCounter.textContent = `${total} / ${LIMITE_EXACTO} Seleccionadas`;
        photoCounter.classList.toggle('valid', total === LIMITE_EXACTO);

        // Limpiar y renderizar miniaturas
        previewGrid.innerHTML = '';
        Array.from(dataTransfer.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const card = document.createElement('div');
                card.className = 'preview-card';
                card.innerHTML = `
                    <img src="${e.target.result}" alt="Vista previa">
                    <button type="button" class="btn-delete-photo" onclick="eliminarFoto(${index})">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <span class="preview-number">#${index + 1}</span>
                `;
                previewGrid.appendChild(card);
            };
            reader.readAsDataURL(file);
        });
    }

    // ==========================================
    // 3. ENVÍO DEL FORMULARIO
    // ==========================================
    if (carForm) {
        carForm.addEventListener('submit', (e) => {
            // Última sincronización antes de enviar
            inputImagenes.files = dataTransfer.files;

            if (inputImagenes.files.length !== LIMITE_EXACTO) {
                e.preventDefault();
                alert(`Error: Debes subir exactamente ${LIMITE_EXACTO} fotos.`);
            } else {
                const btn = carForm.querySelector('.btn-submit');
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Subiendo...';
                btn.disabled = true;
            }
        });
    }
}); // <--- Este es el corchete y paréntesis que cierra el DOMContentLoaded