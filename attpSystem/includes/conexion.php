<?php
// Define las credenciales de la base de datos a la que se conectará.
$host = "localhost";
$user = "root";
$password = "";
$database = "et20plataforma";

// Crea una variable conexión, creando una conexión a las credenciales que definimos.
$conexion = new mysqli($host, $user, $password, $database);

// Si la conexión marca error esta se detiene mostrando la razón por la cual se detuvo.
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>