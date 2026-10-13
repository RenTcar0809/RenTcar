document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".register-form");

    form.addEventListener("submit", (e) => {
        // Obtenemos los valores
        const doc = document.getElementById("documento").value;
        const confDoc = document.getElementById("confirm-documento").value;
        const correo = document.getElementById("correo").value;
        const confCorreo = document.getElementById("confirm-email").value;
        const pass = document.getElementById("contraseña").value;
        const confPass = document.getElementById("confirm-password").value;

        // Validaciones en el navegador
        if (doc !== confDoc) {
            e.preventDefault();
            alert("❌ Los números de documento no coinciden");
            return;
        }

        if (correo !== confCorreo) {
            e.preventDefault();
            alert("❌ Los correos electrónicos no coinciden");
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