<?php
session_start();

$contenido = trim($_POST['contenido'] ?? '');
$id = trim($_POST['id'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['permNoticia'])) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

require_once __DIR__ . '/../../../backend/includes/db.php';
include "/../includes/jsonLoader.php";

$noticias = cargarNoticias();
$noticias[$id] = [
    "id" => $id,
    "titulo" => $titulo,
    "contenido" => $contenido,
    "imagen" => $noticias[$id]['imagen'] ?? ''
];
guardarNoticias($noticias);

header("Location: ../panelNoticias.php");
exit;
