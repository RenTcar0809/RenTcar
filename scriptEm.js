document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.querySelector('.register-form');

    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            // Captura de elementos
            const razonSocial = document.getElementById('razonSocial');
            const nit = document.getElementById('nit');
            const confirmNit = document.getElementById('confirm-nit');
            const representante = document.getElementById('representanteLegal');
            const telefono = document.getElementById('telefonoEmpresa');
            const direccion = document.getElementById('direccionEmpresa');
            const correo = document.getElementById('correoEmpresa');
            const confirmCorreo = document.getElementById('confirm-correoEmpresa');
            const contrasena = document.getElementById('contrasena');
            const confirmContrasena = document.getElementById('confirm-contrasena');

            // 1. Validar que no existan campos con solo espacios en blanco
            const campos = [
                { input: razonSocial, nombre: 'Razón Social' },
                { input: nit, nombre: 'NIT' },
                { input: confirmNit, nombre: 'Confirmación de NIT' },
                { input: representante, nombre: 'Representante Legal' },
                { input: telefono, nombre: 'Teléfono' },
                { input: direccion, nombre: 'Dirección' },
                { input: correo, nombre: 'Correo Electrónico' },
                { input: confirmCorreo, nombre: 'Confirmación de Correo' },
                { input: contrasena, nombre: 'Contraseña' },
                { input: confirmContrasena, nombre: 'Confirmación de Contraseña' }
            ];

            for (let campo of campos) {
                if (campo.input && campo.input.value.trim() === '') {
                    alert(`⚠️ El campo "${campo.nombre}" no puede estar vacío.`);
                    campo.input.focus();
                    e.preventDefault();
                    return;
                }
            }

            // 2. Validar coincidencia de NIT
            if (nit.value.trim() !== confirmNit.value.trim()) {
                alert('⚠️ Los números de NIT ingresados no coinciden.');
                confirmNit.focus();
                e.preventDefault();
                return;
            }

            // 3. Validar coincidencia de Correo Electrónico
            if (correo.value.trim().toLowerCase() !== confirmCorreo.value.trim().toLowerCase()) {
                alert('⚠️ Los correos electrónicos ingresados no coinciden.');
                confirmCorreo.focus();
                e.preventDefault();
                return;
            }

            // 4. Validar longitud mínima de la Contraseña
            if (contrasena.value.length < 6) {
                alert('⚠️ La contraseña debe tener al menos 6 caracteres.');
                contrasena.focus();
                e.preventDefault();
                return;
            }

            // 5. Validar coincidencia de Contraseña
            if (contrasena.value !== confirmContrasena.value) {
                alert('⚠️ Las contraseñas ingresadas no coinciden.');
                confirmContrasena.focus();
                e.preventDefault();
                return;
            }
        });
    }
});