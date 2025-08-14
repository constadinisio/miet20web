<?php
session_start();

$contenido = trim($_POST['contenido'] ?? '');
$titulo = trim($_POST['titulo'] ?? '');

if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['permNoticia'])) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

require_once __DIR__ . '/../../../backend/includes/db.php';
include "../includes/jsonLoader.php";

$imagenNombre = "";

// Procesar imagen si se subió
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['imagen'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    $allowed = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];

    $nombre_tmp = $archivo['tmp_name'];
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($nombre_tmp);

    if ($archivo['size'] > $maxSize || !isset($allowed[$ext]) || $allowed[$ext] !== $mime) {
        http_response_code(400);
        exit('Archivo de imagen no permitido');
    }

    $imagenNombre = bin2hex(random_bytes(16)) . '.' . $ext;
    $carpetaImagenes = '../images/';
    if (!file_exists($carpetaImagenes)) {
        $oldUmask = umask(0);
        mkdir($carpetaImagenes, 0755, true);
        umask($oldUmask);
    }

    $ruta_destino = $carpetaImagenes . $imagenNombre;
    $data = file_get_contents($nombre_tmp);
    $image = @imagecreatefromstring($data);
    if ($image === false) {
        http_response_code(400);
        exit('Imagen no válida');
    }

    switch ($mime) {
        case 'image/jpeg':
            imagejpeg($image, $ruta_destino, 90);
            break;
        case 'image/png':
            imagepng($image, $ruta_destino);
            break;
        case 'image/webp':
            imagewebp($image, $ruta_destino);
            break;
    }
    imagedestroy($image);
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
