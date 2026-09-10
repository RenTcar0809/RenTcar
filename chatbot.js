// Funciones para alternar entre especificaciones y el chat en el panel derecho
function abrirChatEnSeccion() {
    const specsView = document.getElementById('specs-view');
    const chatContainer = document.getElementById('chat-inline-container');
    const chatInput = document.getElementById('chat-input');

    if (specsView) specsView.style.display = 'none';
    if (chatContainer) chatContainer.style.display = 'flex';
    if (chatInput) chatInput.focus();
}

function cerrarChatEnSeccion() {
    const specsView = document.getElementById('specs-view');
    const chatContainer = document.getElementById('chat-inline-container');

    if (chatContainer) chatContainer.style.display = 'none';
    if (specsView) specsView.style.display = 'block';
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