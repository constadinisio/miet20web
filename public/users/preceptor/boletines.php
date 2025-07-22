<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 2) {
    header("Location: /login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// Todos los cursos del sistema
$cursos = [];
$sql = "SELECT id, anio, division FROM cursos ORDER BY anio, division";
$result = $conexion->query($sql);
while ($row = $result->fetch_assoc()) {
    $cursos[] = $row;
}

$curso_id = $_GET['curso_id'] ?? null;
$alumnos = [];
if ($curso_id) {
    $sql = "SELECT u.id, u.nombre, u.apellido FROM alumno_curso ac JOIN usuarios u ON ac.alumno_id = u.id WHERE ac.curso_id = ? ORDER BY u.apellido, u.nombre";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $alumnos[] = $row;
    }
    $stmt->close();
}

$alumno_id = $_GET['alumno_id'] ?? null;
$boletines = [];
if ($curso_id && $alumno_id) {
    $sql = "SELECT * FROM boletin WHERE curso_id = ? AND alumno_id = ? ORDER BY anio_lectivo DESC, periodo DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $curso_id, $alumno_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $boletines[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Boletines | Preceptor</title>
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
    <button id="toggleSidebar" class="absolute top-4 left-4 z-50 text-2xl hover:text-indigo-600 transition">
        ☰
    </button>
    <nav id="sidebar" class="w-60 transition-all duration-300 bg-white shadow-lg px-4 py-4 flex flex-col gap-2">
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400 h-28">
            <img src="/images/et20ico.ico" class="sidebar-expanded block h-full w-auto object-contain">
            <img src="/images/et20ico.ico" class="sidebar-collapsed hidden h-10 w-auto object-contain">
        </div>
        <div class="flex items-center mb-10 gap-2">
            <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-14 h-14">
            <div class="flex flex-col pl-3 sidebar-label">
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['nombre']; ?></div>
                <div class="font-bold text-lg leading-tight"><?php echo $usuario['apellido']; ?></div>
                <div class="mt-2 text-xs text-gray-500">Alumno/a</div>
            </div>
        </div>
        <a href="preceptor.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-gray-200 transition" title="Inicio">
            <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
        </a>
        <a href="asistencias.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Asistencias">
            <span class="text-xl">📆</span><span class="sidebar-label">Asistencias</span>
        </a>
        <a href="calificaciones.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Notas">
            <span class="text-xl">📝</span><span class="sidebar-label">Calificaciones</span>
        </a>
        <a href="boletines.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100 transition" title="Notas">
            <span class="text-xl">📑</span><span class="sidebar-label">Boletines</span>
        </a>
        <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
            <form method="post" action="/includes/cambiar_rol.php" class="mt-auto mb-3">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <select name="rol" onchange="this.form.submit()" class="w-full px-3 py-2 border text-sm rounded-xl text-gray-700 bg-white sidebar-label">
                    <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                            Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <button onclick="window.location='/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
    </nav>
    <main class="flex-1 p-10">
        <h1 class="text-2xl font-bold mb-6">📑 Boletines</h1>
        <!-- Selección de curso -->
        <form class="mb-4 flex gap-4" method="get">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <select name="curso_id" class="px-4 py-2 rounded-xl border" required onchange="this.form.submit()">
                <option value="">Seleccionar curso</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php if ($curso_id == $c['id']) echo "selected"; ?>>
                        <?php echo $c['anio'] . "°" . $c['division']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($curso_id): ?>
                <select name="alumno_id" class="px-4 py-2 rounded-xl border" required onchange="this.form.submit()">
                    <option value="">Seleccionar alumno</option>
                    <?php foreach ($alumnos as $a): ?>
                        <option value="<?php echo $a['id']; ?>" <?php if ($alumno_id == $a['id']) echo "selected"; ?>>
                            <?php echo $a['apellido'] . " " . $a['nombre']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </form>
        <?php if ($curso_id && $alumno_id): ?>
            <div class="mb-4">
                <a href="editar_boletin.php?curso_id=<?php echo $curso_id; ?>&alumno_id=<?php echo $alumno_id; ?>&nuevo=1" class="bg-green-600 text-white px-4 py-2 rounded-xl hover:bg-green-700">+ Nuevo boletín</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-xl shadow">
                    <thead>
                        <tr>
                            <th class="py-2 px-4">Año lectivo</th>
                            <th class="py-2 px-4">Periodo</th>
                            <th class="py-2 px-4">Estado</th>
                            <th class="py-2 px-4">Emisión</th>
                            <th class="py-2 px-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($boletines as $b): ?>
                            <tr>
                                <td class="py-2 px-4"><?php echo $b['anio_lectivo']; ?></td>
                                <td class="py-2 px-4"><?php echo $b['periodo']; ?></td>
                                <td class="py-2 px-4"><?php echo ucfirst($b['estado']); ?></td>
                                <td class="py-2 px-4"><?php echo $b['fecha_emision'] ? date('d/m/Y', strtotime($b['fecha_emision'])) : "-"; ?></td>
                                <td class="py-2 px-4 flex gap-2">
                                    <a href="editar_boletin.php?curso_id=<?php echo $curso_id; ?>&alumno_id=<?php echo $alumno_id; ?>&boletin_id=<?php echo $b['id']; ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Ver/Editar</a>
                                    <a href="exportar_boletin.php?id=<?php echo $b['id']; ?>" target="_blank" class="bg-gray-700 text-white px-3 py-1 rounded hover:bg-gray-900">PDF</a>
                                    <?php if ($b['estado'] == 'borrador'): ?>
                                        <a href="publicar_boletin.php?id=<?php echo $b['id']; ?>" class="bg-green-700 text-white px-3 py-1 rounded hover:bg-green-800">Publicar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($boletines)): ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">No hay boletines para este alumno.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($curso_id): ?>
            <div class="text-gray-500">Seleccioná un alumno para ver o crear boletines.</div>
        <?php endif; ?>
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