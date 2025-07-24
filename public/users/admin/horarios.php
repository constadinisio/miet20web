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

// Traer asignaciones existentes
$asignaciones = [];
$sql = "SELECT pcm.id, u.nombre AS prof_nombre, u.apellido AS prof_apellido,
               c.anio, c.division, m.nombre AS materia
        FROM profesor_curso_materia pcm
        JOIN usuarios u ON pcm.profesor_id = u.id
        JOIN cursos c ON pcm.curso_id = c.id
        JOIN materias m ON pcm.materia_id = m.id
        WHERE pcm.estado = 'activo'
        ORDER BY u.apellido, c.anio, c.division, m.nombre";
$res = $conexion->query($sql);
while ($row = $res->fetch_assoc()) {
    $asignaciones[] = $row;
}

$asignacion_id = $_GET['asignacion_id'] ?? null;

// Horarios actuales
$horarios = [];
if ($asignacion_id) {
    $sql = "SELECT id, dia_semana, hora_inicio, hora_fin FROM horarios_materia WHERE profesor_id = (
                SELECT profesor_id FROM profesor_curso_materia WHERE id = ?
            ) AND curso_id = (
                SELECT curso_id FROM profesor_curso_materia WHERE id = ?
            ) AND materia_id = (
                SELECT materia_id FROM profesor_curso_materia WHERE id = ?
            ) ORDER BY FIELD(dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iii", $asignacion_id, $asignacion_id, $asignacion_id);
    $stmt->execute();
    $horarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// 1. Cargar todos los cursos activos
$cursos = [];
$sql = "SELECT id, anio, division FROM cursos WHERE estado = 'activo' ORDER BY anio, division";
$res = $conexion->query($sql);
while ($row = $res->fetch_assoc()) $cursos[] = $row;

// 2. Recibir selección por GET
$curso_id = $_GET['curso_id'] ?? '';
$materia_id = $_GET['materia_id'] ?? '';
$profesor_id = $_GET['profesor_id'] ?? '';
$asignacion_id = $_GET['asignacion_id'] ?? null;

// 3. Cargar materias solo si hay curso elegido
$materias = [];
if ($curso_id) {
    $stmt = $conexion->prepare("SELECT m.id, m.nombre
        FROM profesor_curso_materia pcm
        JOIN materias m ON pcm.materia_id = m.id
        WHERE pcm.curso_id = ? AND pcm.estado = 'activo'
        GROUP BY m.id, m.nombre
        ORDER BY m.nombre");
    if (!$stmt) die("Error en prepare de materias: " . $conexion->error);
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $materias[] = $row;
    $stmt->close();
}

// 4. Cargar profesores solo si hay curso y materia elegidos
$profesores = [];
if ($curso_id && $materia_id) {
    $stmt = $conexion->prepare("SELECT u.id, u.nombre, u.apellido
        FROM profesor_curso_materia pcm
        JOIN usuarios u ON pcm.profesor_id = u.id
        WHERE pcm.curso_id = ? AND pcm.materia_id = ? AND pcm.estado = 'activo'
        ORDER BY u.apellido, u.nombre");
    if (!$stmt) die("Error en prepare de profesores: " . $conexion->error);
    $stmt->bind_param("ii", $curso_id, $materia_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $profesores[] = $row;
    $stmt->close();
}

if (!$asignacion_id && $curso_id && $materia_id && $profesor_id) {
    $stmt = $conexion->prepare("SELECT id FROM profesor_curso_materia
        WHERE curso_id = ? AND materia_id = ? AND profesor_id = ? AND estado = 'activo'
        LIMIT 1");
    $stmt->bind_param("iii", $curso_id, $materia_id, $profesor_id);
    $stmt->execute();
    $stmt->bind_result($asig_id);
    if ($stmt->fetch()) {
        // Redirigí automáticamente para mantener el flujo con asignacion_id
        header("Location: horarios.php?asignacion_id=$asig_id");
        exit;
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Asignar Horarios</title>
    <link href="/output.css?v=<?= time() ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
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
    <!-- Sidebar -->
    <nav id="sidebar" class="w-60 transition-all duration-300 bg-white shadow-lg px-4 py-4 flex flex-col gap-2">
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
        <a href="horarios.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-900 font-semibold hover:bg-gray-200 transition" title="Horarios">
            <span class="text-xl">⏰</span><span class="sidebar-label">Horarios</span>
        </a>
        <a href="progresion.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Progresión">
            <span class="text-xl">📈</span><span class="sidebar-label">Progresión</span>
        </a>
        <a href="historial.php" class="sidebar-item flex gap-3 items-center py-2 px-3 rounded-xl text-gray-700 hover:bg-indigo-100 transition" title="Historial p/ Curso">
            <span class="text-xl">📋</span><span class="sidebar-label">Historial p/ Curso</span>
        </a>
        <button onclick="window.location='/includes/logout.php'" class="sidebar-item flex items-center justify-center gap-2 mt-auto py-2 px-3 rounded-xl text-white bg-red-500 hover:bg-red-600">
            <span class="text-xl">🚪</span><span class="sidebar-label">Salir</span>
        </button>
    </nav>
    <main class="flex-1 p-10">
        <div class="w-full flex justify-end items-center gap-4 mb-6">
            <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-2 shadow border">
                <img src="<?php echo $usuario['foto_url'] ?? 'https://ui-avatars.com/api/?name=' . $usuario['nombre']; ?>" class="rounded-full w-12 h-12 object-cover">
                <div class="flex flex-col pr-2 text-right">
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['nombre']; ?></div>
                    <div class="font-bold text-base leading-tight"><?php echo $usuario['apellido']; ?></div>
                    <div class="mt-1 text-xs text-gray-500">Administrador/a</div>
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
        <h1 class="text-2xl font-bold mb-6">⏰ Asignación de Horarios</h1>
        <?php if (isset($_GET['ok'])): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-green-100 border border-green-400 text-green-800">
                <?php
                switch ($_GET['ok']) {
                    case 'horario_agregado':
                        echo '✅ Horario asignado correctamente.';
                        break;
                    case 'horario_eliminado':
                        echo '🗑️ Horario eliminado correctamente.';
                        break;
                }
                ?>
            </div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="mb-6 px-4 py-3 rounded-xl bg-red-100 border border-red-400 text-red-800">
                <?php
                switch ($_GET['error']) {
                    case 'faltan_campos':
                        echo '❗ Por favor completá todos los campos para agregar un horario.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        <!-- Card 1: Selección -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">🔍 Seleccionar asignación</h2>
            <form method="get" class="flex flex-col md:flex-row gap-4" id="seleccion-horario-form">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">

                <!-- Select Curso -->
                <select name="curso_id" id="curso_id" class="w-full md:w-1/3 px-4 py-2 rounded-xl border" required onchange="this.form.submit()">
                    <option value="">Seleccionar curso</option>
                    <?php foreach ($cursos as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $curso_id == $c['id'] ? 'selected' : '' ?>>
                            <?= "{$c['anio']}°{$c['division']}" ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Select Materia -->
                <select name="materia_id" id="materia_id" class="w-full md:w-1/3 px-4 py-2 rounded-xl border" <?= $curso_id ? '' : 'disabled' ?> onchange="this.form.submit()">
                    <option value="">Seleccionar materia</option>
                    <?php foreach ($materias as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $materia_id == $m['id'] ? 'selected' : '' ?>>
                            <?= $m['nombre'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Select Profesor -->
                <select name="profesor_id" id="profesor_id" class="w-full md:w-1/3 px-4 py-2 rounded-xl border" <?= ($curso_id && $materia_id) ? '' : 'disabled' ?> onchange="this.form.submit()">
                    <option value="">Seleccionar profesor</option>
                    <?php foreach ($profesores as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $profesor_id == $p['id'] ? 'selected' : '' ?>>
                            <?= "{$p['apellido']}, {$p['nombre']}" ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Botón Ver solo habilitado si se seleccionó todo -->
                <button class="bg-indigo-600 text-white px-4 py-2 rounded-xl hover:bg-indigo-700"
                    <?= ($curso_id && $materia_id && $profesor_id) ? '' : 'disabled' ?>>
                    Ver
                </button>
            </form>
        </div>


        <?php if ($asignacion_id): ?>
            <!-- Card 2: Formulario -->
            <div class="bg-white rounded-xl shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">➕ Agregar horario</h2>
                <form action="admin_agregar_horario.php" method="post" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="asignacion_id" value="<?php echo $asignacion_id; ?>">
                    <select name="dia" class="px-4 py-2 border rounded-xl" required>
                        <option value="">Día</option>
                        <option>Lunes</option>
                        <option>Martes</option>
                        <option>Miércoles</option>
                        <option>Jueves</option>
                        <option>Viernes</option>
                        <option>Sábado</option>
                    </select>
                    <input type="time" name="hora_inicio" class="px-4 py-2 border rounded-xl" required>
                    <input type="time" name="hora_fin" class="px-4 py-2 border rounded-xl" required>
                    <button type="submit" class="col-span-1 md:col-span-4 bg-green-600 text-white py-2 rounded-xl hover:bg-green-700">Guardar horario</button>
                </form>
            </div>

            <!-- Card 3: Lista de horarios -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-semibold mb-4">📋 Horarios asignados</h2>
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left px-4 py-2">Día</th>
                            <th class="text-left px-4 py-2">Inicio</th>
                            <th class="text-left px-4 py-2">Fin</th>
                            <th class="text-left px-4 py-2">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horarios as $h): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo $h['dia_semana']; ?></td>
                                <td class="px-4 py-2"><?php echo substr($h['hora_inicio'], 0, 5); ?></td>
                                <td class="px-4 py-2"><?php echo substr($h['hora_fin'], 0, 5); ?></td>
                                <td class="px-4 py-2">
                                    <form method="post" action="admin_eliminar_horario.php" onsubmit="return confirm('¿Eliminar este horario?');">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?php echo $h['id']; ?>">
                                        <input type="hidden" name="asignacion_id" value="<?php echo $asignacion_id; ?>">
                                        <button type="submit" class="text-red-600 hover:underline">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($horarios)): ?>
                            <tr>
                                <td colspan="4" class="py-4 text-center text-gray-500">No hay horarios asignados.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
</body>

</html>