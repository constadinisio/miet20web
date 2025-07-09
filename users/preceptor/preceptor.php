<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) { // Suponiendo rol 2 = Preceptor
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

// Buscar cursos asignados al preceptor
$preceptor_id = $usuario['id'];
$cursos = [];
$sql = "SELECT c.id, c.anio, c.division
        FROM preceptor_curso pc
        JOIN cursos c ON pc.curso_id = c.id
        WHERE pc.preceptor_id = ? AND pc.estado = 'activo'
        ORDER BY c.anio, c.division";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $preceptor_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cursos[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel de Preceptor</title>
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
        <div class="flex items-center gap-3 mb-10">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div>
                <div class="font-bold text-lg"><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></div>
                <div class="text-xs text-gray-500">Preceptor/a</div>
            </div>
        </div>
        <a href="preceptor.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="asistencias.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📆 Asistencias</a>
        <a href="calificaciones.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📝 Calificaciones</a>
        <a href="boletines.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📑 Boletines</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-3xl font-bold mb-4">¡Bienvenido/a, <?php echo $usuario['nombre']; ?>!</h1>
        <div class="mt-4 text-lg text-gray-700">
            Desde tu panel podés <b>gestionar asistencias</b>, ver <b>calificaciones</b> y <b>generar boletines</b> de tus cursos.
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📆</div>
                <h2 class="text-xl font-bold mb-2">Asistencias</h2>
                <p class="text-gray-500 text-center mb-4">Modificá y gestioná las asistencias de los alumnos.</p>
                <a href="preceptor_asistencias.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ir a Asistencias</a>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📝</div>
                <h2 class="text-xl font-bold mb-2">Calificaciones</h2>
                <p class="text-gray-500 text-center mb-4">Visualizá las calificaciones de cada alumno.</p>
                <a href="preceptor_calificaciones.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ir a Calificaciones</a>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📑</div>
                <h2 class="text-xl font-bold mb-2">Boletines</h2>
                <p class="text-gray-500 text-center mb-4">Generá y exportá boletines en PDF.</p>
                <a href="preceptor_boletines.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ir a Boletines</a>
            </div>
        </div>
        <div class="mt-10">
            <h2 class="font-bold mb-2">Tus Cursos:</h2>
            <ul class="bg-white rounded-xl p-4 shadow grid grid-cols-1 gap-2">
                <?php foreach ($cursos as $curso): ?>
                    <li>
                        <span class="font-semibold"><?php echo $curso['anio'] . "°" . $curso['division']; ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($cursos)): ?>
                    <li class="text-gray-500">No tenés cursos asignados.</li>
                <?php endif; ?>
            </ul>
        </div>
    </main>
</body>

</html>