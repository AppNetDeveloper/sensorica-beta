<?php
// Crear un objeto DateTime con la hora actual
$fecha = new DateTime();

// Formatear salida
echo $fecha->format('Y-m-d H:i:s');

// Mostrar también la zona horaria actual
echo ' Zona: ' . $fecha->getTimezone()->getName();
?>
