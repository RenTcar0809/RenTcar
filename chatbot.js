// Funciones para el Chatbot Flotante de la esquina
function toggleChatFlotante() {
    const chatContainer = document.getElementById('chat-float-container');
    if (!chatContainer) return;
    
    if (chatContainer.style.display === 'flex') {
        chatContainer.style.display = 'none';
    } else {
        chatContainer.style.display = 'flex';
        document.getElementById('chat-input')?.focus();
    }
}

// Función ejecutada al hacer clic en "Reservar ahora" (Estilo Marketplace)
function iniciarChatReserva(nombreVehiculo) {
    // Abre el contenedor flotante si está cerrado
    const chatContainer = document.getElementById('chat-float-container');
    if (chatContainer) {
        chatContainer.style.display = 'flex';
    }

    // Inyecta un mensaje inicial automático simulando el inicio de negociación estilo Marketplace
    setTimeout(() => {
        agregarMensaje(`Hola, estoy interesado/a en reservar: <b>${nombreVehiculo}</b>. ¿Está disponible y cuáles son los pasos a seguir?`, 'user');
        
        // Simular respuesta del asistente o vendedor tras un breve instante
        setTimeout(() => {
            agregarMensaje(`¡Hola! Claro que sí, el modelo <b>${nombreVehiculo}</b> está disponible para reserva. Para proceder, asegúrate de tener a la mano tu documento de identidad y licencia vigente. ¿Para qué fecha deseas programar la recogida?`, 'bot');
        }, 800);
    }, 200);
}

function handleKeyPress(e) {
    if (e.key === 'Enter') {
        enviarMensajeBot();
    }
}

function enviarMensajeBot() {
    const input = document.getElementById('chat-input');
    if (!input) return;

    const texto = input.value.trim();
    if (!texto) return;

    agregarMensaje(texto, 'user');
    input.value = '';

    fetch('chatbot_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'mensaje=' + encodeURIComponent(texto)
    })
    .then(res => res.json())
    .then(data => {
        agregarMensaje(data.respuesta, 'bot');
    })
    .catch(() => {
        agregarMensaje('Lo siento, ocurrió un error de conexión con el asistente.', 'bot');
    });
}

function agregarMensaje(texto, remitente) {
    const mensajesContainer = document.getElementById('chat-messages');
    if (!mensajesContainer) return;

    const div = document.createElement('div');
    div.className = 'message ' + remitente;
    div.innerHTML = texto;
    mensajesContainer.appendChild(div);
    mensajesContainer.scrollTop = mensajesContainer.scrollHeight;
}