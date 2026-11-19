document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".register-form");
    if (!form) return;

    const contrasenasComunes = new Set([
        "1234",
        "12345",
        "123456",
        "1234567",
        "12345678",
        "123456789",
        "1234567890",
        "00000000",
        "11111111",
        "password",
        "password1",
        "password123",
        "qwerty",
        "qwerty123",
        "abc12345",
        "admin123",
        "usuario123",
        "rentcar123",
        "iloveyou",
        "letmein",
        "welcome1"
    ]);

    function validarPasswordFuerte(password) {
        if (password.length < 8) {
            return "La contraseña debe tener al menos 8 caracteres.";
        }

        const normalizada = password.trim().toLowerCase();

        // Bloquear contraseñas comunes conocidas
        if (contrasenasComunes.has(normalizada)) {
            return "Esa contraseña es muy común y fácil de adivinar. Elige otra más segura.";
        }

        // Bloquear si todos los caracteres son iguales (ej. "aaaaaaaa" o "99999999")
        if (/^(.)\1+$/.test(password)) {
            return "No puedes usar el mismo carácter repetido en toda la contraseña.";
        }

        // Bloquear secuencias numéricas o alfabéticas simples (ej. "12345678", "abcdefgh")
        const soloNumeros = /^\d+$/.test(normalizada);
        const soloLetras = /^[a-z]+$/.test(normalizada);
        if (soloNumeros || soloLetras) {
            return "Usa una combinación de letras, números o símbolos (no solo letras o solo números).";
        }

        // Exigir al menos una letra y un número
        if (!/[A-Za-zÀ-ÿ]/.test(password) || !/\d/.test(password)) {
            return "La contraseña debe incluir al menos una letra y un número.";
        }

        return null; // Válida
    }

    form.addEventListener("submit", (e) => {
        const doc = document.getElementById("documento").value.trim();
        const confDoc = document.getElementById("confirm-documento").value.trim();
        const correo = document.getElementById("correo").value.trim();
        const confCorreo = document.getElementById("confirm-email").value.trim();
        const pass = document.getElementById("contraseña").value;
        const confPass = document.getElementById("confirm-password").value;

        if (doc !== confDoc) {
            e.preventDefault();
            alert("❌ Los números de documento no coinciden");
            return;
        }

        if (correo.toLowerCase() !== confCorreo.toLowerCase()) {
            e.preventDefault();
            alert("❌ Los correos electrónicos no coinciden");
            return;
        }

        const errorPass = validarPasswordFuerte(pass);
        if (errorPass) {
            e.preventDefault();
            alert(`❌ ${errorPass}`);
            return;
        }

        if (pass !== confPass) {
            e.preventDefault();
            alert("❌ Las contraseñas no coinciden");
            return;
        }

        // Si todo está bien, el formulario se envía automáticamente a registrar.php
    });
});