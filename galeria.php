<?php
require_once 'includes/db.php';

$categoria = $_GET['categoria'] ?? '';
$page = $_GET['page'] ?? 1;
$por_pagina = 12;
$offset = ($page - 1) * $por_pagina;

$stmt = $conexion->prepare("SELECT * FROM imagenes WHERE categoria = ? ORDER BY fecha_subida DESC LIMIT ? OFFSET ?");
$stmt->bind_param("sii", $categoria, $por_pagina, $offset);
$stmt->execute();
$resultado = $stmt->get_result();
$imagenes = $resultado->fetch_all(MYSQLI_ASSOC);

// Total para paginación
$total_result = $conexion->prepare("SELECT COUNT(*) FROM imagenes WHERE categoria = ?");
$total_result->bind_param("s", $categoria);
$total_result->execute();
$total_result->bind_result($total);
$total_result->fetch();
$total_paginas = ceil($total / $por_pagina);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Galería - <?= htmlspecialchars($categoria) ?></title>
    
    <link rel="stylesheet" href="output.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <style>
        .masonry {
            column-count: 3;
            column-gap: 1rem;
        }
        .masonry a {
            display: inline-block;
            margin-bottom: 1rem;
        }
        .masonry img {
            width: 100%;
            display: block;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-white text-black">
    <!-- Navbar (same as index.html) -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.html" class="flex items-center">
                        <i class="fas text-3xl text-blue-600 mr-4 -right-500"></i>
                            <h1><img src="./images/et20png.png" alt="Icono personalizado" class="w-10 h-10"></h1>
                        <span class="text-xl font-semibold text-gray-800 ml-2">Escuela Técnica 20 D.E. 20</span>
                    </a>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="index.html" class="block text-gray-600 hover:text-azulInstitucional px-4 py-2 rounded-md font-medium transition duration-300">Página Principal</a>
                    <a href="descargas.html" class="block text-gray-600 hover:text-rojoDestacado font-bold px-4 py-2 rounded-md font-medium">Descargas</a>
                    <a href="noticias.html" class="block text-gray-600 hover:text-verdeEsperanza px-4 py-2 rounded-md font-medium transition duration-300">Noticias</a>
                    <a href="galeria_home.php" class="text-amarilloEnergia px-3 py-2 rounded-md font-medium transition duration-300">Galeria</a>
                    <a href="contactos.html" class="text-gray-600 hover:text-rosaMagico px-3 py-2 rounded-md font-medium transition duration-300">Contactos</a>
                <div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button class="mobile-menu-button outline-none">
                        <i class="fas fa-bars text-2xl text-gray-600"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div class="mobile-menu hidden md:hidden pb-4">
                <a href="index.html" class="block text-gray-600 hover:text-azulInstitucional px-4 py-2 rounded-md font-medium transition duration-300">Página Principal</a>
                <a href="descargas.html" class="block text-gray-600 hover:text-rojoDestacado font-bold px-4 py-2 rounded-md font-medium">Descargas</a>
                <a href="noticias.html" class="block text-gray-600 hover:text-verdeEsperanza px-4 py-2 rounded-md font-medium transition duration-300">Noticias</a>
                <a href="galeria_home.php" class="text-amarilloEnergia px-3 py-2 rounded-md font-medium transition duration-300">Galeria</a>
                <a href="contactos.html" class="text-gray-600 hover:text-rosaMagico px-3 py-2 rounded-md font-medium transition duration-300">Contactos</a>
            </div>
        </div>
    </nav>
    <!-- Header Section -->
    <section class="pt-24 pb-12 bg-gradient-to-r from-blue-500 to-blue-700 text-white">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-center">Categoría: <?= htmlspecialchars($categoria) ?></h1>
            <p class="text-center mt-4">Aquí podrás ver todo el trabajo que se hace en nuestra institución</p>
        </div>
    </section>

    <div class="max-w-6xl mx-auto px-4 py-8">

        <div class="masonry">
            <?php foreach ($imagenes as $img): 
                $src = "galeriaUtils/imagenes/" . $categoria . "/" . $img['archivo'];
            ?>
                <a href="<?= $src ?>"
                   class="glightbox"
                   data-title="Autor: <?= htmlspecialchars($img['autor']) ?>"
                   data-description="<?= htmlspecialchars($img['descripcion']) ?>">
                    <img src="<?= $src ?>"
                         alt="<?= htmlspecialchars($img['descripcion']) ?>">
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <div class="flex justify-center mt-8 gap-2">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?categoria=<?= urlencode($categoria) ?>&page=<?= $i ?>"
                   class="px-3 py-1 border rounded <?= $i == $page ? 'bg-black text-white' : 'bg-white text-black' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Glightbox JS -->
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script>
        const lightbox = GLightbox({ selector: '.glightbox' });
    </script>
</body>
</html>