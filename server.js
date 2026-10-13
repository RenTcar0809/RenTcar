const express = require('express');
const mysql = require('mysql2');
const cors = require('cors');

const app = express();
const PORT = 3000;

// --- CONFIGURACIÓN DE MIDDLEWARES ---
app.use(cors());
app.use(express.json()); 

// --- CONEXIÓN A LA BASE DE DATOS ---
const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',       
    password: '',       
    database: 'rentcar' 
});

db.connect((err) => {
    if (err) {
        console.error('❌ Error crítico al conectar a MySQL:', err);
        return;
    }
    console.log('✅ Conexión exitosa a la base de datos MySQL (rentcar)');
});

// --- RUTA DE REGISTRO DE USUARIOS ---
app.post('/registro', (req, res) => {
    console.log("--------------------------------------------------");
    console.log("📥 Petición POST recibida en /registro");
    console.log("📦 Datos del cuerpo (req.body):", req.body);

    const { 
        nombre, 
        apellido, 
        fechaDeNacimiento, 
        documento, 
        numTelefono, 
        correo, 
        contraseña 
    } = req.body;

    // 1. VALIDACIÓN INTERNA: Verificar duplicados (Correo o Teléfono)
    console.log("🔍 Verificando si el correo o teléfono ya existen...");
    const buscarUsuario = "SELECT * FROM usuario WHERE correo = ? OR numTelefono = ?";

    db.query(buscarUsuario, [correo, numTelefono], (err, resultados) => {
        if (err) {
            console.error("❌ Error de SQL en la consulta SELECT:", err);
            return res.status(500).json({ error: err.message, mensaje: "Error interno al validar datos." });
        }

        if (resultados.length > 0) {
            console.log("⚠️ Registro denegado: El correo o el teléfono ya están registrados.");
            return res.status(400).json({ mensaje: "Usuario ya existente (el correo o el teléfono ya están registrados)." });
        }

        // 2. INSERCIÓN: Guardar el nuevo usuario en la base de datos
        console.log("🚀 Datos válidos. Insertando nuevo registro en la tabla 'usuario'...");
        const sql = `
            INSERT INTO usuario 
            (fechaDeNacimiento, contraseña, nombre, apellido, documento, correo, numTelefono) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        `;

        db.query(
            sql, 
            [fechaDeNacimiento, contraseña, nombre, apellido, documento, correo, numTelefono], 
            (err, result) => {
                if (err) {
                    console.error("❌ Error de SQL en la consulta INSERT:", err);
                    return res.status(500).json({ error: err.message, mensaje: "Error al guardar el usuario en la base de datos." });
                }
                
                console.log("🎉 ¡Éxito! Usuario guardado correctamente. ID generado:", result.insertId);
                console.log("--------------------------------------------------");
                
                // Esta respuesta le avisa al script.js frontend que lance el alert() de éxito
                return res.status(200).json({ mensaje: "Usuario registrado con éxito" });
            }
        );
    });
});

// --- INICIALIZACIÓN DEL SERVIDOR ---
app.listen(PORT, () => {
    console.log(`🚀 Servidor backend corriendo en http://localhost:${PORT}`);
    console.log("📌 Esperando interacciones desde el formulario web...");
});