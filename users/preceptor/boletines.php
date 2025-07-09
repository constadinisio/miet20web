<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) {
    header("Location: ../login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once '../../includes/db.php';

// Cursos asignados
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

$curso_id = $_GET['curso_id'] ?? null;
$boletines = [];
if ($curso_id) {
    $sql2 = "SELECT b.id, u.nombre, u.apellido, b.anio_lectivo, b.periodo, b.estado, b.fecha_emision
            FROM boletin b
            JOIN usuarios u ON b.alumno_id = u.id
            WHERE b.curso_id = ?
            ORDER BY u.apellido, u.nombre, b.periodo DESC";
    $stmt2 = $conexion->prepare($sql2);
    $stmt2->bind_param("i", $curso_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $boletines[] = $row;
    }
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Boletines | Preceptor</title>
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
                <div class="mt-2 text-xs text-gray-500">Preceptor/a</div>
            </div>
        </div>
        <a href="preceptor.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="asistencias.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📆 Asistencias</a>
        <a href="calificaciones.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📝 Calificaciones</a>
        <a href="boletines.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📑 Boletines</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">📑 Boletines</h1>
        <form class="mb-8 flex gap-4" method="get">
            <select name="curso_id" class="px-4 py-2 rounded-xl border" required>
                <option value="">Seleccionar curso</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php if ($curso_id == $c['id']) echo "selected"; ?>>
                        <?php echo $c['anio'] . "°" . $c['division']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Ver</button>
        </form>
        <?php if ($curso_id): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-xl shadow">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 text-left">Alumno</th>
                            <th class="py-2 px-4 text-left">Año Lectivo</th>
                            <th class="py-2 px-4 text-left">Periodo</th>
                            <th class="py-2 px-4 text-left">Estado</th>
                            <th class="py-2 px-4 text-left">Emisión</th>
                            <th class="py-2 px-4 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($boletines as $b): ?>
                            <tr>
                                <td class="py-2 px-4"><?php echo $b['apellido'] . " " . $b['nombre']; ?></td>
                                <td class="py-2 px-4"><?php echo $b['anio_lectivo']; ?></td>
                                <td class="py-2 px-4"><?php echo $b['periodo']; ?></td>
                                <td class="py-2 px-4"><?php echo ucfirst($b['estado']); ?></td>
                                <td class="py-2 px-4"><?php echo date("d/m/Y", strtotime($b['fecha_emision'])); ?></td>
                                <td class="py-2 px-4">
                                    <a href="preceptor_boletin_ver.php?id=<?php echo $b['id']; ?>" class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Ver</a>
                                    <a href="preceptor_boletin_pdf.php?id=<?php echo $b['id']; ?>" class="px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700" target="_blank">PDF</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($boletines)): ?>
                            <tr>
                                <td colspan="6" class="py-4 text-center text-gray-500">No hay boletines cargados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-gray-500">Seleccioná un curso para ver los boletines.</div>
        <?php endif; ?>
    </main>
</body>

</html>