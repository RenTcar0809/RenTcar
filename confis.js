document.addEventListener("DOMContentLoaded", function() {
    
    // --- 1. INTERACTIVIDAD DE PESTAÑAS (SPA Transition) ---
    const navLinks = document.querySelectorAll('.settings-nav a');
    const sections = document.querySelectorAll('.settings-main > section');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover clase active de todos los enlaces
            navLinks.forEach(nav => nav.classList.remove('active'));
            // Agregar active al seleccionado
            this.classList.add('active');

            // Obtener el objetivo
            const targetId = this.getAttribute('data-target');

            // Ocultar todas las secciones con efecto suave
            sections.forEach(sec => {
                sec.style.display = 'none';
            });

            // Mostrar la sección seleccionada
            const activeSection = document.getElementById(targetId);
            if (activeSection) {
                activeSection.style.display = 'block';
            }
        });
    });

    // --- 2. VALIDACIÓN DIDÁCTICA EN VIVO DE CONTRASEÑAS ---
    const passNueva = document.getElementById('pass_nueva');
    const passConfirmar = document.getElementById('pass_confirmar');
    const strengthText = document.getElementById('password-strength');
    const matchText = document.getElementById('password-match');

    if (passNueva && passConfirmar) {
        passNueva.addEventListener('input', function() {
            const val = this.value;
            if (val.length === 0) {
                strengthText.textContent = "";
            } else if (val.length < 6) {
                strengthText.textContent = "⚠ Contraseña muy corta (mínimo 6 caracteres)";
                strengthText.style.color = "#ef4444";
            } else if (val.length < 10) {
                strengthText.textContent = "⚡ Nivel de seguridad: Moderado";
                strengthText.style.color = "#f59e0b";
            } else {
                strengthText.textContent = "✔ ¡Contraseña segura y fuerte!";
                strengthText.style.color = "#10b981";
            }
        });

        passConfirmar.addEventListener('input', function() {
            if (this.value !== passNueva.value) {
                matchText.textContent = "Las contraseñas no coinciden";
            } else {
                matchText.textContent = "✔ Las contraseñas coinciden perfectamente";
                matchText.style.color = "#10b981";
            }
        });
    }

    // --- 3. CONFIGURACIÓN DE STRIPE ---
    if (document.getElementById('card-element')) {
        const stripe = Stripe('pk_test_51TioIu2Km1bRrhgffYk9jvu1w7ljZqVZobvPKcvZGqW3zKqu2iAEom38arTTZr4zao9kGBJpN8bmo5VzJLEao4Ym00xflZ9eFS');
        const elements = stripe.elements();

        const card = elements.create('card', {
            style: {
                base: {
                    color: '#ffffff',
                    fontFamily: 'Inter, sans-serif',
                    fontSize: '16px',
                    '::placeholder': { color: '#64748b' }
                }
            }
        });
        card.mount('#card-element');

        card.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
    }

    // --- 4. ANIMACIÓN DE CARGA EN FORMULARIOS ---
    const forms = document.querySelectorAll('.interactive-form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const btn = this.querySelector('.btn-save');
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
                btn.style.opacity = '0.7';
            }
        });
    });

});