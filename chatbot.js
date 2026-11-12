let intervaloChat = null;

function toggleChatFlotante() {
    const chatContainer = document.getElementById('chat-float-container');
    if (!chatContainer) return;
    
    const isOpen = chatContainer.style.display === 'flex';
    chatContainer.style.display = isOpen ? 'none' : 'flex';
    
    if (!isOpen) {
        document.getElementById('chat-input')?.focus();
        iniciarActualizacionChat();
    } else {
        detenerActualizacionChat();
    }
}

// Se ejecuta al hacer clic en "RESERVAR AHORA" (Estilo Marketplace)
function iniciarChatReserva(tipoVehiculo, idItem, nombreVehiculo) {
    const chatContainer = document.getElementById('chat-float-container');
    if (chatContainer) chatContainer.style.display = 'flex';

    // Guardamos los atributos actuales en el contenedor del chat para usarlos al enviar
    chatContainer.dataset.tipo = tipoVehiculo; // 'auto' o 'moto'
    chatContainer.dataset.id = idItem;

    // Mensaje automático inicial si el chat está vacío
    setTimeout(() => {
        enviarMensajeAutomatico(`Hola, estoy interesado/a en reservar y cuadrar detalles de: <b>${nombreVehiculo}</b>.`);
    }, 300);

    iniciarActualizacionChat();
}

function enviarMensajeBot() {
    const input = document.getElementById('chat-input');
    const chatContainer = document.getElementById('chat-float-container');
    if (!input || !chatContainer) return;

    const texto = input.value.trim();
    if (!texto) return;

    const idItem = chatContainer.dataset.id || 0;

    const formData = new URLSearchParams();
    formData.append('accion', 'enviar');
    formData.append('id_item', idItem);
    formData.append('mensaje', texto);

    fetch('chat_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            input.value = '';
            cargarMensajesServidor(); // Refrescar chat de inmediato
        } else {
            console.error("Error al enviar:", data.message);
        }
    })
    .catch(error => console.error('Error de red:', error));
}

function enviarMensajeAutomatico(texto) {
    const chatContainer = document.getElementById('chat-float-container');
    if (!chatContainer) return;
    
    const idItem = chatContainer.dataset.id || 0;

    const formData = new URLSearchParams();
    formData.append('accion', 'enviar');
    formData.append('id_item', idItem);
    formData.append('mensaje', texto);

    fetch('chat_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
    .then(res => res.json())
    .then(() => cargarMensajesServidor())
    .catch(error => console.error('Error de red:', error));
}

function cargarMensajesServidor() {
    const chatContainer = document.getElementById('chat-float-container');
    if (!chatContainer || chatContainer.style.display !== 'flex') return;

    const idItem = chatContainer.dataset.id || 0;

    fetch(`chat_backend.php?accion=obtener&id_item=${idItem}`)
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const mensajesContainer = document.getElementById('chat-messages');
            if (!mensajesContainer) return;
            mensajesContainer.innerHTML = '';

            data.mensajes.forEach(m => {
                const div = document.createElement('div');
                const esMio = m.remitente === data.usuario_actual;
                div.className = 'message ' + (esMio ? 'user' : 'bot');
                div.innerHTML = `<strong>${esMio ? 'Tú' : m.remitente}:</strong> ${m.mensaje}`;
                mensajesContainer.appendChild(div);
            });
            mensajesContainer.scrollTop = mensajesContainer.scrollHeight;
        }
    })
    .catch(error => console.error('Error de red:', error));
}

function iniciarActualizacionChat() {
    cargarMensajesServidor();
    if (!intervaloChat) {
        intervaloChat = setInterval(cargarMensajesServidor, 3000); // Consulta cada 3 segundos nuevos mensajes
    }
}

function detenerActualizacionChat() {
    if (intervaloChat) {
        clearInterval(intervaloChat);
        intervaloChat = null;
    }
}

function handleKeyPress(e) {
    if (e.key === 'Enter') enviarMensajeBot();
}