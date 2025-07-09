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
require_once '../../includes/db.php'; // Asegúrate de tener tu conexión

$alumno_id = $usuario['id'];
$asistencias = [];

$sql = "SELECT ag.fecha, ag.estado, c.anio, c.division
        FROM asistencia_general ag
        JOIN cursos c ON ag.curso_id = c.id
        WHERE ag.alumno_id = ?
        ORDER BY ag.fecha DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $asistencias[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mis Asistencias | Mi ET20</title>
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
        <a href="asistencias.php" class="py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100">📆 Asistencias</a>
        <a href="notas.php" class="py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100">📝 Notas</a>
        <button onclick="window.location='../../includes/logout.php'" class="mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">Salir</button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">📆 Mis Asistencias</h1>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-xl shadow">
                <thead>
                    <tr>
                        <th class="py-2 px-4 text-left">Fecha</th>
                        <th class="py-2 px-4 text-left">Curso</th>
                        <th class="py-2 px-4 text-left">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asistencias as $a): ?>
                        <tr>
                            <td class="py-2 px-4"><?php echo date("d/m/Y", strtotime($a['fecha'])); ?></td>
                            <td class="py-2 px-4"><?php echo $a['anio'] . "°" . $a['division']; ?></td>
                            <td class="py-2 px-4 font-semibold <?php
                                                                if ($a['estado'] == 'P') echo 'text-green-600';
                                                                elseif ($a['estado'] == 'A') echo 'text-red-600';
                                                                elseif ($a['estado'] == 'T') echo 'text-yellow-600';
                                                                else echo 'text-gray-600';
                                                                ?>">
                                <?php
                                if ($a['estado'] == 'P') echo 'Presente';
                                elseif ($a['estado'] == 'A') echo 'Ausente';
                                elseif ($a['estado'] == 'T') echo 'Tarde';
                                else echo $a['estado'];
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($asistencias)): ?>
                        <tr>
                            <td colspan="3" class="py-4 text-center text-gray-500">No hay asistencias registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>