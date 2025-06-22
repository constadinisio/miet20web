<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi ET20 - Contacto</title>
    <link rel="icon" type="image/x-icon" href="images/et20png.png">
    <link href="output.css?v=<?= time() ?>" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
    <!-- Navbar (same as index.html) -->
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
                    <a href="index.php" class="text-gray-600 hover:text-azulInstitucional px-3 py-2 rounded-md font-medium transition duration-300">Página Principal</a>
                    <a href="descargas.php" class="text-gray-600 hover:text-rojoDestacado px-3 py-2 rounded-md font-medium">Descargas</a>
                    <a href="noticias.php" class="text-gray-600 hover:text-verdeEsperanza px-3 py-2 rounded-md font-medium transition duration-300">Noticias</a>
                    <a href="galeria_home.php" class="text-gray-600 hover:text-amarilloEnergia px-3 py-2 rounded-md font-medium transition duration-300">Galeria</a>
                    <a href="contactos.php" class="text-rosaMagico px-3 py-2 rounded-md font-medium transition duration-300">Contactos</a>
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
                <a href="index.php" class="text-gray-600 hover:text-azulInstitucional px-3 py-2 rounded-md font-medium transition duration-300">Página Principal</a>
                    <a href="descargas.php" class="text-gray-600 hover:text-rojoDestacado px-3 py-2 rounded-md font-medium">Descargas</a>
                    <a href="noticias.php" class="text-gray-600 hover:text-verdeEsperanza px-3 py-2 rounded-md font-medium transition duration-300">Noticias</a>
                    <a href="galeria_home.php" class="text-gray-600 hover:text-amarilloEnergia px-3 py-2 rounded-md font-medium transition duration-300">Galeria</a>
                    <a href="contactos.php" class="text-rosaMagico px-3 py-2 rounded-md font-medium transition duration-300">Contactos</a>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <section class="pt-24 pb-12 bg-gradient-to-r from-blue-500 to-blue-700 text-white">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-center">Contactos</h1>
            <p class="text-center mt-4">Ponte en contacto con nosotros para más información</p>
        </div>
    </section>

    <!-- Department Contacts -->
    <main class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="mb-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Academic Affairs -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fa-brands fa-whatsapp text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">WhatsApp</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            +549 11 39107733
                        </a>
                    </div>
                </div>

                <!-- Admissions Office -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fa-solid fa-user-tie text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Secretaria y Sector Administrativo</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            det_20_de20@bue.edu.ar
                        </a>
                    </div>
                </div>

                <!-- Student Services -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fa-solid fa-folder-open text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Oficina de Alumnos</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            oficinadealumnos.et20@gmail.com 
                        </a>
                    </div>
                </div>

                <!-- Athletics Department -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fas fa-running text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Preceptoría</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            preceptoria.et20de20@gmail.com
                        </a>
                    </div>
                </div>

                <!-- Library Services -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fas fa-book text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Departamento de Orientación Escolar</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            doeet20@gmail.com
                        </a>
                    </div>
                </div>

                <!-- IT Support -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fa-solid fa-ticket text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Becas</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            becas.et20de20@gmail.com
                        </a>
                    </div>
                </div>

                <!-- Financial Aid -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fa-solid fa-pen-fancy text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Información sobre Inscripciones</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            informacioninscripciones.et20@gmail.com 
                        </a>
                    </div>
                </div>

                <!-- Career Center -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fa-solid fa-user-group text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Cooperadora</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            cooperadora.tecnica20de20@bue.edu.ar
                        </a>
                    </div>
                </div>

                <!-- Health Services -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fa-solid fa-book text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Biblioteca</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            sites.google.com/view/biblioteca-et20-de20/ 
                        </a>
                    </div>
                </div>

                <!-- International Office -->
                <div class="department-card bg-white rounded-lg shadow-md p-6 hover:shadow-xl">
                    <div class="text-center">
                        <i class="fa-solid fa-screwdriver-wrench text-4xl text-blue-800 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">Taller</h3>
                        <a href="" class="text-blue-600 hover:text-blue-800">
                            et20taller1erciclo@gmail.com
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
