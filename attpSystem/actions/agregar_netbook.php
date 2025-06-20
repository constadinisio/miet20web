<?php
// Se inicia una sesión PHP
session_start();

// Captura los datos del formulario
$carrito = trim($_POST['carrito'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$fecha_adquisicion = trim($_POST['fecha_adquisicion'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');
$numero_serie = trim($_POST['numero_serie'] ?? '');
$estado = trim($_POST['estado'] ?? '');

// Si el usuario no tiene el rol ATTP, no lo dejará proseguir en la consulta.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] != 5) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

// Incluye el archivo a la conexión a la base de datos.
include "../../includes/db.php";

// Crea una variable con una consulta SQL para ingresar los datos capturados en el formulario. 
$sql = "INSERT INTO netbooks (carrito, numero, numero_serie, fecha_adquisicion, estado, observaciones)
        VALUES ('$carrito', '$numero', '$numero_serie', '$fecha_adquisicion', '$estado', '$observaciones')";
// Ejecuta una consulta SQL con la variable $sql utilizando el objeto de conexión a la base de datos $conexion.
$conexion->query($sql);

// Redirige a la página stock.php
header("Location: ../stock.php");