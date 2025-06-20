<?php
session_start();
$id = trim($_GET['id'] ?? '');
if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['permNoticia'])) {
    http_response_code(403);
    exit("Acceso no autorizado");
}

$id = trim($_GET['id'] ?? '');
include "../../includes/db.php";

$noticias = cargarNoticias();
$noticia = null;

foreach ($noticias as $n) {
    if ($n['id'] === $id) {
        $noticia = $n;
        break;
    }
}

if (!$noticia) {
    die("Noticia no encontrada.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Noticia</title>
    <link rel="stylesheet" href='../../output.css'>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="../../images/et20png.png">

    <!-- Quill -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

    <style>
        body {
            font-family: Poppins;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-8 bg-front-et20 bg-no-repeat bg-cover">
    <div class="max-w-xl mx-auto bg-white p-6 rounded shadow-md">
        <h2 class="text-xl font-bold mb-4">Editar Noticia</h2>
        <form action="../actions/guardarEdicion.php" method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?= $id ?>">

            <label class="block font-semibold">Título:</label>
            <input type="text" name="titulo" value="<?= htmlspecialchars($noticia['titulo']) ?>" class="w-full border px-3 py-2 rounded">

            <label class="block font-semibold">Contenido:</label>
            <!-- Editor visual -->
            <div id="editor" class="bg-white border px-3 py-2 rounded" style="min-height: 200px;"></div>
            <input type="hidden" name="contenido" id="contenido">

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar cambios</button>
        </form>
    </div>

    <script>
        const quill = new Quill('#editor', {
            theme: 'snow'
        });

        // Insertar contenido actual
        quill.root.innerHTML = <?= json_encode($noticia['contenido']) ?>;

        // Copiar contenido HTML al enviar el formulario
        document.querySelector("form").addEventListener("submit", function () {
            document.getElementById("contenido").value = quill.root.innerHTML;
        });
    </script>
</body>
</html>