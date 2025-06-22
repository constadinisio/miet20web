<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi ET20 - Página Principal</title>
    <link rel="icon" type="image/x-icon" href="images/et20png.png">
    <link href="output.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <i class="fas text-3xl text-blue-600 mr-4 -right-500"></i>
                            <h1><img src="./images/et20png.png" alt="Icono personalizado" class="w-10 h-10"></h1>
                        <span class="text-xl font-semibold text-gray-800 ml-2">Escuela Técnica 20 D.E. 20</span>
                    </a>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-4">
                    <a href="index.php" class="text-azulInstitucional px-3 py-2 rounded-md font-medium">Página Principal</a>
                    <a href="descargas.php" class="text-gray-600 hover:text-rojoDestacado px-3 py-2 rounded-md font-medium transition duration-300">Descargas</a>
                    <a href="noticias.php" class="text-gray-600 hover:text-verdeEsperanza px-3 py-2 rounded-md font-medium transition duration-300">Noticias</a>
                    <a href="galeria_home.php" class="text-gray-600 hover:text-amarilloEnergia px-3 py-2 rounded-md font-medium transition duration-300">Galeria</a>
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
                <a href="index.php" class="block text-azulInstitucional px-4 py-2 rounded-md font-medium">Página Principal</a>
                <a href="descargas.php" class="block text-gray-600 hover:text-rojoDestacado px-4 py-2 rounded-md font-medium transition duration-300">Descargas</a>
                <a href="noticias.php" class="block text-gray-600 hover:text-verdeEsperanza px-4 py-2 rounded-md font-medium transition duration-300">Noticias</a>
                <a href="galeria_home.php" class="text-gray-600 hover:text-amarilloEnergia px-4 py-2 rounded-md font-medium transition duration-300">Galeria</a>
                <a href="contactos.php" class="text-gray-600 hover:text-rosaMagico px-4 py-2 rounded-md font-medium transition duration-300">Contactos</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative h-screen w-full pt-16 text-white overflow-hidden">
        <!-- Imagen con blur -->
        <div class="absolute inset-0 bg-front-et20 bg-no-repeat bg-cover bg-center filter blur-sm scale-105"></div>
      
        <!-- Overlay opcional (oscurece un poco para mejorar legibilidad) -->
        <div class="absolute inset-0 bg-black/30"></div>
      
        <!-- Contenido -->
        <div class="relative z-10 max-w-7xl mx-auto px-4 py-20 md:py-32">
          <div class="text-center">
            <h2 class="text-4xl md:text-6xl font-bold mb-6">
              Bienvenidos a la <br />
              Escuela Técnica 20 D.E. 20<br />
              "Carolina Muzilli"
            </h2>
            <p class="text-xl md:text-2xl mb-8">
              Formando líderes del mañana con excelencia académica y valores
            </p>
          </div>
        </div>
      </section>      

    <!-- Presentación del Colegio -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Nuestra Institución</h2>
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div>
                    <img src="https://images.pexels.com/photos/8613089/pexels-photo-8613089.jpeg" alt="Fachada del colegio" class="rounded-lg shadow-lg">
                </div>
                <div>
                    <h3 class="text-2xl font-semibold mb-4">Excelencia Educativa</h3>
                    <p class="text-gray-600 mb-6">
                        Con más de 25 años de experiencia, nuestro colegio se ha destacado por brindar una educación integral, 
                        combinando excelencia académica con formación en valores. Nuestro compromiso es preparar a los estudiantes 
                        para los desafíos del futuro, fomentando el pensamiento crítico y la innovación.
                    </p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <i class="fas fa-users text-blue-600 text-2xl mb-2"></i>
                            <h4 class="font-semibold">Comunidad</h4>
                            <p class="text-sm text-gray-600">Más de 700 estudiantes</p>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <i class="fas fa-award text-blue-600 text-2xl mb-2"></i>
                            <h4 class="font-semibold">Reconocimientos</h4>
                            <p class="text-sm text-gray-600">Excelencia educativa</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Especialidades -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Nuestras Especialidades</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Producción Multimedial -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden transition duration-300 hover:shadow-xl">
                    <img src="./images/set_mult.jpg" alt="Laboratorio de ciencias" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2">Producción Multimedial</h3>
                        <p class="text-gray-600">
                            Especialidad centrada en diseño multimedial, animación, audio, video y producción digital.
                        </p>
                    </div>
                </div>

                <!-- Tecnologías de la Información y la Comunicación -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden transition duration-300 hover:shadow-xl">
                    <img src="./images/ing_tics.webp" alt="Biblioteca" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2">Tecnologías de la Información y la Comunicación</h3>
                        <p class="text-gray-600">
                            Especialidad enfocada en informática, redes, programación, soporte técnico y comunicación digital.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Galería de Imágenes -->
    <section class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-6">Galería de Imágenes</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <img src="./images/test7.jpeg" alt="Actividades escolares" class="rounded-lg shadow-md hover:shadow-xl transition duration-300">
                <img src="./images/test3.jpeg" alt="Estudiantes en clase" class="rounded-lg shadow-md hover:shadow-xl transition duration-300">
                <img src="./images/test5.jpeg" alt="Laboratorio" class="rounded-lg shadow-md hover:shadow-xl transition duration-300">
                <img src="./images/test1.jpeg" alt="Biblioteca" class="rounded-lg shadow-md hover:shadow-xl transition duration-300">
            </div>
            <div class="flex justify-center my-10">
                <a href="#ver-mas" class="bg-blue-600 text-white px-4 py-1 rounded text-lg shadow-lg hover:bg-blue-900 transition">
                    Ver Más de la Galeria
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Escuela Técnica 20 D.E. 20<br>"Carolina Muzilli"</h3>
                    <p class="text-gray-400">
                        Formando líderes del mañana con excelencia académica y valores.
                    </p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Contacto</h3>
                    <p class="text-gray-400">
                        <i class="fas fa-map-marker-alt mr-2"></i> Murguiondo 2151, CABA
                        <br>
                        <i class="fas fa-phone mr-2"></i> (54) 113910-7733
                        <br>
                        <i class="fas fa-envelope mr-2"></i> det_20_de20@bue.edu.ar
                    </p>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Síguenos</h3>
                    <div class="flex space-x-4">
                        <a href="https://www.youtube.com/@ETDEPoloMataderos-Verificacion/featured" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fa-brands fa-youtube text-2xl"></i>
                        </a>
                        <a href="https://www.instagram.com/et20polomataderos/" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-instagram text-2xl"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 Escuela Técnica 20 D.E. 20 "Carolina Muzilli". Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript for Mobile Menu -->
    <script>
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>
