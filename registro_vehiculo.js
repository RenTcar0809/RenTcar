// Base de datos de vehículos más comerciales en Colombia
const catalogoColombia = {
    Carro: {
        "Renault": ["Logan", "Sandero", "Duster", "Stepway", "Kwid", "Captur"],
        "Chevrolet": ["Onix", "Sail", "Tracker", "Joy", "Spark GT", "Captiva", "Corvette"],
        "Mazda": ["Mazda 2", "Mazda 3", "Mazda CX-30", "Mazda CX-5", "Mazda CX-50"],
        "Kia": ["Picanto", "Rio", "Cerato", "Sportage", "Sonet", "Seltos"],
        "Toyota": ["Hilux", "Prado", "Corolla", "Fortuner", "RAV4", "Yaris"],
        "Nissan": ["March", "Versa", "Kicks", "Frontier", "Sentra"]
    },
    Motocicleta: {
        "Bajaj": ["Boxer 100", "CT 100", "Pulsar NS 200", "Pulsar NS 160", "Dominar 400"],
        "Yamaha": ["FZ 250", "NMAX 155", "XTZ 125", "Crypton Fi", "MT-09", "DT 125"],
        "AKT": ["NKD 125", "TT 200 DS", "CR4 125", "Dynamic Pro"],
        "Honda": ["CB 125F", "XR 150L", "CB 190R", "DIO", "PCX 160"],
        "Suzuki": ["GN 125", "Gixxer 150", "AX 4", "V-Strom 250"],
        "TVS": ["Apache RTR 160", "Apache RTR 200", "Raider 125"]
    }
};

function actualizarModelosColombia() {
    const tipo = document.getElementById('tipo').value;
    const marcaSelect = document.getElementById('marca');
    marcaSelect.innerHTML = '<option value="">Seleccione Marca...</option>';
    
    const marcas = catalogoColombia[tipo] || {};
    for (let marca in marcas) {
        let opt = document.createElement('option');
        opt.value = marca;
        opt.textContent = marca;
        marcaSelect.appendChild(opt);
    }
    document.getElementById('modelo').innerHTML = '<option value="">Seleccione Modelo...</option>';
    
    // Mostrar/Ocultar campos dinámicos
    if(tipo === 'Motocicleta') {
        document.getElementById('contenedor-traccion').style.display = 'none';
        document.getElementById('contenedor-cilindraje').style.display = 'block';
        document.getElementById('contenedor-asientos').style.display = 'none';
    } else {
        document.getElementById('contenedor-traccion').style.display = 'block';
        document.getElementById('contenedor-cilindraje').style.display = 'none';
        document.getElementById('contenedor-asientos').style.display = 'block';
    }
}

function cargarReferencias() {
    const tipo = document.getElementById('tipo').value;
    const marca = document.getElementById('marca').value;
    const modeloSelect = document.getElementById('modelo');
    modeloSelect.innerHTML = '<option value="">Seleccione Modelo...</option>';

    if (catalogoColombia[tipo] && catalogoColombia[tipo][marca]) {
        catalogoColombia[tipo][marca].forEach(modelo => {
            let opt = document.createElement('option');
            opt.value = modelo;
            opt.textContent = modelo;
            modeloSelect.appendChild(opt);
        });
    }
}

// Inicializar marcas al cargar la página
window.onload = function() {
    actualizarModelosColombia();
};

// LECTOR OCR OPTIMIZADO
document.addEventListener('DOMContentLoaded', () => {
    const inputMatricula = document.getElementById('imagenMatricula');
    if (inputMatricula) {
        inputMatricula.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const estado = document.getElementById('estadoOCR');
            estado.textContent = "⏳ Analizando la licencia de tránsito...";

            Tesseract.recognize(
                file,
                'spa',
                { logger: m => console.log(m) }
            ).then(({ data: { text } }) => {
                estado.textContent = "✅ ¡Datos procesados!";
                console.log("Texto detectado por OCR:\n", text);

                // 1. Limpiar y buscar Placa (Formato Colombia: Tres letras y tres caracteres/números, ej: YSR13F)
                const palabras = text.replace(/[^a-zA-Z0-9\s]/g, '').split(/\s+/);
                for (let palabra of palabras) {
                    if (/^[A-Z]{3}[0-9]{2}[0-9A-Z]$/.test(palabra.toUpperCase())) {
                        document.getElementById('placa').value = palabra.toUpperCase();
                        break;
                    }
                }

                // 2. Buscar líneas clave para Motor y Chasis de forma más precisa
                const lineas = text.split('\n');
                lineas.forEach(linea => {
                    let lin = linea.trim();
                    let upperLin = lin.toUpperCase();

                    // Detectar número de motor
                    if (upperLin.includes('MOTOR') && !upperLin.includes('NUMERO DE')) {
                        let limpia = lin.replace(/[^a-zA-Z0-9]/g, '');
                        if (limpia.length >= 8) {
                            document.getElementById('num_motor').value = limpia.slice(-12); // Toma los caracteres finales válidos
                        }
                    }

                    // Detectar número de chasis / serie / VIN
                    if (upperLin.includes('CHASIS') || upperLin.includes('VIN') || upperLin.includes('SERIE')) {
                        let limpia = lin.replace(/[^a-zA-Z0-9]/g, '');
                        if (limpia.length >= 10) {
                            document.getElementById('num_chasis').value = limpia.slice(-17); // Formato VIN estándar
                        }
                    }
                });

            }).catch(err => {
                console.error(err);
                estado.textContent = "❌ No se pudo leer bien la imagen. Rellena los datos manualmente.";
            });
        });
    }
});