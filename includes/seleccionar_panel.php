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
    <title>Seleccionar Panel</title>
    <link rel="stylesheet" href="../output.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center">
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
</body>

</html>