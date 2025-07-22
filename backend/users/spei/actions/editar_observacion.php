<?php
// Se inicia una sesión PHP
session_start();

// Captura los datos del formulario
$observaciones = trim($_POST['observaciones'] ?? '');
$id = trim($_POST['id'] ?? '');

// Si el usuario no tiene el rol ATTP, no lo dejará proseguir en la consulta.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] != 5) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

// Incluye el archivo a la conexión a la base de datos.
require_once __DIR__ . '/../../../../backend/includes/db.php';

// Crea una variable con una consulta SQL para ingresar los datos capturados en el formulario. 
$sql = "UPDATE netbooks SET observaciones='$observaciones' WHERE id = $id";
// Ejecuta una consulta SQL con la variable $sql utilizando el objeto de conexión a la base de datos $conexion.
$conexion->query($sql);

// Redirige a la página stock.php
header("Location: /users/spei/stock.php");