<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: ../../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

// Traer asignaciones existentes
$asignaciones = [];
$sql = "SELECT pcm.id, u.nombre AS prof_nombre, u.apellido AS prof_apellido,
               c.anio, c.division, m.nombre AS materia
        FROM profesor_curso_materia pcm
        JOIN usuarios u ON pcm.profesor_id = u.id
        JOIN cursos c ON pcm.curso_id = c.id
        JOIN materias m ON pcm.materia_id = m.id
        WHERE pcm.estado = 'activo'
        ORDER BY u.apellido, c.anio, c.division, m.nombre";
$res = $conexion->query($sql);
while ($row = $res->fetch_assoc()) {
    $asignaciones[] = $row;
}

$asignacion_id = $_GET['asignacion_id'] ?? null;

// Horarios actuales
$horarios = [];
if ($asignacion_id) {
    $sql = "SELECT id, dia_semana, hora_inicio, hora_fin FROM horarios_materia WHERE profesor_id = (
                SELECT profesor_id FROM profesor_curso_materia WHERE id = ?
            ) AND curso_id = (
                SELECT curso_id FROM profesor_curso_materia WHERE id = ?
            ) AND materia_id = (
                SELECT materia_id FROM profesor_curso_materia WHERE id = ?
            ) ORDER BY FIELD(dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iii", $asignacion_id, $asignacion_id, $asignacion_id);
    $stmt->execute();
    $horarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Asignar Horarios</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
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
        <a href="admin.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="usuarios.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👥 Usuarios</a>
        <a href="cursos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">🏫 Cursos</a>
        <a href="alumnos.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">👤 Alumnos</a>
        <a href="materias.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📚 Materias</a>
        <a href="horarios.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">⏰ Horarios</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">🕒 Asignación de Horarios</h1>
        <?php if (isset($_GET['ok'])): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-green-100 border border-green-400 text-green-800">
                <?php
                switch ($_GET['ok']) {
                    case 'horario_agregado':
                        echo '✅ Horario asignado correctamente.';
                        break;
                    case 'horario_eliminado':
                        echo '🗑️ Horario eliminado correctamente.';
                        break;
                }
                ?>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-100 border border-red-400 text-red-800">
                <?php
                switch ($_GET['error']) {
                    case 'faltan_campos':
                        echo '❗ Por favor completá todos los campos para agregar un horario.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        <!-- Card 1: Selección -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">🔍 Seleccionar asignación</h2>
            <form method="get" class="flex gap-4">
                <select name="asignacion_id" class="w-full md:w-1/2 px-4 py-2 rounded-xl border" required>
                    <option value="">Seleccionar curso + materia + profesor</option>
                    <?php foreach ($asignaciones as $a): ?>
                        <option value="<?php echo $a['id']; ?>" <?php if ($asignacion_id == $a['id']) echo 'selected'; ?>>
                            <?php echo "{$a['anio']}°{$a['division']} - {$a['materia']} ({$a['prof_apellido']}, {$a['prof_nombre']})"; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="bg-indigo-600 text-white px-4 py-2 rounded-xl hover:bg-indigo-700">Ver</button>
            </form>
        </div>

        <?php if ($asignacion_id): ?>
            <!-- Card 2: Formulario -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">➕ Agregar horario</h2>
                <form action="./utils/admin_agregar_horario.php" method="post" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="asignacion_id" value="<?php echo $asignacion_id; ?>">
                    <select name="dia" class="px-4 py-2 border rounded-xl" required>
                        <option value="">Día</option>
                        <option>Lunes</option>
                        <option>Martes</option>
                        <option>Miércoles</option>
                        <option>Jueves</option>
                        <option>Viernes</option>
                        <option>Sábado</option>
                    </select>
                    <input type="time" name="hora_inicio" class="px-4 py-2 border rounded-xl" required>
                    <input type="time" name="hora_fin" class="px-4 py-2 border rounded-xl" required>
                    <button type="submit" class="col-span-1 md:col-span-4 bg-green-600 text-white py-2 rounded-xl hover:bg-green-700">Guardar horario</button>
                </form>
            </div>

            <!-- Card 3: Lista de horarios -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-semibold mb-4">📋 Horarios asignados</h2>
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left px-4 py-2">Día</th>
                            <th class="text-left px-4 py-2">Inicio</th>
                            <th class="text-left px-4 py-2">Fin</th>
                            <th class="text-left px-4 py-2">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horarios as $h): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo $h['dia_semana']; ?></td>
                                <td class="px-4 py-2"><?php echo substr($h['hora_inicio'], 0, 5); ?></td>
                                <td class="px-4 py-2"><?php echo substr($h['hora_fin'], 0, 5); ?></td>
                                <td class="px-4 py-2">
                                    <form method="post" action="./utils/admin_eliminar_horario.php" onsubmit="return confirm('¿Eliminar este horario?');">
                                        <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                                        <input type="hidden" name="asignacion_id" value="<?php echo $asignacion_id; ?>">
                                        <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($horarios)): ?>
                            <tr>
                                <td colspan="4" class="py-4 text-center text-gray-500">No hay horarios asignados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>