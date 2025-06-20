<?php
session_start();

// Verificamos que haya sesión activa
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit;
}

$nombre = $_SESSION['usuario']['nombre'] ?? '';
$apellido = $_SESSION['usuario']['apellido'] ?? '';
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
        <h1 class="text-2xl font-bold text-gray-800">¡Hola <?= htmlspecialchars($nombre) ?> <?= htmlspecialchars($apellido) ?>!</h1>
        <p class="text-gray-600">Seleccioná a qué sistema querés acceder:</p>

        <div class="grid grid-cols-1 gap-4">
            <a href="../attpSystem/index.php" class="bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-md font-semibold transition">
                Sistema de Préstamos (ATTP)
            </a>
            <a href="../panelNoticias/panelNoticias.php" class="bg-green-600 hover:bg-green-700 text-white py-3 rounded-md font-semibold transition">
                Panel de Noticias
            </a>
        </div>
    </div>
</body>

</html>