<?php
header('Content-Type: application/json');

// Recibir mensaje enviado por POST
$mensaje = isset($_POST['mensaje']) ? strtolower(trim($_POST['mensaje'])) : '';

$respuesta = "No estoy seguro de entender tu consulta. Recuerda que puedes preguntar sobre <b>requisitos</b>, <b>métodos de pago</b>, <b>pasos para reservar</b> o <b>horarios de recogida</b>.";

// Lógica de coincidencias (Keywords)
if (strpos($mensaje, 'requisito') !== false || strpos($mensaje, 'papeles') !== false || strpos($mensaje, 'documento') !== false) {
    $respuesta = "Para realizar el préstamo/alquiler de un vehículo necesitas:<br>
    1. <b>Pasaporte o DNI</b> vigente.<br>
    2. <b>Licencia de conducir</b> vigente.<br>
    3. Una <b>tarjeta de crédito</b> válida para la garantía.";
} 
elseif (strpos($mensaje, 'reservar') !== false || strpos($mensaje, 'alquilar') !== false || strpos($mensaje, 'como hacer') !== false) {
    $respuesta = "¡Es muy fácil! Solo debes ir a la sección de <a href='automoviles.php' style='color: #e50914; font-weight:bold;'>Automóviles</a>, elegir el auto que más te guste, hacer clic en <b>'Reservar Ahora'</b> y seguir los pasos en pantalla.";
} 
elseif (strpos($mensaje, 'pago') !== false || strpos($mensaje, 'tarjeta') !== false || strpos($mensaje, 'efectivo') !== false || strpos($mensaje, 'precio') !== false) {
    $respuesta = "Aceptamos tarjetas de crédito principales para las reservas y garantías. Los precios se calculan por día directamente en el detalle de cada vehículo.";
} 
elseif (strpos($mensaje, 'hola') !== false || strpos($mensaje, 'buenos dias') !== false || strpos($mensaje, 'buenas tardes') !== false) {
    $respuesta = "¡Hola! Bienvenido a RentCar. ¿Te gustaría conocer los vehículos disponibles o tienes dudas sobre el proceso de préstamo?";
} 
elseif (strpos($mensaje, 'contacto') !== false || strpos($mensaje, 'telefono') !== false || strpos($mensaje, 'ayuda humana') !== false) {
    $respuesta = "Puedes comunicarte con nuestro equipo de soporte técnico o atención al cliente directamente desde tu panel de usuario o llamando a nuestra línea de asistencia principal.";
}

// Retornar la respuesta en formato JSON
echo json_encode(['respuesta' => $respuesta]);