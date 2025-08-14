<?php
session_start();

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['permNoticia'])) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Método no permitido");
}

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403);
    exit("Token CSRF inválido");
}

$id = trim($_POST['id'] ?? '');
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