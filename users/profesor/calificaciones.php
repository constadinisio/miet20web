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

// Selección de curso/materia
$curso_id = $_GET['curso_id'] ?? null;
$materia_id = $_GET['materia_id'] ?? null;

// Buscar alumnos del curso
$alumnos = [];
if ($curso_id) {
    $sql2 = "SELECT u.id, u.nombre, u.apellido
             FROM alumno_curso ac
             JOIN usuarios u ON ac.alumno_id = u.id
             WHERE ac.curso_id = ?
             ORDER BY u.apellido, u.nombre";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("i", $curso_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $alumnos[] = $row;
    }
    $stmt2->close();
}

// Listar notas ya cargadas para ese curso y materia
$notas = [];
if ($curso_id && $materia_id) {
    $sql3 = "SELECT n.id, n.alumno_id, u.nombre, u.apellido, n.nota, n.fecha_carga
             FROM notas n
             JOIN usuarios u ON n.alumno_id = u.id
             WHERE n.materia_id = ? AND n.alumno_id IN 
                (SELECT alumno_id FROM alumno_curso WHERE curso_id = ?)
             ORDER BY u.apellido, u.nombre, n.fecha_carga DESC";
    $stmt3 = $conexion->prepare($sql3);
    $stmt3->bind_param("ii", $materia_id, $curso_id);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    while ($row = $result3->fetch_assoc()) {
        $notas[] = $row;
    }
    $stmt3->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar Calificaciones</title>
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
                <div class="text-xs text-gray-500">Alumno</div>
            </div>
        </div>
        <a href="profesor.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="libro_temas.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📚 Libro de Temas</a>
        <a href="calificaciones.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📝 Calificaciones</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">📝 Cargar Calificaciones</h1>
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
        <?php if ($curso_id && $materia_id): ?>
            <!-- Formulario de carga de notas (a completar según tus reglas) -->
            <div class="mb-8">
                <a href="profesor_cargar_nota.php?curso_id=<?php echo $curso_id; ?>&materia_id=<?php echo $materia_id; ?>" class="px-4 py-2 bg-green-600 text-white rounded-xl">Cargar nueva nota</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-xl shadow">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 text-left">Alumno</th>
                            <th class="py-2 px-4 text-left">Nota</th>
                            <th class="py-2 px-4 text-left">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($notas as $n): ?>
                        <tr>
                            <td class="py-2 px-4"><?php echo $n['apellido']." ".$n['nombre']; ?></td>
                            <td class="py-2 px-4 font-semibold"><?php echo $n['nota']; ?></td>
                            <td class="py-2 px-4"><?php echo date("d/m/Y", strtotime($n['fecha_carga'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($notas)): ?>
                        <tr><td colspan="3" class="py-4 text-center text-gray-500">No hay notas cargadas aún.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
        <div class="text-gray-500">Seleccioná un curso y una materia para ver y cargar calificaciones.</div>
        <?php endif; ?>
    </main>
</body>
</html>
