<?php
session_start();
if (
    !isset($_SESSION['usuario']) ||
    !is_array($_SESSION['usuario']) ||
    (int)$_SESSION['usuario']['rol'] !== 4
) {
    // Si no cumple las condiciones, redirige al login con un error de rol
    header("Location: /login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

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
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .sidebar-item {
            min-height: 3.5rem;
            width: 100%;
        }

        .w-16 .sidebar-item {
            justify-content: center !important;
        }

        .w-16 .sidebar-item span.sidebar-label {
            display: none;
        }

        .w-16 .sidebar-item span.text-xl {
            margin: 0 auto;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex">
    <button id="toggleSidebar" class="absolute top-4 left-4 z-50 text-2xl hover:text-indigo-600 transition">☰</button>
    <!-- Sidebar -->
    <nav id="sidebar" class="w-60 transition-all duration-300 bg-white shadow-lg px-4 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400 h-28">
            <img src="/images/et20ico.ico" class="sidebar-expanded block h-full w-auto object-contain">
            <img src="/images/et20ico.ico" class="sidebar-collapsed hidden h-10 w-auto object-contain">
        </div>
        <a href="alumno.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Inicio">
            <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
        </a>
        <a href="asistencias.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Asistencias">
            <span class="text-xl">📆</span><span class="sidebar-label">Asistencias</span>
        </a>
        <a href="notas.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Notas">
            <span class="text-xl">📝</span><span class="sidebar-label">Notas</span>
        </a>
        <button onclick="window.location='/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
    </nav>

    <!-- Contenido principal -->
    <main class="flex-1 p-10">
        <!-- BLOQUE DE USUARIO, ROL Y SALIR A LA DERECHA -->
        <div class="w-full flex justify-end mb-6">
            <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-2 shadow border">
                <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-12 h-12 object-cover">
                <div class="flex flex-col pr-2 text-right">
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['nombre']; ?></div>
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['apellido']; ?></div>
                    <div class="mt-1 text-xs text-gray-500">Alumno/a</div>
                </div>
                <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
                    <form method="post" action="/includes/cambiar_rol.php" class="ml-4">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <select name="rol" onchange="this.form.submit()" class="px-2 py-1 border text-sm rounded-xl text-gray-700 bg-white">
                            <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                                <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                                    Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
            </div>
        </div>
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
    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const labels = sidebar.querySelectorAll('.sidebar-label');
            const expandedElements = sidebar.querySelectorAll('.sidebar-expanded');
            const collapsedElements = sidebar.querySelectorAll('.sidebar-collapsed');

            if (sidebar.classList.contains('w-60')) {
                sidebar.classList.remove('w-60');
                sidebar.classList.add('w-16');
                labels.forEach(label => label.classList.add('hidden'));
                expandedElements.forEach(el => el.classList.add('hidden'));
                collapsedElements.forEach(el => el.classList.remove('hidden'));
            } else {
                sidebar.classList.remove('w-16');
                sidebar.classList.add('w-60');
                labels.forEach(label => label.classList.remove('hidden'));
                expandedElements.forEach(el => el.classList.remove('hidden'));
                collapsedElements.forEach(el => el.classList.add('hidden'));
            }
        });
    </script>
</body>

</html>