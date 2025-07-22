<?php
// Se inicia una sesión PHP
session_start();

// Si el usuario no tiene el rol ATTP, no lo dejará proseguir en la consulta.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] != 5) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

// Incluye el archivo a la conexión a la base de datos.
require_once __DIR__ . '/../../../../backend/includes/db.php';

// Crea una variable con una consulta SQL para obtener las fechas por orden desdenciente.
$sql = "SELECT * FROM pizarron ORDER BY fecha DESC";
// Crea una variable resultado con el resultado de la consulta creada mediante la variable $sql.
$resultado = $conexion->query($sql);

// Crea una variable como un array vacio.
$notas = [];

// Cada fila se obtiene como un array asociativo y se almacena para su posterior uso.
while ($row = $resultado->fetch_assoc()) {
    $notas[] = $row;
}

// Indica que la respuesta será en formato JSON
header('Content-Type: application/json');
// Convierte el array $notas a formato JSON y lo imprime como respuesta al cliente (por ejemplo, una solicitud AJAX)
echo json_encode($notas);
?>