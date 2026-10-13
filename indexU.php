<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentCar - Crea tu cuenta</title>
    <!-- Fuentes originales -->
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Graduate&display=swap" rel="stylesheet">
    <!-- Tu archivo CSS -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <header class="top-bar">
        <div class="logo-container">
            <span class="rent-text">REN<span class="t-black">T</span>CAR</span>
            <!-- Imagen del carro -->
            <img src="unnamed.png" alt="RentCar Logo" class="carro">
           
            <svg class="car-icon" viewBox="0 0 100 35" width="60">
                <path d="M15,25 Q30,5 65,10 Q85,12 95,20 L95,25 Z" fill="none" stroke="white" stroke-width="2"/>
            </svg>
        </div>
    </header>

    <main class="main-container">
        <!-- El action apunta al procesador registrar.php -->
        <form class="register-form" action="registrar.php" method="POST">
           
            <h1 class="main-title"><span class="underline-red">CREA</span><span class="underline-red"> TU CUENTA</span></h1>

            <div class="form-split">
               
                <!-- COLUMNA IZQUIERDA: DATOS PERSONALES -->
                <div class="form-column left-column">
                    <h2><span class="text-white underline-white">DATOS</span> <span class="text-black underline-white"> PERSONALES</span></h2>
                   
                    <div class="input-group">
                        <label for="nombre"><span class="required">*</span>NOMBRE</label>
                        <input type="text" id="nombre" name="nombre" placeholder="..." required>
                    </div>

                    <div class="input-group">
                        <label for="apellido"><span class="required">*</span>APELLIDO</label>
                        <input type="text" id="apellido" name="apellido" placeholder="..." required>
                    </div>

                    <div class="input-group">
                        <label for="fechaDeNacimiento"><span class="required">*</span>FECHA DE NACIMIENTO</label>
                        <input type="text" id="fechaDeNacimiento" name="fechaDeNacimiento" placeholder="DD/MM/AAAA" onfocus="(this.type='date')" onblur="(this.placeholder='DD/MM/YY')" required>
                    </div>

                    <div class="input-group">
                        <label for="numTelefono"><span class="required">*</span>NÚMERO DE TELEFONO</label>
                        <div class="phone-input-container">
                            <input type="tel" id="numTelefono" name="numTelefono" placeholder="..." required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="documento"><span class="required">*</span>DOCUMENTO DE IDENTIDAD</label>
                        <input type="text" id="documento" name="documento" placeholder="..." required>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: DATOS DE INICIO DE SESIÓN -->
                <div class="form-column right-column">
                    <h2><span class="text-custom-red underline-red">DATOS DE</span> <span class="text-white underline-red">INICIO DE SESIÓN</span></h2>
                   
                    <div class="input-group">
                        <label for="correo"><span class="required">*</span>CORREO ELECTRÓNICO</label>
                        <input type="email" id="correo" name="correo" placeholder="ejemplo@correo.com" required>
                    </div>

                    <div class="input-group">
                        <label for="confirm-email"><span class="required">*</span>CONFIRME SU CORREO ELECTRÓNICO</label>
                        <input type="email" id="confirm-email" name="confirm_correo" placeholder="..." required>
                    </div>

                    <div class="input-group">
                        <label for="contraseña"><span class="required">*</span>CONTRASEÑA</label>
                        <input type="password" id="contraseña" name="contraseña" placeholder="Cree una contraseña" required>
                    </div>

                    <div class="input-group">
                        <label for="confirm-password"><span class="required">*</span>CONFIRME SU CONTRASEÑA</label>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="..." required>
                    </div>

                    <div class="input-group">
                        <label for="confirm-documento"><span class="required">*</span>CONFIRMAR DOCUMENTO</label>
                        <input type="text" id="confirm-documento" name="confirm_documento" placeholder="..." required>
                    </div>
                </div>

            </div>

            <footer class="form-footer">
                <!-- Botón Cancelar -->
                <a href="antesdellogin.html" class="btn btn-cancel" style="text-decoration: none; text-align: center; display: inline-block; line-height: 40px;">Cancelar</a>
                           
                <!-- ENLACES RECUPERADOS -->
                <div class="footer-links">
                    <a href="privacidad.html">Política de privacidad</a> — <a href="privacidad.html">Acerca de nosotros</a>
                    <div class="terms-link">
                        <a href="terminosYcondiciones.html">Términos y condiciones</a>
                    </div>
                </div>

                <!-- Botón Aceptar -->
                <button type="submit" class="btn btn-accept">Aceptar</button>
            </footer>

        </form>
    </main>

    <!-- Script de validación -->
    <script src="script.js"></script>
</body>
</html>