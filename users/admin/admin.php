<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) { // Suponiendo rol 1 = Admin
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Administración</title>
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
    <nav class="w-60 bg-white shadow-lg px-6 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400">
            <img src="../../images/et20ico.ico" class="block items-center h-28 w-28">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3">
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Administrador/a</div>
            </div>
        </div>
        <a href="admin.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="usuarios.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👥 Usuarios</a>
        <a href="cursos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">🏫 Cursos</a>
        <a href="alumnos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👤 Alumnos</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-3xl font-bold mb-4">¡Bienvenido/a, <?php echo $usuario['nombre']; ?>!</h1>
        <div class="mt-4 text-lg text-gray-700">
            Desde el panel de administración podés <b>gestionar usuarios pendientes</b>, <b>cursos</b> y <b>alumnos</b> de todo el sistema.
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">👥</div>
                <h2 class="text-xl font-bold mb-2">Usuarios pendientes</h2>
                <p class="text-gray-500 text-center mb-4">Aprobá nuevos usuarios y definí sus roles.</p>
                <a href="admin_usuarios.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Gestionar</a>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">🏫</div>
                <h2 class="text-xl font-bold mb-2">Cursos</h2>
                <p class="text-gray-500 text-center mb-4">Agregá o quitá alumnos de los cursos.</p>
                <a href="cursos.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Gestionar</a>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">👤</div>
                <h2 class="text-xl font-bold mb-2">Alumnos</h2>
                <p class="text-gray-500 text-center mb-4">Modificá la información de los alumnos.</p>
                <a href="admin_alumnos.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Gestionar</a>
            </div>
        </div>
    </main>
</body>

</html>