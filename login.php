<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi ET20</title>
    <link rel="icon" type="image/x-icon" href="./images/et20png.png">
    <link rel="stylesheet" href="./output.css" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div class="flex justify-center items-center h-16">
                <div class="flex items-center">
                    <a href="./index.html" class="flex items-center">
                        <i class="fas text-3xl text-blue-600 mr-4 -right-500"></i>
                        <h1><img src="./images/et20ico.ico" alt="Icono personalizado" class="w-10 h-10"></h1>
                        <span class="text-xl font-semibold text-gray-800 ml-2">Escuela Técnica 20 D.E. 20</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative h-screen w-full pt-16 text-white overflow-hidden">
        <!-- Imagen con blur -->
        <div class="absolute inset-0 bg-front-et20 bg-no-repeat bg-cover bg-center filter blur-sm scale-105"></div>

        <!-- Overlay opcional (oscurece un poco para mejorar legibilidad) -->
        <div class="absolute inset-0 bg-black/30"></div>

        <!-- Panel Login -->
        <div class="relative z-10 flex items-center justify-center h-full">
            <div class="bg-gray-800 text-white rounded-xl shadow-lg w-full max-w-md p-8 space-y-6">
                <h2 class="text-2xl font-bold text-center">Inicio de Sesión <br>Sistema Unificado</h2>

                <?php
                $error = trim($_GET['error'] ?? '');

                if ($error === 'perm'): ?>
                    <p class="text-red-600 bg-red-100 p-2 rounded mb-4 text-center">
                        No tenés permisos para acceder a ningún panel.
                    </p>
                <?php elseif ($error == 1): ?>
                    <p class="text-red-600 bg-red-100 p-2 rounded mb-4 text-center">
                        Usuario o contraseña incorrectos.
                    </p>
                <?php endif; ?>

                <form action="./includes/validar_login.php" method="POST" class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium">Email</label>
                        <input type="email" name="mail" id="usuario"
                            class="mt-1 w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium">Contraseña</label>
                        <input type="password" name="contrasena" id="password" required
                            class="mt-1 w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-md transition">
                        Iniciar Sesión
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 Escuela Técnica 20 D.E. 20 "Carolina Muzilli". Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript for Mobile Menu -->
    <script>
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.querySelector('.mobile-menu');

        mobileMenuButton?.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>

</html>