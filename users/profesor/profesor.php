<?php
session_start();
if (
    !isset($_SESSION['usuario']) ||
    !is_array($_SESSION['usuario']) ||
    (int)$_SESSION['usuario']['rol'] !== 3
) {
    // Si no cumple las condiciones, redirige al login con un error de rol
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

// Buscar cursos asignados al profesor
$profesor_id = $usuario['id'];
$cursos = [];

$sql = "SELECT c.id, c.anio, c.division, m.nombre AS materia
        FROM profesor_curso_materia pcm
        JOIN cursos c ON pcm.curso_id = c.id
        JOIN materias m ON pcm.materia_id = m.id
        WHERE pcm.profesor_id = ?
        ORDER BY c.anio, c.division, m.nombre";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $profesor_id);
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
    <title>Panel de Profesor</title>
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
    <nav class="w-60 bg-white shadow-lg px-6 py-8 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400">
            <img src="../../images/et20ico.ico" class="block items-center h-28 w-28">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3">
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Profesor/a</div>
            </div>
        </div>
        <a href="profesor.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="libro_temas.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📚 Libro de Temas</a>
        <a href="calificaciones.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📝 Calificaciones</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>

    <main class="flex-1 p-10">
        <h1 class="text-3xl font-bold mb-4">¡Bienvenido/a, <?php echo $usuario['nombre']; ?>!</h1>
        <div class="mt-4 text-lg text-gray-700">
            Desde tu panel podés cargar <b>temas vistos</b>, <b>calificaciones</b> y más.
        </div>
        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📚</div>
                <h2 class="text-xl font-bold mb-2">Libro de Temas</h2>
                <p class="text-gray-500 text-center mb-4">Registrá o consultá los temas dados en cada clase.</p>
                <a href="profesor_libro_temas.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ir al Libro</a>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-xl flex flex-col items-center">
                <div class="text-5xl mb-2">📝</div>
                <h2 class="text-xl font-bold mb-2">Calificaciones</h2>
                <p class="text-gray-500 text-center mb-4">Subí las notas de tus alumnos.</p>
                <a href="profesor_calificaciones.php" class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">Ir a Calificaciones</a>
            </div>
        </div>
        <div class="mt-10">
            <div class="bg-yellow-100 p-4 rounded-xl text-yellow-900 text-center">
                📢 <b>Próximamente</b> notificaciones y avisos importantes.
            </div>
        </div>
        <div class="mt-8">
            <h2 class="font-bold mb-2">Tus Cursos y Materias:</h2>
            <ul class="bg-white rounded-xl p-4 shadow grid grid-cols-1 gap-2">
                <?php foreach ($cursos as $curso): ?>
                    <li>
                        <span class="font-semibold"><?php echo $curso['anio'] . "°" . $curso['division']; ?></span> — <span><?php echo $curso['materia']; ?></span>
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