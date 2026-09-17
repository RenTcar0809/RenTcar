// valida_placa.js

document.addEventListener("DOMContentLoaded", function() {
    const inputPlaca = document.getElementById("placa");
    const botonGuardar = document.querySelector(".btn-primary");
    
    // Si por alguna razón no encuentra el input de placa en esta vista, detenemos el script para evitar errores
    if (!inputPlaca) return;

    // Buscamos automáticamente el formulario donde está la placa
    const formulario = inputPlaca.closest("form");

    // Variable global interna para controlar si la placa sirve o no
    let placaAprobada = false;

    // 1. Crear el contenedor del mensaje abajo del input si no existe
    let msgError = document.getElementById("msg-error-placa");
    if (!msgError) {
        msgError = document.createElement("span");
        msgError.id = "msg-error-placa";
        msgError.style.display = "block";
        msgError.style.fontSize = "14px";
        msgError.style.marginTop = "5px";
        msgError.style.fontWeight = "bold";
        inputPlaca.parentNode.insertBefore(msgError, inputPlaca.nextSibling);
    }

    // 2. BLOQUEO CRÍTICO: Interceptar el envío del formulario
    if (formulario) {
        formulario.addEventListener("submit", function(event) {
            // Si el usuario intenta enviar y la placa NO está aprobada...
            if (!placaAprobada) {
                event.preventDefault(); // <-- Cancela el envío a procesar_vehiculo.php
                mostrarError("❌ No puedes registrar el vehículo hasta que la placa sea válida y esté libre.");
                inputPlaca.focus();
            }
        });
    }

    // 3. Validación al salir del campo (blur)
    inputPlaca.addEventListener("blur", function() {
        let placaValor = inputPlaca.value.trim().toUpperCase();
        inputPlaca.value = placaValor;

        // EXPRESIONES REGULARES PARA COLOMBIA:
        // - Carro: 3 letras y 3 números (Ej: ABC123)
        // - Moto: 3 letras, 2 números y 1 letra/número (Ej: YSR13F)
        const formatoCarro = /^[A-Z]{3}[0-9]{3}$/;
        const formatoMoto = /^[A-Z]{3}[0-9]{2}[A-Z0-9]$/;

        if (placaValor.length === 0) {
            limpiarEstado();
            return;
        }

        // Validación de Formato (Carro o Moto)
        if (!formatoCarro.test(placaValor) && !formatoMoto.test(placaValor)) {
            mostrarError("❌ Formato inválido. Carro (ABC123) o Moto (ABC12F).");
            return;
        }

        // Validación de Duplicado con el PHP de fondo
        fetch("verificar_placa.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ placa: placaValor })
        })
        .then(response => response.json())
        .then(data => {
            if (data.existe) {
                mostrarError("❌ Este vehículo ya está registrado en el sistema.");
            } else {
                mostrarExito("✅ Placa correcta y disponible.");
            }
        })
        .catch(error => {
            console.error("Error en la validación:", error);
            mostrarError("⚠️ No se pudo verificar la placa con el servidor.");
        });
    });

    // 4. Limpiar alertas cuando vuelvan a escribir
    inputPlaca.addEventListener("input", function() {
        limpiarEstado();
    });

    // Funciones de control visual y lógico
    function mostrarError(mensaje) {
        placaAprobada = false; // Desaprueba la placa
        msgError.textContent = mensaje;
        msgError.style.color = "#ff4444"; 
        inputPlaca.style.borderColor = "#ff4444";
        inputPlaca.style.boxShadow = "0 0 5px rgba(255, 68, 68, 0.5)";
        if (botonGuardar) {
            botonGuardar.disabled = true;
            botonGuardar.style.opacity = "0.5";
        }
    }

    function mostrarExito(mensaje) {
        placaAprobada = true; // ¡APROBADA! El formulario ya se puede enviar
        msgError.textContent = mensaje;
        msgError.style.color = "#28a745"; 
        inputPlaca.style.borderColor = "#28a745";
        inputPlaca.style.boxShadow = "0 0 5px rgba(40, 167, 69, 0.5)";
        if (botonGuardar) {
            botonGuardar.disabled = false;
            botonGuardar.style.opacity = "1";
        }
    }

    function limpiarEstado() {
        placaAprobada = false; // Por seguridad, si cambia el texto vuelve a false
        msgError.textContent = "";
        inputPlaca.style.borderColor = "";
        inputPlaca.style.boxShadow = "";
        if (botonGuardar) {
            botonGuardar.disabled = false;
            botonGuardar.style.opacity = "1";
        }
    }
});