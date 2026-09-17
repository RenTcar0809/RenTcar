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

// LECTOR OCR (Extrae textos de la tarjeta de matrícula / propiedad)
document.addEventListener('DOMContentLoaded', () => {
    const inputMatricula = document.getElementById('imagenMatricula');
    if (inputMatricula) {
        inputMatricula.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const estado = document.getElementById('estadoOCR');
            estado.textContent = "⏳ Leyendo la tarjeta de propiedad (esto puede tomar unos segundos)...";

            Tesseract.recognize(
                file,
                'spa', // Idioma español
                { logger: m => console.log(m) }
            ).then(({ data: { text } }) => {
                estado.textContent = "✅ ¡Datos extraídos con éxito!";
                console.log("Texto detectado:", text);

                // Buscar una placa tipo AAA123 o AAA12A
                const regexPlaca = /[A-Z]{3}[0-9]{2}[0-9A-Z]/i;
                const matchPlaca = text.match(regexPlaca);
                if (matchPlaca) {
                    document.getElementById('placa').value = matchPlaca[0].toUpperCase();
                }

                const lineas = text.split('\n');
                lineas.forEach(linea => {
                    let lin = linea.trim();
                    if (lin.toUpperCase().includes('MOTOR') && lin.length > 8) {
                        let valMotor = lin.replace(/[^a-zA-Z0-9]/g, '');
                        if(valMotor.length > 5) document.getElementById('num_motor').value = valMotor.slice(-8);
                    }
                    if (lin.toUpperCase().includes('CHASIS') || lin.toUpperCase().includes('SERIE')) {
                        let valChasis = lin.replace(/[^a-zA-Z0-9]/g, '');
                        if(valChasis.length > 5) document.getElementById('num_chasis').value = valChasis.slice(-10);
                    }
                });

            }).catch(err => {
                console.error(err);
                estado.textContent = "❌ Error al leer la imagen. Intenta con una foto más iluminada.";
            });
        });
    }
});