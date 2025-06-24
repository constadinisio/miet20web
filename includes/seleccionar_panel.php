<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit;
}

$usuario = $_SESSION['usuario'];

$tieneATTP = ((int)$usuario['rol'] === 5);
$tieneNoticias = !empty($usuario['permNoticia']);
$tieneSubida = !empty($usuario['permSubidaArch']);

$totalPermisos = ($tieneATTP ? 1 : 0) + ($tieneNoticias ? 1 : 0) + ($tieneSubida ? 1 : 0);

// Redirección automática si solo tiene 1 permiso
if ($totalPermisos === 1) {
    if ($tieneATTP) {
        header("Location: ../attpSystem/index.php");
        exit;
    } elseif ($tieneNoticias) {
        header("Location: ../panelNoticias/panelNoticias.php");
        exit;
    } elseif ($tieneSubida) {
        header("Location: ../galeriaUtils/subirImagenes.php");
        exit;
    }
} elseif ($totalPermisos === 0) {
    header("Location: ../login.php?error=perm");
    exit;
}

// Si llega hasta acá, tiene 2 o más permisos → mostrar pantalla de selección
$nombre = htmlspecialchars($usuario['nombre'] ?? '');
$apellido = htmlspecialchars($usuario['apellido'] ?? '');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi ET20 - Seleccionar Panel</title>
    <link rel="stylesheet" href="../output.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg fixed w-full z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-center items-center h-16">
                <div class="flex items-center">
                    <a href="./index.html" class="flex items-center">
                        <i class="fas text-3xl text-blue-600 mr-4 -right-500"></i>
                        <h1><img src="../images/et20ico.ico" alt="Icono personalizado" class="w-10 h-10"></h1>
                        <span class="text-xl font-semibold text-gray-800 ml-2">Escuela Técnica 20 D.E. 20</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md text-center space-y-6">
            <h1 class="text-2xl font-bold text-gray-800">¡Hola <?= $nombre ?> <?= $apellido ?>!</h1>
            <p class="text-gray-600">Seleccioná a qué sistema querés acceder:</p>

            <div class="grid grid-cols-1 gap-4">
                <?php if ($tieneATTP): ?>
                    <a href="../attpSystem/index.php" class="bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-md font-semibold transition">
                        Sistema de Préstamos (ATTP)
                    </a>
                <?php endif; ?>

                <?php if ($tieneNoticias): ?>
                    <a href="../panelNoticias/panelNoticias.php" class="bg-green-600 hover:bg-green-700 text-white py-3 rounded-md font-semibold transition">
                        Panel de Noticias
                    </a>
                <?php endif; ?>

                <?php if ($tieneSubida): ?>
                    <a href="../galeriaUtils/subirImagenes.php" class="bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-md font-semibold transition">
                        Galería de Imágenes
                    </a>
                <?php endif; ?>
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
</body>

</html>