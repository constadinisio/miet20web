<?php
session_start();
if (
    !isset($_SESSION['usuario']) ||
    !is_array($_SESSION['usuario']) ||
    (int)$_SESSION['usuario']['rol'] !== 4
) {
    // Si no cumple las condiciones, redirige al login con un error de rol
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Alumno</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <!-- Navbar lateral -->
    <nav class="w-60 bg-white shadow-lg px-6 py-8 flex flex-col gap-2">
        <div class="flex items-center mb-10 gap-4">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3">
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Alumno/a</div>
            </div>
        </div>

        <a href="alumno.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="asistencias.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📆 Asistencias</a>
        <a href="notas.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📝 Notas</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <!-- Contenido principal -->
    <main class="flex-1 p-10">
        <h1 class="text-3xl font-bold mb-4">¡Bienvenid@, <?php echo $usuario['nombre']; ?>!</h1>
        <div class="mt-4 text-lg text-gray-700">
            Desde tu panel podrás consultar tus <b>asistencias</b>, <b>notas</b> y recibir notificaciones escolares.
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📆</div>
                <h2 class="text-xl font-bold mb-2">Asistencias</h2>
                <p class="text-gray-500 text-center mb-4">Revisá tus asistencias diarias.</p>
                <a href="asistencias.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ver Asistencias</a>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📝</div>
                <h2 class="text-xl font-bold mb-2">Notas</h2>
                <p class="text-gray-500 text-center mb-4">Consultá todas tus calificaciones.</p>
                <a href="notas.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ver Notas</a>
            </div>
        </div>
        <div class="mt-10">
            <div class="bg-yellow-100 p-4 rounded-xl text-yellow-900 text-center">
                📢 <b>Próximamente</b> podrás recibir notificaciones importantes directamente desde tu panel.
            </div>
        </div>
    </main>
</body>

</html>