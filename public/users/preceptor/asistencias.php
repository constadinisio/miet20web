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
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$modo = $_GET['modo'] ?? 'ver';

// Traer alumnos y asistencias (turno y contraturno)
$alumnos = [];
if ($curso_id) {
    // Listar alumnos del curso
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
        $alumnos[$row['id']] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'apellido' => $row['apellido'],
            'turno' => '-',         // Por defecto
            'contraturno' => '-',   // Por defecto
        ];
    }
    $stmt2->close();

    // Traer asistencias de ese día (para turno y contraturno)
    $sql3 = "SELECT alumno_id, estado, es_contraturno
             FROM asistencia_general
             WHERE curso_id = ? AND fecha = ?";
    $stmt3 = $conexion->prepare($sql3);
    $stmt3->bind_param("is", $curso_id, $fecha);
    $stmt3->execute();
    $result3 = $stmt3->get_result();
    while ($row = $result3->fetch_assoc()) {
        if (isset($alumnos[$row['alumno_id']])) {
            if ($row['es_contraturno']) {
                $alumnos[$row['alumno_id']]['contraturno'] = $row['estado'];
            } else {
                $alumnos[$row['alumno_id']]['turno'] = $row['estado'];
            }
        }
    }
    $stmt3->close();
}

$dias_es = [
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sábado',
    'Sunday' => 'Domingo',
];

// Guardar cambios (solo si está en modo edición)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asistencias']) && isset($_POST['curso_id']) && isset($_POST['dias_semana'])) {
    $curso_id = $_POST['curso_id'];
    $preceptor_id = $usuario['id'];
    $dias_semana = $_POST['dias_semana']; // Array de fechas (lunes a viernes)
    $semana_lunes = $_POST['semana_lunes'] ?? '';

    foreach ($_POST['asistencias'] as $alumno_id => $asist_dias) {
        foreach ($dias_semana as $fecha) {
            foreach (['turno' => 0, 'contraturno' => 1] as $tipo => $es_contraturno) {
                $estado = $asist_dias[$fecha][$tipo] ?? '';
                if (!$estado) continue;

                // Buscar si ya existe
                $sql_check = "SELECT id FROM asistencia_general WHERE alumno_id=? AND curso_id=? AND fecha=? AND es_contraturno=?";
                $stmt_check = $conexion->prepare($sql_check);
                if (!$stmt_check) die("Error: " . $conexion->error);
                $stmt_check->bind_param("iisi", $alumno_id, $curso_id, $fecha, $es_contraturno);
                $stmt_check->execute();
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    $stmt_check->bind_result($asist_id);
                    $stmt_check->fetch();
                    $sql_upd = "UPDATE asistencia_general SET estado=? WHERE id=?";
                    $stmt_upd = $conexion->prepare($sql_upd);
                    if (!$stmt_upd) die("Error: " . $conexion->error);
                    $stmt_upd->bind_param("si", $estado, $asist_id);
                    $stmt_upd->execute();
                    $stmt_upd->close();
                } else {
                    $sql_ins = "INSERT INTO asistencia_general (alumno_id, curso_id, fecha, estado, creado_por, es_contraturno)
            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_ins = $conexion->prepare($sql_ins);
                    if (!$stmt_ins) die("Error: " . $conexion->error);
                    $stmt_ins->bind_param("iissii", $alumno_id, $curso_id, $fecha, $estado, $preceptor_id, $es_contraturno);
                    if (!$stmt_ins->execute()) {
                        die("❌ Error al ejecutar: " . $stmt_ins->error);
                    }
                    $stmt_ins->close();
                }
                $stmt_check->close();
            }
        }
    }
    header("Location: asistencias.php?curso_id=$curso_id&semana_lunes=$semana_lunes&modo=editar&msg=ok");
    exit;
}
$msg = $_GET['msg'] ?? '';

// Busca el rango total de fechas con asistencias cargadas para ese curso
$fechas = $conexion->query("SELECT MIN(fecha) AS inicio, MAX(fecha) AS fin FROM asistencia_general WHERE curso_id = " . (int)$curso_id)->fetch_assoc();
$fecha_inicio = $fechas['inicio'] ?? date('Y-m-d');
$fecha_fin = $fechas['fin'] ?? date('Y-m-d');

// Rango escolar típico (ajustá si tu ciclo empieza/termina en otra fecha)
$año_actual = date('Y');
$primer_lunes = new DateTime("$año_actual-03-05");
if ($primer_lunes->format('N') != 1) {
    $primer_lunes->modify('next monday');
}

// El último lunes será el mayor entre hoy (semana actual) y último lunes de diciembre
$hoy = new DateTime();
$lunes_hoy = clone $hoy;
if ($lunes_hoy->format('N') != 1) {
    $lunes_hoy->modify('monday this week');
}
$ultimo_lunes_calendario = new DateTime("$año_actual-12-19");
if ($ultimo_lunes_calendario->format('N') != 1) {
    $ultimo_lunes_calendario->modify('last monday');
}
$ultimo_lunes = ($lunes_hoy > $ultimo_lunes_calendario) ? $lunes_hoy : $ultimo_lunes_calendario;

// Genera todos los lunes del ciclo (de marzo hasta el último lunes definido)
$luneses = [];
$dt = clone $primer_lunes;
while ($dt <= $ultimo_lunes) {
    $luneses[] = $dt->format('Y-m-d');
    $dt->modify('+1 week');
}

// Semana seleccionada: por defecto, la última semana con datos o la actual
$semana_lunes = $_GET['semana_lunes'] ?? (end($luneses) ?: date('Y-m-d'));
if (!in_array($semana_lunes, $luneses)) $semana_lunes = reset($luneses);

// Genera los 5 días hábiles de la semana seleccionada
$dias_semana = [];
$dt = new DateTime($semana_lunes);
for ($i = 0; $i < 5; $i++) {
    $dias_semana[] = $dt->format('Y-m-d');
    $dt->modify('+1 day');
}

// Panel de Resumen de Asistencias del Día Actualizado
// Obtener la fecha actual (en el formato que usan las claves de asistencia)
$hoy_str = date('Y-m-d');

// Inicializar contadores
$conteo = ['P' => 0, 'A' => 0, 'AJ' => 0, 'T' => 0];

// Recorrer alumnos y contar asistencias de hoy (solo turno, no contraturno)
foreach ($alumnos as $al) {
    $estado = $asist_semana[$al['id']][$hoy_str]['turno'] ?? 'NC';
    if (in_array($estado, ['P', 'A', 'AJ', 'T'])) {
        $conteo[$estado]++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Asistencias | Preceptor</title>
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
        <a href="preceptor.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Inicio">
            <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
        </a>
        <a href="asistencias.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Asistencias">
            <span class="text-xl">📆</span><span class="sidebar-label">Asistencias</span>
        </a>
        <a href="calificaciones.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Calificaciones">
            <span class="text-xl">📝</span><span class="sidebar-label">Calificaciones</span>
        </a>
        <a href="boletines.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Boletines">
            <span class="text-xl">📑</span><span class="sidebar-label">Boletines</span>
        </a>
        <button onclick="window.location='/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
    </nav>

    <main class="flex-1 p-10">
        <!-- BLOQUE DE USUARIO, ROL Y SALIR A LA DERECHA -->
        <div class="w-full flex justify-end mb-6">
            <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-2 shadow border">
                <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-12 h-12 object-cover">
                <div class="flex flex-col pr-2 text-right">
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['nombre']; ?></div>
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['apellido']; ?></div>
                    <div class="mt-1 text-xs text-gray-500">Preceptor/a</div>
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
                <button id="btn-notificaciones" class="relative focus:outline-none group">
                    <!-- Campanita Font Awesome -->
                    <i id="icono-campana" class="fa-regular fa-bell text-2xl text-gray-400 group-hover:text-gray-700 transition-colors"></i>
                    <!-- Badge cantidad (oculto si no hay notificaciones) -->
                    <span id="badge-notificaciones"
                        class="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1 hidden border border-white font-bold"
                        style="min-width:1.2em; text-align:center;"></span>
                </button>
            </div>
        </div>

        <!-- POPUP DE NOTIFICACIONES -->
        <div id="popup-notificaciones" class="hidden fixed right-4 top-16 w-80 max-h-[70vh] bg-white shadow-2xl rounded-2xl border border-gray-200 z-50 flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <span class="font-bold text-gray-800 text-lg">Notificaciones</span>
                <button onclick="cerrarPopup()" class="text-gray-400 hover:text-red-400 text-xl">&times;</button>
            </div>
            <div id="lista-notificaciones" class="overflow-y-auto p-2">
                <!-- Notificaciones aquí -->
            </div>
        </div>

        <h1 class="text-2xl font-bold mb-6">📆 Gestión de Asistencias</h1>
        <?php if ($msg == 'ok'): ?>
            <div class="bg-green-100 text-green-700 rounded-xl p-3 mb-4">Asistencias guardadas correctamente.</div>
        <?php endif; ?>

        <!-- Selector de curso y semana -->
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

            <div class="flex items-center gap-3">
                <input type="date" id="selector-fecha" class="px-4 py-2 rounded-xl border" required>
                <input type="hidden" name="semana_lunes" id="input-semana-lunes" value="<?= $semana_lunes ?>">
                <div id="texto-rango" class="text-gray-600 font-semibold"></div>
            </div>

            <select name="modo" class="px-4 py-2 rounded-xl border">
                <option value="ver" <?php if ($modo == 'ver') echo 'selected'; ?>>Ver</option>
                <option value="editar" <?php if ($modo == 'editar') echo 'selected'; ?>>Editar</option>
            </select>
            <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white">Ver</button>
        </form>

        <?php if ($curso_id): ?>
            <?php
            // Trae asistencias de todos los días de la semana seleccionada
            $asist_semana = [];
            if ($curso_id && $dias_semana) {
                $in = "'" . implode("','", $dias_semana) . "'";
                $sql = "SELECT alumno_id, fecha, estado, es_contraturno FROM asistencia_general WHERE curso_id = $curso_id AND fecha IN ($in)";
                $res = $conexion->query($sql);
                while ($row = $res->fetch_assoc()) {
                    $asist_semana[$row['alumno_id']][$row['fecha']][$row['es_contraturno'] ? 'contraturno' : 'turno'] = $row['estado'];
                }
            }
            ?>

            <?php if ($modo == 'editar'): ?>
                <!-- EDICIÓN SEMANAL -->
                <form method="post" class="mt-4">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="curso_id" value="<?= $curso_id ?>">
                    <input type="hidden" name="semana_lunes" value="<?= $semana_lunes ?>">
                    <?php foreach ($dias_semana as $dia): ?>
                        <input type="hidden" name="dias_semana[]" value="<?= $dia ?>">
                    <?php endforeach; ?>
                    <div class="overflow-y-auto rounded-xl shadow bg-white" style="max-height: 600px;">
                        <table class="min-w-full bg-white rounded-xl shadow">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 text-left">#</th>
                                    <th class="py-2 px-4 text-left">Alumno</th>
                                    <?php foreach ($dias_semana as $dia): ?>
                                        <?php
                                        $dt = new DateTime($dia);
                                        $nombre_es = $dias_es[$dt->format('l')]; // Nombre en español
                                        ?>
                                        <th class="py-2 px-4 text-center" colspan="2"><?= $nombre_es . " " . $dt->format('d/m') ?></th>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                <tr class="bg-gray-50 text-sm text-center">
                                    <th colspan="2" class="py-2 px-4 font-medium text-left">Aplicar a todos:</th>
                                    <?php foreach ($dias_semana as $dia): ?>
                                        <?php foreach (['turno', 'contraturno'] as $tipo): ?>
                                            <th class="py-1 px-2">
                                                <select onchange="setAll('<?= $dia ?>', '<?= $tipo ?>', this.value)" class="border rounded px-2 py-1 text-sm">
                                                    <option value="NC">NC</option>
                                                    <option value="P">P</option>
                                                    <option value="A">A</option>
                                                    <option value="AJ">AJ</option>
                                                    <option value="T">T</option>
                                                </select>
                                            </th>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tr>
                                <?php foreach ($dias_semana as $dia): ?>
                                    <th class="py-1 px-2 text-center">Turno</th>
                                    <th class="py-1 px-2 text-center">Contra</th>
                                <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $contador = 1; ?>
                                <?php foreach ($alumnos as $a): ?>
                                    <tr>
                                        <td class="py-2 px-4 text-gray-500 font-mono"><?= $contador++ ?></td>
                                        <td class="py-2 px-4"><?= $a['apellido'] . " " . $a['nombre']; ?></td>
                                        <?php foreach ($dias_semana as $dia): ?>
                                            <?php foreach (['turno', 'contraturno'] as $tipo): ?>
                                                <td class="py-2 px-4 text-center">
                                                    <select name="asistencias[<?= $a['id'] ?>][<?= $dia ?>][<?= $tipo ?>]" data-dia="<?= $dia ?>" data-tipo="<?= $tipo ?>" class="border rounded px-2 py-1">
                                                        <option value="NC" <?= !in_array(($asist_semana[$a['id']][$dia][$tipo] ?? ''), ['P', 'A', 'T', 'AJ']) ? 'selected' : '' ?>>NC</option>
                                                        <option value="P" <?= ($asist_semana[$a['id']][$dia][$tipo] ?? '') == 'P' ? 'selected' : '' ?>>P</option>
                                                        <option value="A" <?= ($asist_semana[$a['id']][$dia][$tipo] ?? '') == 'A' ? 'selected' : '' ?>>A</option>
                                                        <option value="AJ" <?= ($asist_semana[$a['id']][$dia][$tipo] ?? '') == 'AJ' ? 'selected' : '' ?>>AJ</option>
                                                        <option value="T" <?= ($asist_semana[$a['id']][$dia][$tipo] ?? '') == 'T' ? 'selected' : '' ?>>T</option>
                                                    </select>
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($alumnos)): ?>
                                    <tr>
                                        <td colspan="<?= 2 + count($dias_semana) * 2 ?>" class="py-4 text-center text-gray-500">No hay alumnos cargados.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="mt-4 px-6 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700 font-bold">
                        Guardar asistencias
                    </button>
                </form>
            <?php else: ?>
                <!-- MODO VISUALIZACIÓN -->
                <div class="overflow-y-auto rounded-xl shadow bg-white" style="max-height: 600px;">
                    <table class="min-w-full bg-white rounded-xl shadow">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 text-left">#</th>
                                <th class="py-2 px-4 text-left">Alumno</th>
                                <?php foreach ($dias_semana as $dia): ?>
                                    <?php
                                    $dt = new DateTime($dia);
                                    $nombre_es = $dias_es[$dt->format('l')]; // Nombre en español
                                    ?>
                                    <th class="py-2 px-4 text-center" colspan="2"><?= $nombre_es . " " . $dt->format('d/m') ?></th>

                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <?php foreach ($dias_semana as $dia): ?>
                                    <th class="py-1 px-2 text-center">Turno</th>
                                    <th class="py-1 px-2 text-center">Contra</th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $contador = 1; ?>
                            <?php foreach ($alumnos as $a): ?>
                                <tr>
                                    <td class="py-2 px-4 text-gray-500 font-mono"><?= $contador++ ?></td>
                                    <td class="py-2 px-4"><?= $a['apellido'] . " " . $a['nombre']; ?></td>
                                    <?php foreach ($dias_semana as $dia): ?>
                                        <?php foreach (['turno', 'contraturno'] as $tipo): ?>
                                            <td class="py-2 px-4 text-center">
                                                <?php
                                                $est = $asist_semana[$a['id']][$dia][$tipo] ?? '-';
                                                if ($est == 'P') echo '<span class="text-green-700 font-bold">P</span>';
                                                elseif ($est == 'A') echo '<span class="text-red-700 font-bold">A</span>';
                                                elseif ($est == 'AJ') echo '<span class="text-green-900 font-bold">AJ</span>';
                                                elseif ($est == 'T') echo '<span class="text-yellow-700 font-bold">T</span>';
                                                elseif ($est == 'NC' || $est == '') echo '<span class="text-gray-700 font-bold">NC</span>';
                                                else echo '-';
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($alumnos)): ?>
                                <tr>
                                    <td colspan="<?= 2 + count($dias_semana) * 2 ?>" class="py-4 text-center text-gray-500">No hay alumnos cargados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <div class="mt-6 bg-white border rounded-xl p-4 shadow text-sm w-fit w-full">
                <h2 class="font-bold mb-2 text-lg">Resumen de hoy (<?= date('d/m/Y') ?>)</h2>
                <ul class="space-y-1">
                    <li><span class="font-medium text-green-700">✅ Presentes:</span> <?= $conteo['P'] ?></li>
                    <li><span class="font-medium text-red-700">❌ Ausentes:</span> <?= $conteo['A'] ?></li>
                    <li><span class="font-medium text-yellow-700">🕒 Tarde:</span> <?= $conteo['T'] ?></li>
                </ul>
            </div>
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
    <script>
        document.getElementById('btn-notificaciones').addEventListener('click', function() {
            const popup = document.getElementById('popup-notificaciones');
            popup.classList.toggle('hidden');
            cargarNotificaciones();
        });

        function cerrarPopup() {
            document.getElementById('popup-notificaciones').classList.add('hidden');
        }

        function marcarLeida(destinatarioId) {
            fetch('/../../../includes/notificaciones/marcar_leida.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(destinatarioId)
                }).then(res => res.json())
                .then(data => {
                    if (data.ok) cargarNotificaciones();
                });
        }

        function confirmar(destinatarioId) {
            fetch('/../../../includes/notificaciones/confirmar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'id=' + encodeURIComponent(destinatarioId)
                }).then(res => res.json())
                .then(data => {
                    if (data.ok) cargarNotificaciones();
                });
        }

        function cargarNotificaciones() {
            fetch('/../../../includes/notificaciones/listar.php')
                .then(res => res.json())
                .then(data => {
                    const lista = document.getElementById('lista-notificaciones');
                    const badge = document.getElementById('badge-notificaciones');
                    const campana = document.getElementById('icono-campana');
                    lista.innerHTML = '';
                    let sinLeer = 0;
                    if (data.length === 0) {
                        lista.innerHTML = '<div class="text-center text-gray-400 p-4">Sin notificaciones nuevas.</div>';
                        badge.classList.add('hidden');
                        // Ícono gris claro, sin detalles rojos
                        campana.classList.remove('text-red-500');
                        campana.classList.add('text-gray-400');
                        campana.classList.remove('fa-shake');
                    } else {
                        data.forEach(n => {
                            if (n.estado_lectura === 'NO_LEIDA') sinLeer++;
                            lista.innerHTML += `
                                <div class="rounded-xl px-3 py-2 mb-2 bg-gray-100 shadow hover:bg-gray-50 flex flex-col">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-base font-semibold">${n.titulo}</span>
                                    <span class="ml-auto text-xs">${n.fecha_creacion}</span>
                                </div>
                                <div class="text-sm text-gray-700 mb-2">${n.contenido}</div>
                                <div class="flex gap-2">
                                    ${n.estado_lectura === 'NO_LEIDA' ? `<button class="text-blue-600 text-xs" onclick="marcarLeida(${n.destinatario_row_id})">Marcar como leída</button>` : ''}
                                    ${(n.requiere_confirmacion == 1 && n.estado_lectura !== 'CONFIRMADA') ? `<button class="text-green-600 text-xs" onclick="confirmar(${n.destinatario_row_id})">Confirmar</button>` : ''}
                                    ${n.estado_lectura === 'LEIDA' ? '<span class="text-green-700 text-xs">Leída</span>' : ''}
                                    ${n.estado_lectura === 'CONFIRMADA' ? '<span class="text-green-700 text-xs">Confirmada</span>' : ''}
                                </div>
                                </div>`;
                        });

                        if (sinLeer > 0) {
                            badge.textContent = sinLeer;
                            badge.classList.remove('hidden');
                            // Ícono gris pero con detalle rojo (y/o animación, opcional)
                            campana.classList.remove('text-gray-400');
                            campana.classList.add('text-red-500');
                            campana.classList.add('fa-shake'); // animación de FA, opcional
                        } else {
                            badge.classList.add('hidden');
                            campana.classList.remove('text-red-500');
                            campana.classList.add('text-gray-400');
                            campana.classList.remove('fa-shake');
                        }
                    }
                });
        }
        document.addEventListener('DOMContentLoaded', function() {
            cargarNotificaciones(); // Esto chequea notificaciones ni bien se carga la página
            setInterval(cargarNotificaciones, 15000);
        });
    </script>
    <script>
        function setAll(dia, tipo, valor) {
            const selects = document.querySelectorAll(`select[data-dia='${dia}'][data-tipo='${tipo}']`);
            selects.forEach(s => s.value = valor);
        }
    </script>
    <script>
        function calcularLunesDesde(fechaStr) {
            const partes = fechaStr.split('-');
            const fecha = new Date(partes[0], partes[1] - 1, partes[2]); // local date
            const diaSemana = fecha.getDay(); // 0 = domingo ... 6 = sábado
            const diff = diaSemana === 0 ? -6 : 1 - diaSemana;
            fecha.setDate(fecha.getDate() + diff);
            return fecha;
        }

        function formatoFechaCorta(fecha) {
            const d = fecha.getDate().toString().padStart(2, '0');
            const m = (fecha.getMonth() + 1).toString().padStart(2, '0');
            return `${d}-${m}`;
        }

        function actualizarRangoVisual(fechaStr) {
            const lunes = calcularLunesDesde(fechaStr);
            const viernes = new Date(lunes);
            viernes.setDate(lunes.getDate() + 4);

            const lunesISO = lunes.toISOString().split('T')[0];
            document.getElementById('selector-fecha').value = lunesISO;
            document.getElementById('input-semana-lunes').value = lunesISO;

            document.getElementById('texto-rango').textContent =
                `${formatoFechaCorta(lunes)} / ${formatoFechaCorta(viernes)}`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fechaActual = document.getElementById('input-semana-lunes').value || new Date().toISOString().split('T')[0];
            actualizarRangoVisual(fechaActual);

            document.getElementById('selector-fecha').addEventListener('change', function() {
                actualizarRangoVisual(this.value);
            });
        });
    </script>
</body>

</html>