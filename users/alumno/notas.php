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
require_once '../../includes/db.php';

$alumno_id = $usuario['id'];
$notas = [];
$notas_bimestrales = [];

// Notas por trabajo práctico/examen
$sql = "SELECT m.nombre AS materia, t.nombre AS trabajo, n.nota, n.fecha_carga
        FROM notas n
        JOIN materias m ON n.materia_id = m.id
        JOIN trabajos t ON n.trabajo_id = t.id
        WHERE n.alumno_id = ?
        ORDER BY m.nombre, n.fecha_carga DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notas[] = $row;
}
$stmt->close();

// Notas bimestrales
$sql2 = "SELECT m.nombre AS materia, nb.periodo, nb.nota, nb.promedio_actividades, nb.fecha_carga
         FROM notas_bimestrales nb
         JOIN materias m ON nb.materia_id = m.id
         WHERE nb.alumno_id = ?
         ORDER BY m.nombre, nb.periodo";
$stmt2 = $conexion->prepare($sql2);
$stmt2->bind_param("i", $alumno_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) {
    $notas_bimestrales[] = $row;
}
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Notas | Mi ET20</title>
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
        <div class="flex items-center gap-3 mb-10">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div>
                <div class="font-bold text-lg"><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></div>
                <div class="text-xs text-gray-500">Alumno</div>
            </div>
        </div>
        <a href="alumno.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition">🏠 Inicio</a>
        <a href="asistencias.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📆 Asistencias</a>
        <a href="notas.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📝 Notas</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">📝 Mis Notas</h1>
        <!-- Notas por trabajo/examen -->
        <h2 class="text-lg font-semibold mb-2">Trabajos y Exámenes</h2>
        <div class="overflow-x-auto mb-8">
            <table class="min-w-full bg-white rounded-xl shadow">
                <thead>
                    <tr>
                        <th class="py-2 px-4 text-left">Materia</th>
                        <th class="py-2 px-4 text-left">Trabajo/Examen</th>
                        <th class="py-2 px-4 text-left">Nota</th>
                        <th class="py-2 px-4 text-left">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notas as $n): ?>
                        <tr>
                            <td class="py-2 px-4"><?php echo $n['materia']; ?></td>
                            <td class="py-2 px-4"><?php echo $n['trabajo']; ?></td>
                            <td class="py-2 px-4 font-semibold"><?php echo $n['nota']; ?></td>
                            <td class="py-2 px-4"><?php echo date("d/m/Y", strtotime($n['fecha_carga'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($notas)): ?>
                        <tr>
                            <td colspan="4" class="py-4 text-center text-gray-500">No hay notas registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Notas bimestrales/promedios -->
        <h2 class="text-lg font-semibold mb-2">Notas Bimestrales / Promedios</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-xl shadow">
                <thead>
                    <tr>
                        <th class="py-2 px-4 text-left">Materia</th>
                        <th class="py-2 px-4 text-left">Periodo</th>
                        <th class="py-2 px-4 text-left">Nota</th>
                        <th class="py-2 px-4 text-left">Prom. Actividades</th>
                        <th class="py-2 px-4 text-left">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notas_bimestrales as $nb): ?>
                        <tr>
                            <td class="py-2 px-4"><?php echo $nb['materia']; ?></td>
                            <td class="py-2 px-4"><?php echo $nb['periodo']; ?></td>
                            <td class="py-2 px-4 font-semibold"><?php echo $nb['nota']; ?></td>
                            <td class="py-2 px-4"><?php echo $nb['promedio_actividades']; ?></td>
                            <td class="py-2 px-4"><?php echo date("d/m/Y", strtotime($nb['fecha_carga'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($notas_bimestrales)): ?>
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">No hay notas bimestrales registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>