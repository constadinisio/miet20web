<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ./actions/validar_login.php");
    exit;
}

$usuario = $_SESSION['usuario'];

include "../includes/db.php";
include "./includes/jsonLoader.php";

$noticias = cargarNoticias();
$noticias = array_reverse($noticias);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel Noticias</title>
    <link rel="stylesheet" href="../output.css">
    
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <style>
        body {
            font-family: Poppins;
        }
    </style>
    <!-- Navbar -->
    <nav class="bg-white shadow-lg w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-center items-center h-16">
                <div class="flex items-center">
                    <a href="../index.html" class="flex items-center">
                        <h1><img src="../images/et20png.png" alt="Icono personalizado" class="w-10 h-10"></h1>
                        <span class="text-xl font-semibold text-gray-800 ml-2">Escuela Técnica 20 D.E. 20</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative h-screen w-full pt-2 text-black overflow-hidden">
        <div class="absolute inset-0 bg-front-et20 bg-no-repeat bg-cover bg-center filter blur-sm scale-105"></div>
        <div class="absolute inset-0 bg-black/30"></div>

        <div class="max-w-3xl mx-auto relative bg-white p-6 rounded shadow-md m-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Panel de Noticias</h2>
                <a href="../includes/logout.php" class="bg-red-600 text-white px-4 py-2 rounded transition-colors hover:bg-red-700">Cerrar sesión</a>
            </div>

            <!-- Formulario -->
            <form action="./actions/guardarNoticia.php" method="POST" enctype="multipart/form-data" class="mb-8 space-y-4">
                <input type="text" name="titulo" placeholder="Título" required class="w-full border px-3 py-2 rounded">

                <!-- Editor Quill -->
                <div id="editor" class="bg-white h-48 mb-2 rounded border"></div>
                <input type="hidden" name="contenido" id="contenido">

                <input type="file" name="imagen" class="bg-yellow-500 text-white px-4 py-2 rounded transition-colors hover:bg-yellow-600">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded transition-colors hover:bg-green-700">Publicar Noticia</button>
            </form>

            <!-- Noticias -->
            <h3 class="text-xl font-semibold mb-4">Noticias publicadas</h3>
            <ul class="space-y-4">
                <?php foreach ($noticias as $noticia): ?>
                    <li class="border rounded p-4 bg-gray-50">
                        <h4 class="text-lg font-bold"><?= htmlspecialchars($noticia['titulo']) ?></h4>
                        <div class="contenido-noticia"><?= $noticia['contenido'] ?></div>
                        <div class="mt-2 flex space-x-4">
                            <a href="./actions/editarNoticia.php?id=<?= $noticia['id'] ?>" class="text-blue-600 hover:underline">Editar</a>
                            <a href="./actions/eliminarNoticia.php?id=<?= $noticia['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('¿Seguro que deseas eliminar esta noticia?');">Eliminar</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <!-- Script Quill -->
    <script>
        const quill = new Quill('#editor', {
            theme: 'snow'
        });

        // Antes de enviar, copiar contenido del editor al input hidden
        document.querySelector('form').addEventListener('submit', function(e) {
            // Prevenir envío momentáneamente
            e.preventDefault();

            // Copiar contenido del editor al input hidden
            document.getElementById('contenido').value = quill.root.innerHTML;

            console.log("Contenido a enviar:", document.getElementById('contenido').value);

            // Reenviar el formulario manualmente
            this.submit();
        });
    </script>

</body>

</html>