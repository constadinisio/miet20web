<?php
session_start();
if (
  !isset($_SESSION['usuario']) ||
  !is_array($_SESSION['usuario']) ||
  (int)$_SESSION['usuario']['rol'] !== 3
) {
  header("Location: ../login.php?error=rol");
  exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

$profesor_id = $usuario['id'];
// Buscar cursos y materias asignadas
$cursos = [];
$sql = "SELECT pcm.id, c.id AS curso_id, m.id AS materia_id, c.anio, c.division, m.nombre AS materia
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

$curso_id = $_GET['curso_id'] ?? null;
$materia_id = $_GET['materia_id'] ?? null;

// --- ALTA DE NUEVO TEMA ---
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_tema'])) {
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $contenido = trim($_POST['contenido'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $curso_id = $_POST['curso_id'];
    $materia_id = $_POST['materia_id'];

    if ($contenido && $curso_id && $materia_id) {
        // 1. Buscar o crear el libro de temas para ese curso, materia y profesor
        $libro_id = null;
        $sql_libro = "SELECT id FROM libros_temas WHERE curso_id=? AND materia_id=? AND profesor_id=?";
        $stmt_libro = $conexion->prepare($sql_libro);
        $stmt_libro->bind_param("iii", $curso_id, $materia_id, $profesor_id);
        $stmt_libro->execute();
        $stmt_libro->bind_result($libro_id_res);
        if ($stmt_libro->fetch()) {
            $libro_id = $libro_id_res;
        }
        $stmt_libro->close();
        if (!$libro_id) {
            $sql_new = "INSERT INTO libros_temas (curso_id, materia_id, profesor_id, anio_lectivo, estado) VALUES (?, ?, ?, YEAR(CURDATE()), 'activo')";
            $stmt_new = $conexion->prepare($sql_new);
            $stmt_new->bind_param("iii", $curso_id, $materia_id, $profesor_id);
            $stmt_new->execute();
            $libro_id = $conexion->insert_id;
            $stmt_new->close();
        }
        // 2. Insertar nuevo contenido
        $sql_insert = "INSERT INTO contenidos_libro (libro_id, fecha, contenido, observaciones, fecha_creacion) VALUES (?, ?, ?, ?, NOW())";
        $stmt_insert = $conexion->prepare($sql_insert);
        $stmt_insert->bind_param("isss", $libro_id, $fecha, $contenido, $observaciones);
        if ($stmt_insert->execute()) {
            $mensaje = '<div class="bg-green-100 text-green-700 rounded-xl p-3 mb-4">Tema guardado correctamente.</div>';
        } else {
            $mensaje = '<div class="bg-red-100 text-red-700 rounded-xl p-3 mb-4">Error al guardar el tema.</div>';
        }
        $stmt_insert->close();
    } else {
        $mensaje = '<div class="bg-yellow-100 text-yellow-800 rounded-xl p-3 mb-4">Completá el contenido del tema.</div>';
    }
}

// Mostrar los temas
$temas = [];
if ($curso_id && $materia_id) {
    $sql2 = "SELECT cl.fecha, cl.contenido, cl.observaciones
             FROM libros_temas lt
             JOIN contenidos_libro cl ON lt.id = cl.libro_id
             WHERE lt.curso_id = ? AND lt.materia_id = ? AND lt.profesor_id = ?
             ORDER BY cl.fecha DESC";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("iii", $curso_id, $materia_id, $profesor_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row2 = $result2->fetch_assoc()) {
        $temas[] = $row2;
    }
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libro de Temas</title>
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
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name='.$usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div>
                <div class="font-bold text-lg"><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></div>
                <div class="text-xs text-gray-500">Profesor</div>
            </div>
        </div>
        <a href="profesor.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="libro_temas.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📚 Libro de Temas</a>
        <a href="calificaciones.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📝 Calificaciones</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">📚 Libro de Temas</h1>
        <form class="mb-8 flex gap-4" method="get">
            <select name="curso_id" class="px-4 py-2 rounded-xl border" required>
                <option value="">Seleccionar curso</option>
                <?php foreach($cursos as $c): ?>
                    <option value="<?php echo $c['curso_id']; ?>" <?php if($curso_id==$c['curso_id']) echo "selected"; ?>>
                        <?php echo $c['anio']."°".$c['division']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="materia_id" class="px-4 py-2 rounded-xl border" required>
                <option value="">Seleccionar materia</option>
                <?php foreach($cursos as $c): ?>
                    <?php if($curso_id==$c['curso_id']): ?>
                        <option value="<?php echo $c['materia_id']; ?>" <?php if($materia_id==$c['materia_id']) echo "selected"; ?>>
                            <?php echo $c['materia']; ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Ver</button>
        </form>

        <?php echo $mensaje; ?>

        <?php if ($curso_id && $materia_id): ?>
        <!-- Formulario para nuevo tema -->
        <form method="post" class="bg-white rounded-xl shadow p-6 mb-6 flex flex-col gap-3">
            <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
            <input type="hidden" name="materia_id" value="<?php echo $materia_id; ?>">
            <input type="hidden" name="nuevo_tema" value="1">
            <div>
                <label class="font-semibold">Fecha:</label>
                <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" class="px-4 py-2 border rounded-xl" required>
            </div>
            <div>
                <label class="font-semibold">Contenido del tema:</label>
                <textarea name="contenido" rows="2" class="w-full px-4 py-2 border rounded-xl" required></textarea>
            </div>
            <div>
                <label class="font-semibold">Observaciones:</label>
                <input name="observaciones" type="text" class="w-full px-4 py-2 border rounded-xl" placeholder="Opcional">
            </div>
            <button type="submit" class="mt-2 px-6 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700 font-bold">
                + Agregar tema
            </button>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-xl shadow">
                <thead>
                    <tr>
                        <th class="py-2 px-4 text-left">Fecha</th>
                        <th class="py-2 px-4 text-left">Contenido</th>
                        <th class="py-2 px-4 text-left">Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($temas as $t): ?>
                    <tr>
                        <td class="py-2 px-4"><?php echo date("d/m/Y", strtotime($t['fecha'])); ?></td>
                        <td class="py-2 px-4"><?php echo $t['contenido']; ?></td>
                        <td class="py-2 px-4"><?php echo $t['observaciones']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($temas)): ?>
                    <tr><td colspan="3" class="py-4 text-center text-gray-500">No hay temas cargados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-gray-500">Seleccioná un curso y una materia para ver el libro de temas.</div>
        <?php endif; ?>
    </main>
</body>
</html>