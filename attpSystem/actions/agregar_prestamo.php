<?php
// Se inicia una sesión PHP
session_start();

// Captura los datos del formulario
$Netbook_ID = trim($_POST['Netbook_ID'] ?? '');
$Curso = trim($_POST['Curso'] ?? '');
$Tutor = trim($_POST['Tutor'] ?? '');
$Alumno = trim($_POST['Alumno'] ?? '');
$Hora_Prestamo = trim($_POST['Hora_Prestamo'] ?? '');
$Fecha_Prestamo = trim($_POST['Fecha_Prestamo'] ?? '');

// Si el usuario no tiene el rol ATTP, no lo dejará proseguir en la consulta.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] != 5) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

// Incluye el archivo a la conexión a la base de datos.
include "../includes/conexion.php";

// Crea una variable con una consulta SQL para ingresar los datos capturados en el formulario.
$sql = "INSERT INTO prestamos (Netbook_ID, Fecha_Prestamo, Hora_Prestamo, Curso, Alumno, Tutor)
        VALUES ('$Netbook_ID', '$Fecha_Prestamo', '$Hora_Prestamo', '$Curso', '$Alumno', '$Tutor')";

// Ejecuta una consulta SQL con la variable $sql utilizando el objeto de conexión a la base de datos $conexion.
$conexion->query($sql);

// Redirige a la página prestamos.php.
header("Location: ../prestamos.php");
exit;