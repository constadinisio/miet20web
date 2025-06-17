<?php
// Se inicia una sesión PHP
session_start();

// Si el usuario no tiene el rol ATTP, no lo dejará proseguir en la consulta.
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] != 5) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

// Elimina todas las variables de sesión actual.
session_unset();

// Elimina la sesión actual.
session_destroy();

// Redirige a la página login.php
header("Location: ../login.php");
exit;
?>