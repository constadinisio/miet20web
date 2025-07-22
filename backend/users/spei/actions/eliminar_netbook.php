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

// Captura los datos del formulario
$id = trim($_GET['id'] ?? '');

// Crea una variable con una consulta SQL para ingresar los datos capturados en el formulario. 
$sql = "DELETE FROM netbooks WHERE id = $id";
// Ejecuta una consulta SQL con la variable $sql utilizando el objeto de conexión a la base de datos $conexion.
$conexion->query($sql);

// Redirige a la página stock.php
header("Location: /users/spei/stock.php");