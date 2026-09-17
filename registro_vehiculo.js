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
    
    // Mostrar/Ocultar campos dinámicos según el tipo de vehículo
    const contenedorTraccion = document.getElementById('contenedor-traccion');
    const contenedorAsientos = document.getElementById('contenedor-asientos');

    if(tipo === 'Motocicleta') {
        if(contenedorTraccion) contenedorTraccion.style.display = 'none';
        if(contenedorAsientos) contenedorAsientos.style.display = 'none';
    } else {
        if(contenedorTraccion) contenedorTraccion.style.display = 'block';
        if(contenedorAsientos) contenedorAsientos.style.display = 'block';
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