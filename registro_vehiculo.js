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

    // 2. Cargar Motores o Cilindrajes (CC) según corresponda
    if (motorSelect) {
        motorSelect.innerHTML = '<option value="">Seleccione opción...</option>';
        opcionesMotores[tipo].forEach(item => {
            let opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            motorSelect.appendChild(opt);
        });
    }

    // 3. Cargar Transmisiones según corresponda
    if (transmisionSelect) {
        transmisionSelect.innerHTML = '<option value="">Seleccione Transmisión...</option>';
        opcionesTransmisiones[tipo].forEach(item => {
            let opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            transmisionSelect.appendChild(opt);
        });
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