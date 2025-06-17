<?php
session_start();
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['permNoticia'])) {
    http_response_code(403);
    exit("Acceso no autorizado");
}
session_start();
session_destroy();
header("Location: ../login.php");
?>