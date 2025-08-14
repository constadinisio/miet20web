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

// Captura y valida el ID de la netbook
$id = $_GET['id'] ?? '';
if (!ctype_digit($id)) {
    http_response_code(400);
    exit('ID inválido');
}

// Prepara y ejecuta la eliminación utilizando sentencias preparadas
$stmt = $conexion->prepare('DELETE FROM netbooks WHERE id = ?');
$idInt = (int) $id;
$stmt->bind_param('i', $idInt);
$stmt->execute();

// Verifica si se eliminó algún registro y redirige
if ($stmt->affected_rows > 0) {
    header('Location: /users/spei/stock.php');
    exit;
}

http_response_code(404);
exit('Netbook no encontrada');
