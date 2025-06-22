<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Galería de Categorías</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        div {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-white text-black font-sans">
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
                    <a href="index.php" class="block text-gray-600 hover:text-azulInstitucional px-4 py-2 rounded-md font-medium transition duration-300">Página Principal</a>
                    <a href="descargas.php" class="block text-gray-600 hover:text-rojoDestacado px-4 py-2 rounded-md font-medium">Descargas</a>
                    <a href="noticias.php" class="block text-gray-600 hover:text-verdeEsperanza px-4 py-2 rounded-md font-medium transition duration-300">Noticias</a>
                    <a href="galeria_home.php" class="text-amarilloEnergia px-3 py-2 rounded-md font-medium transition duration-300">Galeria</a>
                    <a href="contactos.php" class="text-gray-600 hover:text-rosaMagico px-3 py-2 rounded-md font-medium transition duration-300">Contactos</a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="md:hidden flex items-center">
                    <button class="mobile-menu-button outline-none">
                        <i class="fas fa-bars text-2xl text-gray-600"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div class="mobile-menu hidden md:hidden pb-4">
                <a href="index.php" class="block text-gray-600 hover:text-azulInstitucional px-4 py-2 rounded-md font-medium transition duration-300">Página Principal</a>
                    <a href="descargas.php" class="block text-gray-600 hover:text-rojoDestacado px-4 py-2 rounded-md font-medium">Descargas</a>
                    <a href="noticias.php" class="block text-gray-600 hover:text-verdeEsperanza px-4 py-2 rounded-md font-medium transition duration-300">Noticias</a>
                    <a href="galeria_home.php" class="text-amarilloEnergia px-3 py-2 rounded-md font-medium transition duration-300">Galeria</a>
                    <a href="contactos.php" class="text-gray-600 hover:text-rosaMagico px-3 py-2 rounded-md font-medium transition duration-300">Contactos</a>
            </div>
        </div>
    </nav>
    <!-- Header Section -->
    <section class="pt-24 pb-12 bg-gradient-to-r from-blue-500 to-blue-700 text-white">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-center">Menú de Imagenes</h1>
            <p class="text-center mt-4">Elegí que categoría queres ver en imagenes hoy</p>
        </div>
    </section>
    <div class="max-w-screen-xl mx-auto mt-10">
        <h1 class="text-3xl font-bold mb-6 text-center">Elegí una categoría</h1>
        <div class="grid grid-cols-2 grid-rows-2 gap-4">
            <a href="galeria.php?categoria=Eventos" class="bg-cat-eventos bg-no-repeat bg-cover bg-center border-2  border-black text-white font-bold p-10 row-span-2 grid place-items-center text-xl" style="text-shadow: 2px 2px 5px rgba(0,0,0,0.5);">Eventos</a>
            <a href="galeria.php?categoria=Talleres" class="bg-cat-especialidad bg-no-repeat bg-cover bg-center border-2 border-black text-white font-bold p-10 grid place-items-center text-xl" style="text-shadow: 2px 2px 5px rgba(0,0,0,0.5);">Talleres</a>
            <a href="galeria.php?categoria=Especialidades"class="bg-cat-talleres bg-no-repeat bg-cover bg-center border-2 border-black text-white font-bold p-10 col-start-2 row-start-2 grid place-items-center text-xl" style="text-shadow: 2px 2px 5px rgba(0,0,0,0.5);">Especialidades</a>
        </div>
    </div>
</body>

</html>