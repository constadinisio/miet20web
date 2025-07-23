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

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : null;
$periodo = $_GET['periodo'] ?? '';

// Buscar notas (por bimestre/cuatrimestre o todos)
$calificaciones = [];
if ($curso_id) {
    $wherePeriodo = '';
    $paramTipos = "i"; // curso_id
    $params = [$curso_id];

    if ($periodo == '1er Cuatrimestre') {
        $wherePeriodo = " AND (n.periodo = '1er Bimestre' OR n.periodo = '2do Bimestre')";
    } elseif ($periodo == '2do Cuatrimestre') {
        $wherePeriodo = " AND (n.periodo = '3er Bimestre' OR n.periodo = '4to Bimestre')";
    } elseif ($periodo && !in_array($periodo, ['1er Cuatrimestre', '2do Cuatrimestre'])) {
        $wherePeriodo = " AND n.periodo = ?";
        $paramTipos .= "s";
        $params[] = $periodo;
    }

    $sql2 = "SELECT u.id, u.nombre, u.apellido, m.nombre AS materia, n.nota, n.fecha_carga, n.periodo
            FROM alumno_curso ac
            JOIN usuarios u ON ac.alumno_id = u.id
            JOIN notas_bimestrales n ON n.alumno_id = u.id
            JOIN materias m ON n.materia_id = m.id
            WHERE ac.curso_id = ? $wherePeriodo
            ORDER BY u.apellido, u.nombre, m.nombre, n.periodo";
    $stmt2 = $conexion->prepare($sql2);

    // Bind dinámico
    if ($periodo && !in_array($periodo, ['1er Cuatrimestre', '2do Cuatrimestre'])) {
        $stmt2->bind_param($paramTipos, ...$params);
    } else {
        $stmt2->bind_param($paramTipos, $curso_id);
    }
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $calificaciones[] = $row;
    }
    $stmt2->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Calificaciones | Preceptor</title>
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
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
        <a href="calificaciones.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-indigo-100 transition" title="Notas">
            <span class="text-xl">📝</span><span class="sidebar-label">Calificaciones</span>
        </a>
        <a href="boletines.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Notas">
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
        <h1 class="text-2xl font-bold mb-6">📝 Calificaciones</h1>
        <form class="mb-8 flex gap-4" method="get">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <select name="curso_id" class="px-4 py-2 rounded-xl border" required>
                <option value="">Seleccionar curso</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php if ($curso_id == $c['id']) echo "selected"; ?>>
                        <?php echo $c['anio'] . "°" . $c['division']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="periodo" class="px-4 py-2 rounded-xl border">
                <option value="">Todos los bimestres/cuatrimestres</option>
                <option value="1er Bimestre" <?= $periodo == '1er Bimestre' ? 'selected' : '' ?>>1º Bimestre</option>
                <option value="2do Bimestre" <?= $periodo == '2do Bimestre' ? 'selected' : '' ?>>2º Bimestre</option>
                <option value="1er Cuatrimestre" <?= $periodo == '1er Cuatrimestre' ? 'selected' : '' ?>>1º Cuatrimestre</option>
                <option value="3er Bimestre" <?= $periodo == '3er Bimestre' ? 'selected' : '' ?>>3º Bimestre</option>
                <option value="4to Bimestre" <?= $periodo == '4to Bimestre' ? 'selected' : '' ?>>4º Bimestre</option>
                <option value="2do Cuatrimestre" <?= $periodo == '2do Cuatrimestre' ? 'selected' : '' ?>>2º Cuatrimestre</option>
            </select>
            <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Ver</button>
        </form>
        <?php if ($curso_id): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded-xl shadow">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 text-left">Alumno</th>
                            <th class="py-2 px-4 text-left">Materia</th>
                            <th class="py-2 px-4 text-left">Bimestre</th>
                            <th class="py-2 px-4 text-left">Cuatrimestre</th>
                            <th class="py-2 px-4 text-left">Nota</th>
                            <th class="py-2 px-4 text-left">Desempeño</th>
                            <th class="py-2 px-4 text-left">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calificaciones as $cal): ?>
                            <tr>
                                <td class="py-2 px-4"><?php echo $cal['apellido'] . " " . $cal['nombre']; ?></td>
                                <td class="py-2 px-4"><?php echo $cal['materia']; ?></td>
                                <td class="py-2 px-4"><?php echo $cal['periodo']; ?></td>
                                <td class="py-2 px-4">
                                    <?php
                                    if (in_array($cal['periodo'], ['1er Bimestre', '2do Bimestre'])) echo '1º Cuatrimestre';
                                    elseif (in_array($cal['periodo'], ['3er Bimestre', '4to Bimestre'])) echo '2º Cuatrimestre';
                                    else echo '-';
                                    ?>
                                </td>
                                <td class="py-2 px-4 font-semibold"><?php echo $cal['nota']; ?></td>
                                <td class="py-2 px-4">
                                    <?php
                                    $nota = (float)$cal['nota'];
                                    if ($nota >= 1 && $nota < 6) {
                                        echo '<span class="text-red-600 font-bold">En Proceso</span>';
                                    } elseif ($nota >= 6 && $nota < 8) {
                                        echo '<span class="text-yellow-700 font-bold">Suficiente</span>';
                                    } elseif ($nota >= 8 && $nota <= 10) {
                                        echo '<span class="text-green-700 font-bold">Avanzado</span>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td class="py-2 px-4"><?php echo date("d/m/Y", strtotime($cal['fecha_carga'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($calificaciones)): ?>
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500">No hay calificaciones cargadas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-gray-500">Seleccioná un curso para ver las calificaciones.</div>
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