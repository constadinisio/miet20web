<?php
session_start();
$id = trim($_GET['id'] ?? '');
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['permNoticia'])) {
    http_response_code(403);
    exit("Acceso no autorizado");
}
require_once __DIR__ . '/../../../backend/includes/db.php';
include "../includes/jsonLoader.php";

$noticias = cargarNoticias();

// Buscar la noticia con ese ID y eliminarla
foreach ($noticias as $i => $noticia) {
    if (isset($noticia['id']) && $noticia['id'] === $id) {
        array_splice($noticias, $i, 1);
        break;
    }
}

guardarNoticias($noticias);
header("Location: ../panelNoticias.php");
?>