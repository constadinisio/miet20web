<?php
session_start();
if (!isset($_SESSION['usuario']) || (int)$_SESSION['usuario']['rol'] !== 1) {
    header("Location: /login.php?error=rol");
    exit;
}
$usuario = $_SESSION['usuario'];
require_once __DIR__ . '/../../../backend/includes/db.php';

if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

// --- Traer cursos (año y división, únicos) ---
$cursos = $conexion->query("
    SELECT DISTINCT curso_anio, curso_division
    FROM vista_estado_academico_alumnos
    ORDER BY curso_anio, curso_division
")->fetch_all(MYSQLI_ASSOC);

// Agrupar divisiones por año para el JS dinámico
$años = [];
$divisiones_por_año = [];
foreach ($cursos as $c) {
    $año = $c['curso_anio'];
    $div = $c['curso_division'];
    if (!in_array($año, $años)) $años[] = $año;
    $divisiones_por_año[$año][] = $div;
}

$curso_anio = $_GET['curso_anio'] ?? '';
$curso_division = $_GET['curso_division'] ?? '';
$alumno_id = $_GET['alumno_id'] ?? null;

// --- Listar alumnos del curso seleccionado ---
$alumnos = [];
if ($curso_anio && $curso_division) {
    $stmt = $conexion->prepare("
        SELECT * FROM vista_estado_academico_alumnos
        WHERE curso_anio = ? AND curso_division = ?
        ORDER BY nombre_completo
    ");
    $stmt->bind_param("ii", $curso_anio, $curso_division);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $alumnos[] = $row;
    $stmt->close();
}

// --- Historial del alumno seleccionado ---
$alumno_detalle = null;
$historial_por_anio = [];
if ($alumno_id) {
    $alumno_detalle = $conexion->query("SELECT * FROM vista_estado_academico_alumnos WHERE alumno_id = $alumno_id")->fetch_assoc();
    // Traer historial académico agrupado por año
    $historial = $conexion->query("
    SELECT 
        m.nombre AS materia, 
        n.nota AS nota_final
    FROM notas n
    JOIN materias m ON n.materia_id = m.id
    WHERE n.alumno_id = $alumno_id
    ORDER BY m.nombre
    ")->fetch_all(MYSQLI_ASSOC);

    // Agrupa materias por año (si existe campo anio)
    foreach ($historial as $h) {
        $historial_por_anio[$h['ciclo_lectivo']][] = $h;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
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
    <!-- Sidebar -->
    <nav id="sidebar" class="w-60 transition-all duration-300 bg-white shadow-lg px-4 py-4 flex flex-col gap-2">
        <button id="toggleSidebar" class="absolute top-4 left-4 z-50 text-2xl hover:text-indigo-600 transition">
            ☰
        </button>
        <div class="flex justify-center items-center p-2 mb-4 border-b border-gray-400 h-28">
            <img src="/images/et20ico.ico" class="sidebar-expanded block h-full w-auto object-contain">
            <img src="/images/et20ico.ico" class="sidebar-collapsed hidden h-10 w-auto object-contain">
        </div>
        <a href="admin.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Inicio">
            <span class="text-xl">🏠</span><span class="sidebar-label">Inicio</span>
        </a>
        <a href="usuarios.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Usuarios">
            <span class="text-xl">👥</span><span class="sidebar-label">Usuarios</span>
        </a>
        <a href="cursos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Cursos">
            <span class="text-xl">🏫</span><span class="sidebar-label">Cursos</span>
        </a>
        <a href="alumnos.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Alumnos">
            <span class="text-xl">👤</span><span class="sidebar-label">Alumnos</span>
        </a>
        <a href="materias.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Materias">
            <span class="text-xl">📚</span><span class="sidebar-label">Materias</span>
        </a>
        <a href="horarios.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Horarios">
            <span class="text-xl">⏰</span><span class="sidebar-label">Horarios</span>
        </a>
        <a href="progresion.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Progresión">
            <span class="text-xl">📈</span><span class="sidebar-label">Progresión</span>
        </a>
        <a href="historial.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Historial p/ Curso">
            <span class="text-xl">📋</span><span class="sidebar-label">Historial p/ Curso</span>
        </a>
        <a href="notificaciones.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Panel de Notificaciones">
            <span class="text-xl">🔔</span><span class="sidebar-label">Panel de Notificaciones</span>
        </a>
        <button onclick="window.location='/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
    </nav>
    
    <!-- Contenido principal -->
    <main class="flex-1 p-10">
        <!-- BLOQUE DE USUARIO, ROL, CONFIGURACIÓN Y NOTIFICACIONES -->
        <div class="w-full flex justify-end mb-6">
            <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-2 shadow border">

                <!-- Avatar -->
                <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>"
                    class="rounded-full w-12 h-12 object-cover">

                <!-- Nombre y rol -->
                <div class="flex flex-col pr-2 text-right">
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['nombre']; ?></div>
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['apellido']; ?></div>
                    <div class="mt-1 text-xs text-gray-500">Administrador</div>
                </div>

                <!-- Selector de rol (si corresponde) -->
                <?php if (isset($_SESSION['roles_disponibles']) && count($_SESSION['roles_disponibles']) > 1): ?>
                    <form method="post" action="/includes/cambiar_rol.php" class="ml-4">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <select name="rol" onchange="this.form.submit()"
                            class="px-2 py-1 border text-sm rounded-xl text-gray-700 bg-white">
                            <?php foreach ($_SESSION['roles_disponibles'] as $r): ?>
                                <option value="<?php echo $r['id']; ?>"
                                    <?php if ($_SESSION['usuario']['rol'] == $r['id']) echo 'selected'; ?>>
                                    Cambiar a: <?php echo ucfirst($r['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <!-- Botón de Configuración -->
                <a href="configuracion.php"
                    class="relative focus:outline-none group ml-2">
                    <i class="fa-solid fa-gear text-2xl text-gray-500 group-hover:text-gray-700 transition-colors"></i>
                </a>

                <!-- Notificaciones -->
                <button id="btn-notificaciones" class="relative focus:outline-none group ml-2">
                    <i id="icono-campana" class="fa-regular fa-bell text-2xl text-gray-400 group-hover:text-gray-700 transition-colors"></i>
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

        <div class="max-w-5xl mx-auto p-6 bg-white rounded-xl shadow">
            <h2 class="text-2xl font-bold mb-4">Historial académico por curso</h2>
            <!-- Selector de curso -->
            <form method="get" class="mb-4 flex gap-2 flex-wrap" id="form-curso">
                <select name="curso_anio" id="curso_anio" class="p-2 border rounded" required onchange="actualizarDivisiones();">
                    <option value="">Año</option>
                    <?php foreach ($años as $a): ?>
                        <option value="<?= $a ?>" <?= $curso_anio == $a ? 'selected' : '' ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="curso_division" id="curso_division" class="p-2 border rounded" required <?= $curso_anio ? '' : 'disabled' ?> onchange="document.getElementById('form-curso').submit();">
                    <option value="">División</option>
                    <?php if ($curso_anio): foreach ($divisiones_por_año[$curso_anio] as $div): ?>
                            <option value="<?= $div ?>" <?= $curso_division == $div ? 'selected' : '' ?>><?= $div ?></option>
                    <?php endforeach;
                    endif; ?>
                </select>
            </form>

            <!-- Tabla de alumnos -->
            <?php if (!empty($alumnos)): ?>
                <div class="max-h-[400px] overflow-y-auto rounded-xl shadow border mb-6">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-2 px-2 text-left">Alumno</th>
                                <th class="py-2 px-2">DNI</th>
                                <th class="py-2 px-2">Estado académico</th>
                                <th class="py-2 px-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumnos as $al): ?>
                                <tr>
                                    <td class="py-1 px-2"><?= $al['nombre_completo'] ?></td>
                                    <td class="py-1 px-2 text-center"><?= $al['dni'] ?></td>
                                    <td class="py-1 px-2 text-center"><?= $al['estado_academico'] ?></td>
                                    <td class="py-1 px-2 text-center">
                                        <form method="get" style="display:inline">
                                            <input type="hidden" name="curso_anio" value="<?= $curso_anio ?>">
                                            <input type="hidden" name="curso_division" value="<?= $curso_division ?>">
                                            <input type="hidden" name="alumno_id" value="<?= $al['alumno_id'] ?>">
                                            <button class="px-2 py-1 bg-gray-300 rounded hover:bg-gray-400" title="Ver historial">👁️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($curso_anio && $curso_division): ?>
                <div class="text-red-700 mb-4">No se encontraron alumnos para ese curso.</div>
            <?php endif; ?>

            <!-- Historial académico del alumno -->
            <?php if ($alumno_detalle): ?>
                <div class="p-4 bg-gray-50 rounded-xl mb-4">
                    <h3 class="text-lg font-bold mb-2"><?= $alumno_detalle['nombre_completo'] ?> (<?= $alumno_detalle['curso_completo'] ?>)</h3>
                    <b>Estado académico actual:</b> <?= $alumno_detalle['estado_academico'] ?>
                </div>
                <div class="p-4 bg-white rounded-xl mb-4 shadow">
                    <h4 class="text-lg font-bold mb-2">📋 Historial académico</h4>
                    <?php if (!empty($historial_por_anio)): ?>
                        <?php foreach ($historial_por_anio as $anio => $materias_hist): ?>
                            <div class="mb-3">
                                <div class="font-semibold text-indigo-700 mb-1">Ciclo lectivo: <?= $anio ?></div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full mb-2 border rounded">
                                        <thead>
                                            <tr class="bg-gray-100">
                                                <th class="py-1 px-2">Materia</th>
                                                <th class="py-1 px-2">Nota final</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($materias_hist as $row): ?>
                                                <tr>
                                                    <td class="py-1 px-2"><?= $row['materia'] ?></td>
                                                    <td class="py-1 px-2"><?= $row['nota_final'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-gray-500 italic">No se encontró historial académico.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
    <script>
        // Datos de divisiones por año para JS dinámico
        const divisionesPorAnio = <?= json_encode($divisiones_por_año) ?>;

        function actualizarDivisiones() {
            const añoSel = document.getElementById('curso_anio').value;
            const divisionSel = document.getElementById('curso_division');
            divisionSel.innerHTML = '<option value="">División</option>';
            if (divisionesPorAnio[añoSel]) {
                divisionesPorAnio[añoSel].forEach(function(div) {
                    let option = document.createElement('option');
                    option.value = div;
                    option.textContent = div;
                    divisionSel.appendChild(option);
                });
                divisionSel.disabled = false;
            } else {
                divisionSel.disabled = true;
            }
        }
        // Al cargar, si hay año seleccionado, cargar divisiones
        window.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('curso_anio').value !== "") {
                actualizarDivisiones();
            }
        });
        // Cuando se elige división, auto-submit
        document.getElementById('curso_division').addEventListener('change', function() {
            document.getElementById('form-curso').submit();
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
</body>

</html>