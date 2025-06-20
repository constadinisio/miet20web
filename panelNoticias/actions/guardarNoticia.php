<?php
session_start();

$contenido = trim($_POST['contenido'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['permNoticia'])) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

include "../../includes/db.php";

$imagenNombre = "";

// Procesar imagen si se subió
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $nombreOriginal = $_FILES['imagen']['name'];
    $rutaTemporal = $_FILES['imagen']['tmp_name'];
    $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
    $imagenNombre = uniqid('img_') . '.' . $extension;

    $carpetaImagenes = '../images/';
    if (!file_exists($carpetaImagenes)) {
        mkdir($carpetaImagenes, 0777, true);
    }

    move_uploaded_file($rutaTemporal, $carpetaImagenes . $imagenNombre);
}

// Cargar, agregar y guardar nueva noticia
$noticias = cargarNoticias();
$noticias[] = [
    "id" => uniqid(),
    "titulo" => $titulo,
    "contenido" => $contenido,
    "imagen" => $imagenNombre
];
guardarNoticias($noticias);

// Redirigir al panel
header("Location: ../panelNoticias.php");
exit;
