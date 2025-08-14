<?php
// Se inicia una sesión PHP.
session_start();

// Si el usuario no tiene el rol ATTP, no lo dejará proseguir en la consulta.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] != 5) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

// Incluye el archivo a la conexión a la base de datos.
require_once __DIR__ . '/../../../../backend/includes/db.php';


// Captura y valida el ID del préstamo
$id = $_GET['id'] ?? '';
if (!ctype_digit($id)) {
    http_response_code(400);
    exit('ID inválido');
}
$fecha = date('d/m/Y');
$hora = date('H:i');

// Prepara y ejecuta la actualización utilizando sentencias preparadas
$stmt = $conexion->prepare('UPDATE prestamos SET Fecha_Devolucion = ?, Hora_Devolucion = ? WHERE Prestamo_ID = ?');
$idInt = (int) $id;
$stmt->bind_param('ssi', $fecha, $hora, $idInt);
$stmt->execute();

// Verifica si se actualizó algún registro y redirige
if ($stmt->affected_rows > 0) {
    header('Location: /users/spei/prestamos.php');
    exit;
}

http_response_code(404);
exit('Préstamo no encontrado');
