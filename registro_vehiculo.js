// Base de datos ampliada de vehículos más comerciales en Colombia
const catalogoColombia = {
    Carro: {
        "Renault": ["Logan", "Sandero", "Duster", "Stepway", "Kwid", "Captur", "Koleos", "Alaskan", "Megane E-Tech"],
        "Chevrolet": ["Onix", "Sail", "Tracker", "Joy", "Spark GT", "Captiva", "Corvette", "Cruze", "Tahoe", "Blazer EV"],
        "Mazda": ["Mazda 2", "Mazda 3", "Mazda CX-30", "Mazda CX-5", "Mazda CX-50", "Mazda CX-90", "Mazda MX-5"],
        "Kia": ["Picanto", "Rio", "Cerato", "Sportage", "Sonet", "Seltos", "Carnival", "Niro", "EV5", "K3"],
        "Toyota": ["Hilux", "Prado", "Corolla", "Fortuner", "RAV4", "Yaris", "SW4", "Land Cruiser", "Corolla Cross", "BZ4X"],
        "Nissan": ["March", "Versa", "Kicks", "Frontier", "Sentra", "X-Trail", "Qashqai", "Leaf"],
        "Volkswagen": ["Gol", "Voyage", "Polo", "T-Cross", "Nivus", "Taos", "Tiguan", "Amarok", "Jetta"],
        "Suzuki": ["Swift", "Vitara", "S-Cross", "Ignis", "Jimny", "Baleno", "Grand Vitara"],
        "Hyundai": ["Grand i10", "Accent", "Creta", "Tucson", "Santa Fe", "HB20", "Kona", "Ioniq 5"],
        "Ford": ["Fiesta", "Focus", "Escape", "Edge", "Explorer", "Ranger", "F-150", "Mustang"],
        "Chery": ["Tiggo 2", "Tiggo 4 Pro", "Tiggo 7 Pro", "Tiggo 8 Pro", "QQ"],
        "JAC": ["JS2", "JS3", "JS4", "T6", "T8", "E-JS1"],
        "BYD": ["Song Plus", "Yuan Plus", "Dolphin", "Han", "Tang", "Qin Plus"]
    },
    Motocicleta: {
        "Bajaj": ["Boxer 100", "CT 100", "Pulsar NS 200", "Pulsar NS 160", "Dominar 400", "Dominar 250", "Pulsar N160", "Pulsar N250", "Discover 125"],
        "Yamaha": ["FZ 250", "NMAX 155", "XTZ 125", "Crypton Fi", "MT-09", "DT 125", "MT-03", "MT-15", "XTZ 150", "XTZ 250", "FZN 150"],
        "AKT": ["NKD 125", "TT 200 DS", "CR4 125", "Dynamic Pro", "TT 250 Adventour", "CR5 180", "Flex 125", "Jet 125"],
        "Honda": ["CB 125F", "XR 150L", "CB 190R", "DIO", "PCX 160", "CB 500X", "XRE 300", "XR 190L", "Elite 125"],
        "Suzuki": ["GN 125", "Gixxer 150", "AX 4", "V-Strom 250", "V-Strom 650", "Burgman Street", "Gixxer 250", "DR 150"],
        "TVS": ["Apache RTR 160", "Apache RTR 200", "Raider 125", "Sport 100", "NTRQ 125", "Apache RR 310"],
        "KTM": ["Duke 200", "Duke 250", "Duke 390", "RC 200", "Adventure 250", "Adventure 390"],
        "Hero": ["Eco 100", "Dawn 125", "Hunk 160R", "XPulse 200", "Ignitor 125", "Dash 125"],
        "Victory": ["One 110","Life 125", "Bomber 125", "MRX 125", "MRX 150", "MRX 200", "Tornado 250", "Zontes 350T", "Crypton (Gama)"],
        "Husqvarna": ["Svartpilen 200", "Svartpilen 401", "Vitpilen 401"]
    }
};

// Listas técnicas de motores / cilindrajes
const opcionesMotores = {
    Carro: [
        "1.0L Turbo", "1.2L", "1.4L", "1.5L", "1.6L", "1.8L", "2.0L", 
        "2.4L", "2.5L", "3.0L o superior", "Híbrido / Eléctrico"
    ],
    Motocicleta: [
        "100 CC", "110 CC", "125 CC", "150 CC", "160 CC", 
        "200 CC", "250 CC", "300 CC", "400 CC o superior"
    ]
};

// Listas técnicas de transmisiones adaptadas
const opcionesTransmisiones = {
    Carro: [
        "Manual (Mecánica)", 
        "Automática", 
        "CVT", 
        "Semiautomática / Triptónica"
    ],
    Motocicleta: [
        "Mecánica (Cadena)", 
        "Automática (Scooter / CVT)", 
        "Semiautomática (Sin embrague manual)"
    ]
};

function actualizarModelosColombia() {
    const tipo = document.getElementById('tipo').value;
    const marcaSelect = document.getElementById('marca');
    const modeloSelect = document.getElementById('modelo');
    const motorSelect = document.getElementById('motor');
    const transmisionSelect = document.getElementById('transmision');
    
    const contenedorTraccion = document.getElementById('contenedor-traccion');
    const contenedorAsientos = document.getElementById('contenedor-asientos');
    const labelMotor = document.getElementById('label-motor');

    // 1. Cargar Marcas según el tipo
    marcaSelect.innerHTML = '<option value="">Seleccione Marca...</option>';
    const marcas = catalogoColombia[tipo] || {};
    for (let marca in marcas) {
        let opt = document.createElement('option');
        opt.value = marca;
        opt.textContent = marca;
        marcaSelect.appendChild(opt);
    }
    modeloSelect.innerHTML = '<option value="">Seleccione Modelo...</option>';

    // 2. Cargar Motores o Cilindrajes (CC) de forma obligatoria
    if (motorSelect) {
        motorSelect.innerHTML = '<option value="">Seleccione opción...</option>';
        if (opcionesMotores[tipo]) {
            opcionesMotores[tipo].forEach(item => {
                let opt = document.createElement('option');
                opt.value = item;
                opt.textContent = item;
                motorSelect.appendChild(opt);
            });
        }
    }

    // 3. Cargar Transmisiones de forma obligatoria
    if (transmisionSelect) {
        transmisionSelect.innerHTML = '<option value="">Seleccione Transmisión...</option>';
        if (opcionesTransmisiones[tipo]) {
            opcionesTransmisiones[tipo].forEach(item => {
                let opt = document.createElement('option');
                opt.value = item;
                opt.textContent = item;
                transmisionSelect.appendChild(opt);
            });
        }
    }

    // 4. Ocultar o mostrar tracción, asientos y ajustar etiqueta de motor
    if (tipo === 'Motocicleta') {
        if (contenedorTraccion) contenedorTraccion.style.display = 'none';
        if (contenedorAsientos) contenedorAsientos.style.display = 'none';
        if (labelMotor) labelMotor.textContent = "Cilindraje (CC)";
    } else {
        if (contenedorTraccion) contenedorTraccion.style.display = 'block';
        if (contenedorAsientos) contenedorAsientos.style.display = 'block';
        if (labelMotor) labelMotor.textContent = "Motor (Ej: 1.6L)";
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

// Inicializar al cargar la página completamente
document.addEventListener("DOMContentLoaded", function() {
    actualizarModelosColombia();
});